    -- Run on Hostinger live database to fix courses table
    -- Add missing columns if they don't exist

    ALTER TABLE `courses`
        ADD COLUMN IF NOT EXISTS `price`             INT(11)      NOT NULL DEFAULT 0      AFTER `is_active`,
        ADD COLUMN IF NOT EXISTS `original_price`    INT(11)      NOT NULL DEFAULT 0      AFTER `price`,
        ADD COLUMN IF NOT EXISTS `installment_price` INT(11)      NOT NULL DEFAULT 0      AFTER `original_price`,
        ADD COLUMN IF NOT EXISTS `duration`          VARCHAR(50)  NOT NULL DEFAULT ''     AFTER `installment_price`,
        ADD COLUMN IF NOT EXISTS `sort_order`        INT(11)      NOT NULL DEFAULT 0      AFTER `duration`;

    -- Add MERN Stack if not exists
    INSERT IGNORE INTO `courses` (`name`, `category`, `is_active`, `price`, `original_price`, `installment_price`, `duration`, `sort_order`)
    VALUES ('MERN Stack', 'Web Development', 1, 0, 0, 0, '6 Months', 10);
