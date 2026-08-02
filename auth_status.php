<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

session_init();

$logged = isset($_SESSION['user_id']);
$user = null;
if ($logged) {
    $user = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'phone' => $_SESSION['phone'] ?? null,
    ];
}

echo json_encode(['logged_in' => $logged, 'user' => $user]);
