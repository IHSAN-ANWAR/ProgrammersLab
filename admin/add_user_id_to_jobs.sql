-- Add user_id to job_applications so logged-in users can track their applications
-- Run in phpMyAdmin → u896451268_programmerslab → SQL tab

ALTER TABLE job_applications
    ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL AFTER id,
    ADD INDEX IF NOT EXISTS idx_user_id (user_id);
