-- Migration: Add courses table
-- Run this on your database (local or Hostinger) to enable course management.

CREATE TABLE IF NOT EXISTS courses (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    category   VARCHAR(100) NOT NULL,
    is_active  TINYINT(1)   DEFAULT 1,
    sort_order INT          DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default courses
INSERT INTO courses (name, category, is_active, sort_order) VALUES
('Full Stack Web Development', 'Web Development', 1, 1),
('Front-End Web Development', 'Web Development', 1, 2),
('PHP & MySQL', 'Web Development', 1, 3),
('WordPress', 'Web Development', 1, 4),
('React Native', 'Mobile App Development', 1, 5),
('Flutter', 'Mobile App Development', 1, 6),
('Android Development', 'Mobile App Development', 1, 7),
('iOS Development', 'Mobile App Development', 1, 8),
('Graphic Designing', 'Graphic Designing', 1, 9),
('UI/UX Design', 'Graphic Designing', 1, 10),
('Canva Course', 'Graphic Designing', 1, 11),
('Digital Marketing', 'Digital Marketing', 1, 12),
('SEO Course', 'Digital Marketing', 1, 13),
('Social Media Marketing', 'Digital Marketing', 1, 14),
('Python', 'Programming', 1, 15),
('C++ Course', 'Programming', 1, 16),
('Java Course', 'Programming', 1, 17),
('Database Management', 'Other', 1, 18),
('Freelancing', 'Other', 1, 19),
('Video Editing', 'Other', 1, 20),
('MS Office / CIT', 'Other', 1, 21);
