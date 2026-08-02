-- ============================================================
-- Installment Price Migration
-- Hostinger phpMyAdmin > SQL tab mein paste karo > Go
-- ============================================================

-- courses table mein installment_price column add karo
ALTER TABLE courses
    ADD COLUMN IF NOT EXISTS installment_price INT DEFAULT 0 AFTER price;

-- Default installment prices (optional — admin se bhi set kar sakte hain)
UPDATE courses SET installment_price = ROUND(price * 1.15) WHERE price > 0 AND installment_price = 0;
