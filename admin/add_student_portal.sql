-- ============================================================
-- Student Portal Migration
-- Hostinger phpMyAdmin > SQL tab mein paste karo > Go
-- ============================================================

-- 1. enroll table mein cnic_number text column add karo
ALTER TABLE enroll
    ADD COLUMN IF NOT EXISTS cnic_number   VARCHAR(15)  DEFAULT '' AFTER cnic_file,
    ADD COLUMN IF NOT EXISTS enrollment_status ENUM('pending','active','inactive') DEFAULT 'pending' AFTER cnic_number;

-- 2. Index for fast lookup by cnic_number
CREATE INDEX IF NOT EXISTS idx_cnic ON enroll (cnic_number);

-- 3. (Optional) Agar purane enrollments mein manually CNIC add karna ho:
-- UPDATE enroll SET cnic_number = '12345-6789012-3' WHERE id = 1;
