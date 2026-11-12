USE lost_found_db;

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
