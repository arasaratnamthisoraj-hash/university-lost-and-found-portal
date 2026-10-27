CREATE DATABASE IF NOT EXISTS university_lost_found CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_lost_found;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_items_date (event_date, id),
    INDEX idx_items_type (type)
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name) VALUES
('Electronics'), ('Books and stationery'), ('Clothing'), ('Keys'), ('Personal belongings'), ('Other');
