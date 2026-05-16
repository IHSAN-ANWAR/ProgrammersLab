<?php
require_once __DIR__ . '/../config.php';
send_security_headers();
session_init();

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $conn = get_db();

    $window = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
    $stmt   = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?");
    $stmt->bind_param('ss', $ip, $window);
    $stmt->execute();
    $attempts = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $error = 'Too many failed attempts. Please try again in 15 minutes.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $user['username'];
            $_SESSION['last_activity']   = time();
            $_SESSION['fingerprint']     = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . $ip);

            if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $upd = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                $upd->bind_param('si', $newHash, $user['id']);
                $upd->execute();
                $upd->close();
            }

            log_activity($conn, 'LOGIN', 'Successful login');
            $conn->close();
            header('Location: index.php');
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $stmt->close();
            usleep(500000);
            $error = 'Invalid username or password.';
        }
    }

    $conn->close();
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Programmers Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,500,600,700" rel="stylesheet">
    <style>
        body {
            font-family: 'Raleway', sans-serif;
            background: linear-gradient(135deg, #f07b14 0%, #d56e00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,.3); }
        .login-header { background: #f07b14; color: #fff; border-radius: 15px 15px 0 0; padding: 30px; text-align: center; }
        .login-header h3 { font-weight: 600; margin-bottom: 8px; }
        .form-control { border-radius: 8px; padding: 12px 15px; font-size: 15px; }
        .form-control:focus { border-color: #f07b14; box-shadow: 0 0 0 .2rem rgba(240,123,20,.25); }
        .btn-login { background: #f07b14; border-color: #f07b14; font-weight: 600; padding: 12px; border-radius: 8px; }
        .btn-login:hover { background: #d56e00; border-color: #d56e00; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card login-card">
                <div class="login-header">
                    <h3>Admin Login</h3>
                    <p class="mb-0 opacity-75">Programmers Lab</p>
                </div>
                <div class="card-body p-5">
                    <?php if ($timeout): ?>
                        <div class="alert alert-warning">Session expired. Please log in again.</div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   required autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-login btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
