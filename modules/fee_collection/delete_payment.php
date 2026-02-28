<?php
/**
 * Delete Fee Payment - Sysadmin only
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin']);

$payment_id = (int)($_GET['id'] ?? 0);

if ($payment_id <= 0) {
    redirectWithMessage('view_payments.php', 'error', 'Invalid payment ID.');
}

try {
    $db = getDB();

    // Verify payment exists
    $payment = $db->fetchOne(
        "SELECT payment_id, receipt_no FROM fee_collection WHERE payment_id = :id",
        ['id' => $payment_id]
    );

    if (!$payment) {
        redirectWithMessage('view_payments.php', 'error', 'Payment record not found.');
    }

    // Delete the payment record
    $db->query(
        "DELETE FROM fee_collection WHERE payment_id = :id",
        ['id' => $payment_id]
    );

    redirectWithMessage('view_payments.php', 'success', 'Payment record (Receipt: ' . $payment['receipt_no'] . ') deleted successfully.');

} catch(Exception $e) {
    error_log($e->getMessage());
    redirectWithMessage('view_payments.php', 'error', 'Error deleting payment. Please try again.');
}
?>
