<?php
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

// CSRF check — only allow logout via POST with valid token,
// OR a GET with a valid token in query string (for simple link support).
// This prevents logout CSRF attacks.
$token        = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if ($sessionToken && !hash_equals($sessionToken, $token)) {
    // Silently redirect — don't reveal the reason to potential attacker
    header('Location: index.html');
    exit;
}

// Destroy session data
session_unset();
session_destroy();

// Expire the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: auth.php');
exit;
