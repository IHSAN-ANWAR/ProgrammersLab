<?php
// ============================================================
// Reset Password — Step 2: Verify OTP + Set New Password
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/otp_helper.php';
send_security_headers();
session_init();

if (isset($_SESSION['user_id'])) {
    header('Location: user-profile.php'); exit;
}

// No reset session → back to forgot
if (empty($_SESSION['reset_email']) || empty($_SESSION['reset_time'])) {
    header('Location: forgot-password.php'); exit;
}

// 15-minute window to complete reset
if ((time() - $_SESSION['reset_time']) > 900) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_time']);
    header('Location: forgot-password.php?expired=1'); exit;
}

$error       = '';
$success_msg = '';
$userId      = (int)($_SESSION['reset_user_id'] ?? 0);
$email       = $_SESSION['reset_email'] ?? '';
$done        = false;

// Mask email for display
$parts   = explode('@', $email);
$local   = $parts[0] ?? '';
$domain  = $parts[1] ?? '';
$masked  = substr($local, 0, min(3, strlen($local))) . str_repeat('*', max(0, strlen($local)-3)) . '@' . $domain;

// ── Resend OTP ────────────────────────────────────────────────
if (isset($_GET['resend']) && $userId > 0) {
    $conn = get_db();
    $s = $conn->prepare("SELECT full_name FROM site_users WHERE id=?");
    $s->bind_param('i', $userId);
    $s->execute();
    $u = $s->get_result()->fetch_assoc();
    $s->close();
    if ($u) {
        $result = otp_generate_and_send($conn, 'user', $userId, $email, $u['full_name'], 'Account');
        if ($result['success']) {
            $_SESSION['reset_time'] = time();
            $conn->close();
            header('Location: reset-password.php?sent=1'); exit;
        }
        $error = $result['message'];
    }
    $conn->close();
}
if (isset($_GET['sent'])) $success_msg = 'A new OTP has been sent to your email.';
if (isset($_GET['expired'])) $error = 'Session expired. Please request a new reset OTP.';

// ── Handle form submit ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code     = trim(implode('', $_POST['otp'] ?? []));
    $newpass  = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter the complete 6-digit OTP.';
    } elseif (strlen($newpass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newpass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($userId <= 0) {
        $error = 'Invalid request. Please start over.';
    } else {
        $conn   = get_db();
        $result = otp_verify($conn, 'user', $userId, $code);

        if ($result['success']) {
            // Update password
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE site_users SET password_hash=? WHERE id=?");
            $upd->bind_param('si', $hash, $userId);
            $upd->execute();
            $upd->close();
            $conn->close();

            // Clear reset session
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_time']);

            $done = true;
        } else {
            $error = $result['message'];
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
<title>Reset Password — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;}
.wrap{width:100%;max-width:460px;}
.logo{text-align:center;margin-bottom:24px;}
.logo a{font-size:24px;font-weight:800;color:#fff;text-decoration:none;}
.logo a span{color:#f07b14;}
.logo p{color:rgba(255,255,255,.45);font-size:13px;margin-top:4px;}
.card{background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 24px 64px rgba(0,0,0,.35);}
.icon-circle{width:64px;height:64px;background:rgba(240,123,20,.1);border:2px solid rgba(240,123,20,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:26px;color:#f07b14;}
h2{font-size:20px;font-weight:800;color:#0d1b2a;text-align:center;margin-bottom:6px;}
.sub{font-size:13.5px;color:#6b7280;text-align:center;margin-bottom:6px;line-height:1.6;}
.email-chip{display:inline-flex;align-items:center;gap:6px;background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;margin:0 auto 22px;display:flex;justify-content:center;width:fit-content;}
/* OTP boxes */
.otp-grid{display:flex;gap:10px;justify-content:center;margin-bottom:22px;}
.otp-grid input{width:48px;height:56px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;font-size:22px;font-weight:900;color:#0d1b2a;font-family:'Inter',sans-serif;outline:none;transition:border .2s,box-shadow .2s;background:#fafafa;}
.otp-grid input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.12);background:#fff;}
.otp-grid input.filled{border-color:#10b981;background:#f0fdf4;color:#065f46;}
/* Password fields */
.field{margin-bottom:18px;}
.field label{display:block;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
.input-wrap{position:relative;}
.input-wrap i.ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;}
.field input[type=password],.field input[type=text]{width:100%;padding:13px 44px 13px 42px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:inherit;color:#0d1b2a;background:#fafafa;outline:none;transition:border .2s,box-shadow .2s;}
.field input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.1);background:#fff;}
.pw-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;font-size:14px;padding:4px;}
.pw-eye:hover{color:#374151;}
.btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s,transform .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;}
.btn:hover:not(:disabled){background:#d96a00;transform:translateY(-1px);}
.btn:disabled{background:#9ca3af;cursor:not-allowed;}
.alert{border-radius:10px;font-size:13.5px;padding:12px 16px;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;}
.timer{text-align:center;font-size:12.5px;color:#9ca3af;margin-top:10px;}
.timer strong{color:#f07b14;}
.links{display:flex;justify-content:space-between;margin-top:18px;font-size:13px;}
.links a{color:#f07b14;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;}
.links a.disabled-link{color:#9ca3af;pointer-events:none;}
/* Success state */
.success-screen{text-align:center;padding:10px 0;}
.success-screen .check{width:72px;height:72px;background:rgba(16,185,129,.1);border:2px solid rgba(16,185,129,.3);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:30px;color:#10b981;}
.success-screen h3{font-size:22px;font-weight:800;color:#0d1b2a;margin-bottom:8px;}
.success-screen p{font-size:14px;color:#6b7280;margin-bottom:24px;}
.success-screen a{display:inline-flex;align-items:center;gap:8px;padding:13px 32px;background:#f07b14;color:#fff;border-radius:12px;font-size:15px;font-weight:700;text-decoration:none;transition:background .2s;}
.success-screen a:hover{background:#d96a00;}
@media(max-width:480px){.card{padding:28px 20px;}.otp-grid input{width:40px;height:48px;font-size:18px;}}
</style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <a href="index.html"><span>Programmers</span> Lab</a>
        <p>Password Reset</p>
    </div>

    <div class="card">
    <?php if ($done): ?>
        <!-- SUCCESS -->
        <div class="success-screen">
            <div class="check"><i class="fas fa-check"></i></div>
            <h3>Password Updated!</h3>
            <p>Your password has been successfully reset. You can now login with your new password.</p>
            <a href="auth.php"><i class="fas fa-sign-in-alt"></i> Login Now</a>
        </div>
    <?php else: ?>
        <div class="icon-circle"><i class="fas fa-lock-open"></i></div>
        <h2>Reset Password</h2>
        <p class="sub">Enter the 6-digit OTP sent to:</p>
        <div class="email-chip"><i class="fas fa-envelope"></i> <?= h($masked) ?></div>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($success_msg) ?></div>
        <?php endif; ?>

        <form method="POST" id="resetForm">
            <!-- OTP boxes -->
            <div class="otp-grid">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" name="otp[]" class="otp-input"
                       maxlength="1" pattern="\d" inputmode="numeric"
                       <?= $i===0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>

            <!-- New Password -->
            <div class="field">
                <label>New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock ico"></i>
                    <input type="password" name="new_password" id="newPass"
                           placeholder="Min 6 characters" autocomplete="new-password">
                    <button type="button" class="pw-eye" onclick="togglePw('newPass',this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="field">
                <label>Confirm New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock ico"></i>
                    <input type="password" name="confirm_password" id="confPass"
                           placeholder="Repeat new password" autocomplete="new-password">
                    <button type="button" class="pw-eye" onclick="togglePw('confPass',this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn" id="submitBtn" disabled>
                <i class="fas fa-key"></i> Reset Password
            </button>
        </form>

        <div class="timer">OTP expires in <strong id="countdown">10:00</strong></div>

        <div class="links">
            <a href="forgot-password.php"><i class="fas fa-arrow-left"></i> Try different email</a>
            <a href="reset-password.php?resend=1" id="resendLink" class="disabled-link">
                <i class="fas fa-redo"></i> Resend OTP
            </a>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
const inputs    = document.querySelectorAll('.otp-input');
const submitBtn = document.getElementById('submitBtn');

if (inputs.length) {
    inputs.forEach(function(inp, idx) {
        inp.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g,'');
            e.target.classList.toggle('filled', !!e.target.value);
            if (e.target.value && idx < 5) inputs[idx+1].focus();
            checkDone();
        });
        inp.addEventListener('keydown', function(e) {
            if (e.key==='Backspace' && !e.target.value && idx>0) {
                inputs[idx-1].focus(); inputs[idx-1].value='';
                inputs[idx-1].classList.remove('filled'); checkDone();
            }
            if (e.key==='ArrowLeft'  && idx>0) inputs[idx-1].focus();
            if (e.key==='ArrowRight' && idx<5) inputs[idx+1].focus();
        });
        inp.addEventListener('paste', function(e) {
            e.preventDefault();
            var txt = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            txt.split('').forEach(function(ch,i){ if(inputs[i]){inputs[i].value=ch;inputs[i].classList.add('filled');} });
            if (inputs[Math.min(txt.length,5)]) inputs[Math.min(txt.length,5)].focus();
            checkDone();
        });
    });

    function checkDone(){
        var allFilled = Array.from(inputs).every(function(i){return i.value.length===1;});
        var newp = document.getElementById('newPass');
        var conf = document.getElementById('confPass');
        submitBtn.disabled = !(allFilled && newp && newp.value.length>=6 && conf && conf.value.length>=1);
    }

    ['newPass','confPass'].forEach(function(id){
        var el = document.getElementById(id);
        if(el) el.addEventListener('input', checkDone);
    });

    // Countdown
    var secs = 600;
    var cd   = document.getElementById('countdown');
    var rl   = document.getElementById('resendLink');
    var timer = setInterval(function(){
        secs--;
        if(secs<=0){
            clearInterval(timer);
            if(cd) cd.parentElement.innerHTML='<span style="color:#ef4444;font-weight:700;">OTP expired.</span> <a href="reset-password.php?resend=1" style="color:#f07b14;font-weight:700;">Request new →</a>';
            submitBtn.disabled=true; return;
        }
        if(cd) cd.textContent=String(Math.floor(secs/60)).padStart(2,'0')+':'+String(secs%60).padStart(2,'0');
        if(rl && secs<=540){ rl.classList.remove('disabled-link'); rl.style.color='#f07b14'; }
    },1000);
}

function togglePw(id, btn) {
    var inp = document.getElementById(id);
    var isText = inp.type==='text';
    inp.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
