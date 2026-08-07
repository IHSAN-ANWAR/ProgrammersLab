<?php
header('Content-Type: application/json');
// Restrict CORS to own domain only — open wildcard is a security risk
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://www.programmerslabs.com', 'https://programmerslabs.com', 'http://localhost'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
require_once __DIR__ . '/../config.php';
$conn   = get_db();
$result = $conn->query("SELECT id, title, message, badge, badge_color, btn_text, btn_url, IFNULL(image,'') AS image FROM notices WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$notice = $result ? $result->fetch_assoc() : null;

echo json_encode(['success' => true, 'notice' => $notice]);
$conn->close();
