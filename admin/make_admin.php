<?php
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403); die('Access denied.');
}
require_once __DIR__ . '/../config.php';
$conn     = get_db();
$username = 'admin';
$password = 'Admin@123';
$hash     = password_hash($password, PASSWORD_BCRYPT);
$check    = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$check->bind_param('s', $username);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();
if ($exists) {
    $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
    $stmt->bind_param('ss', $hash, $username);
    $msg = 'Password updated!';
} else {
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $hash);
    $msg = 'Admin created!';
}
$stmt->execute();
$stmt->close();
$conn->close();
echo "<h2 style='font-family:sans-serif;padding:40px;'>✅ $msg<br><br>Username: <b>admin</b><br>Password: <b>Admin@123</b><br><br><a href='login.php'>Go to Login</a><br><br><small style='color:red'>Delete this file after use!</small></h2>";
