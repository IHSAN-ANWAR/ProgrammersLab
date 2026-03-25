<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// CSRF check (token sent as hidden field or X-CSRF-Token header)
session_init();
csrf_verify();

// Collect & validate inputs
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$phone || !$subject || !$message) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!validate_email($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

if (!validate_phone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number.']);
    exit;
}

if (strlen($name) > 255 || strlen($subject) > 500) {
    echo json_encode(['success' => false, 'message' => 'Input too long.']);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare(
    "INSERT INTO contact (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} else {
    error_log('contact insert error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Could not save your message. Please try again.']);
}

$stmt->close();
$conn->close();
