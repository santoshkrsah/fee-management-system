<?php
/**
 * AJAX: Get sections for a class
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    try {
        $class_id = (int)$_POST['class_id'];

        $db = getDB();
        $sections = $db->fetchAll(
            "SELECT section_id, section_name FROM sections WHERE class_id = :class_id AND status = 'active' ORDER BY section_name",
            ['class_id' => $class_id]
        );

        echo json_encode([
            'success' => true,
            'sections' => $sections
        ]);

    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading sections'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
