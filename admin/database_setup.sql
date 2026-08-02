-- ============================================================
-- Programmers Lab — Full Database Setup (LOCAL XAMPP)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    cnic_number          VARCHAR(20)  DEFAULT '',
    course_interest      VARCHAR(255) NOT NULL,
    study_mode           VARCHAR(50),
    qualification_file   VARCHAR(255),
    passport_photo       VARCHAR(255),
    cnic_file            VARCHAR(255),
    previous_experience  TEXT,
    reason_for_joining   TEXT,
    enrollment_status    ENUM('pending','approved','rejected','in_progress','completed') DEFAULT 'pending',
    added_by             ENUM('online','admin') DEFAULT 'online',
    notes                TEXT,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Courses (admin managed)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255)  NOT NULL,
    category       VARCHAR(100)  NOT NULL,
    is_active      TINYINT(1)    DEFAULT 1,
    sort_order     INT           DEFAULT 0,
    price          INT           DEFAULT 0,
    original_price INT           DEFAULT 0,
    duration       VARCHAR(50)   DEFAULT '',
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default courses
INSERT INTO courses (name, category, is_active, sort_order, price, original_price, duration) VALUES
('Full Stack Web Development',  'Web Development',       1, 1,  22000, 28000, '3-4 Months'),
('Front-End Web Development',   'Web Development',       1, 2,  15000, 20000, '2-3 Months'),
('PHP & MySQL',                 'Web Development',       1, 3,  18000, 22000, '2-3 Months'),
('WordPress',                   'Web Development',       1, 4,  10000, 15000, '1-2 Months'),
('React Native',                'Mobile App Development',1, 5,  30000, 35000, '4-5 Months'),
('Flutter',                     'Mobile App Development',1, 6,  32000, 38000, '4-5 Months'),
('Android Development',         'Mobile App Development',1, 7,  25000, 30000, '3-4 Months'),
('iOS Development',             'Mobile App Development',1, 8,  28000, 35000, '3-4 Months'),
('Graphic Designing',           'Graphic Designing',     1, 9,  20000, 25000, '2-3 Months'),
('UI/UX Design',                'Graphic Designing',     1, 10, 22000, 28000, '2-3 Months'),
('Canva Course',                'Graphic Designing',     1, 11, 5000,  8000,  '1 Month'),
('Digital Marketing',           'Digital Marketing',     1, 12, 25000, 30000, '3-4 Months'),
('SEO Course',                  'Digital Marketing',     1, 13, 15000, 20000, '2-3 Months'),
('Social Media Marketing',      'Digital Marketing',     1, 14, 18000, 22000, '2-3 Months'),
('Python',                      'Programming',           1, 15, 15000, 20000, '2-3 Months'),
('C++ Course',                  'Programming',           1, 16, 12000, 15000, '2-3 Months'),
('Java Course',                 'Programming',           1, 17, 15000, 18000, '2-3 Months'),
('Database Management',         'Other',                 1, 18, 12000, 15000, '2 Months'),
('Freelancing',                 'Other',                 1, 19, 15000, 20000, '3-4 Months'),
('Video Editing',               'Other',                 1, 20, 8000,  12000, '1-2 Months'),
('MS Office / CIT',             'Other',                 1, 21, 8000,  12000, '1-2 Months');

-- ------------------------------------------------------------
-- Notices
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notices (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    message      TEXT         NOT NULL,
    badge        VARCHAR(100),
    badge_color  VARCHAR(20)  DEFAULT '#f07b14',
    btn_text     VARCHAR(100),
    btn_url      VARCHAR(500),
    is_active    TINYINT(1)   DEFAULT 1,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Job Applications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_applications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    phone      VARCHAR(20),
    position   VARCHAR(255),
    status     VARCHAR(50)  DEFAULT 'new',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Admin users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Login rate limiting
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Admin user banane ke liye:
-- localhost/pl/admin/create_admin.php kholo (phir delete karo)
-- ============================================================

