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
    <title>Admin Login — Programmers Lab</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0d1b2a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        /* background decorative circles */
        body::before {
            content: '';
            position: fixed; top: -120px; left: -120px;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(240,123,20,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed; bottom: -100px; right: -100px;
            width: 400px; height: 400px; border-radius: 50%;
            background: radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-wrap {
            width: 100%; max-width: 420px;
            position: relative; z-index: 1;
        }
        /* Brand */
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-brand-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: #f07b14;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
            margin-bottom: 12px;
            box-shadow: 0 8px 24px rgba(240,123,20,0.35);
        }
        .login-brand h1 {
            color: #fff; font-size: 20px; font-weight: 800; margin: 0 0 4px;
        }
        .login-brand h1 span { color: #f07b14; }
        .login-brand p { color: rgba(255,255,255,0.4); font-size: 13px; margin: 0; }

        /* Card */
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 40px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
        }
        .login-card h2 {
            font-size: 18px; font-weight: 700; color: #0d1b2a;
            margin: 0 0 6px;
        }
        .login-card .login-sub {
            font-size: 13px; color: #9ca3af; margin: 0 0 28px;
        }

        /* Form */
        .form-label {
            font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;
        }
        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px; font-family: 'Inter', sans-serif;
            color: #0d1b2a; background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #f07b14;
            box-shadow: 0 0 0 3px rgba(240,123,20,0.12);
            background: #fff; outline: none;
        }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap .form-control { padding-left: 44px; }
        .input-icon-wrap .field-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: 14px; pointer-events: none;
        }

        .btn-login {
            width: 100%; padding: 13px;
            background: #f07b14; color: #fff; border: none;
            border-radius: 50px; font-size: 15px; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: background 0.25s, transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover {
            background: #d96a00;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(240,123,20,0.3);
        }

        .alert {
            border-radius: 10px; font-size: 13.5px; padding: 12px 16px;
            border: none; display: flex; align-items: center; gap: 8px;
        }
        .alert-danger  { background: rgba(239,68,68,0.08);  color: #b91c1c; }
        .alert-warning { background: rgba(245,158,11,0.1);  color: #92400e; }
    </style>
</head>
<body>
<div class="login-wrap">

    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-icon"><i class="fas fa-code"></i></div>
        <h1><span>Programmers</span> Lab</h1>
        <p>Admin Panel</p>
    </div>

    <!-- Card -->
    <div class="login-card">
        <h2>Welcome back</h2>
        <p class="login-sub">Sign in to your admin account</p>

        <?php if ($timeout): ?>
            <div class="alert alert-warning mb-3">
                <i class="fas fa-clock"></i> Session expired. Please log in again.
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle"></i> <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="admin" required autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
    </div>

</div>
</body>
</html>
