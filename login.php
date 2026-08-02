<?php
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $identity = trim($_POST['identity'] ?? ''); // username, email or phone
    $password = $_POST['password'] ?? '';

    if (!$identity || !$password) {
        $errors[] = 'Provide username/email and password.';
    } else {
        $conn = get_db();
        $stmt = $conn->prepare('SELECT id, username, password, full_name, email, phone FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1');
        $stmt->bind_param('sss', $identity, $identity, $identity);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid credentials.';
            $conn->close();
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $conn->close();
            $next = $_GET['next'] ?? 'enroll-form.html';
            header('Location: ' . $next);
            exit;
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — Programmers Lab</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>body{padding:40px;background:#f8f9fc} .card{max-width:520px;margin:0 auto;padding:24px;border-radius:14px}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Login</h3>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo h($e); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                <div class="form-group">
                    <label>Username, Email or Phone</label>
                    <input class="form-control" name="identity" value="<?php echo h($_POST['identity'] ?? ''); ?>">
                </div>
            <div class="form-group">
                <label>Password</label>
                <input class="form-control" name="password" type="password">
            </div>
            <div style="margin-top:12px">
                <button class="btn btn-primary" type="submit">Login</button>
                <a href="register.php" style="margin-left:12px">Create account</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
