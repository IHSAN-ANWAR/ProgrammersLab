<?php
// ============================================================
// OTP Helper — Programmers Lab
// Handles generate → store → send → verify lifecycle
// ============================================================

require_once __DIR__ . '/mailer.php';

define('OTP_EXPIRY_MINUTES', 10);
define('OTP_MAX_ATTEMPTS',   5);    // wrong guesses before invalidation
define('OTP_RESEND_COOLDOWN', 60);  // seconds before resend allowed

/**
 * Auto-create the otp_verifications table if it doesn't exist.
 */
function otp_ensure_table(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS otp_verifications (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            target_type ENUM('admin','teacher','user') NOT NULL,
            target_id   INT          NOT NULL,
            email       VARCHAR(255) NOT NULL,
            otp_hash    VARCHAR(255) NOT NULL,
            expires_at  DATETIME     NOT NULL,
            used        TINYINT(1)   DEFAULT 0,
            attempts    TINYINT      DEFAULT 0,
            ip_address  VARCHAR(45),
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_target  (target_type, target_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Generate, store, and email a fresh OTP.
 * $email can be a string OR an array of emails (all receive the same OTP).
 * Returns ['success'=>bool, 'message'=>string, 'otp_id'=>int]
 */
function otp_generate_and_send(
    mysqli $conn,
    string $targetType,  // 'admin' | 'teacher' | 'user'
    int    $targetId,
    $email,              // string or array of strings
    string $name,
    string $role = 'Admin'
): array {
    otp_ensure_table($conn);

    // Normalise to array
    $emails     = is_array($email) ? $email : [$email];
    $primaryEmail = $emails[0]; // stored in DB (first one)

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // --- Resend cooldown: block if a valid unused OTP sent < 60s ago ---
    $cooldown = date('Y-m-d H:i:s', time() - OTP_RESEND_COOLDOWN);
    $chk = $conn->prepare(
        "SELECT id FROM otp_verifications
         WHERE target_type=? AND target_id=? AND used=0
           AND expires_at > NOW() AND created_at > ?
         ORDER BY id DESC LIMIT 1"
    );
    $chk->bind_param('sis', $targetType, $targetId, $cooldown);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        return ['success' => false, 'message' => 'Please wait 60 seconds before requesting a new OTP.', 'otp_id' => 0];
    }
    $chk->close();

    // --- Invalidate all previous unused OTPs for this user ---
    $inv = $conn->prepare("UPDATE otp_verifications SET used=1 WHERE target_type=? AND target_id=? AND used=0");
    $inv->bind_param('si', $targetType, $targetId);
    $inv->execute();
    $inv->close();

    // --- Generate 6-digit OTP ---
    $plain   = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashed  = password_hash($plain, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + OTP_EXPIRY_MINUTES * 60);

    // --- Store (use primary email) ---
    $ins = $conn->prepare(
        "INSERT INTO otp_verifications (target_type, target_id, email, otp_hash, expires_at, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param('sissss', $targetType, $targetId, $primaryEmail, $hashed, $expires, $ip);
    $ins->execute();
    $otpId = (int)$conn->insert_id;
    $ins->close();

    // --- Send to ALL emails ---
    $allSent = true;
    foreach ($emails as $toEmail) {
        $sent = send_otp_email($toEmail, $name, $plain, $role);
        if (!$sent) $allSent = false;
    }

    if (!$allSent) {
        // Mark used so user can retry
        $conn->query("UPDATE otp_verifications SET used=1 WHERE id={$otpId}");
        return ['success' => false, 'message' => 'Failed to send OTP email. Check SMTP config.', 'otp_id' => 0];
    }

    return ['success' => true, 'message' => 'OTP sent successfully.', 'otp_id' => $otpId];
}

/**
 * Verify a submitted OTP code.
 * Returns ['success'=>bool, 'message'=>string]
 */
function otp_verify(
    mysqli $conn,
    string $targetType,
    int    $targetId,
    string $submittedCode
): array {
    otp_ensure_table($conn);

    // Fetch the latest valid unused OTP
    $stmt = $conn->prepare(
        "SELECT id, otp_hash, attempts FROM otp_verifications
         WHERE target_type=? AND target_id=? AND used=0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('si', $targetType, $targetId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'message' => 'OTP expired or not found. Please request a new one.'];
    }

    // Too many wrong attempts
    if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) {
        $conn->query("UPDATE otp_verifications SET used=1 WHERE id={$row['id']}");
        return ['success' => false, 'message' => 'Too many wrong attempts. Please request a new OTP.'];
    }

    if (!password_verify($submittedCode, $row['otp_hash'])) {
        // Increment attempts
        $conn->query("UPDATE otp_verifications SET attempts=attempts+1 WHERE id={$row['id']}");
        $left = OTP_MAX_ATTEMPTS - ((int)$row['attempts'] + 1);
        return ['success' => false, 'message' => "Incorrect OTP. {$left} attempt(s) remaining."];
    }

    // Mark as used
    $conn->query("UPDATE otp_verifications SET used=1 WHERE id={$row['id']}");
    return ['success' => true, 'message' => 'OTP verified.'];
}
