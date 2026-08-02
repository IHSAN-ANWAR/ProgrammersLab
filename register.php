<?php
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username  = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!$full_name) $errors[] = 'Full name is required.';
    if (!validate_email($email)) $errors[] = 'A valid email is required.';
    if (!validate_phone($phone)) $errors[] = 'A valid phone number is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password2) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $conn = get_db();

        // Create users table if missing
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Check duplicates
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1');
        $stmt->bind_param('sss', $username, $email, $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username, email or phone already registered.';
            $stmt->close();
            $conn->close();
        } else {
            $stmt->close();
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare('INSERT INTO users (username, password, full_name, email, phone) VALUES (?, ?, ?, ?, ?)');
            $ins->bind_param('sssss', $username, $passHash, $full_name, $email, $phone);
            if ($ins->execute()) {
                $_SESSION['user_id'] = $ins->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                $ins->close();
                $conn->close();
                header('Location: enroll-form.html');
                exit;
            } else {
                $errors[] = 'Could not create account. Please try again.';
                $ins->close();
                $conn->close();
            }
        }
    }
}

// Render simple register form using site styles
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register — Programmers Lab</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>body{padding:40px;background:#f8f9fc} .card{max-width:520px;margin:0 auto;padding:24px;border-radius:14px}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Create Account</h3>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <div class="form-group">
                <label>Username</label>
                <input class="form-control" name="username" value="<?php echo h($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input class="form-control" name="full_name" value="<?php echo h($_POST['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input class="form-control" name="email" type="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input class="form-control" name="phone" value="<?php echo h($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input class="form-control" name="password" type="password">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input class="form-control" name="password2" type="password">
            </div>
            <div style="margin-top:12px">
                <button class="btn btn-primary" type="submit">Register & Continue</button>
                <a href="login.php" style="margin-left:12px">Already have an account?</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
