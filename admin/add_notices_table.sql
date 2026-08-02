-- Notices Table
-- phpMyAdmin → u896451268_programmerslab → SQL → paste → Go

CREATE TABLE IF NOT EXISTS notices (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    message      TEXT         NOT NULL,
    badge        VARCHAR(100),
    badge_color  VARCHAR(20)  DEFAULT '#f07b14',
    btn_text     VARCHAR(100),
    btn_url      VARCHAR(500),
    is_active    TINYINT(1)   DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
