-- =============================================
-- Student Fee Collection and Management System
-- Database Schema (Hostinger Compatible)
-- =============================================
-- NOTE: This version works with existing Hostinger database
-- Removed DROP DATABASE and CREATE DATABASE statements
-- Uses the correct Hostinger database name

USE u638211070_demo_fms;

-- =============================================
-- Table: admin
-- Purpose: Store admin login credentials
-- =============================================
CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('sysadmin', 'admin', 'operator') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: settings
-- Purpose: Store system settings (school name, logo, etc.)
-- =============================================
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: classes
-- Purpose: Store class information
-- =============================================
CREATE TABLE classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    class_numeric INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: sections
-- Purpose: Store section information
-- =============================================
CREATE TABLE sections (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    section_name VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_section (class_id, section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: students
-- Purpose: Store student information
-- =============================================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    admission_no VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    roll_number VARCHAR(20),
    contact_number VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    admission_date DATE NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: academic_sessions
-- Purpose: Store academic year/session data
-- =============================================
CREATE TABLE academic_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: fee_structure
-- Purpose: Define fee structure for each class
-- =============================================
CREATE TABLE fee_structure (
    fee_structure_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    exam_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    library_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sports_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    transport_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee + exam_fee + library_fee + sports_fee +
        lab_fee + transport_fee + other_charges
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    UNIQUE KEY unique_class_year (class_id, academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: monthly_fee_structure
-- Purpose: Define month-wise fee structure (used when fee_mode = 'monthly')
-- =============================================
CREATE TABLE monthly_fee_structure (
    monthly_fee_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    fee_month TINYINT NOT NULL COMMENT '1=April, 2=May, ..., 12=March',
    month_label VARCHAR(20) NOT NULL COMMENT 'Display name: April, May, etc.',
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    exam_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    library_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sports_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    transport_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee + exam_fee + library_fee + sports_fee +
        lab_fee + transport_fee + other_charges
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    UNIQUE KEY unique_class_year_month (class_id, academic_year, fee_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: fee_collection
-- Purpose: Record fee payments
-- =============================================
CREATE TABLE fee_collection (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_no VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    fee_structure_id INT NOT NULL,
    payment_date DATE NOT NULL,
    tuition_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    exam_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    library_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sports_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    lab_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    transport_fee_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_charges_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fine DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_paid DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee_paid + exam_fee_paid + library_fee_paid +
        sports_fee_paid + lab_fee_paid + transport_fee_paid +
        other_charges_paid + fine - discount
    ) STORED,
    payment_mode ENUM('Cash', 'Card', 'UPI', 'Net Banking', 'Cheque') NOT NULL,
    transaction_id VARCHAR(100),
    remarks TEXT,
    collected_by INT NOT NULL,
    fee_month TINYINT NULL DEFAULT NULL COMMENT 'Only used in monthly mode: 1=April ... 12=March',
    monthly_fee_structure_id INT NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(fee_structure_id),
    FOREIGN KEY (collected_by) REFERENCES admin(admin_id),
    FOREIGN KEY (monthly_fee_structure_id) REFERENCES monthly_fee_structure(monthly_fee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Insert Default Data
-- =============================================

-- Insert default sysadmin (username: sysadmin, password: sysadmin123)
INSERT INTO admin (username, password, full_name, email, role) VALUES
('sysadmin', '$2y$12$JfM7JZGyrxFuqGUJZWV4muGAyDOc6uk5/r3HpWw6Cw3VjR19Lmatu', 'System Administrator', 'sysadmin@school.com', 'sysadmin');

-- Insert default admin (username: admin, password: admin123)
-- Password is hashed using PHP password_hash()
INSERT INTO admin (username, password, full_name, email, role) VALUES
('admin', '$2y$12$mEZJAmO08xdW8ENpg9K.quMKZZfetqMlM9QysJstEztfhT1gnASOe', 'Administrator', 'admin@school.com', 'admin');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('school_name', 'Fee Management System'),
('school_address', 'School Address Line 1, City, State - PIN'),
('school_phone', '+91 XXXXX XXXXX'),
('school_email', 'school@example.com'),
('school_logo', ''),
('fee_mode', 'annual');

-- Insert default academic session
INSERT INTO academic_sessions (session_name, start_date, end_date, is_active) VALUES
('2026-2027', '2026-04-01', '2027-03-31', 1);

-- Insert classes
INSERT INTO classes (class_name, class_numeric) VALUES
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
INSERT INTO sections (class_id, section_name)
SELECT class_id, 'A' FROM classes;
INSERT INTO sections (class_id, section_name)
SELECT class_id, 'B' FROM classes;
INSERT INTO sections (class_id, section_name)
SELECT class_id, 'C' FROM classes WHERE class_numeric >= 6;

-- Insert sample fee structure for academic year 2026-2027
INSERT INTO fee_structure (class_id, academic_year, tuition_fee, exam_fee, library_fee, sports_fee, lab_fee, transport_fee, other_charges) VALUES
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

-- =============================================
-- Table: subscription
-- Purpose: Store annual subscription tenure (single-row)
-- =============================================
CREATE TABLE subscription (
    id INT PRIMARY KEY AUTO_INCREMENT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin(admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default subscription (1 year from today)
INSERT INTO subscription (start_date, end_date) VALUES (CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR));

-- =============================================
-- Create Indexes for Better Performance
-- =============================================
CREATE INDEX idx_student_admission ON students(admission_no);
CREATE INDEX idx_student_class ON students(class_id, section_id);
CREATE INDEX idx_payment_student ON fee_collection(student_id);
CREATE INDEX idx_payment_date ON fee_collection(payment_date);
CREATE INDEX idx_payment_receipt ON fee_collection(receipt_no);
CREATE INDEX idx_student_status ON students(status);
CREATE INDEX idx_student_class_status ON students(class_id, section_id, status);
CREATE INDEX idx_fee_collection_structure ON fee_collection(fee_structure_id);
CREATE INDEX idx_students_academic_year ON students(academic_year);
CREATE INDEX idx_fee_structure_class_year ON fee_structure(class_id, academic_year);
CREATE INDEX idx_monthly_fee_class_year ON monthly_fee_structure(class_id, academic_year);
CREATE INDEX idx_fee_collection_month ON fee_collection(fee_month);
CREATE INDEX idx_fee_collection_monthly_struct ON fee_collection(monthly_fee_structure_id);

-- =============================================
-- Create View for Student Fee Summary
-- =============================================
CREATE VIEW vw_student_fee_summary AS
SELECT
    s.student_id,
    s.admission_no,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.father_name,
    c.class_name,
    sec.section_name,
    fs.academic_year,
    fs.total_fee AS total_fee_amount,
    COALESCE(SUM(fc.total_paid), 0) AS total_paid_amount,
    (fs.total_fee - COALESCE(SUM(fc.total_paid), 0)) AS balance_amount,
    CASE
        WHEN (fs.total_fee - COALESCE(SUM(fc.total_paid), 0)) <= 0 THEN 'Paid'
        WHEN COALESCE(SUM(fc.total_paid), 0) > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END AS payment_status
FROM students s
JOIN classes c ON s.class_id = c.class_id
JOIN sections sec ON s.section_id = sec.section_id
LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.status = 'active'
LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = fs.academic_year
WHERE s.status = 'active'
GROUP BY s.student_id, fs.academic_year;

-- =============================================
-- Create View for Monthly Student Fee Summary
-- =============================================
CREATE VIEW vw_student_monthly_fee_summary AS
SELECT
    s.student_id,
    s.admission_no,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.father_name,
    c.class_name,
    sec.section_name,
    mfs.academic_year,
    mfs.fee_month,
    mfs.month_label,
    mfs.total_fee AS monthly_fee_amount,
    COALESCE(SUM(fc.total_paid), 0) AS monthly_paid_amount,
    (mfs.total_fee - COALESCE(SUM(fc.total_paid), 0)) AS monthly_balance,
    CASE
        WHEN (mfs.total_fee - COALESCE(SUM(fc.total_paid), 0)) <= 0 THEN 'Paid'
        WHEN COALESCE(SUM(fc.total_paid), 0) > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END AS payment_status
FROM students s
JOIN classes c ON s.class_id = c.class_id
JOIN sections sec ON s.section_id = sec.section_id
LEFT JOIN monthly_fee_structure mfs ON s.class_id = mfs.class_id AND mfs.status = 'active'
LEFT JOIN fee_collection fc ON s.student_id = fc.student_id
    AND fc.fee_month = mfs.fee_month
    AND fc.academic_year = mfs.academic_year
WHERE s.status = 'active'
GROUP BY s.student_id, mfs.academic_year, mfs.fee_month;
