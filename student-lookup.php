<?php
/**
 * Programmers Lab — Student Certificate/Status Lookup
 * POST: cnic, csrf_token
 * Returns: JSON with enrollment status
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

session_init();

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
    exit;
}

// Rate limiting — max 10 per 10 min per IP
$ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$windowSecs  = 600;
$maxAttempts = 10;
$conn = get_db();

$conn->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL {$windowSecs} SECOND)");
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ?");
$stmt->bind_param('s', $ip);
$stmt->execute();
$attempts = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

if ($attempts >= $maxAttempts) {
    echo json_encode(['success' => false, 'rate_limited' => true, 'message' => 'Too many attempts. Please wait 10 minutes.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
$stmt->bind_param('s', $ip);
$stmt->execute();
$stmt->close();

// Validate CNIC
$cnic = trim($_POST['cnic'] ?? '');
if (!preg_match('/^\d{5}-\d{7}-\d$/', $cnic)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CNIC format. Use: XXXXX-XXXXXXX-X']);
    exit;
}

// Lookup — any status but show meaningful info
$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, gender, course_interest,
            study_mode, created_at,
            IFNULL(enrollment_status, 'pending') AS status
     FROM enroll
     WHERE cnic_number = ?
     ORDER BY id DESC
     LIMIT 1"
);
$stmt->bind_param('s', $cnic);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if ($student) {
    $safe = [];
    foreach ($student as $k => $v) {
        $safe[$k] = is_string($v) ? htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v;
    }
    echo json_encode(['success' => true, 'found' => true, 'student' => $safe]);
} else {
    echo json_encode(['success' => true, 'found' => false]);
}
