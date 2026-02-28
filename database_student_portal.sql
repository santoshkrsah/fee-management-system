-- =============================================
-- Student Portal - Database Migration
-- Run this script before deploying the student portal
-- =============================================

-- Add password and password_changed columns to students table
ALTER TABLE students
    ADD COLUMN password VARCHAR(255) NULL AFTER email,
    ADD COLUMN password_changed TINYINT(1) NOT NULL DEFAULT 0 AFTER password;

-- Index for fast login lookups
CREATE INDEX idx_student_admission_status ON students(admission_no, status);
