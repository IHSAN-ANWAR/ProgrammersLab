-- ============================================================
-- Programmers Lab — Hostinger Database Setup
-- phpMyAdmin mein u896451268_programmerslabs select karke
-- SQL tab mein yeh sara content paste karo → Go
-- ============================================================

-- Contact messages
CREATE TABLE IF NOT EXISTS contact (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    subject    VARCHAR(500) NOT NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollment form
CREATE TABLE IF NOT EXISTS enroll (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    full_name            VARCHAR(255) NOT NULL,
    father_name          VARCHAR(255),
    email                VARCHAR(255) NOT NULL,
    phone                VARCHAR(20)  NOT NULL,
    gender               VARCHAR(20),
    course_interest      VARCHAR(255) NOT NULL,
    study_mode           VARCHAR(50),
    qualification_file   VARCHAR(255),
    passport_photo       VARCHAR(255),
    cnic_file            VARCHAR(255),
    previous_experience  TEXT,
    reason_for_joining   TEXT,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity / audit log
CREATE TABLE IF NOT EXISTS admin_logs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(100),
    action         VARCHAR(255) NOT NULL,
    details        TEXT,
    ip_address     VARCHAR(45),
    user_agent     VARCHAR(500),
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
