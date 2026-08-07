<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please enter email and password.']);
    exit;
}

$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$conn = get_db();

// ── Rate limiting: max 10 failed attempts per IP in 15 minutes ──
$window = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
$rl = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?"
);
$rl->bind_param('ss', $ip, $window);
$rl->execute();
$attempts = (int)$rl->get_result()->fetch_assoc()['cnt'];
$rl->close();

if ($attempts >= MAX_LOGIN_ATTEMPTS) {
    $conn->close();
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please wait 15 minutes.']);
    exit;
}

// ── Lookup user ──────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, password_hash FROM site_users WHERE email = ? LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Log failed attempt
    $ins = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $ins->bind_param('s', $ip);
    $ins->execute();
    $ins->close();
    $conn->close();
    // Artificial delay to slow brute force
    usleep(400000);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

// ── Rehash if needed ─────────────────────────────────────────
if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $upd = $conn->prepare("UPDATE site_users SET password_hash = ? WHERE id = ?");
    $upd->bind_param('si', $newHash, $user['id']);
    $upd->execute();
    $upd->close();
}

$conn->close();

// ── Establish session (regenerate BEFORE writing session data) ──
session_regenerate_id(true);

$_SESSION['user_id']        = $user['id'];
$_SESSION['full_name']      = $user['full_name'];
$_SESSION['email']          = $user['email'];
$_SESSION['phone']          = $user['phone'];
$_SESSION['last_activity']  = time();

echo json_encode(['success' => true, 'message' => 'Login successful!']);
