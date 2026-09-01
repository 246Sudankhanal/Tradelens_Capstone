<?php
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);
ob_start();
/**
 * Google OAuth start + callback.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/oauth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$loginUrl = rtrim(BASE_URL, '/') . '/index.php';

function oauthFail(string $message): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . rtrim(BASE_URL, '/') . '/index.php?msg=oauth_error&detail=' . urlencode($message));
    exit;
}

function oauthHttp(string $url, string $method = 'GET', ?string $body = null, array $headers = []): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        if ($raw === false) {
            oauthFail($err ?: 'Could not reach Google.');
        }
        return (string) $raw;
    }

    $headerStr = implode("\r\n", $headers);
    $ctx = stream_context_create([
        'http' => [
            'method'        => $method,
            'header'        => $headerStr,
            'content'       => $body,
            'timeout'       => 20,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        oauthFail('Could not reach Google. Enable PHP curl or allow_url_fopen.');
    }
    return $raw;
}

function tableHasColumn(PDO $db, string $table, string $column): bool {
    $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $stmt = $db->query('SHOW COLUMNS FROM `' . $table . '`');
    foreach ($stmt->fetchAll() as $row) {
        if (strcasecmp((string) ($row['Field'] ?? ''), $column) === 0) {
            return true;
        }
    }
    return false;
}

function ensureGoogleUserColumns(PDO $db): void {
    if (!tableHasColumn($db, 'users', 'google_id')) {
        try {
            $db->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL');
        } catch (PDOException $e) { /* exists */ }
        try {
            $db->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_google_id (google_id)');
        } catch (PDOException $e) { /* exists */ }
    }
    if (!tableHasColumn($db, 'users', 'auth_provider')) {
        try {
            $db->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'local'");
        } catch (PDOException $e) { /* exists */ }
    }
}

if (!googleOAuthConfigured()) {
    header('Location: ' . $loginUrl . '?msg=oauth_not_configured');
    exit;
}

$redirectUri = googleRedirectUri();

if (isset($_GET['error'])) {
    oauthFail($_GET['error_description'] ?? $_GET['error']);
}

if (isset($_GET['code'])) {
    $code  = $_GET['code'];
    $state = $_GET['state'] ?? '';
    $expected = $_SESSION['oauth_state'] ?? ($_COOKIE['oauth_state'] ?? '');
    if (!$state || !$expected || !hash_equals($expected, $state)) {
        oauthFail('Sign-in expired. Click Continue with Google again.');
    }
    unset($_SESSION['oauth_state']);
    setcookie('oauth_state', '', time() - 3600, '/');

    $tokenRaw = oauthHttp(GOOGLE_TOKEN_URL, 'POST', http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]), ['Content-Type: application/x-www-form-urlencoded']);

    $token = json_decode($tokenRaw, true);
    if (empty($token['access_token'])) {
        $hint = $token['error_description'] ?? $token['error'] ?? 'Google did not return an access token.';
        if (stripos($hint, 'redirect_uri') !== false) {
            $hint .= ' Add this exact URI in Google Cloud → Credentials → Authorized redirect URIs: ' . $redirectUri;
        }
        oauthFail($hint);
    }

    $infoRaw = oauthHttp(GOOGLE_USERINFO_URL, 'GET', null, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
    $info = json_decode($infoRaw, true);

    $googleId = $info['sub'] ?? '';
    $email    = strtolower(trim($info['email'] ?? ''));
    $name = trim($info['name'] ?? ($info['given_name'] ?? 'Trader'));
    if (strlen($name) > 100) {
        $name = substr($name, 0, 100);
    }

    if (!$googleId || !$email) {
        oauthFail('Google did not return an email. Enable the userinfo.email scope on the OAuth client.');
    }

    try {
        $db = getDB();
        ensureGoogleUserColumns($db);

        $hasGoogle = tableHasColumn($db, 'users', 'google_id');
        $hasProvider = tableHasColumn($db, 'users', 'auth_provider');

        $user = null;
        if ($hasGoogle) {
            $stmt = $db->prepare('SELECT id, name, email FROM users WHERE google_id = ? LIMIT 1');
            $stmt->execute([$googleId]);
            $user = $stmt->fetch() ?: null;
        }
        if (!$user) {
            $stmt = $db->prepare('SELECT id, name, email FROM users WHERE LOWER(email) = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch() ?: null;
        }

        if ($user) {
            if ($hasGoogle && $hasProvider) {
                $db->prepare('UPDATE users SET google_id = ?, auth_provider = ? WHERE id = ?')
                   ->execute([$googleId, 'google', $user['id']]);
            } elseif ($hasGoogle) {
                $db->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleId, $user['id']]);
            }
        } else {
            $placeholderHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            if ($hasGoogle && $hasProvider) {
                $stmt = $db->prepare('INSERT INTO users (name, email, password, google_id, auth_provider) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $placeholderHash, $googleId, 'google']);
            } elseif ($hasGoogle) {
                $stmt = $db->prepare('INSERT INTO users (name, email, password, google_id) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $placeholderHash, $googleId]);
            } else {
                $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $stmt->execute([$name, $email, $placeholderHash]);
            }
            $user = [
                'id'    => (int) $db->lastInsertId(),
                'name'  => $name,
                'email' => $email,
            ];
        }
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate') !== false || (string) $e->getCode() === '23000') {
            oauthFail('This Google email is already on a TradeLens account. Sign in with email and password, then try Google again.');
        }
        oauthFail('Could not save your Google login: ' . $msg);
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
setcookie('oauth_state', $state, [
    'expires'  => time() + 600,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
