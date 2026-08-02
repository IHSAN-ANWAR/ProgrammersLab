-- ============================================================
-- Migration: enroll table mein new columns add karo
-- Hostinger phpMyAdmin → SQL tab → paste → Go
-- ============================================================

-- CNIC number (student khud fill kare ga form mein)
ALTER TABLE enroll
    ADD COLUMN IF NOT EXISTS cnic_number       VARCHAR(20)  DEFAULT '' AFTER gender,
    ADD COLUMN IF NOT EXISTS enrollment_status ENUM('pending','approved','rejected','in_progress','completed') DEFAULT 'pending' AFTER reason_for_joining,
    ADD COLUMN IF NOT EXISTS added_by          ENUM('online','admin') DEFAULT 'online' AFTER enrollment_status,
    ADD COLUMN IF NOT EXISTS notes             TEXT AFTER added_by;

-- Index for fast CNIC lookup (certificate check ke liye)
ALTER TABLE enroll ADD INDEX IF NOT EXISTS idx_cnic (cnic_number);
