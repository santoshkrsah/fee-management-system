-- ================================================================
-- Fee Types Management System
-- ================================================================
-- This migration adds dynamic fee type management without breaking
-- existing data or schema. All changes are backward-compatible.
-- ================================================================

-- 1. Create fee_types configuration table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dynamic fee type configuration and management';

-- 2. Seed initial system-defined fee types
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

-- 3. Create fee type audit log for tracking changes
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for fee type modifications';

-- 4. Create relationship table for tracking which fee types are used in which structures
CREATE TABLE IF NOT EXISTS `fee_structure_audit` (
  `audit_id` INT PRIMARY KEY AUTO_INCREMENT,
  `fee_structure_id` INT NOT NULL,
  `fee_type_id` INT NOT NULL,
  `amount` DECIMAL(10,2),
  `recorded_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fee_structure` (`fee_structure_id`),
  INDEX `idx_fee_type` (`fee_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track fee type usage in structures for validation';

-- 5. Schema versioning table
CREATE TABLE IF NOT EXISTS `schema_versions` (
  `version_id` INT PRIMARY KEY AUTO_INCREMENT,
  `version_name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'applied', 'failed') DEFAULT 'applied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Database schema version tracking';

-- Record this migration
INSERT IGNORE INTO `schema_versions`
(`version_name`, `description`, `status`)
VALUES
('fee_types_management', 'Dynamic fee type configuration system', 'applied');

-- ================================================================
-- End of Fee Types Management System Migration
-- ================================================================
