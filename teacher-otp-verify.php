<?php
// ============================================================
// Teacher Login — Step 2: OTP Verification
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/otp_helper.php';
send_security_headers();
session_init();

if (isset($_SESSION['teacher_id'])) {
    header('Location: teacher-portal.php'); exit;
}

if (
    empty($_SESSION['otp_pending_type']) ||
    $_SESSION['otp_pending_type'] !== 'teacher' ||
    empty($_SESSION['otp_pending_id'])
) {
    header('Location: teacher-login.php'); exit;
}

if ((time() - ($_SESSION['otp_pending_time'] ?? 0)) > 900) {
    session_unset(); session_destroy();
    header('Location: teacher-login.php?timeout=1'); exit;
}

$error       = '';
$success_msg = '';
$teacherId   = (int)$_SESSION['otp_pending_id'];
$teacherName = $_SESSION['otp_pending_name']     ?? 'Teacher';
$teacherUser = $_SESSION['otp_pending_username'] ?? '';
$maskedEmail = mask_email($_SESSION['otp_pending_email'] ?? '');

// ── Resend ────────────────────────────────────────────────────
if (isset($_GET['resend'])) {
    $conn   = get_db();
    $result = otp_generate_and_send($conn, 'teacher', $teacherId, $_SESSION['otp_pending_email'], $teacherName, 'Teacher');
    $conn->close();
    if ($result['success']) {
        $_SESSION['otp_pending_time'] = time();
        header('Location: teacher-otp-verify.php?sent=1'); exit;
    }
    $error = $result['message'];
}
if (isset($_GET['sent'])) $success_msg = 'A new OTP has been sent to your email.';

// ── Verify ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim(implode('', $_POST['otp'] ?? []));
    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter the complete 6-digit OTP.';
    } else {
        $conn   = get_db();
        $result = otp_verify($conn, 'teacher', $teacherId, $code);

        if ($result['success']) {
            session_regenerate_id(true);
            $ip = $_SESSION['otp_pending_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');

            $_SESSION['teacher_id']       = $teacherId;
            $_SESSION['teacher_username'] = $teacherUser;
            $_SESSION['teacher_name']     = $teacherName;
            $_SESSION['last_activity']    = time();
            $_SESSION['teacher_fp']       = hash('sha256', ($_SESSION['otp_pending_ua'] ?? '') . $ip);

            unset(
                $_SESSION['otp_pending_type'], $_SESSION['otp_pending_id'],
                $_SESSION['otp_pending_name'], $_SESSION['otp_pending_username'],
                $_SESSION['otp_pending_email'], $_SESSION['otp_pending_ip'],
                $_SESSION['otp_pending_ua'], $_SESSION['otp_pending_time']
            );
            $conn->close();
            header('Location: teacher-portal.php'); exit;
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

$initials = strtoupper(substr($teacherName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OTP Verification — Teacher | Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:24px;padding:42px 40px;width:100%;max-width:440px;box-shadow:0 24px 80px rgba(0,0,0,.3);}
.top{text-align:center;margin-bottom:28px;}
.av{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#f07b14,#d96a00);display:inline-flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#fff;margin-bottom:12px;box-shadow:0 8px 24px rgba(240,123,20,.35);}
.brand{font-size:20px;font-weight:800;color:#0d1b2a;} .brand span{color:#f07b14;}
.brand-sub{font-size:12px;color:#9ca3af;margin-top:3px;}
.badge{display:inline-flex;align-items:center;gap:6px;background:rgba(240,123,20,.1);border:1px solid rgba(240,123,20,.25);color:#f07b14;padding:5px 14px;border-radius:50px;font-size:12px;font-weight:700;margin:18px 0 10px;}
h2{font-size:22px;font-weight:800;color:#0d1b2a;margin-bottom:6px;}
.sub{font-size:13.5px;color:#6b7280;margin-bottom:8px;}
.email-chip{display:inline-flex;align-items:center;gap:6px;background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px;}
/* OTP Grid */
.otp-grid{display:flex;gap:10px;justify-content:center;margin-bottom:22px;}
.otp-grid input{width:50px;height:58px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;font-size:24px;font-weight:900;color:#0d1b2a;font-family:'Inter',sans-serif;outline:none;transition:border .2s,box-shadow .2s;background:#fafafa;}
.otp-grid input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.15);background:#fff;}
.otp-grid input.filled{border-color:#10b981;background:#f0fdf4;color:#065f46;}
.btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s,transform .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn:hover:not(:disabled){background:#d96a00;transform:translateY(-1px);}
.btn:disabled{background:#9ca3af;cursor:not-allowed;}
.alert{border-radius:10px;font-size:13.5px;padding:12px 16px;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.alert-danger{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;}
.timer{text-align:center;font-size:12.5px;color:#9ca3af;margin-top:12px;}
.timer strong{color:#f07b14;}
.links{display:flex;justify-content:space-between;margin-top:20px;font-size:13px;}
.links a{color:#f07b14;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;}
.links a.disabled-link{color:#9ca3af;pointer-events:none;}
@media(max-width:480px){.card{padding:28px 20px;} .otp-grid input{width:42px;height:50px;font-size:20px;}}
</style>
</head>
<body>
<div class="card">
    <div class="top">
        <div class="av"><?= h($initials) ?></div>
        <div class="brand"><span>Programmers</span> Lab</div>
        <div class="brand-sub">Teacher Portal</div>
    </div>

    <div style="text-align:center;">
        <span class="badge"><i class="fas fa-shield-alt"></i> 2-Step Verification</span>
        <h2>Enter OTP</h2>
        <p class="sub">A 6-digit code was sent to:</p>
        <span class="email-chip"><i class="fas fa-envelope"></i> <?= h($maskedEmail) ?></span>
    </div>

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
            <i class="fas fa-check-circle"></i> Verify & Login
        </button>
    </form>

    <div class="timer">
        Code expires in <strong id="countdown">10:00</strong>
    </div>

    <div class="links">
        <a href="teacher-login.php"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="teacher-otp-verify.php?resend=1" id="resendLink" class="disabled-link">
            <i class="fas fa-redo"></i> Resend OTP
        </a>
    </div>
</div>
<script>
const inputs    = document.querySelectorAll('.otp-input');
const submitBtn = document.getElementById('submitBtn');

inputs.forEach((inp, idx) => {
    inp.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/\D/g,'');
        e.target.classList.toggle('filled', !!e.target.value);
        if (e.target.value && idx < 5) inputs[idx+1].focus();
        checkDone();
    });
    inp.addEventListener('keydown', e => {
        if (e.key==='Backspace' && !e.target.value && idx>0) {
            inputs[idx-1].focus(); inputs[idx-1].value=''; inputs[idx-1].classList.remove('filled'); checkDone();
        }
        if (e.key==='ArrowLeft'  && idx>0) inputs[idx-1].focus();
        if (e.key==='ArrowRight' && idx<5) inputs[idx+1].focus();
    });
    inp.addEventListener('paste', e => {
        e.preventDefault();
        const txt = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        txt.split('').forEach((ch,i) => { if(inputs[i]){inputs[i].value=ch;inputs[i].classList.add('filled');} });
        inputs[Math.min(txt.length,5)]?.focus();
        checkDone();
    });
});
function checkDone(){ submitBtn.disabled = ![...inputs].every(i=>i.value.length===1); }

// Countdown
let secs=600;
const cd = document.getElementById('countdown');
const rl = document.getElementById('resendLink');
const timer = setInterval(()=>{
    secs--;
    if(secs<=0){
        clearInterval(timer);
        cd.parentElement.innerHTML='<span style="color:#ef4444;font-weight:700;">OTP expired.</span> <a href="teacher-otp-verify.php?resend=1" style="color:#f07b14;font-weight:700;">Request new →</a>';
        submitBtn.disabled=true; return;
    }
    cd.textContent=String(Math.floor(secs/60)).padStart(2,'0')+':'+String(secs%60).padStart(2,'0');
    if(secs<=540){ rl.classList.remove('disabled-link'); rl.style.color='#f07b14'; }
},1000);
</script>
</body>
</html>
