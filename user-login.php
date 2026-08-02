<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please enter email and password.']);
    exit;
}

$conn = get_db();
$stmt = $conn->prepare("SELECT id, full_name, email, phone, password_hash FROM site_users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$conn->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

// Start session
$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['phone']     = $user['phone'];
session_regenerate_id(true);

echo json_encode(['success' => true, 'message' => 'Login successful!']);
