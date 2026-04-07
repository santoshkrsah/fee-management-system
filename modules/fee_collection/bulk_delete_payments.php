<?php
/**
 * Bulk Delete Payments - Sysadmin only
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('view_payments.php', 'error', 'Invalid request.');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWithMessage('view_payments.php', 'error', 'Invalid security token. Please try again.');
}

$ids = $_POST['payment_ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    redirectWithMessage('view_payments.php', 'error', 'No payments selected.');
}

// Sanitize IDs to integers
$ids = array_map('intval', $ids);
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    redirectWithMessage('view_payments.php', 'error', 'No valid payments selected.');
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

    // Clear references in upi_payments before deleting (handles cases where
    // ON DELETE SET NULL FK constraint may not exist in older DB setups)
    $db->query(
        "UPDATE upi_payments SET payment_id = NULL WHERE payment_id IN ($in)",
        $params
    );

    // Hard delete payment records
    $db->query(
        "DELETE FROM fee_collection WHERE payment_id IN ($in)",
        $params
    );

    $count = count($ids);
    redirectWithMessage('view_payments.php', 'success', $count . ' payment record(s) deleted successfully!');

} catch (Exception $e) {
    error_log($e->getMessage());
    redirectWithMessage('view_payments.php', 'error', 'Error deleting payments. Please try again.');
}
?>
