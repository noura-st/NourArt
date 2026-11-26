-- NourArt - database schema
--
-- Import this in phpMyAdmin (XAMPP) or run it with the mysql client.
-- The PHP handlers in this repository expect exactly this table.

CREATE DATABASE IF NOT EXISTS nourart
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nourart;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    -- 190 keeps the unique index inside utf8mb4's 767-byte key limit on older
    -- MySQL builds.
    email      VARCHAR(190) NOT NULL UNIQUE,
    -- password_hash() output is 60 chars today; 255 leaves room if the default
    -- algorithm changes.
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
