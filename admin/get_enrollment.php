<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
session_init();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

check_session_timeout();
check_session_fingerprint();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$conn = get_db();

$stmt = $conn->prepare(
    "SELECT id, full_name, father_name, email, phone, gender, course_interest, study_mode,
            qualification_file, passport_photo, cnic_file, previous_experience,
            reason_for_joining, created_at
     FROM enroll WHERE id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if ($row) {
    echo json_encode(['success' => true, 'enrollment' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
}
