<?php
require_once __DIR__ . '/config.php';
session_init();

// If not logged in → redirect to auth page, come back here after login
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?redirect=enroll-form.php&tab=login');
    exit;
}

// Pre-fill user data from session
$user_name  = $_SESSION['user_name']  ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';
$csrf       = csrf_token();

// Read enroll-form.html and inject user data + PHP logic
$html = file_get_contents(__DIR__ . '/enroll-form.html');

// Inject login bar before </body>
$loginBar = '
<div id="pl-login-bar" style="position:fixed;top:68px;left:0;right:0;z-index:999;background:#f07b14;color:#fff;padding:8px 20px;display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:600;">
    <span><i class="fas fa-user-check" style="margin-right:6px;"></i>Logged in as <strong>' . htmlspecialchars($user_name) . '</strong></span>
    <a href="user-logout.php" style="color:#fff;font-size:12px;opacity:.85;text-decoration:underline;">Logout</a>
</div>
<script>
// Pre-fill form fields from session
document.addEventListener("DOMContentLoaded", function() {
    var n = document.querySelector(\'[name="fullName"]\');
    var e = document.querySelector(\'[name="email"]\');
    var p = document.querySelector(\'[name="phone"]\');
    if (n && !n.value) n.value = ' . json_encode($user_name) . ';
    if (e && !e.value) e.value = ' . json_encode($user_email) . ';
    if (p && !p.value) p.value = ' . json_encode($user_phone) . ';
});
</script>
';

$html = str_replace('</body>', $loginBar . '</body>', $html);

echo $html;
