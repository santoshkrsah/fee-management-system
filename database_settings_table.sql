-- ═══════════════════════════════════════════════════════════════════════
-- System Settings Table for Email Configuration
-- ═══════════════════════════════════════════════════════════════════════

-- Create system_settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'int', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255),
    `updated_by` INT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`),
    FOREIGN KEY (`updated_by`) REFERENCES `admin`(`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default email settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('smtp_enabled', '1', 'boolean', 'Enable/disable email sending'),
('smtp_host', 'smtp.gmail.com', 'string', 'SMTP server hostname'),
('smtp_port', '587', 'int', 'SMTP server port'),
('smtp_encryption', 'tls', 'string', 'SMTP encryption type (tls/ssl)'),
('smtp_username', 'your-email@gmail.com', 'string', 'SMTP username/email address'),
('smtp_password', 'your-app-password', 'string', 'SMTP password or app-specific password'),
('smtp_from_email', 'noreply@school.com', 'string', 'From email address'),
('smtp_from_name', 'Fee Management System', 'string', 'From name')
ON DUPLICATE KEY UPDATE setting_key = setting_key; -- Don't update if already exists

-- ═══════════════════════════════════════════════════════════════════════
-- Run this SQL file to create the settings table
-- Then you can configure email settings from the web interface
-- ═══════════════════════════════════════════════════════════════════════
