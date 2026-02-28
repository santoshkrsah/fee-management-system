-- ════════════════════════════════════════════════════════════════════════════
-- Student Portal - Database Migration (Hostinger Compatible)
-- Allows students to login and check their fee status
-- SAFE VERSION: Only adds columns if they don't exist
-- ════════════════════════════════════════════════════════════════════════════

USE u638211070_demo_fms;

-- Add password column if it doesn't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = 'u638211070_demo_fms' AND table_name = 'students' AND column_name = 'password');

SET @sql := CASE WHEN @col_exists = 0 THEN
  'ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL AFTER email'
ELSE
  'SELECT "password column already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add password_changed column if it doesn't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = 'u638211070_demo_fms' AND table_name = 'students' AND column_name = 'password_changed');

SET @sql := CASE WHEN @col_exists = 0 THEN
  'ALTER TABLE students ADD COLUMN password_changed TINYINT(1) NOT NULL DEFAULT 0 AFTER password'
ELSE
  'SELECT "password_changed column already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create index for fast login lookups (if it doesn't exist)
CREATE INDEX IF NOT EXISTS idx_student_admission_status ON students(admission_no, status);

-- ════════════════════════════════════════════════════════════════════════════
-- Student portal setup complete!
-- Students can now login with admission_no and password
-- ════════════════════════════════════════════════════════════════════════════
