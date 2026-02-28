<?php
/**
 * AJAX: Get fee structure for a class
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    try {
        $class_id = (int)$_POST['class_id'];
        $activeYear = getSelectedSession();

        $db = getDB();
        $feeStructure = $db->fetchOne(
            "SELECT * FROM fee_structure WHERE class_id = :class_id AND academic_year = :year AND status = 'active'",
            ['class_id' => $class_id, 'year' => $activeYear]
        );

        if ($feeStructure) {
            echo json_encode([
                'success' => true,
                'fee_structure' => $feeStructure
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fee structure not found for this class'
            ]);
        }

    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading fee structure'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
