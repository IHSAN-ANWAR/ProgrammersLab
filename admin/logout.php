<?php
require_once __DIR__ . '/../config.php';
session_init();

if (isset($_SESSION['admin_logged_in'])) {
    $conn = get_db();
    log_activity($conn, 'LOGOUT', 'Admin logged out');
    $conn->close();
}

session_unset();
session_destroy();

// Clear session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: login.php');
exit();
