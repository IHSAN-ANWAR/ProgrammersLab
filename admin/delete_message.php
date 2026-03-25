<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
session_init();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

csrf_verify();
check_session_timeout();
check_session_fingerprint();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare("DELETE FROM contact WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    log_activity($conn, 'DELETE_MESSAGE', "Deleted message ID: $id");
    echo json_encode(['success' => true, 'message' => 'Message deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Message not found or already deleted.']);
}

$stmt->close();
$conn->close();
