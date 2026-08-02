<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

// Return active notice for frontend
$conn   = get_db();
$result = $conn->query("SELECT id, title, message, badge, badge_color, btn_text, btn_url, IFNULL(image,'') AS image FROM notices WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$notice = $result ? $result->fetch_assoc() : null;

echo json_encode(['success' => true, 'notice' => $notice]);
$conn->close();
