-- ============================================================
-- Programmers Lab — Full Database Setup
-- Run once via phpMyAdmin or: mysql -u root -p < admin/database_setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS programmerslab_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE programmerslab_db;

-- ------------------------------------------------------------
-- Contact messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    subject    VARCHAR(500) NOT NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Full enrollment form
-- ------------------------------------------------------------
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
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Admin users (hashed passwords)
-- DO NOT insert credentials here.
-- Run admin/setup_database.php in the browser — it generates
-- a real bcrypt hash via PHP and inserts the admin user safely.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Login rate limiting
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Activity / audit log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_logs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(100),
    action         VARCHAR(255) NOT NULL,
    details        TEXT,
    ip_address     VARCHAR(45),
    user_agent     VARCHAR(500),
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- NOTE: Admin user create karne ke liye:
-- localhost/pl/admin/create_admin.php use karo (phir delete karo)
-- ============================================================

