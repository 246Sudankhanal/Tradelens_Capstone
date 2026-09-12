<?php
// Copy this file to oauth.php and paste Google Cloud credentials.
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/db.php';
}
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost:8080/api/google_oauth.php');

define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v3/userinfo');

function googleOAuthConfigured(): bool {
    $id     = GOOGLE_CLIENT_ID;
    $secret = GOOGLE_CLIENT_SECRET;
    return $id !== ''
        && $secret !== ''
        && strpos($id, 'YOUR_GOOGLE_CLIENT_ID') === false
        && strpos($secret, 'YOUR_GOOGLE_CLIENT_SECRET') === false;
}

function googleRedirectUri(): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $host . '/api/google_oauth.php';
    }
    return GOOGLE_REDIRECT_URI;
}
