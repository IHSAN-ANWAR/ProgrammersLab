<?php
// Local debug — DELETE AFTER USE
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) { die('Access denied.'); }
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

echo "<h2 style='font-family:sans-serif;padding:20px 20px 0'>Local Debug</h2>";
echo "<pre style='font-family:monospace;padding:20px;font-size:13px;'>";

echo "ENV: " . APP_ENV . "\n";
echo "DB: "  . DB_NAME . "\n\n";

$conn = get_db();
echo "DB Connection: OK\n\n";

// Check tables
$tables = ['enroll','contact','courses','admin_logs','job_applications','notices'];
foreach ($tables as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    echo "Table '$t': " . ($r->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
}

echo "\n--- enroll columns ---\n";
$r = $conn->query("SHOW COLUMNS FROM enroll");
if ($r) { while($row=$r->fetch_assoc()) echo "  {$row['Field']} ({$row['Type']})\n"; }
else echo "Table missing!\n";

echo "\n--- job_applications columns ---\n";
$r = $conn->query("SHOW COLUMNS FROM job_applications");
if ($r) { while($row=$r->fetch_assoc()) echo "  {$row['Field']} ({$row['Type']})\n"; }
else echo "Table missing!\n";

$conn->close();
echo "</pre>";
?>
