-- Job Applications Table
-- phpMyAdmin → u896451268_programmerslab → SQL tab → paste → Go

CREATE TABLE IF NOT EXISTS job_applications (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    full_name           VARCHAR(255) NOT NULL,
    father_name         VARCHAR(255),
    email               VARCHAR(255) NOT NULL,
    phone               VARCHAR(20)  NOT NULL,
    city                VARCHAR(100),
    gender              VARCHAR(30),
    position            VARCHAR(255) NOT NULL,
    job_type            VARCHAR(50),
    expected_salary     VARCHAR(100),
    degree              VARCHAR(100) NOT NULL,
    field_of_study      VARCHAR(150),
    institute           VARCHAR(255),
    graduation_year     VARCHAR(10),
    experience          VARCHAR(50),
    last_job_title      VARCHAR(150),
    skills              VARCHAR(500),
    previous_experience TEXT,
    cover_letter        TEXT,
    portfolio_url       VARCHAR(500),
    resume_path         VARCHAR(500),
    status              ENUM('new','reviewed','shortlisted','rejected') DEFAULT 'new',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If table already exists, add resume_path column
ALTER TABLE job_applications
    ADD COLUMN IF NOT EXISTS resume_path VARCHAR(500) AFTER portfolio_url;

-- Job Openings Table (dynamic career page cards)
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
