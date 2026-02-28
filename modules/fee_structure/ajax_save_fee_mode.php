<?php
/**
 * AJAX: Save fee mode setting (annual/monthly)
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Only sysadmin can change fee mode
if (!isSysAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Only System Administrators can change the fee mode.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fee_mode'])) {
    try {
        $fee_mode = sanitize($_POST['fee_mode']);
        if (!in_array($fee_mode, ['annual', 'monthly'])) {
            $fee_mode = 'annual';
        }

        $db = getDB();

        // Get current fee mode for audit log
        $oldFeeMode = getFeeMode();

        $db->query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            ['key' => 'fee_mode', 'val' => $fee_mode]
        );

        // Log the fee mode change
        logAudit(
            getAdminId(),
            'FEE_MODE_CHANGED',
            'settings',
            null,
            ['fee_mode' => $oldFeeMode],
            ['fee_mode' => $fee_mode]
        );

        echo json_encode([
            'success' => true,
            'fee_mode' => $fee_mode,
            'message' => 'Fee mode changed to ' . $fee_mode
        ]);

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error saving fee mode'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
