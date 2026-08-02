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

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$password  = $_POST['password']       ?? '';
$confirm   = $_POST['confirm']        ?? '';

// Validate
if (!$full_name || !$email || !$phone || !$password || !$confirm) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if (!validate_phone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

$conn = get_db();

// Check if email already exists
$check = $conn->prepare("SELECT id FROM site_users WHERE email = ?");
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please login.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO site_users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $full_name, $email, $phone, $hash);
$ok = $stmt->execute();

if (!$ok) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    exit;
}

$user_id = $conn->insert_id;
$conn->close();

// Auto login after register
$_SESSION['user_id']   = $user_id;
$_SESSION['full_name'] = $full_name;
$_SESSION['email']     = $email;
$_SESSION['phone']     = $phone;
session_regenerate_id(true);

echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
