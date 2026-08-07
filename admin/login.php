<?php
// ============================================================
// Admin Login — Step 1: Username + Password
// On success → generates OTP and redirects to otp-verify.php
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../otp_helper.php';
send_security_headers();
session_init();

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);
$otpSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $conn = get_db();

    // Rate limit check
    $window = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
    $rl = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address=? AND attempted_at>?");
    $rl->bind_param('ss', $ip, $window);
    $rl->execute();
    $attempts = (int)$rl->get_result()->fetch_assoc()['cnt'];
    $rl->close();

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $error = 'Too many failed attempts. Please wait 15 minutes.';
    } elseif (!$username || !$password) {
        $error = 'Username and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin_users WHERE username=?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            // ── Credentials OK → generate & send OTP ──
            // Rehash if needed
            if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost'=>12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                $upd = $conn->prepare("UPDATE admin_users SET password_hash=? WHERE id=?");
                $upd->bind_param('si', $newHash, $user['id']);
                $upd->execute(); $upd->close();
            }

            $result = otp_generate_and_send(
                $conn, 'admin', $user['id'],
                ADMIN_OTP_EMAILS,
                $user['username'],
                'Admin'
            );

            if ($result['success']) {
                // Save pending state in session (not logged in yet)
                session_regenerate_id(true);
                $_SESSION['otp_pending_type']     = 'admin';
                $_SESSION['otp_pending_id']        = $user['id'];
                $_SESSION['otp_pending_username']  = $user['username'];
                $_SESSION['otp_pending_ip']        = $ip;
                $_SESSION['otp_pending_ua']        = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['otp_pending_time']      = time();
                log_activity($conn, 'OTP_SENT', 'Admin OTP sent to ' . implode(', ', ADMIN_OTP_EMAILS));
                $conn->close();
                header('Location: otp-verify.php'); exit;
            } else {
                $error = $result['message'];
            }
        } else {
            // Log failed attempt
            $ins = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
            $ins->bind_param('s', $ip);
            $ins->execute(); $ins->close();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',sans-serif;background:#0d1b2a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
        body::before{content:'';position:fixed;top:-120px;left:-120px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(240,123,20,.12),transparent 70%);pointer-events:none;}
        body::after{content:'';position:fixed;bottom:-100px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.08),transparent 70%);pointer-events:none;}
        .wrap{width:100%;max-width:420px;position:relative;z-index:1;}
        .brand{text-align:center;margin-bottom:28px;}
        .brand-icon{width:56px;height:56px;border-radius:16px;background:#f07b14;display:inline-flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:12px;box-shadow:0 8px 24px rgba(240,123,20,.35);}
        .brand h1{color:#fff;font-size:20px;font-weight:800;margin:0 0 4px;} .brand h1 span{color:#f07b14;}
        .brand p{color:rgba(255,255,255,.4);font-size:13px;margin:0;}
        .card{background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 24px 64px rgba(0,0,0,.35);}
        .card h2{font-size:18px;font-weight:700;color:#0d1b2a;margin:0 0 5px;}
        .card .sub{font-size:13px;color:#9ca3af;margin:0 0 26px;}
        .field{margin-bottom:18px;}
        .lbl{font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;}
        .input-wrap{position:relative;}
        .input-wrap .ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none;}
        input[type=text],input[type=password]{width:100%;padding:12px 16px 12px 42px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#0d1b2a;background:#fafafa;outline:none;transition:border .2s,box-shadow .2s;}
        input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.12);background:#fff;}
        .eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:14px;padding:4px;}
        .btn{width:100%;padding:13px;background:#f07b14;color:#fff;border:none;border-radius:50px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:background .2s,transform .2s,box-shadow .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;}
        .btn:hover{background:#d96a00;transform:translateY(-1px);box-shadow:0 8px 20px rgba(240,123,20,.3);}
        .alert{border-radius:10px;font-size:13.5px;padding:12px 16px;display:flex;align-items:center;gap:8px;margin-bottom:18px;}
        .alert-danger{background:rgba(239,68,68,.08);color:#b91c1c;}
        .alert-warn{background:rgba(245,158,11,.1);color:#92400e;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-code"></i></div>
        <h1><span>Programmers</span> Lab</h1>
        <p>Admin Panel</p>
    </div>
    <div class="card">
        <h2>Welcome back</h2>
        <p class="sub">Sign in to your admin account</p>

        <?php if ($timeout): ?>
        <div class="alert alert-warn"><i class="fas fa-clock"></i> Session expired. Please log in again.</div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

            <div class="field">
                <label class="lbl">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user ico"></i>
                    <input type="text" name="username" placeholder="admin" required autocomplete="username"
                           value="<?= h($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="field">
                <label class="lbl">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock ico"></i>
                    <input type="password" name="password" id="passInput" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="eye-btn" onclick="togglePass()"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
            </div>

            <button type="submit" class="btn"><i class="fas fa-arrow-right"></i> Continue</button>
        </form>

    </div>
</div>
<script>
function togglePass(){
    const i=document.getElementById('passInput'),e=document.getElementById('eyeIcon');
    i.type=i.type==='password'?'text':'password';
    e.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
</script>
</body>
</html>
