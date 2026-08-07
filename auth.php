<?php
require_once __DIR__ . '/config.php';
send_security_headers();
session_init();

// Already logged in → redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = $_GET['redirect'] ?? 'user-profile.php';
    header('Location: ' . $redirect);
    exit;
}

$csrf = csrf_token();
$tab  = $_GET['tab'] ?? 'login'; // login or register
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login / Register — Programmers Lab</title>
<meta name="robots" content="noindex,nofollow">
<link href="img/favicon-16x16.png" rel="shortcut icon"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:20px;overflow-y:auto;}
@media(min-height:700px){body{justify-content:center;}}

.auth-logo{text-align:center;margin-bottom:28px;}
.auth-logo a{font-size:26px;font-weight:800;color:#fff;text-decoration:none;}
.auth-logo a span{color:#f07b14;}
.auth-logo p{color:rgba(255,255,255,.5);font-size:13px;margin-top:4px;}

.auth-card{background:#fff;border-radius:20px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35);}

/* Tabs */
.auth-tabs{display:flex;border-bottom:1px solid #f0f0f0;}
.auth-tab{flex:1;padding:16px;text-align:center;font-size:14px;font-weight:700;cursor:pointer;color:#9ca3af;border:none;background:none;transition:all .2s;}
.auth-tab.active{color:#f07b14;border-bottom:2px solid #f07b14;background:#fff;}
.auth-tab:hover:not(.active){background:#fafafa;color:#374151;}

/* Form */
.auth-body{padding:32px 36px;}
.auth-title{font-size:20px;font-weight:800;color:#0d1b2a;margin-bottom:6px;}
.auth-sub{font-size:13.5px;color:#6b7280;margin-bottom:24px;}

.auth-field{margin-bottom:18px;}
.auth-field label{display:block;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
.auth-input-wrap{position:relative;}
.auth-input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;}
.auth-field input{width:100%;padding:13px 44px 13px 40px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-weight:500;color:#0d1b2a;outline:none;transition:border-color .2s,box-shadow .2s;font-family:inherit;background:#fafafa;}
.auth-field input:focus{border-color:#f07b14;box-shadow:0 0 0 3px rgba(240,123,20,.1);background:#fff;}
.auth-field input.error{border-color:#ef4444;}

.auth-pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;background:none;border:none;font-size:14px;padding:0;}
.auth-pw-toggle:hover{color:#374151;}

.auth-btn{width:100%;padding:14px;background:#f07b14;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s,transform .2s;font-family:inherit;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:10px;}
.auth-btn:hover{background:#d96a00;transform:translateY(-1px);}
.auth-btn:disabled{background:#9ca3af;cursor:not-allowed;transform:none;}

.auth-alert{padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:16px;display:none;align-items:center;gap:8px;}
.auth-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}
.auth-alert.success{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);color:#059669;}

.auth-divider{display:flex;align-items:center;gap:12px;margin:20px 0;}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:#e5e7eb;}
.auth-divider span{font-size:12px;color:#9ca3af;white-space:nowrap;}

.auth-switch{text-align:center;font-size:13.5px;color:#6b7280;margin-top:20px;}
.auth-switch a{color:#f07b14;font-weight:700;text-decoration:none;cursor:pointer;}
.auth-switch a:hover{text-decoration:underline;}

.auth-back{display:flex;align-items:center;gap:6px;color:rgba(255,255,255,.6);text-decoration:none;font-size:13px;margin-bottom:20px;transition:color .2s;}
.auth-back:hover{color:#fff;}

@media(max-width:480px){.auth-body{padding:24px 20px;}.auth-card{border-radius:16px;}}
</style>
</head>
<body>

<a href="index.html" class="auth-back"><i class="fas fa-arrow-left"></i> Back to Home</a>

<div class="auth-logo">
    <a href="index.html"><span>Programmers</span> Lab</a>
    <p>Pakistan's #1 IT Training Institute — Rawalpindi</p>
</div>

<div class="auth-card">

    <!-- Tabs -->
    <div class="auth-tabs">
        <button class="auth-tab <?= $tab==='login'    ? 'active' : '' ?>" onclick="switchTab('login')">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
        <button class="auth-tab <?= $tab==='register' ? 'active' : '' ?>" onclick="switchTab('register')">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </div>

    <!-- LOGIN FORM -->
    <div id="loginSection" class="auth-body" style="display:<?= $tab==='login' ? 'block' : 'none' ?>;">
        <h2 class="auth-title">Welcome Back!</h2>
        <p class="auth-sub">Login to your account to enroll in courses.</p>

        <div class="auth-alert error" id="loginError"><i class="fas fa-exclamation-circle"></i><span id="loginErrorMsg"></span></div>
        <div class="auth-alert success" id="loginSuccess"><i class="fas fa-check-circle"></i><span id="loginSuccessMsg"></span></div>

        <div class="auth-field">
            <label>Email Address</label>
            <div class="auth-input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" id="loginEmail" placeholder="your@email.com" autocomplete="email">
            </div>
        </div>
        <div class="auth-field">
            <label>Password</label>
            <div class="auth-input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="loginPassword" placeholder="Enter your password" autocomplete="current-password">
                <button class="auth-pw-toggle" onclick="togglePw('loginPassword', this)" type="button"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button class="auth-btn" id="loginBtn" onclick="doLogin()">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>

        <div style="text-align:right;margin-top:10px;">
            <a href="forgot-password.php" style="font-size:13px;color:#f07b14;font-weight:600;text-decoration:none;">
                <i class="fas fa-key" style="margin-right:4px;"></i>Forgot Password?
            </a>
        </div>

        <div class="auth-switch">
            Don't have an account? <a onclick="switchTab('register')">Register here</a>
        </div>
    </div>

    <!-- REGISTER FORM -->
    <div id="registerSection" class="auth-body" style="display:<?= $tab==='register' ? 'block' : 'none' ?>;">
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-sub">Register to enroll in our courses.</p>

        <div class="auth-alert error" id="regError"><i class="fas fa-exclamation-circle"></i><span id="regErrorMsg"></span></div>
        <div class="auth-alert success" id="regSuccess"><i class="fas fa-check-circle"></i><span id="regSuccessMsg"></span></div>

        <div class="auth-field">
            <label>Full Name</label>
            <div class="auth-input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" id="regName" placeholder="Your full name" autocomplete="name">
            </div>
        </div>
        <div class="auth-field">
            <label>Email Address</label>
            <div class="auth-input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" id="regEmail" placeholder="your@email.com" autocomplete="email">
            </div>
        </div>
        <div class="auth-field">
            <label>Phone Number</label>
            <div class="auth-input-wrap">
                <i class="fas fa-phone"></i>
                <input type="tel" id="regPhone" placeholder="03XX-XXXXXXX" autocomplete="tel">
            </div>
        </div>
        <div class="auth-field">
            <label>Password</label>
            <div class="auth-input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="regPassword" placeholder="Min 6 characters" autocomplete="new-password">
                <button class="auth-pw-toggle" onclick="togglePw('regPassword', this)" type="button"><i class="fas fa-eye"></i></button>
            </div>
        </div>
        <div class="auth-field">
            <label>Confirm Password</label>
            <div class="auth-input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="regConfirm" placeholder="Repeat your password" autocomplete="new-password">
                <button class="auth-pw-toggle" onclick="togglePw('regConfirm', this)" type="button"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button class="auth-btn" id="regBtn" onclick="doRegister()">
            <i class="fas fa-user-plus"></i> Create Account
        </button>

        <div class="auth-switch">
            Already have an account? <a onclick="switchTab('login')">Login here</a>
        </div>
    </div>

</div>

<script>
const CSRF    = <?= json_encode($csrf) ?>;
const REDIRECT = <?= json_encode($_GET['redirect'] ?? 'user-profile.php') ?>;

function switchTab(tab) {
    document.getElementById('loginSection').style.display    = tab === 'login'    ? 'block' : 'none';
    document.getElementById('registerSection').style.display = tab === 'register' ? 'block' : 'none';
    document.querySelectorAll('.auth-tab').forEach(function(t, i) {
        t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
    });
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

function showAlert(id, msg) {
    const el = document.getElementById(id);
    const span = el.querySelector('span') || el;
    if (el.querySelector('span')) el.querySelector('span').textContent = msg;
    else el.textContent = msg;
    el.style.display = 'flex';
}
function hideAlerts(prefix) {
    document.getElementById(prefix + 'Error').style.display   = 'none';
    document.getElementById(prefix + 'Success').style.display = 'none';
}

// Enter key
['loginEmail','loginPassword'].forEach(function(id) {
    document.getElementById(id).addEventListener('keydown', function(e) { if(e.key==='Enter') doLogin(); });
});
['regName','regEmail','regPhone','regPassword','regConfirm'].forEach(function(id) {
    document.getElementById(id).addEventListener('keydown', function(e) { if(e.key==='Enter') doRegister(); });
});

function doLogin() {
    hideAlerts('login');
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPassword').value;
    const btn   = document.getElementById('loginBtn');

    if (!email || !pass) { showAlert('loginErrorMsg', 'Please enter email and password.'); document.getElementById('loginError').style.display='flex'; return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';

    const fd = new FormData();
    fd.append('email',      email);
    fd.append('password',   pass);
    fd.append('csrf_token', CSRF);

    fetch('user-login.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            if (data.success) {
                showAlert('loginSuccessMsg', 'Login successful! Redirecting...');
                document.getElementById('loginSuccess').style.display = 'flex';
                setTimeout(function() { window.location.href = REDIRECT; }, 1000);
            } else {
                showAlert('loginErrorMsg', data.message || 'Login failed.');
                document.getElementById('loginError').style.display = 'flex';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            showAlert('loginErrorMsg', 'Network error. Please try again.');
            document.getElementById('loginError').style.display = 'flex';
        });
}

function doRegister() {
    hideAlerts('reg');
    const name  = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const phone = document.getElementById('regPhone').value.trim();
    const pass  = document.getElementById('regPassword').value;
    const conf  = document.getElementById('regConfirm').value;
    const btn   = document.getElementById('regBtn');

    if (!name || !email || !phone || !pass || !conf) {
        showAlert('regErrorMsg', 'Please fill all fields.');
        document.getElementById('regError').style.display = 'flex'; return;
    }
    if (pass !== conf) {
        showAlert('regErrorMsg', 'Passwords do not match.');
        document.getElementById('regError').style.display = 'flex'; return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';

    const fd = new FormData();
    fd.append('full_name',  name);
    fd.append('email',      email);
    fd.append('phone',      phone);
    fd.append('password',   pass);
    fd.append('confirm',    conf);
    fd.append('csrf_token', CSRF);

    fetch('user-register.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            if (data.success) {
                showAlert('regSuccessMsg', 'Account created! Redirecting...');
                document.getElementById('regSuccess').style.display = 'flex';
                setTimeout(function() { window.location.href = REDIRECT; }, 1200);
            } else {
                showAlert('regErrorMsg', data.message || 'Registration failed.');
                document.getElementById('regError').style.display = 'flex';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            showAlert('regErrorMsg', 'Network error. Please try again.');
            document.getElementById('regError').style.display = 'flex';
        });
}
</script>
</body>
</html>
