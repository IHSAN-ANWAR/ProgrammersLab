-- Fix enroll table by adding missing columns
-- Copy and paste this into phpMyAdmin SQL tab

USE programmerslab_db;

-- Add missing columns to enroll table
ALTER TABLE enroll ADD COLUMN full_name VARCHAR(100) NOT NULL;
ALTER TABLE enroll ADD COLUMN father_name VARCHAR(100) NOT NULL;
ALTER TABLE enroll ADD COLUMN email VARCHAR(150) NOT NULL;
ALTER TABLE enroll ADD COLUMN phone VARCHAR(20) NOT NULL;
ALTER TABLE enroll ADD COLUMN gender ENUM('male', 'female', 'other') NOT NULL;
ALTER TABLE enroll ADD COLUMN course_interest VARCHAR(100) NOT NULL;
ALTER TABLE enroll ADD COLUMN study_mode ENUM('online', 'onsite') NOT NULL;
ALTER TABLE enroll ADD COLUMN qualification_file VARCHAR(255);
ALTER TABLE enroll ADD COLUMN passport_photo VARCHAR(255);
ALTER TABLE enroll ADD COLUMN cnic_file VARCHAR(255);
ALTER TABLE enroll ADD COLUMN previous_experience TEXT;
ALTER TABLE enroll ADD COLUMN reason_for_joining TEXT;
ALTER TABLE enroll ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending';
ALTER TABLE enroll ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Note: Some columns might already exist, MySQL will skip those with errors
-- That's normal and expected