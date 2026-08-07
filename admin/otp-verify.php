<?php
// ============================================================
// Admin Login — Step 2: OTP Verification
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../otp_helper.php';
send_security_headers();
session_init();

// Already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

// No pending OTP state → back to login
if (
    empty($_SESSION['otp_pending_type']) ||
    $_SESSION['otp_pending_type'] !== 'admin' ||
    empty($_SESSION['otp_pending_id'])
) {
    header('Location: login.php'); exit;
}

// Pending session expired (15 min window)
if ((time() - ($_SESSION['otp_pending_time'] ?? 0)) > 900) {
    session_unset(); session_destroy();
    header('Location: login.php?timeout=1'); exit;
}

$error       = '';
$success_msg = '';
$adminId     = (int)$_SESSION['otp_pending_id'];
$adminUser   = $_SESSION['otp_pending_username'] ?? 'Admin';
$maskedEmail = mask_email($_SESSION['otp_pending_email'] ?? '');

// ── Resend OTP ────────────────────────────────────────────────
if (isset($_GET['resend'])) {
    $conn   = get_db();
    $result = otp_generate_and_send($conn, 'admin', $adminId, $_SESSION['otp_pending_email'], $adminUser, 'Admin');
    $conn->close();
    if ($result['success']) {
        $_SESSION['otp_pending_time'] = time();
        header('Location: otp-verify.php?sent=1'); exit;
    }
    $error = $result['message'];
}
if (isset($_GET['sent'])) $success_msg = 'A new OTP has been sent to your email.';

// ── Verify OTP ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim(implode('', $_POST['otp'] ?? []));  // joins the 6 individual digit inputs
    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter the complete 6-digit OTP.';
    } else {
        $conn   = get_db();
        $result = otp_verify($conn, 'admin', $adminId, $code);

        if ($result['success']) {
            // ── Full login ──
            session_regenerate_id(true);
            $ip = $_SESSION['otp_pending_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $adminUser;
            $_SESSION['last_activity']   = time();
            $_SESSION['fingerprint']     = hash('sha256', ($_SESSION['otp_pending_ua'] ?? '') . $ip);

            // Clear pending keys
            unset(
                $_SESSION['otp_pending_type'], $_SESSION['otp_pending_id'],
                $_SESSION['otp_pending_username'], $_SESSION['otp_pending_email'],
                $_SESSION['otp_pending_ip'], $_SESSION['otp_pending_ua'],
                $_SESSION['otp_pending_time']
            );

            log_activity($conn, 'LOGIN', 'Admin OTP verified — login successful');
            $conn->close();
            header('Location: index.php'); exit;
        } else {
            $error = $result['message'];
        }
        $conn->close();
    }
}

function mask_email(string $e): string {
    if (!$e) return '***';
    [$local, $domain] = array_pad(explode('@', $e, 2), 2, '');
    $visible = substr($local, 0, min(3, strlen($local)));
    return $visible . str_repeat('*', max(0, strlen($local)-3)) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification — Admin | Programmers Lab</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',sans-serif;background:#0d1b2a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;overflow:hidden;position:relative;}
        body::before{content:'';position:fixed;top:-100px;left:-100px;width:450px;height:450px;border-radius:50%;background:radial-gradient(circle,rgba(240,123,20,.13),transparent 70%);pointer-events:none;}
        body::after{content:'';position:fixed;bottom:-80px;right:-80px;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.09),transparent 70%);pointer-events:none;}
        .wrap{width:100%;max-width:440px;position:relative;z-index:1;}
        .brand{text-align:center;margin-bottom:26px;}
        .brand-icon{width:56px;height:56px;border-radius:16px;background:#f07b14;display:inline-flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:12px;box-shadow:0 8px 24px rgba(240,123,20,.35);}
        .brand h1{color:#fff;font-size:20px;font-weight:800;margin:0 0 4px;}.brand h1 span{color:#f07b14;}
        .brand p{color:rgba(255,255,255,.4);font-size:13px;margin:0;}
        .card{background:#fff;border-radius:20px;padding:36px 40px;box-shadow:0 24px 64px rgba(0,0,0,.35);}
        .badge{display:inline-flex;align-items:center;gap:6px;background:rgba(240,123,20,.1);border:1px solid rgba(240,123,20,.25);color:#f07b14;padding:5px 14px;border-radius:50px;font-size:12px;font-weight:700;margin-bottom:14px;}
        .card h2{font-size:20px;font-weight:800;color:#0d1b2a;margin:0 0 5px;}
        .card .sub{font-size:13.5px;color:#6b7280;margin:0 0 6px;line-height:1.6;}
        .email-chip{display:inline-flex;align-items:center;gap:6px;background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px;}
        /* 6-box OTP input */
        .otp-grid{display:flex;gap:10px;justify-content:center;margin:6px 0 22px;}
        .otp-grid input{width:50px;height:58px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;font-size:24px;font-weight:800;color:#0d1b2a;font-family:'Inter',sans-serif;outline:none;transition:border .2s,box-shadow .2s;background:#fafafa;}
        .otp-grid input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.15);background:#fff;}
        .otp-grid input.filled{border-color:#10b981;background:#f0fdf4;}
        .btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:50px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:background .2s,transform .2s,box-shadow .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
        .btn:hover{background:#d96a00;transform:translateY(-1px);box-shadow:0 8px 20px rgba(240,123,20,.3);}
        .btn:disabled{background:#9ca3af;cursor:not-allowed;transform:none;box-shadow:none;}
        .alert{border-radius:10px;font-size:13.5px;padding:12px 16px;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
        .alert-danger {background:rgba(239,68,68,.08);color:#b91c1c;}
        .alert-success{background:rgba(16,185,129,.08);color:#065f46;}
        .links{display:flex;justify-content:space-between;margin-top:20px;font-size:13px;}
        .links a{color:#f07b14;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;}
        .links a:hover{color:#d96a00;}
        .timer{text-align:center;font-size:12.5px;color:#9ca3af;margin-top:12px;}
        .timer strong{color:#f07b14;}
        @media(max-width:480px){
            .card{padding:28px 22px;}
            .otp-grid input{width:42px;height:50px;font-size:20px;}
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
        <h1><span>Programmers</span> Lab</h1>
        <p>Admin Panel — 2-Step Verification</p>
    </div>
    <div class="card">
        <div><span class="badge"><i class="fas fa-lock"></i> Security Check</span></div>
        <h2>Enter OTP</h2>
        <p class="sub">A 6-digit verification code has been sent to your registered email address.</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= h($success_msg) ?></div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="otp-grid">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" name="otp[]" class="otp-input"
                       maxlength="1" pattern="\d" inputmode="numeric"
                       autocomplete="one-time-code"
                       <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>
            <button type="submit" class="btn" id="submitBtn" disabled>
                <i class="fas fa-check-circle"></i> Verify OTP
            </button>
        </form>

        <div class="timer" id="timerBox">
            Code expires in <strong id="countdown">10:00</strong>
        </div>

        <div class="links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            <a href="otp-verify.php?resend=1" id="resendLink" style="pointer-events:none;color:#9ca3af;">
                <i class="fas fa-redo"></i> Resend OTP
            </a>
        </div>
    </div>
</div>
<script>
// ── OTP Box Navigation ──────────────────────────────────────
const inputs = document.querySelectorAll('.otp-input');
const submitBtn = document.getElementById('submitBtn');

inputs.forEach((inp, idx) => {
    inp.addEventListener('input', (e) => {
        const v = e.target.value.replace(/\D/g,'');
        e.target.value = v;
        if (v) {
            inp.classList.add('filled');
            if (idx < 5) inputs[idx+1].focus();
        } else {
            inp.classList.remove('filled');
        }
        checkComplete();
    });
    inp.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) {
            inputs[idx-1].focus();
            inputs[idx-1].value = '';
            inputs[idx-1].classList.remove('filled');
            checkComplete();
        }
        if (e.key === 'ArrowLeft'  && idx > 0) inputs[idx-1].focus();
        if (e.key === 'ArrowRight' && idx < 5) inputs[idx+1].focus();
    });
    inp.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        text.split('').forEach((ch, i) => {
            if (inputs[i]) { inputs[i].value=ch; inputs[i].classList.add('filled'); }
        });
        if (inputs[Math.min(text.length,5)]) inputs[Math.min(text.length,5)].focus();
        checkComplete();
    });
});

function checkComplete() {
    const done = [...inputs].every(i => i.value.length === 1);
    submitBtn.disabled = !done;
}

// ── Countdown Timer (10 min) ────────────────────────────────
let seconds = 600;
const cd = document.getElementById('countdown');
const resendLink = document.getElementById('resendLink');
let resendEnabled = false;

const timer = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
        clearInterval(timer);
        cd.textContent = '00:00';
        cd.parentElement.innerHTML = '<span style="color:#ef4444;font-weight:700;">OTP expired.</span> <a href="otp-verify.php?resend=1" style="color:#f07b14;font-weight:700;">Request new OTP →</a>';
        submitBtn.disabled = true;
        return;
    }
    const m = String(Math.floor(seconds/60)).padStart(2,'0');
    const s = String(seconds%60).padStart(2,'0');
    cd.textContent = m+':'+s;

    // Enable resend link after 60s
    if (!resendEnabled && seconds <= 540) {
        resendEnabled = true;
        resendLink.style.pointerEvents = 'auto';
        resendLink.style.color = '#f07b14';
    }
}, 1000);
</script>
</body>
</html>
