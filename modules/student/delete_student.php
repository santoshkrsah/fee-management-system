<?php
/**
 * Delete Student
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

$student_id = (int)($_GET['id'] ?? 0);

if ($student_id <= 0) {
    redirectWithMessage('view_students.php', 'error', 'Invalid student ID.');
}

try {
    $db = getDB();

    // Check if student exists
    $student = $db->fetchOne(
        "SELECT * FROM students WHERE student_id = :id",
        ['id' => $student_id]
    );

    if (!$student) {
        redirectWithMessage('view_students.php', 'error', 'Student not found.');
    }

    // Soft delete - set status to inactive
    $db->query(
        "UPDATE students SET status = 'inactive' WHERE student_id = :id",
        ['id' => $student_id]
    );

    redirectWithMessage('view_students.php', 'success', 'Student deleted successfully!');

} catch(Exception $e) {
    error_log($e->getMessage());
    redirectWithMessage('view_students.php', 'error', 'Error deleting student. Please try again.');
}
?>
