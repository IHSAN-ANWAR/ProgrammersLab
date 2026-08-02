-- Add user_id to enroll table so enrolled students link to their account
ALTER TABLE enroll ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL AFTER id;
ALTER TABLE enroll ADD INDEX IF NOT EXISTS idx_user_id (user_id);
