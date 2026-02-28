<?php
/**
 * Bulk Delete Students - Sysadmin only
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('view_students.php', 'error', 'Invalid request.');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWithMessage('view_students.php', 'error', 'Invalid security token. Please try again.');
}

$ids = $_POST['student_ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    redirectWithMessage('view_students.php', 'error', 'No students selected.');
}

// Sanitize IDs to integers
$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    redirectWithMessage('view_students.php', 'error', 'No valid students selected.');
}

try {
    $db = getDB();

    // Build placeholders for IN clause
    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $key = 'id' . $i;
        $placeholders[] = ':' . $key;
        $params[$key] = $id;
    }
    $in = implode(',', $placeholders);

    // Soft delete - set status to inactive
    $db->query(
        "UPDATE students SET status = 'inactive' WHERE student_id IN ($in)",
        $params
    );

    $count = count($ids);
    redirectWithMessage('view_students.php', 'success', $count . ' student(s) deleted successfully!');

} catch (Exception $e) {
    error_log($e->getMessage());
    redirectWithMessage('view_students.php', 'error', 'Error deleting students. Please try again.');
}
?>
