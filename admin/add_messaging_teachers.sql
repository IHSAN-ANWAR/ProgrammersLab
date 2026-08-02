-- ============================================================
-- Programmers Lab — Messaging & Teachers Migration
-- phpMyAdmin > SQL tab mein paste karo > Execute
-- Ya phir: mysql -u root -p programmerslab_db < admin/add_messaging_teachers.sql
-- ============================================================

USE programmerslab_db;

-- ------------------------------------------------------------
-- 1. TEACHER ACCOUNTS
--    Admin yahan se teacher create karta hai (credentials deta hai)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teacher_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email         VARCHAR(255) DEFAULT '',
    phone         VARCHAR(20)  DEFAULT '',
    subject       VARCHAR(255) DEFAULT '',   -- e.g. "Web Development"
    bio           TEXT,
    is_active     TINYINT(1)   DEFAULT 1,
    created_by    VARCHAR(100) DEFAULT 'admin',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. TEACHER ↔ ENROLLMENT ASSIGNMENTS
--    Admin decide karta hai konsa teacher konse enrollment ka instructor hai
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teacher_assignments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id    INT NOT NULL,
    enrollment_id INT NOT NULL,
    assigned_by   VARCHAR(100) DEFAULT 'admin',
    assigned_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (teacher_id, enrollment_id),
    FOREIGN KEY (teacher_id)    REFERENCES teacher_users(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enroll(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. ADMIN BROADCAST MESSAGES
--    Admin kisi ek student ko ya sab ko message bhejta hai
--    target_type: 'all' | 'course' | 'student'
--    target_id:   enrollment_id (agar student specific) ya NULL
--    target_course: course name (agar course specific) ya NULL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS broadcast_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    body           TEXT         NOT NULL,
    msg_type       ENUM('info','warning','success','urgent') DEFAULT 'info',
    target_type    ENUM('all','course','student')            DEFAULT 'all',
    target_id      INT          DEFAULT NULL,    -- enrollment_id (student specific)
    target_course  VARCHAR(255) DEFAULT NULL,    -- course name (course specific)
    sent_by        VARCHAR(100) DEFAULT 'admin',
    is_active      TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Read receipts (student ne padha ya nahi)
CREATE TABLE IF NOT EXISTS broadcast_reads (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    message_id     INT NOT NULL,
    user_id        INT NOT NULL,    -- site_users.id
    read_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES broadcast_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. TEACHER ↔ STUDENT CHAT MESSAGES
--    sender_type: 'teacher' | 'student'
--    msg_type:    'text' | 'link' | 'video' | 'file' | 'question'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT NOT NULL,           -- teacher_assignments.id
    sender_type     ENUM('teacher','student') NOT NULL,
    sender_id       INT NOT NULL,           -- teacher_users.id OR site_users.id
    msg_type        ENUM('text','link','video','file','question') DEFAULT 'text',
    body            TEXT NOT NULL,
    attachment_url  VARCHAR(500) DEFAULT NULL,   -- link ya file URL
    is_read         TINYINT(1)   DEFAULT 0,      -- 0=unread, 1=read
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for fast queries
CREATE INDEX IF NOT EXISTS idx_chat_assignment ON chat_messages(assignment_id);
CREATE INDEX IF NOT EXISTS idx_chat_created    ON chat_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_broadcast_type  ON broadcast_messages(target_type, is_active);
CREATE INDEX IF NOT EXISTS idx_ta_teacher      ON teacher_assignments(teacher_id);
CREATE INDEX IF NOT EXISTS idx_ta_enrollment   ON teacher_assignments(enrollment_id);

-- ============================================================
-- DONE. Tables created:
--   teacher_users        — Teacher accounts
--   teacher_assignments  — Teacher-Student connections
--   broadcast_messages   — Admin → Student messages
--   broadcast_reads      — Read receipts
--   chat_messages        — Teacher ↔ Student chat
-- ============================================================
