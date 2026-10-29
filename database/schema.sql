CREATE DATABASE IF NOT EXISTS university_lost_found CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_lost_found;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_rate_limits_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(160) NOT NULL,
    event_date DATE NOT NULL,
    reporter_name VARCHAR(100) NOT NULL,
    contact VARCHAR(160) NOT NULL,
    owner_id INT UNSIGNED NULL DEFAULT NULL,
    contact_visible TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_items_owner FOREIGN KEY (owner_id) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_items_date (event_date, id),
    INDEX idx_items_type (type)
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name) VALUES
('Electronics'), ('Books and stationery'), ('Clothing'), ('Keys'), ('Personal belongings'), ('Other');
