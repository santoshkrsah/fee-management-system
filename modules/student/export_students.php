<?php
/**
 * Export Selected Students as CSV
 * Sysadmin only
 */
ob_start();

require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('view_students.php', 'error', 'Invalid request.');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWithMessage('view_students.php', 'error', 'Invalid security token.');
}

$ids = $_POST['student_ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    redirectWithMessage('view_students.php', 'error', 'No students selected for export.');
}

$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    redirectWithMessage('view_students.php', 'error', 'No valid students selected.');
}

try {
    $db = getDB();

    // Build placeholders
    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $key = 'id' . $i;
        $placeholders[] = ':' . $key;
        $params[$key] = $id;
    }
    $in = implode(',', $placeholders);

    $students = $db->fetchAll(
        "SELECT s.admission_no, s.first_name, s.last_name, s.father_name, s.mother_name,
                s.date_of_birth, s.gender, c.class_name, sec.section_name, s.roll_number,
                s.contact_number, s.email, s.address, s.admission_date
         FROM students s
         JOIN classes c ON s.class_id = c.class_id
         JOIN sections sec ON s.section_id = sec.section_id
         WHERE s.student_id IN ($in)
         ORDER BY c.class_numeric, sec.section_name, s.roll_number, s.first_name",
        $params
    );

    // Output CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d_His') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header row
    fputcsv($output, [
        'admission_no', 'first_name', 'last_name', 'father_name', 'mother_name',
        'date_of_birth', 'gender', 'class_name', 'section_name', 'roll_number',
        'contact_number', 'email', 'address', 'admission_date'
    ]);

    // Data rows
    foreach ($students as $s) {
        fputcsv($output, [
            $s['admission_no'],
            $s['first_name'],
            $s['last_name'],
            $s['father_name'],
            $s['mother_name'] ?? '',
            $s['date_of_birth'],
            $s['gender'],
            $s['class_name'],
            $s['section_name'],
            $s['roll_number'] ?? '',
            $s['contact_number'],
            $s['email'] ?? '',
            $s['address'],
            $s['admission_date']
        ]);
    }

    fclose($output);

} catch (Exception $e) {
    error_log($e->getMessage());
    redirectWithMessage('view_students.php', 'error', 'Error exporting students.');
}
?>
