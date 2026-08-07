<?php
// ============================================================
// Mailer Helper — Programmers Lab
// PHPMailer 6.x with Gmail SMTP
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

// ── SMTP Config ───────────────────────────────────────────────
// All values must be set via environment variables / .env file.
// No credentials are hardcoded here.
define('MAIL_HOST',     getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT',     (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('MAIL_USER') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASS') ?: '');
define('MAIL_FROM',     getenv('MAIL_FROM') ?: '');
define('MAIL_FROM_NAME','Programmers Lab');

// Admin OTP goes to ALL these emails
define('ADMIN_OTP_EMAILS', [
    'muzamilqureshiarid@gmail.com',
    'ihsan.anwar4321@gmail.com',
]);

// ── Shared email CSS ──────────────────────────────────────────
function _mail_css(): string {
    return '
    <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;background:#f4f6f9;padding:32px 16px;}
    .wrap{max-width:580px;margin:0 auto;}
    .card{background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);}
    .header{background:#0d1b2a;padding:28px 36px;text-align:center;}
    .logo-text{font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-.3px;}
    .logo-text span{color:#f07b14;}
    .header-sub{font-size:12px;color:rgba(255,255,255,.4);margin-top:5px;letter-spacing:.5px;text-transform:uppercase;}
    .body{padding:36px 36px 28px;}
    .greeting{font-size:18px;font-weight:700;color:#0d1b2a;margin-bottom:8px;}
    .body-text{font-size:14.5px;color:#6b7280;line-height:1.75;margin-bottom:24px;}
    .highlight-box{border-radius:12px;padding:22px 24px;margin-bottom:24px;text-align:center;}
    .highlight-box .hl-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#9ca3af;margin-bottom:10px;}
    .highlight-box .hl-value{font-size:21px;font-weight:800;line-height:1.3;}
    .icon-row{display:flex;align-items:flex-start;gap:14px;padding:13px 0;border-bottom:1px solid #f3f4f6;}
    .icon-row:last-child{border-bottom:none;}
    .icon-circle{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;}
    .icon-row-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9ca3af;margin-bottom:3px;}
    .icon-row-value{font-size:14px;font-weight:600;color:#0d1b2a;}
    .steps-box{background:#f8f9fc;border-radius:12px;padding:20px 22px;margin-bottom:22px;}
    .steps-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9ca3af;margin-bottom:14px;}
    .step-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;}
    .step-item:last-child{margin-bottom:0;}
    .step-num{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;margin-top:1px;}
    .step-text{font-size:13.5px;color:#374151;line-height:1.55;padding-top:3px;}
    .cta-btn{display:block;text-align:center;padding:14px 32px;border-radius:50px;font-size:14px;font-weight:700;text-decoration:none;margin:22px 0;}
    .info-note{border-radius:10px;padding:14px 18px;font-size:13px;line-height:1.6;}
    .info-note strong{font-weight:700;}
    .otp-box{border-radius:14px;padding:24px 16px;text-align:center;margin-bottom:22px;}
    .otp-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#9ca3af;margin-bottom:12px;}
    .otp-code{font-size:48px;font-weight:900;letter-spacing:14px;font-family:"Courier New",monospace;}
    .otp-exp{font-size:12.5px;color:#9ca3af;margin-top:10px;}
    .warn-box{border-radius:10px;padding:13px 16px;font-size:13px;color:#b91c1c;line-height:1.6;}
    .footer{background:#f8f9fc;border-top:1px solid #f0f0f0;padding:18px 36px;text-align:center;}
    .footer p{font-size:12px;color:#9ca3af;line-height:1.8;}
    .footer a{color:#f07b14;text-decoration:none;}
    .divider{height:1px;background:#f0f0f0;margin:20px 0;}
    @media(max-width:600px){
        body{padding:16px 8px;}
        .body{padding:24px 20px 20px;}
        .otp-code{font-size:36px;letter-spacing:8px;}
    }
    </style>';
}

// ── Header + Footer blocks ─────────────────────────────────────
function _mail_header(string $subtitle): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . _mail_css() . '</head>
<body><div class="wrap"><div class="card">
<div class="header">
  <div class="logo-text"><span>Programmers</span> Lab</div>
  <div class="header-sub">' . htmlspecialchars($subtitle) . '</div>
</div>';
}

function _mail_footer(): string {
    return '
<div class="footer">
  <p>Programmers Lab &nbsp;&bull;&nbsp; Office No. 8, Mian Plaza, Chandni Chowk, Rawalpindi<br>
  <a href="tel:+923331912898">+92 333 1912898</a> &nbsp;&bull;&nbsp;
  <a href="mailto:infoprogrammerslabs@gmail.com">infoprogrammerslabs@gmail.com</a></p>
  <p style="margin-top:8px;color:#c4c9d4;">This is an automated message — please do not reply.</p>
</div>
</div></div></body></html>';
}

// ─────────────────────────────────────────────────────────────
// CORE SEND FUNCTION
// ─────────────────────────────────────────────────────────────
function send_mail(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
    if (!$textBody) $textBody = strip_tags($htmlBody);
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[Mailer] Failed to ' . $to . ': ' . $mail->ErrorInfo);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────
// 1. OTP EMAIL (Login verification — Admin & Teacher)
// ─────────────────────────────────────────────────────────────
function send_otp_email(string $to, string $name, string $otp, string $role = 'Admin'): bool {
    $subject = "Login Verification Code — Programmers Lab";
    $html = _mail_header("{$role} Portal — Security Verification") . '
<div class="body">
  <div class="greeting">Hello, ' . htmlspecialchars($name) . '</div>
  <p class="body-text">
    A sign-in was attempted on your Programmers Lab <strong>' . htmlspecialchars($role) . ' account</strong>.<br>
    Use the one-time code below to complete your login.
  </p>
  <div class="otp-box" style="background:#fff7ed;border:2px dashed #f07b14;">
    <div class="otp-label">One-Time Password</div>
    <div class="otp-code" style="color:#f07b14;">' . htmlspecialchars($otp) . '</div>
    <div class="otp-exp">Expires in <strong>10 minutes</strong></div>
  </div>
  <div class="warn-box" style="background:#fef2f2;border-left:4px solid #ef4444;">
    <strong>Never share this code</strong> with anyone — including Programmers Lab staff.<br>
    If you did not attempt to log in, please ignore this email.
  </div>
</div>' . _mail_footer();

    $text = "Hello {$name},\n\nYour {$role} login OTP: {$otp}\n\nExpires in 10 minutes. Never share this code.\n\n— Programmers Lab";
    return send_mail($to, $subject, $html, $text);
}

// ─────────────────────────────────────────────────────────────
// 2. PASSWORD RESET OTP
// ─────────────────────────────────────────────────────────────
function send_reset_otp_email(string $to, string $name, string $otp): bool {
    $subject = "Password Reset Code — Programmers Lab";
    $html = _mail_header("Password Reset Request") . '
<div class="body">
  <div class="greeting">Hello, ' . htmlspecialchars($name) . '</div>
  <p class="body-text">
    We received a request to <strong>reset your password</strong> for your Programmers Lab account.<br>
    Enter the code below on the reset page. If you did not request this, ignore this email.
  </p>
  <div class="otp-box" style="background:#f0f9ff;border:2px dashed #0094d9;">
    <div class="otp-label">Password Reset Code</div>
    <div class="otp-code" style="color:#0094d9;">' . htmlspecialchars($otp) . '</div>
    <div class="otp-exp">Expires in <strong>10 minutes</strong></div>
  </div>
  <div class="warn-box" style="background:#fef2f2;border-left:4px solid #ef4444;">
    <strong>Never share this code.</strong> If you did not request a password reset, your account is safe — just ignore this email.
  </div>
</div>' . _mail_footer();

    $text = "Hello {$name},\n\nYour password reset code: {$otp}\n\nExpires in 10 minutes.\n\n— Programmers Lab";
    return send_mail($to, $subject, $html, $text);
}

// ─────────────────────────────────────────────────────────────
// 3. ENROLLMENT CONFIRMATION (to student)
// ─────────────────────────────────────────────────────────────
function send_enrollment_confirmation(string $to, string $name, string $course): bool {
    $subject = "Enrollment Application Received — Programmers Lab";
    $html = _mail_header("Enrollment Confirmation") . '
<div class="body">
  <div class="greeting">Hello, ' . htmlspecialchars($name) . '</div>
  <p class="body-text">
    Thank you for applying to <strong>Programmers Lab</strong>. Your enrollment application has been
    received and is currently under review by our team.
  </p>
  <div class="highlight-box" style="background:#fff7ed;border:1.5px solid #f07b14;">
    <div class="hl-label">Applied Course</div>
    <div class="hl-value" style="color:#f07b14;">' . htmlspecialchars($course) . '</div>
  </div>
  <div class="steps-box">
    <div class="steps-title">What Happens Next</div>
    <div class="step-item">
      <div class="step-num" style="background:#f07b14;">1</div>
      <div class="step-text">Our team reviews your application within 1–2 business days</div>
    </div>
    <div class="step-item">
      <div class="step-num" style="background:#f07b14;">2</div>
      <div class="step-text">We will contact you on your registered phone or email</div>
    </div>
    <div class="step-item">
      <div class="step-num" style="background:#f07b14;">3</div>
      <div class="step-text">You will be guided about batch timings and fee payment</div>
    </div>
  </div>
  <a href="https://www.programmerslabs.com/user-profile.php" class="cta-btn" style="background:#f07b14;color:#fff;">
    Track Application Status
  </a>
  <div class="info-note" style="background:#f0fdf4;border-left:4px solid #10b981;">
    Application received successfully. For any queries, call us at
    <strong>+92 333 1912898</strong> or reply to this email.
  </div>
</div>' . _mail_footer();

    $text = "Hello {$name},\n\nYour enrollment application for '{$course}' has been received.\nWe will contact you within 1-2 business days.\n\nTrack: https://www.programmerslabs.com/user-profile.php\n\n— Programmers Lab";
    return send_mail($to, $subject, $html, $text);
}

// ─────────────────────────────────────────────────────────────
// 4. JOB APPLICATION CONFIRMATION (to applicant)
// ─────────────────────────────────────────────────────────────
function send_job_application_confirmation(string $to, string $name, string $position): bool {
    $subject = "Job Application Received — Programmers Lab";
    $html = _mail_header("Job Application Confirmation") . '
<div class="body">
  <div class="greeting">Hello, ' . htmlspecialchars($name) . '</div>
  <p class="body-text">
    Thank you for applying at <strong>Programmers Lab</strong>. Your application has been successfully
    submitted and is under review by our HR team.
  </p>
  <div class="highlight-box" style="background:#f0f9ff;border:1.5px solid #0094d9;">
    <div class="hl-label">Applied Position</div>
    <div class="hl-value" style="color:#0094d9;">' . htmlspecialchars($position) . '</div>
  </div>
  <div class="steps-box">
    <div class="steps-title">What Happens Next</div>
    <div class="step-item">
      <div class="step-num" style="background:#0094d9;">1</div>
      <div class="step-text">HR team reviews your application and CV</div>
    </div>
    <div class="step-item">
      <div class="step-num" style="background:#0094d9;">2</div>
      <div class="step-text">Shortlisted candidates are contacted for an interview</div>
    </div>
    <div class="step-item">
      <div class="step-num" style="background:#0094d9;">3</div>
      <div class="step-text">Final decision communicated within 5–7 business days</div>
    </div>
  </div>
  <a href="https://www.programmerslabs.com/user-profile.php" class="cta-btn" style="background:#0094d9;color:#fff;">
    Track Application Status
  </a>
  <div class="info-note" style="background:#f0fdf4;border-left:4px solid #10b981;">
    Application received successfully. For queries contact:
    <strong>+92 333 1912898</strong> or <strong>infoprogrammerslabs@gmail.com</strong>
  </div>
</div>' . _mail_footer();

    $text = "Hello {$name},\n\nYour application for '{$position}' at Programmers Lab has been received.\nShortlisted candidates will be contacted within 5-7 business days.\n\nTrack: https://www.programmerslabs.com/user-profile.php\n\n— Programmers Lab";
    return send_mail($to, $subject, $html, $text);
}

// ─────────────────────────────────────────────────────────────
// 5. ADMIN — New Enrollment Notification
// ─────────────────────────────────────────────────────────────
function send_admin_enrollment_notification(string $studentName, string $studentEmail, string $studentPhone, string $course): bool {
    $to      = 'muzamilqureshiarid@gmail.com';
    $subject = "New Enrollment Request — {$course}";
    $time    = date('d M Y, h:i A');

    $html = _mail_header("Admin Notification — New Enrollment") . '
<div class="body">
  <div class="greeting">New Enrollment Request</div>
  <p class="body-text">A new course enrollment has been submitted on Programmers Lab and is awaiting your review.</p>
  <div style="background:#f8f9fc;border-radius:12px;padding:6px 0;margin-bottom:22px;">
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(240,123,20,.12);color:#f07b14;">&#128100;</div>
      <div><div class="icon-row-label">Student Name</div><div class="icon-row-value">' . htmlspecialchars($studentName) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(0,148,217,.12);color:#0094d9;">&#9993;</div>
      <div><div class="icon-row-label">Email</div><div class="icon-row-value">' . htmlspecialchars($studentEmail) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(16,185,129,.12);color:#10b981;">&#128222;</div>
      <div><div class="icon-row-label">Phone</div><div class="icon-row-value">' . htmlspecialchars($studentPhone) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(139,92,246,.12);color:#8b5cf6;">&#127891;</div>
      <div><div class="icon-row-label">Course</div><div class="icon-row-value">' . htmlspecialchars($course) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;border-bottom:none;">
      <div class="icon-circle" style="background:rgba(245,158,11,.12);color:#f59e0b;">&#128336;</div>
      <div><div class="icon-row-label">Submitted At</div><div class="icon-row-value">' . htmlspecialchars($time) . '</div></div>
    </div>
  </div>
  <a href="https://www.programmerslabs.com/admin/enrollments.php" class="cta-btn" style="background:#f07b14;color:#fff;">
    Review in Admin Panel
  </a>
</div>' . _mail_footer();

    $text = "New Enrollment Request\n\nStudent: {$studentName}\nEmail: {$studentEmail}\nPhone: {$studentPhone}\nCourse: {$course}\nTime: {$time}\n\nReview: https://www.programmerslabs.com/admin/enrollments.php\n\n— Programmers Lab Admin";
    return send_mail($to, $subject, $html, $text);
}

// ─────────────────────────────────────────────────────────────
// 6. ADMIN — New Job Application Notification
// ─────────────────────────────────────────────────────────────
function send_admin_job_notification(string $applicantName, string $applicantEmail, string $applicantPhone, string $position): bool {
    $to      = 'muzamilqureshiarid@gmail.com';
    $subject = "New Job Application — {$position}";
    $time    = date('d M Y, h:i A');

    $html = _mail_header("Admin Notification — New Job Application") . '
<div class="body">
  <div class="greeting">New Job Application</div>
  <p class="body-text">A new job application has been submitted on Programmers Lab and is awaiting HR review.</p>
  <div style="background:#f8f9fc;border-radius:12px;padding:6px 0;margin-bottom:22px;">
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(240,123,20,.12);color:#f07b14;">&#128100;</div>
      <div><div class="icon-row-label">Applicant Name</div><div class="icon-row-value">' . htmlspecialchars($applicantName) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(0,148,217,.12);color:#0094d9;">&#9993;</div>
      <div><div class="icon-row-label">Email</div><div class="icon-row-value">' . htmlspecialchars($applicantEmail) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(16,185,129,.12);color:#10b981;">&#128222;</div>
      <div><div class="icon-row-label">Phone</div><div class="icon-row-value">' . htmlspecialchars($applicantPhone) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;">
      <div class="icon-circle" style="background:rgba(0,148,217,.12);color:#0094d9;">&#128188;</div>
      <div><div class="icon-row-label">Position Applied</div><div class="icon-row-value">' . htmlspecialchars($position) . '</div></div>
    </div>
    <div class="icon-row" style="padding:13px 18px;border-bottom:none;">
      <div class="icon-circle" style="background:rgba(245,158,11,.12);color:#f59e0b;">&#128336;</div>
      <div><div class="icon-row-label">Submitted At</div><div class="icon-row-value">' . htmlspecialchars($time) . '</div></div>
    </div>
  </div>
  <a href="https://www.programmerslabs.com/admin/job_applications.php" class="cta-btn" style="background:#0094d9;color:#fff;">
    Review in Admin Panel
  </a>
</div>' . _mail_footer();

    $text = "New Job Application\n\nApplicant: {$applicantName}\nEmail: {$applicantEmail}\nPhone: {$applicantPhone}\nPosition: {$position}\nTime: {$time}\n\nReview: https://www.programmerslabs.com/admin/job_applications.php\n\n— Programmers Lab Admin";
    return send_mail($to, $subject, $html, $text);
}
