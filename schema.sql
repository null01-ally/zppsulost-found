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

-- Additive migration; old reports remain in Other with their original contact fallback.
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='category');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE items ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT ''Other''', 'SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='contact_email');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE items ADD COLUMN contact_email VARCHAR(100) DEFAULT NULL', 'SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='contact_phone');
SET @ddl = IF(@column_exists=0, 'ALTER TABLE items ADD COLUMN contact_phone VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
CREATE TABLE IF NOT EXISTS recovery_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 item_id INT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(100) NOT NULL,
 proof TEXT NOT NULL,
 status ENUM('pending','resolved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_recovery_item (item_id,status),
 CONSTRAINT fk_recovery_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS request_limits (
 bucket CHAR(64) PRIMARY KEY, window_start DATETIME NOT NULL, hits INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supporting ownership evidence is private and never served from public uploads/.
CREATE TABLE IF NOT EXISTS recovery_attachments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id INT UNSIGNED NOT NULL,
 stored_name VARCHAR(100) NOT NULL,
 original_name VARCHAR(150) NOT NULL,
 mime_type VARCHAR(50) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_attachment_request FOREIGN KEY (request_id) REFERENCES recovery_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account gateway; legacy reporter records stay separate from login accounts.
CREATE TABLE IF NOT EXISTS accounts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL UNIQUE,
 email VARCHAR(100) NOT NULL UNIQUE,
 student_id VARCHAR(40) NULL UNIQUE,
 phone VARCHAR(20) NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('student','admin') NOT NULL DEFAULT 'student',
 active TINYINT(1) NOT NULL DEFAULT 1,
 session_version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='moderation_status');
SET @ddl=IF(@present=0,'ALTER TABLE items ADD COLUMN moderation_status ENUM(''pending'',''approved'',''rejected'',''archived'') NOT NULL DEFAULT ''approved''','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='reviewed_by');
SET @ddl=IF(@present=0,'ALTER TABLE items ADD COLUMN reviewed_by INT UNSIGNED NULL','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items' AND COLUMN_NAME='reviewed_at');
SET @ddl=IF(@present=0,'ALTER TABLE items ADD COLUMN reviewed_at DATETIME NULL','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recovery_requests' AND COLUMN_NAME='account_id');
SET @ddl=IF(@present=0,'ALTER TABLE recovery_requests ADD COLUMN account_id INT UNSIGNED NULL','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recovery_requests' AND COLUMN_NAME='reviewed_by');
SET @ddl=IF(@present=0,'ALTER TABLE recovery_requests ADD COLUMN reviewed_by INT UNSIGNED NULL','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
SET @present=(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recovery_requests' AND COLUMN_NAME='reviewed_at');
SET @ddl=IF(@present=0,'ALTER TABLE recovery_requests ADD COLUMN reviewed_at DATETIME NULL','SELECT 1');
PREPARE migration FROM @ddl; EXECUTE migration; DEALLOCATE PREPARE migration;
ALTER TABLE items ALTER COLUMN moderation_status SET DEFAULT 'pending';
