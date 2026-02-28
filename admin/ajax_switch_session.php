<?php
/**
 * AJAX: Switch selected academic session
 * Stores the selected session in PHP session
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_name'])) {
    $sessionName = sanitize($_POST['session_name']);

    // Validate that the session exists
    try {
        $db = getDB();
        $exists = $db->fetchOne(
            "SELECT session_id FROM academic_sessions WHERE session_name = :name",
            ['name' => $sessionName]
        );

        if ($exists) {
            setSelectedSession($sessionName);
            echo json_encode(['success' => true, 'session' => $sessionName]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error switching session']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
