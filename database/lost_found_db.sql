-- Schema reconstructed from the application's PHP queries.
-- Import with phpMyAdmin or the MySQL client. Existing tables are preserved.
CREATE DATABASE IF NOT EXISTS lost_found_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_users_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('lost', 'found', 'claimed') NOT NULL DEFAULT 'lost',
    location VARCHAR(255) NOT NULL,
    date_reported DATE NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_items_reporter (reporter_id),
    KEY idx_items_created (created_at),
    KEY idx_items_status_created (status, created_at),
    CONSTRAINT fk_items_reporter FOREIGN KEY (reporter_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS claims (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id INT UNSIGNED NOT NULL,
    claimer_id INT UNSIGNED NOT NULL,
    claim_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_claims_item (item_id),
    KEY idx_claims_claimer (claimer_id),
    CONSTRAINT fk_claims_item FOREIGN KEY (item_id)
        REFERENCES items (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_claims_claimer FOREIGN KEY (claimer_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The create page uses reporter ID 1 as its fallback.
INSERT INTO users (id, name, email)
SELECT 1, 'Student', 'student@zppsu.edu.ph'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 1);
