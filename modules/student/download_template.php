<?php
/**
 * Download Student Upload Template (CSV)
 * Sysadmin only
 */
@ob_start();
@ini_set('display_errors', 0);
error_reporting(0);

require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

// Discard ALL output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student_upload_template.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fwrite($output, "\xEF\xBB\xBF");

// Write header row
fputcsv($output, [
    'admission_no',
    'first_name',
    'last_name',
    'father_name',
    'mother_name',
    'date_of_birth',
    'gender',
    'class_name',
    'section_name',
    'roll_number',
    'contact_number',
    'email',
    'address',
    'admission_date'
]);

// Write two sample rows
fputcsv($output, [
    'ADM2026001',
    'Rahul',
    'Sharma',
    'Rajesh Sharma',
    'Priya Sharma',
    '2015-05-10',
    'Male',
    'Class 1',
    'A',
    '1',
    '9876543210',
    'rahul@example.com',
    '123 Main Street, City',
    '2026-04-01'
]);

fputcsv($output, [
    'ADM2026002',
    'Anita',
    'Verma',
    'Sunil Verma',
    'Meena Verma',
    '2014-08-22',
    'Female',
    'Class 2',
    'B',
    '5',
    '9123456780',
    '',
    '456 Park Avenue, Town',
    '2026-04-01'
]);

fclose($output);
exit;
