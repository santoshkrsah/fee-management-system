-- =============================================
-- UPI Payment Feature - Database Migration
-- Run this SQL on the fee_management_system database
-- =============================================

-- 1. Create upi_payments table
CREATE TABLE IF NOT EXISTS `upi_payments` (
    `upi_payment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `utr_number` VARCHAR(50) NOT NULL,
    `screenshot_path` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `rejection_reason` TEXT NULL DEFAULT NULL,
    `fee_month` TINYINT NULL DEFAULT NULL COMMENT 'Only for monthly mode: 1=April...12=March',
    `payment_id` INT NULL DEFAULT NULL COMMENT 'FK to fee_collection after approval',
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_by` INT NULL DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_utr_number` (`utr_number`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_status` (`status`),
    KEY `idx_academic_year` (`academic_year`),
    KEY `idx_submitted_at` (`submitted_at`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
    FOREIGN KEY (`reviewed_by`) REFERENCES `admin`(`admin_id`),
    FOREIGN KEY (`payment_id`) REFERENCES `fee_collection`(`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insert UPI settings into system_settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('upi_enabled', '0', 'boolean', 'Enable/disable UPI QR code payment option for students'),
('upi_id', '', 'string', 'UPI ID (VPA) for receiving payments - stored encrypted'),
('upi_payee_name', '', 'string', 'Display name for UPI payments (school/institution name)')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
