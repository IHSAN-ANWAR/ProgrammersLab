<?php
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

// Agar pehle se login hai redirect
if (isset($_SESSION['teacher_id'])) {
    header('Location: teacher-portal.php'); exit;
}

$error   = '';
$locked  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';          // intentionally not trimmed (passwords can have spaces)

    if (!$username || !$password) {
        $error = 'Username and password are required.';
    } else {
        $conn = get_db();

        // ── Rate limiting — same login_attempts table as admin ──
        $window = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
        $ra = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address=? AND attempted_at>?");
        $ra->bind_param('ss', $ip, $window);
        $ra->execute();
        $attempts = (int)$ra->get_result()->fetch_assoc()['cnt'];
        $ra->close();

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $locked = true;
            $error  = 'Too many failed attempts. Please wait 15 minutes before trying again.';
            $conn->close();
        } else {
            $stmt = $conn->prepare(
                "SELECT id, full_name, username, password_hash, is_active
                 FROM teacher_users WHERE username=?"
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $teacher = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$teacher || !password_verify($password, $teacher['password_hash'])) {
                // Log failed attempt
                $ins = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
                $ins->bind_param('s', $ip);
                $ins->execute();
                $ins->close();
                usleep(500000); // 0.5s delay — brute-force slowdown
                $error = 'Invalid username or password.';
            } elseif (!$teacher['is_active']) {
                $error = 'Your account has been deactivated. Contact the admin.';
            } else {
                // ── Login success ──
                session_regenerate_id(true);
                $_SESSION['teacher_id']       = $teacher['id'];
                $_SESSION['teacher_username'] = $teacher['username'];
                $_SESSION['teacher_name']     = $teacher['full_name'];
                $_SESSION['last_activity']    = time();
                // Session fingerprint — same pattern as admin
                $_SESSION['teacher_fp'] = hash(
                    'sha256',
                    ($_SERVER['HTTP_USER_AGENT'] ?? '') . $ip
                );
                $conn->close();
                header('Location: teacher-portal.php'); exit;
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Login — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.login-card{background:#fff;border-radius:24px;padding:42px 40px;width:100%;max-width:420px;box-shadow:0 24px 80px rgba(0,0,0,.3);}
.login-logo{text-align:center;margin-bottom:28px;}
.login-logo .brand{font-size:22px;font-weight:800;color:#0d1b2a;}
.login-logo .brand span{color:#f07b14;}
.login-logo .sub{font-size:13px;color:#9ca3af;margin-top:4px;}
.login-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(240,123,20,.1);border:1px solid rgba(240,123,20,.25);color:#f07b14;padding:6px 16px;border-radius:50px;font-size:12px;font-weight:700;margin-bottom:8px;}
h2{font-size:22px;font-weight:800;color:#0d1b2a;margin-bottom:6px;}
.sub-text{font-size:13.5px;color:#6b7280;margin-bottom:24px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
.field input{width:100%;padding:13px 16px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:inherit;outline:none;transition:border .2s,box-shadow .2s;}
.field input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.12);}
.pass-wrap{position:relative;}
.pass-wrap input{padding-right:42px;}
.pass-eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;font-size:15px;}
.btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s,transform .2s;margin-top:4px;}
.btn:hover{background:#d96a00;transform:translateY(-1px);}
.error-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#dc2626;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.links{text-align:center;margin-top:20px;font-size:13px;color:#9ca3af;}
.links a{color:#f07b14;font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="brand"><span>Programmers</span> Lab</div>
        <div class="sub">Rawalpindi's #1 IT Institute</div>
    </div>

    <div style="text-align:center;margin-bottom:22px;">
        <div class="login-badge"><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</div>
        <h2>Teacher Login</h2>
        <p class="sub-text">Enter your credentials to access your portal.</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><i class="fas fa-exclamation-circle"></i><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" placeholder="Your username"
                   value="<?= h($_POST['username'] ?? '') ?>"
                   autocomplete="username" <?= $locked ? 'disabled' : '' ?> required>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="pass-wrap">
                <input type="password" name="password" id="passInput"
                       placeholder="Your password"
                       autocomplete="current-password"
                       <?= $locked ? 'disabled' : '' ?> required>
                <button type="button" class="pass-eye" onclick="togglePass()">
                    <i class="fas fa-eye" id="passEye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn" <?= $locked ? 'disabled style="background:#9ca3af;cursor:not-allowed;"' : '' ?>>
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
    </form>

    <div class="links">
        <a href="index.html"><i class="fas fa-home me-1"></i>Back to Website</a>
        &nbsp;|&nbsp;
        <a href="user-login.php">Student Login</a>
    </div>
</div>
<script>
function togglePass() {
    const inp = document.getElementById('passInput');
    const eye = document.getElementById('passEye');
    inp.type = inp.type==='password' ? 'text' : 'password';
    eye.className = inp.type==='password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
