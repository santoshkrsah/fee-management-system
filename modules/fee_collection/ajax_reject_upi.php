<?php
/**
 * AJAX: Reject UPI Payment
 * Updates status to Rejected with reason
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/upi_helper.php';

requireLogin();
header('Content-Type: application/json');

try {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid security token.');
    }

    if (!canEdit()) {
        throw new Exception('You do not have permission to reject payments.');
    }

    $upiPaymentId = (int)($_POST['upi_payment_id'] ?? 0);
    $rejectionReason = trim(sanitize($_POST['rejection_reason'] ?? ''));
    $adminId = getAdminId();
    $db = getDB();

    if (empty($rejectionReason)) {
        throw new Exception('Rejection reason is required.');
    }

    // Fetch the UPI payment (must be Pending)
    $upiPayment = $db->fetchOne("
        SELECT * FROM upi_payments WHERE upi_payment_id = :id AND status = 'Pending'
    ", ['id' => $upiPaymentId]);

    if (!$upiPayment) {
        throw new Exception('Payment not found or already reviewed.');
    }

    // Update status
    $db->query("UPDATE upi_payments SET
        status = 'Rejected',
        rejection_reason = :reason,
        reviewed_by = :admin_id,
        reviewed_at = NOW()
        WHERE upi_payment_id = :id", [
        'reason' => $rejectionReason,
        'admin_id' => $adminId,
        'id' => $upiPaymentId
    ]);

    // Audit log
    logAudit($adminId, 'UPI_PAYMENT_REJECTED', 'upi_payments', $upiPaymentId, null,
        json_encode([
            'reason' => $rejectionReason,
            'amount' => $upiPayment['amount'],
            'utr' => $upiPayment['utr_number'],
            'student_id' => $upiPayment['student_id']
        ])
    );

    echo json_encode([
        'success' => true,
        'message' => 'Payment has been rejected.'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
