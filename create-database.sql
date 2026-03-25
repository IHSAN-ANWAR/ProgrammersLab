-- Create database and table for contact messages
CREATE DATABASE IF NOT EXISTS programmerslab_db;
USE programmerslab_db;

CREATE TABLE IF NOT EXISTS contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create table for courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(150) NOT NULL,
    course_slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    duration VARCHAR(50),
    fee DECIMAL(10, 2),
    category VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for course enrollments
CREATE TABLE IF NOT EXISTS enroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    course_interest VARCHAR(100) NOT NULL,
    study_mode ENUM('online', 'onsite') NOT NULL,
    qualification_file VARCHAR(255),
    passport_photo VARCHAR(255),
    cnic_file VARCHAR(255),
    previous_experience TEXT,
    reason_for_joining TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert all courses from your institute
INSERT INTO courses (course_name, course_slug, category) VALUES
('Front End Development', 'front-end-development', 'Web Development'),
('Full Stack Development', 'full-stack-development', 'Web Development'),
('JavaScript Development', 'javascript-development', 'Programming'),
('React Js Development', 'react-js-development', 'Web Development'),
('React Native Development', 'react-native-development', 'Mobile Development'),
('MySQL Development', 'mysql-development', 'Database'),
('Mobile App Development', 'mobile-app-development', 'Mobile Development'),
('Python Development', 'python-development', 'Programming'),
('PHP-MySQL Development', 'php-mysql-development', 'Web Development'),
('ASP.NET Development', 'asp-net-development', 'Web Development'),
('Flutter Development', 'flutter-development', 'Mobile Development'),
('JAVA Development', 'java-development', 'Programming'),
('Quality Assurance', 'quality-assurance', 'Testing'),
('C++', 'c-plus-plus', 'Programming'),
('C#', 'c-sharp', 'Programming'),
('SEO', 'seo', 'Digital Marketing'),
('Digital Marketing', 'digital-marketing', 'Digital Marketing'),
('Social Media Marketing', 'social-media-marketing', 'Digital Marketing'),
('UI/UX Designer', 'ui-ux-designer', 'Design'),
('Basic IT', 'basic-it', 'IT Fundamentals'),
('MS Office', 'ms-office', 'IT Fundamentals'),
('Graphic Designing', 'graphic-designing', 'Design'),
('Canva Designing', 'canva-designing', 'Design'),
('WordPress', 'wordpress', 'Web Development'),
('Video Editing', 'video-editing', 'Design'),
('SQL', 'sql', 'Database'),
('Android App Development', 'android-app-development', 'Mobile Development'),
('iOS Development', 'ios-development', 'Mobile Development'),
('Freelancing', 'freelancing', 'Career Development');