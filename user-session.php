<?php
require_once __DIR__ . '/config.php';
session_init();
header('Content-Type: application/json');
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'name'      => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email']     ?? '',
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
