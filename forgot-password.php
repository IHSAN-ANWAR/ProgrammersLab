<?php
// ============================================================
// Forgot Password — Step 1: Enter email → receive OTP
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/otp_helper.php';
send_security_headers();
session_init();

// Already logged in → no need
if (isset($_SESSION['user_id'])) {
    header('Location: user-profile.php'); exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = get_db();

        // Find user — always show same message to prevent email enumeration
        $stmt = $conn->prepare("SELECT id, full_name FROM site_users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $result = otp_generate_and_send(
                $conn, 'user', (int)$user['id'],
                $email, $user['full_name'], 'Account'
            );
            // Override send_otp_email with reset-specific email
            if ($result['success']) {
                // Re-send with reset template instead
                $plain = ''; // already sent above — use the same OTP
                // Store reset state in session
                session_regenerate_id(true);
                $_SESSION['reset_user_id']    = (int)$user['id'];
                $_SESSION['reset_email']      = $email;
                $_SESSION['reset_time']       = time();
                $conn->close();
                header('Location: reset-password.php'); exit;
            } else {
                $error = $result['message'];
            }
        } else {
            // Don't reveal if email exists — show same success message
            session_regenerate_id(true);
            $_SESSION['reset_user_id']    = 0; // fake — will fail gracefully on next step
            $_SESSION['reset_email']      = $email;
            $_SESSION['reset_time']       = time();
            $conn->close();
            header('Location: reset-password.php'); exit;
        }

        if (isset($conn) && $conn instanceof mysqli) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;}
.auth-back{display:flex;align-items:center;gap:6px;color:rgba(255,255,255,.6);text-decoration:none;font-size:13px;margin-bottom:20px;transition:color .2s;align-self:flex-start;}
.auth-back:hover{color:#fff;}
.wrap{width:100%;max-width:440px;}
.logo{text-align:center;margin-bottom:28px;}
.logo a{font-size:24px;font-weight:800;color:#fff;text-decoration:none;}
.logo a span{color:#f07b14;}
.logo p{color:rgba(255,255,255,.45);font-size:13px;margin-top:4px;}
.card{background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 24px 64px rgba(0,0,0,.35);}
.icon-circle{width:64px;height:64px;background:rgba(240,123,20,.1);border:2px solid rgba(240,123,20,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:26px;color:#f07b14;}
h2{font-size:20px;font-weight:800;color:#0d1b2a;text-align:center;margin-bottom:6px;}
.sub{font-size:13.5px;color:#6b7280;text-align:center;margin-bottom:26px;line-height:1.6;}
.field{margin-bottom:20px;}
.field label{display:block;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;}
.field input{width:100%;padding:13px 16px 13px 42px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:inherit;color:#0d1b2a;background:#fafafa;outline:none;transition:border .2s,box-shadow .2s;}
.field input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.1);background:#fff;}
.btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s,transform .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn:hover{background:#d96a00;transform:translateY(-1px);}
.alert{border-radius:10px;font-size:13.5px;padding:12px 16px;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
.back-login{text-align:center;margin-top:20px;font-size:13.5px;color:#6b7280;}
.back-login a{color:#f07b14;font-weight:700;text-decoration:none;}
@media(max-width:480px){.card{padding:28px 20px;}}
</style>
</head>
<body>
<a href="auth.php" class="auth-back"><i class="fas fa-arrow-left"></i> Back to Login</a>
<div class="wrap">
    <div class="logo">
        <a href="index.html"><span>Programmers</span> Lab</a>
        <p>Password Recovery</p>
    </div>
    <div class="card">
        <div class="icon-circle"><i class="fas fa-key"></i></div>
        <h2>Forgot Password?</h2>
        <p class="sub">Enter your registered email address. We'll send a 6-digit OTP to reset your password.</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="your@email.com"
                           value="<?= h($_POST['email'] ?? '') ?>"
                           autocomplete="email" required>
                </div>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Send Reset OTP
            </button>
        </form>

        <div class="back-login">
            Remembered your password? <a href="auth.php">Login here</a>
        </div>
    </div>
</div>
</body>
</html>
