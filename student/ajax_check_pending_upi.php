<?php
/**
 * AJAX: Check if student has pending UPI payment
 * Returns JSON response
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/upi_helper.php';

requireStudentLogin();
header('Content-Type: application/json');

$studentId = getStudentId();
$academicYear = getActiveSessionName();

$pending = getStudentPendingUpiPayment($studentId, $academicYear);

echo json_encode([
    'success' => true,
    'has_pending' => ($pending !== false),
    'pending' => $pending ? [
        'amount' => $pending['amount'],
        'utr_number' => $pending['utr_number'],
        'submitted_at' => $pending['submitted_at']
    ] : null
]);
