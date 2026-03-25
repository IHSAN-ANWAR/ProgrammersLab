-- FINAL FIX: Recreate enroll table with correct structure
-- Copy and paste this ENTIRE script into phpMyAdmin SQL tab

USE programmerslab_db;

-- Step 1: Drop the old table (WARNING: This deletes existing data!)
DROP TABLE IF EXISTS enroll;

-- Step 2: Create the correct table structure
CREATE TABLE enroll (
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

-- Done! Now your enrollment form will work perfectly.