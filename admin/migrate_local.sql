-- ============================================================
-- Local XAMPP Migration
-- phpMyAdmin > programmerslab_db > SQL tab > paste > Go
-- ============================================================

USE programmerslab_db;

-- 1. enroll table update
ALTER TABLE enroll
    MODIFY COLUMN full_name   VARCHAR(255) NOT NULL,
    MODIFY COLUMN father_name VARCHAR(255),
    MODIFY COLUMN email       VARCHAR(255) NOT NULL,
    MODIFY COLUMN gender      VARCHAR(20);

-- Add missing columns (ignore error if already exist)
ALTER TABLE enroll
    ADD COLUMN cnic_number       VARCHAR(20)  DEFAULT '' AFTER gender,
    ADD COLUMN enrollment_status ENUM('pending','approved','rejected','in_progress','completed') DEFAULT 'pending' AFTER reason_for_joining,
    ADD COLUMN added_by          ENUM('online','admin') DEFAULT 'online' AFTER enrollment_status,
    ADD COLUMN notes             TEXT AFTER added_by;

-- Copy old 'status' values to 'enrollment_status' if status column exists
UPDATE enroll SET enrollment_status = status WHERE status IS NOT NULL AND status != '';

-- Add index for CNIC lookup
ALTER TABLE enroll ADD INDEX idx_cnic (cnic_number);

-- 2. courses table -- add missing columns if not exist
ALTER TABLE courses
    ADD COLUMN IF NOT EXISTS price          INT DEFAULT 0 AFTER sort_order,
    ADD COLUMN IF NOT EXISTS original_price INT DEFAULT 0 AFTER price,
    ADD COLUMN IF NOT EXISTS duration       VARCHAR(50) DEFAULT '' AFTER original_price;

-- 3. job_applications -- add missing columns
ALTER TABLE job_applications
    ADD COLUMN IF NOT EXISTS cover_letter TEXT AFTER position,
    ADD COLUMN IF NOT EXISTS cv_file      VARCHAR(255) AFTER cover_letter;

-- 4. job_applications -- add resume_path column
ALTER TABLE job_applications
    ADD COLUMN IF NOT EXISTS resume_path VARCHAR(500) AFTER portfolio_url;

-- 5. job_openings table (dynamic career page cards)
CREATE TABLE IF NOT EXISTS job_openings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255)  NOT NULL,
    job_type     VARCHAR(100)  NOT NULL,
    badge_type   ENUM('internship','fulltime','parttime') DEFAULT 'internship',
    duration     VARCHAR(100)  DEFAULT '',
    location     VARCHAR(150)  DEFAULT 'Onsite — Rawalpindi',
    salary_label VARCHAR(150)  DEFAULT '',
    description  TEXT,
    is_featured  TINYINT(1)    DEFAULT 0,
    is_active    TINYINT(1)    DEFAULT 1,
    sort_order   INT           DEFAULT 0,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default job openings (same as old static cards)
INSERT INTO job_openings (title, job_type, badge_type, duration, location, salary_label, description, is_featured, sort_order) VALUES
('Web Development Intern',        'Internship', 'internship', '3 Months',            'Onsite — Rawalpindi', 'Paid Internship',    'Work on live web projects using HTML, CSS, JavaScript and PHP. Learn from senior developers on real client work.', 0, 1),
('Mobile App Development Intern', 'Internship', 'internship', '3 Months',            'Onsite — Rawalpindi', 'Paid Internship',    'Build mobile applications using React Native, Flutter and Android. Hands-on experience with real apps.', 0, 2),
('Senior Mobile App Developer',   'Full Time',  'fulltime',   '2+ Years Experience', 'Onsite — Rawalpindi', 'Market Competitive', 'Lead mobile app development projects using React Native and Flutter. Work with Firebase and REST APIs.', 1, 3),
('Graphic Design Intern',         'Internship', 'internship', '3 Months',            'Onsite — Rawalpindi', 'Paid Internship',    'Create visual content using Photoshop, Illustrator and Canva. Work on branding and marketing materials.', 0, 4),
('Digital Marketing Intern',      'Internship', 'internship', '3 Months',            'Onsite — Rawalpindi', 'Paid Internship',    'Manage SEO, social media campaigns and Google Ads. Gain real experience in digital marketing.', 0, 5),
('IT Instructor',                 'Full Time',  'fulltime',   '1+ Year Experience',  'Onsite — Rawalpindi', 'Market Competitive', 'Teach IT courses to students. Strong communication skills and knowledge of any IT field required.', 0, 6);

-- 4. job_openings table (career page positions)
CREATE TABLE IF NOT EXISTS job_openings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    job_type     VARCHAR(50)  DEFAULT 'Full Time',
    badge_type   ENUM('internship','fulltime','parttime') DEFAULT 'fulltime',
    duration     VARCHAR(100),
    location     VARCHAR(255) DEFAULT 'Rawalpindi',
    salary_label VARCHAR(100),
    description  TEXT,
    is_featured  TINYINT(1)   DEFAULT 0,
    is_active    TINYINT(1)   DEFAULT 1,
    sort_order   INT          DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. notices table -- add image column
ALTER TABLE notices
    ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT '' AFTER btn_url;

-- 6. Site users table (student login/register)
CREATE TABLE IF NOT EXISTS site_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified   TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
