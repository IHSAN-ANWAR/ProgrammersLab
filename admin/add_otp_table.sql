-- ============================================================
-- OTP Verifications Table
-- Run via phpMyAdmin or: mysql -u root -p programmerslab_db < admin/add_otp_table.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS otp_verifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('admin','teacher') NOT NULL,        -- who is logging in
    target_id   INT          NOT NULL,                   -- admin_users.id or teacher_users.id
    email       VARCHAR(255) NOT NULL,                   -- email OTP was sent to
    otp_code    VARCHAR(6)   NOT NULL,                   -- 6-digit code (hashed)
    expires_at  DATETIME     NOT NULL,                   -- valid for 10 minutes
    used        TINYINT(1)   DEFAULT 0,                  -- 1 = already used
    attempts    TINYINT      DEFAULT 0,                  -- wrong attempts counter
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target_type, target_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
