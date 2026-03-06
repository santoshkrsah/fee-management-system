-- ════════════════════════════════════════════════════════════════════════════════════════════
-- MASTER SQL IMPORT FILE - Complete Fee Management System
-- For Database: u638211070_demo_fms
-- ════════════════════════════════════════════════════════════════════════════════════════════
--
-- INSTRUCTIONS:
-- 1. Open phpMyAdmin and select database: u638211070_demo_fms
-- 2. Go to Import tab
-- 3. Choose this file: MASTER_IMPORT_ALL_TABLES.sql
-- 4. Click Import
-- 5. Wait for completion - it will create all tables and insert default data
--
-- This file contains:
-- ✓ Main database schema (tables: admin, settings, classes, sections, students, academic_sessions, etc.)
-- ✓ Fee Types management system (dynamic fee types)
-- ✓ Security features (audit logs, login attempts tracking, password reset tokens)
-- ✓ Student portal support (password fields for student login)
-- ✓ Additional student fields (WhatsApp number, Aadhar number)
-- ✓ System settings for email configuration
-- ✓ UPI Payment feature (optional - can be disabled)
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 1: MAIN DATABASE SCHEMA
-- ════════════════════════════════════════════════════════════════════════════════════════════

USE u638211070_demo_fms;

-- =============================================
-- Table: admin
-- Purpose: Store admin login credentials
-- =============================================
CREATE TABLE IF NOT EXISTS `admin` (
    `admin_id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `role` ENUM('sysadmin', 'admin', 'operator') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    `session_id` VARCHAR(100) NULL,
    `failed_login_attempts` INT DEFAULT 0,
    `account_locked_until` DATETIME NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: settings
-- Purpose: Store system settings (school name, logo, etc.)
-- =============================================
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: classes
-- Purpose: Store class information
-- =============================================
CREATE TABLE IF NOT EXISTS `classes` (
    `class_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_name` VARCHAR(50) NOT NULL,
    `class_numeric` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: sections
-- Purpose: Store section information
-- =============================================
CREATE TABLE IF NOT EXISTS `sections` (
    `section_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `section_name` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_class_section` (`class_id`, `section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: students
-- Purpose: Store student information
-- =============================================
CREATE TABLE IF NOT EXISTS `students` (
    `student_id` INT PRIMARY KEY AUTO_INCREMENT,
    `admission_no` VARCHAR(20) UNIQUE NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(100),
    `father_name` VARCHAR(100) NOT NULL,
    `mother_name` VARCHAR(100),
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `class_id` INT NOT NULL,
    `section_id` INT NOT NULL,
    `roll_number` VARCHAR(50) NOT NULL DEFAULT '',
    `contact_number` VARCHAR(15) NOT NULL,
    `email` VARCHAR(100),
    `password` VARCHAR(255) NULL,
    `password_changed` TINYINT(1) NOT NULL DEFAULT 0,
    `whatsapp_number` VARCHAR(15),
    `aadhar_number` VARCHAR(12) UNIQUE,
    `address` TEXT NOT NULL,
    `admission_date` DATE NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
    FOREIGN KEY (`section_id`) REFERENCES `sections`(`section_id`),
    INDEX `idx_admission_no` (`admission_no`),
    INDEX `idx_admission_status` (`admission_no`, `status`),
    INDEX `idx_aadhar_number` (`aadhar_number`),
    INDEX `idx_whatsapp_number` (`whatsapp_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: academic_sessions
-- Purpose: Store academic year/session data
-- =============================================
CREATE TABLE IF NOT EXISTS `academic_sessions` (
    `session_id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_name` VARCHAR(20) NOT NULL UNIQUE,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: fee_structure
-- Purpose: Define fee structure for each class
-- =============================================
CREATE TABLE IF NOT EXISTS `fee_structure` (
    `fee_structure_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `tuition_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `exam_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `library_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sports_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `lab_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `transport_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `other_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS (
        `tuition_fee` + `exam_fee` + `library_fee` + `sports_fee` +
        `lab_fee` + `transport_fee` + `other_charges`
    ) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
    UNIQUE KEY `unique_class_year` (`class_id`, `academic_year`),
    INDEX `idx_fee_structure_class_year` (`class_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: monthly_fee_structure
-- Purpose: Define month-wise fee structure (used when fee_mode = 'monthly')
-- =============================================
CREATE TABLE IF NOT EXISTS `monthly_fee_structure` (
    `monthly_fee_id` INT PRIMARY KEY AUTO_INCREMENT,
    `class_id` INT NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `fee_month` TINYINT NOT NULL COMMENT '1=April, 2=May, ..., 12=March',
    `month_label` VARCHAR(20) NOT NULL COMMENT 'Display name: April, May, etc.',
    `tuition_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `exam_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `library_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sports_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `lab_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `transport_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `other_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_fee` DECIMAL(10,2) GENERATED ALWAYS AS (
        `tuition_fee` + `exam_fee` + `library_fee` + `sports_fee` +
        `lab_fee` + `transport_fee` + `other_charges`
    ) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
    UNIQUE KEY `unique_class_year_month` (`class_id`, `academic_year`, `fee_month`),
    INDEX `idx_monthly_fee_class_year` (`class_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: fee_collection
-- Purpose: Record fee payments
-- =============================================
CREATE TABLE IF NOT EXISTS `fee_collection` (
    `payment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `receipt_no` VARCHAR(50) UNIQUE NOT NULL,
    `student_id` INT NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `fee_structure_id` INT NOT NULL,
    `payment_date` DATE NOT NULL,
    `tuition_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `exam_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `library_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sports_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `lab_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `transport_fee_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `other_charges_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `fine` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_paid` DECIMAL(10,2) GENERATED ALWAYS AS (
        `tuition_fee_paid` + `exam_fee_paid` + `library_fee_paid` +
        `sports_fee_paid` + `lab_fee_paid` + `transport_fee_paid` +
        `other_charges_paid` + `fine` - `discount`
    ) STORED,
    `payment_mode` ENUM('Cash', 'Card', 'UPI', 'Net Banking', 'Cheque') NOT NULL,
    `transaction_id` VARCHAR(100),
    `remarks` TEXT,
    `collected_by` INT NOT NULL,
    `fee_month` TINYINT NULL DEFAULT NULL COMMENT 'Only used in monthly mode: 1=April ... 12=March',
    `monthly_fee_structure_id` INT NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
    FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure`(`fee_structure_id`),
    FOREIGN KEY (`collected_by`) REFERENCES `admin`(`admin_id`),
    FOREIGN KEY (`monthly_fee_structure_id`) REFERENCES `monthly_fee_structure`(`monthly_fee_id`),
    INDEX `idx_payment_student` (`student_id`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_payment_receipt` (`receipt_no`),
    INDEX `idx_fee_collection_structure` (`fee_structure_id`),
    INDEX `idx_fee_collection_month` (`fee_month`),
    INDEX `idx_fee_collection_monthly_struct` (`monthly_fee_structure_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: subscription
-- Purpose: Store annual subscription tenure (single-row)
-- =============================================
CREATE TABLE IF NOT EXISTS `subscription` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `admin`(`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 2: FEE TYPES MANAGEMENT SYSTEM
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- Create fee_types table for dynamic fee type management
CREATE TABLE IF NOT EXISTS `fee_types` (
    `fee_type_id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(50) UNIQUE NOT NULL COMMENT 'System identifier: tuition_fee, exam_fee, etc.',
    `label` VARCHAR(100) NOT NULL COMMENT 'Display name: Tuition Fee, Exam Fee, etc.',
    `description` TEXT COMMENT 'Detailed description of fee type',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = deactivated (soft delete)',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Display order in forms and reports',
    `column_name` VARCHAR(50) NOT NULL COMMENT 'Database column: tuition_fee, exam_fee, etc.',
    `is_system_defined` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = hardcoded, 0 = custom',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_code_active` (`code`, `is_active`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed initial system-defined fee types
INSERT IGNORE INTO `fee_types`
(`code`, `label`, `description`, `column_name`, `is_system_defined`, `sort_order`)
VALUES
('tuition_fee', 'Tuition Fee', 'Core tuition charges for academic instruction', 'tuition_fee', 1, 1),
('exam_fee', 'Exam Fee', 'Examination fees including assessments and tests', 'exam_fee', 1, 2),
('library_fee', 'Library Fee', 'Library access and reference materials', 'library_fee', 1, 3),
('sports_fee', 'Sports Fee', 'Sports activities facilities and programs', 'sports_fee', 1, 4),
('lab_fee', 'Lab Fee', 'Laboratory access and equipment usage', 'lab_fee', 1, 5),
('transport_fee', 'Transport Fee', 'School transportation and conveyance', 'transport_fee', 1, 6),
('other_charges', 'Other Charges', 'Miscellaneous charges and fees', 'other_charges', 1, 7);

-- Create fee_types_audit table for tracking changes
CREATE TABLE IF NOT EXISTS `fee_types_audit` (
    `audit_id` INT PRIMARY KEY AUTO_INCREMENT,
    `fee_type_id` INT,
    `action` ENUM('created', 'updated', 'deleted') NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `changed_by` INT NOT NULL COMMENT 'admin.admin_id',
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fee_type_id` (`fee_type_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create fee_structure_audit table
CREATE TABLE IF NOT EXISTS `fee_structure_audit` (
    `audit_id` INT PRIMARY KEY AUTO_INCREMENT,
    `fee_structure_id` INT NOT NULL,
    `fee_type_id` INT NOT NULL,
    `amount` DECIMAL(10,2),
    `recorded_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fee_structure` (`fee_structure_id`),
    INDEX `idx_fee_type` (`fee_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 3: SECURITY FEATURES
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- Create audit_log table for activity tracking
CREATE TABLE IF NOT EXISTS `audit_log` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT DEFAULT NULL,
    `username` VARCHAR(50) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50) DEFAULT NULL,
    `record_id` INT DEFAULT NULL,
    `old_values` TEXT,
    `new_values` TEXT,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_attempts table for rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `attempt_id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT '0',
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `attempted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`attempt_id`),
    KEY `idx_username` (`username`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create password_reset_tokens table
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `token_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT '0',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `token` (`token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 4: SYSTEM SETTINGS
-- ════════════════════════════════════════════════════════════════════════════════════════════

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
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('smtp_enabled', '1', 'boolean', 'Enable/disable email sending'),
('smtp_host', 'smtp.hostinger.com', 'string', 'SMTP server hostname'),
('smtp_port', '465', 'int', 'SMTP server port'),
('smtp_encryption', 'ssl', 'string', 'SMTP encryption type (tls/ssl)'),
('smtp_username', 'info@santoshkr.in', 'string', 'SMTP username/email address'),
('smtp_password', 'your-app-password', 'string', 'SMTP password or app-specific password'),
('smtp_from_email', 'info@santoshkr.in', 'string', 'From email address'),
('smtp_from_name', 'Fee Management System', 'string', 'From name'),
('upi_enabled', '0', 'boolean', 'Enable/disable UPI QR code payment option for students'),
('upi_id', '', 'string', 'UPI ID (VPA) for receiving payments - stored encrypted'),
('upi_payee_name', '', 'string', 'Display name for UPI payments (school/institution name)'),
('site_icon', '', 'string', 'Path to site favicon/icon'),
('school_logo', '', 'string', 'Path to school logo');

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 5: UPI PAYMENTS (OPTIONAL - Only if you want UPI payment feature)
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- Create upi_payments table
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
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admin`(`admin_id`) ON DELETE SET NULL,
    FOREIGN KEY (`payment_id`) REFERENCES `fee_collection`(`payment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 6: SCHEMA VERSIONING
-- ════════════════════════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `schema_versions` (
    `version_id` INT PRIMARY KEY AUTO_INCREMENT,
    `version_name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'applied', 'failed') DEFAULT 'applied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Record this migration
INSERT IGNORE INTO `schema_versions`
(`version_name`, `description`, `status`)
VALUES
('master_complete_schema', 'Complete Fee Management System with all features', 'applied');

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 7: INSERT DEFAULT DATA
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- Insert default sysadmin (username: sysadmin, password: sysadmin123)
INSERT IGNORE INTO `admin` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('sysadmin', '$2y$12$JfM7JZGyrxFuqGUJZWV4muGAyDOc6uk5/r3HpWw6Cw3VjR19Lmatu', 'System Administrator', 'sysadmin@school.com', 'sysadmin');

-- Insert default admin (username: admin, password: admin123)
INSERT IGNORE INTO `admin` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$12$mEZJAmO08xdW8ENpg9K.quMKZZfetqMlM9QysJstEztfhT1gnASOe', 'Administrator', 'admin@school.com', 'admin');

-- Insert default settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'Fee Management System'),
('school_address', 'School Address Line 1, City, State - PIN'),
('school_phone', '+91 XXXXX XXXXX'),
('school_email', 'school@example.com'),
('school_logo', ''),
('site_icon', ''),
('fee_mode', 'annual');

-- Insert default academic session
INSERT IGNORE INTO `academic_sessions` (`session_name`, `start_date`, `end_date`, `is_active`) VALUES
('2026-2027', '2026-04-01', '2027-03-31', 1);

-- Insert default subscription (1 year from today)
INSERT IGNORE INTO `subscription` (`start_date`, `end_date`) VALUES
(CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR));

-- Insert classes
INSERT IGNORE INTO `classes` (`class_name`, `class_numeric`) VALUES
('Nursery', 0),
('LKG', 0),
('UKG', 0),
('Class 1', 1),
('Class 2', 2),
('Class 3', 3),
('Class 4', 4),
('Class 5', 5),
('Class 6', 6),
('Class 7', 7),
('Class 8', 8),
('Class 9', 9),
('Class 10', 10),
('Class 11', 11),
('Class 12', 12);

-- Insert sections for each class
INSERT IGNORE INTO `sections` (`class_id`, `section_name`)
SELECT `class_id`, 'A' FROM `classes`;

INSERT IGNORE INTO `sections` (`class_id`, `section_name`)
SELECT `class_id`, 'B' FROM `classes`;

INSERT IGNORE INTO `sections` (`class_id`, `section_name`)
SELECT `class_id`, 'C' FROM `classes` WHERE `class_numeric` >= 6;

-- Insert sample fee structure for academic year 2026-2027
INSERT IGNORE INTO `fee_structure` (`class_id`, `academic_year`, `tuition_fee`, `exam_fee`, `library_fee`, `sports_fee`, `lab_fee`, `transport_fee`, `other_charges`) VALUES
(1, '2026-2027', 5000.00, 500.00, 300.00, 200.00, 0.00, 1000.00, 100.00),
(2, '2026-2027', 5000.00, 500.00, 300.00, 200.00, 0.00, 1000.00, 100.00),
(3, '2026-2027', 5000.00, 500.00, 300.00, 200.00, 0.00, 1000.00, 100.00),
(4, '2026-2027', 6000.00, 600.00, 400.00, 300.00, 0.00, 1200.00, 100.00),
(5, '2026-2027', 6000.00, 600.00, 400.00, 300.00, 0.00, 1200.00, 100.00),
(6, '2026-2027', 7000.00, 700.00, 500.00, 400.00, 0.00, 1500.00, 100.00),
(7, '2026-2027', 7000.00, 700.00, 500.00, 400.00, 0.00, 1500.00, 100.00),
(8, '2026-2027', 8000.00, 800.00, 600.00, 500.00, 0.00, 1500.00, 100.00),
(9, '2026-2027', 9000.00, 900.00, 700.00, 600.00, 500.00, 2000.00, 200.00),
(10, '2026-2027', 9000.00, 900.00, 700.00, 600.00, 500.00, 2000.00, 200.00),
(11, '2026-2027', 10000.00, 1000.00, 800.00, 700.00, 1000.00, 2000.00, 300.00),
(12, '2026-2027', 12000.00, 1200.00, 1000.00, 800.00, 1500.00, 2500.00, 400.00),
(13, '2026-2027', 15000.00, 1500.00, 1200.00, 1000.00, 2000.00, 3000.00, 500.00),
(14, '2026-2027', 15000.00, 1500.00, 1200.00, 1000.00, 2000.00, 3000.00, 500.00),
(15, '2026-2027', 15000.00, 1500.00, 1200.00, 1000.00, 2000.00, 3000.00, 500.00);

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- STEP 8: CREATE VIEWS
-- ════════════════════════════════════════════════════════════════════════════════════════════

-- Create View for Student Fee Summary
CREATE OR REPLACE VIEW `vw_student_fee_summary` AS
SELECT
    s.`student_id`,
    s.`admission_no`,
    CONCAT(s.`first_name`, ' ', COALESCE(s.`last_name`, '')) AS `student_name`,
    s.`father_name`,
    c.`class_name`,
    sec.`section_name`,
    fs.`academic_year`,
    fs.`total_fee` AS `total_fee_amount`,
    COALESCE(SUM(`fc`.`total_paid`), 0) AS `total_paid_amount`,
    (fs.`total_fee` - COALESCE(SUM(`fc`.`total_paid`), 0)) AS `balance_amount`,
    CASE
        WHEN (fs.`total_fee` - COALESCE(SUM(`fc`.`total_paid`), 0)) <= 0 AND COALESCE(SUM(`fc`.`total_paid`), 0) > 0 THEN 'Paid'
        WHEN COALESCE(SUM(`fc`.`total_paid`), 0) > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END AS `payment_status`
FROM `students` s
JOIN `classes` c ON s.`class_id` = c.`class_id`
JOIN `sections` sec ON s.`section_id` = sec.`section_id`
LEFT JOIN `fee_structure` fs ON s.`class_id` = fs.`class_id` AND fs.`status` = 'active'
LEFT JOIN `fee_collection` fc ON s.`student_id` = fc.`student_id` AND fc.`academic_year` = fs.`academic_year`
WHERE s.`status` = 'active'
GROUP BY s.`student_id`, fs.`academic_year`;

-- ════════════════════════════════════════════════════════════════════════════════════════════
-- IMPORT COMPLETE!
-- ════════════════════════════════════════════════════════════════════════════════════════════
--
-- ✓ All tables created successfully
-- ✓ Default data inserted
-- ✓ All primary keys and foreign keys configured
-- ✓ All indexes created for performance
--
-- LOGIN CREDENTIALS:
-- SysAdmin - Username: sysadmin | Password: sysadmin123
-- Admin    - Username: admin    | Password: admin123
--
-- NOTES:
-- 1. Change admin passwords immediately after first login
-- 2. Update school information in Settings page
-- 3. Configure email settings if you want email notifications
-- 4. UPI feature is disabled by default (optional feature)
-- 5. Student portal requires setting student passwords
-- 6. Fee structure can be modified through the application
--
-- For more information, contact the administrator
-- ════════════════════════════════════════════════════════════════════════════════════════════
