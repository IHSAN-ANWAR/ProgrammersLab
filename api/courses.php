<?php
header('Content-Type: application/json');
// Restrict CORS to own domain only
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://www.programmerslabs.com', 'https://programmerslabs.com', 'http://localhost'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$conn   = get_db();
$result = $conn->query(
    "SELECT name, category, price, original_price, installment_price, duration 
     FROM courses 
     WHERE is_active = 1 
     ORDER BY category, sort_order, name"
);

$grouped = [];
$all     = [];

while ($row = $result->fetch_assoc()) {
    $grouped[$row['category']][] = $row['name'];
    $all[$row['name']] = [
        'price'             => (int)$row['price'],
        'original_price'    => (int)$row['original_price'],
        'installment_price' => (int)$row['installment_price'],
        'duration'          => $row['duration'],
        'category'          => $row['category'],
    ];
}

echo json_encode([
    'success' => true,
    'courses' => $grouped,  // for dropdown (grouped by category)
    'details' => $all,      // for price lookup by name
]);

$conn->close();
