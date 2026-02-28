<?php
/**
 * AJAX: Get monthly fee structure for a class
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    try {
        $class_id = (int)$_POST['class_id'];
        $activeYear = getSelectedSession();

        $db = getDB();
        $months = $db->fetchAll(
            "SELECT * FROM monthly_fee_structure
             WHERE class_id = :class_id AND academic_year = :year AND status = 'active'
             ORDER BY fee_month",
            ['class_id' => $class_id, 'year' => $activeYear]
        );

        echo json_encode([
            'success' => true,
            'months' => $months
        ]);

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error loading monthly fee structure'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
