-- ════════════════════════════════════════════════════════════════════════════
-- Critical Security Enhancements - Hostinger Compatible (SAFE VERSION)
-- Only adds columns if they don't exist
-- ════════════════════════════════════════════════════════════════════════════

USE u638211070_demo_fms;

-- Table to track all user activity (Audit Trail)
CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for all system activities';

-- Table to track login attempts for rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `user_agent` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track login attempts for rate limiting';

-- Table for password reset tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `token_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Password reset tokens';

-- Add session_id column if it doesn't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = 'u638211070_demo_fms' AND table_name = 'admin' AND column_name = 'session_id');

SET @sql := CASE WHEN @col_exists = 0 THEN
  'ALTER TABLE `admin` ADD COLUMN `session_id` VARCHAR(100) NULL AFTER `last_login`'
ELSE
  'SELECT "session_id column already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add failed_login_attempts column if it doesn't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = 'u638211070_demo_fms' AND table_name = 'admin' AND column_name = 'failed_login_attempts');

SET @sql := CASE WHEN @col_exists = 0 THEN
  'ALTER TABLE `admin` ADD COLUMN `failed_login_attempts` INT DEFAULT 0 AFTER `session_id`'
ELSE
  'SELECT "failed_login_attempts column already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add account_locked_until column if it doesn't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = 'u638211070_demo_fms' AND table_name = 'admin' AND column_name = 'account_locked_until');

SET @sql := CASE WHEN @col_exists = 0 THEN
  'ALTER TABLE `admin` ADD COLUMN `account_locked_until` DATETIME NULL AFTER `failed_login_attempts`'
ELSE
  'SELECT "account_locked_until column already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ════════════════════════════════════════════════════════════════════════════
-- Security enhancement complete!
-- ════════════════════════════════════════════════════════════════════════════
