<?php
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
        curl_close($ch);
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
    $stmt = $db->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
    $stmt->execute([$column]);
    return (bool) $stmt->fetch();
}

function ensureGoogleUserColumns(PDO $db): void {
    if (!tableHasColumn($db, 'users', 'google_id')) {
        $db->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE');
    }
    if (!tableHasColumn($db, 'users', 'auth_provider')) {
        $db->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'local'");
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
    $name     = trim($info['name'] ?? ($info['given_name'] ?? 'Trader'));

    if (!$googleId || !$email) {
        oauthFail('Google did not return an email. Enable the userinfo.email scope on the OAuth client.');
    }

    try {
        $db = getDB();
        ensureGoogleUserColumns($db);

        $stmt = $db->prepare('SELECT id, name, email FROM users WHERE google_id = ? OR email = ? LIMIT 1');
        $stmt->execute([$googleId, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $db->prepare('UPDATE users SET google_id = ?, auth_provider = ? WHERE id = ?')
               ->execute([$googleId, 'google', $user['id']]);
        } else {
            $placeholderHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
            $stmt = $db->prepare('INSERT INTO users (name, email, password, google_id, auth_provider) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $placeholderHash, $googleId, 'google']);
            $user = [
                'id'    => (int) $db->lastInsertId(),
                'name'  => $name,
                'email' => $email,
            ];
        }
    } catch (PDOException $e) {
        oauthFail('Database error while creating your Google account. Run the ALTER TABLE notes in setup.sql, then try again.');
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];

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

header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
