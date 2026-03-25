<?php
/**
 * One-time database setup script.
 * Run once in browser, then restrict or delete.
 */
require_once __DIR__ . '/../config.php';

// Connect without selecting a DB first
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$steps = [];

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$steps[] = "Database '" . DB_NAME . "' created or already exists.";

$conn->select_db(DB_NAME);

$tables = [
    "contact" => "CREATE TABLE IF NOT EXISTS contact (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "enroll" => "CREATE TABLE IF NOT EXISTS enroll (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        father_name VARCHAR(255),
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        gender VARCHAR(20),
        course_interest VARCHAR(255) NOT NULL,
        study_mode VARCHAR(50),
        qualification_file VARCHAR(255),
        passport_photo VARCHAR(255),
        cnic_file VARCHAR(255),
        previous_experience TEXT,
        reason_for_joining TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "admin_users" => "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "login_attempts" => "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_time (attempted_at)
    ) ENGINE=InnoDB",

    "admin_logs" => "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_username VARCHAR(100),
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        $steps[] = "Table '$name' created or already exists.";
    } else {
        $steps[] = "ERROR creating '$name': " . $conn->error;
    }
}

// Seed admin user with bcrypt password
$hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $conn->prepare("INSERT IGNORE INTO admin_users (id, username, password_hash) VALUES (1, 'admin', ?)");
$stmt->bind_param('s', $hash);
$stmt->execute();
$stmt->close();
$steps[] = "Admin user seeded. Username: admin | Password: Admin@1234 — CHANGE THIS IMMEDIATELY.";

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: sans-serif; padding: 40px; background: #f8f9fa; }</style>
</head>
<body>
<div class="container" style="max-width:700px">
    <h2 class="mb-4" style="color:#f07b14">Database Setup</h2>
    <ul class="list-group mb-4">
        <?php foreach ($steps as $step): ?>
            <li class="list-group-item <?php echo str_starts_with($step, 'ERROR') ? 'list-group-item-danger' : 'list-group-item-success'; ?>">
                <?php echo htmlspecialchars($step); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="alert alert-warning">
        <strong>Security:</strong> Delete or restrict access to this file after setup.
    </div>
    <a href="login.php" class="btn btn-primary" style="background:#f07b14;border-color:#f07b14">Go to Admin Login</a>
</div>
</body>
</html>
