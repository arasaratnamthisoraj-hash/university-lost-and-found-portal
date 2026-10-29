-- One-time additive migration for the existing pre-account database.
-- Select the application's database before importing. Back up privately first.
-- DDL auto-commits: do not run this file a second time after a successful import.
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
    bucket_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_rate_limits_expiry (expires_at)
) ENGINE=InnoDB;

ALTER TABLE items
    ADD COLUMN owner_id INT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN contact_visible TINYINT(1) NOT NULL DEFAULT 0,
    ADD CONSTRAINT fk_items_owner FOREIGN KEY (owner_id) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT;
-- Existing reports retain their contents, have no owner and stay locked/private.
