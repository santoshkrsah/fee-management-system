<?php
/**
 * AJAX: Approve UPI Payment
 * Creates fee_collection record with proportional distribution
 * Must be atomic - wrapped in transaction
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
        throw new Exception('You do not have permission to approve payments.');
    }

    $upiPaymentId = (int)($_POST['upi_payment_id'] ?? 0);
    $adminId = getAdminId();
    $db = getDB();

    // Fetch the UPI payment (must be Pending)
    $upiPayment = $db->fetchOne("
        SELECT up.*, s.class_id, s.admission_no
        FROM upi_payments up
        JOIN students s ON up.student_id = s.student_id
        WHERE up.upi_payment_id = :id AND up.status = 'Pending'
    ", ['id' => $upiPaymentId]);

    if (!$upiPayment) {
        throw new Exception('Payment not found or already reviewed.');
    }

    $db->beginTransaction();

    $studentId = $upiPayment['student_id'];
    $academicYear = $upiPayment['academic_year'];
    $classId = $upiPayment['class_id'];
    $amount = (float)$upiPayment['amount'];
    $feeMonth = $upiPayment['fee_month'];
    $feeMode = getFeeMode();

    // 1. Get fee structure
    $feeStructure = $db->fetchOne("
        SELECT * FROM fee_structure
        WHERE class_id = :cid AND academic_year = :year AND status = 'active'
    ", ['cid' => $classId, 'year' => $academicYear]);

    if (!$feeStructure) {
        throw new Exception('Fee structure not found for this student\'s class.');
    }

    $feeStructureId = $feeStructure['fee_structure_id'];
    $monthlyFeeStructureId = null;

    // For monthly mode, also get monthly fee structure
    $feeForDistribution = $feeStructure; // Default: use annual structure for distribution
    if ($feeMode === 'monthly' && $feeMonth) {
        $monthlyFs = $db->fetchOne("
            SELECT * FROM monthly_fee_structure
            WHERE class_id = :cid AND academic_year = :year AND fee_month = :month AND status = 'active'
        ", ['cid' => $classId, 'year' => $academicYear, 'month' => $feeMonth]);

        if ($monthlyFs) {
            $monthlyFeeStructureId = $monthlyFs['monthly_fee_id'];
            $feeForDistribution = $monthlyFs; // Use monthly structure for distribution
        }
    }

    // 2. Get already-paid amounts
    if ($feeMode === 'monthly' && $feeMonth) {
        $paidSoFar = $db->fetchOne("
            SELECT COALESCE(SUM(tuition_fee_paid),0) as tuition_fee_paid,
                   COALESCE(SUM(exam_fee_paid),0) as exam_fee_paid,
                   COALESCE(SUM(library_fee_paid),0) as library_fee_paid,
                   COALESCE(SUM(sports_fee_paid),0) as sports_fee_paid,
                   COALESCE(SUM(lab_fee_paid),0) as lab_fee_paid,
                   COALESCE(SUM(transport_fee_paid),0) as transport_fee_paid,
                   COALESCE(SUM(other_charges_paid),0) as other_charges_paid
            FROM fee_collection
            WHERE student_id = :sid AND academic_year = :year AND fee_month = :month
        ", ['sid' => $studentId, 'year' => $academicYear, 'month' => $feeMonth]);
    } else {
        $paidSoFar = $db->fetchOne("
            SELECT COALESCE(SUM(tuition_fee_paid),0) as tuition_fee_paid,
                   COALESCE(SUM(exam_fee_paid),0) as exam_fee_paid,
                   COALESCE(SUM(library_fee_paid),0) as library_fee_paid,
                   COALESCE(SUM(sports_fee_paid),0) as sports_fee_paid,
                   COALESCE(SUM(lab_fee_paid),0) as lab_fee_paid,
                   COALESCE(SUM(transport_fee_paid),0) as transport_fee_paid,
                   COALESCE(SUM(other_charges_paid),0) as other_charges_paid
            FROM fee_collection
            WHERE student_id = :sid AND academic_year = :year
        ", ['sid' => $studentId, 'year' => $academicYear]);
    }

    // 3. Calculate proportional distribution
    $distribution = distributePaymentProportionally($amount, $feeForDistribution, $paidSoFar);

    // 4. Generate receipt number
    $receiptNo = generateReceiptNo();
    $existing = $db->fetchOne("SELECT receipt_no FROM fee_collection WHERE receipt_no = :r", ['r' => $receiptNo]);
    if ($existing) {
        $receiptNo = generateReceiptNo() . '-' . rand(10, 99);
    }

    // 5. Create fee_collection record
    $db->query("INSERT INTO fee_collection (
        receipt_no, student_id, academic_year, fee_structure_id, payment_date,
        tuition_fee_paid, exam_fee_paid, library_fee_paid, sports_fee_paid,
        lab_fee_paid, transport_fee_paid, other_charges_paid, fine, discount,
        payment_mode, transaction_id, remarks, collected_by,
        fee_month, monthly_fee_structure_id
    ) VALUES (
        :receipt_no, :student_id, :academic_year, :fee_structure_id, :payment_date,
        :tuition_fee_paid, :exam_fee_paid, :library_fee_paid, :sports_fee_paid,
        :lab_fee_paid, :transport_fee_paid, :other_charges_paid, 0, 0,
        'UPI', :transaction_id, :remarks, :collected_by,
        :fee_month, :monthly_fee_structure_id
    )", [
        'receipt_no' => $receiptNo,
        'student_id' => $studentId,
        'academic_year' => $academicYear,
        'fee_structure_id' => $feeStructureId,
        'payment_date' => date('Y-m-d'),
        'tuition_fee_paid' => $distribution['tuition_fee_paid'],
        'exam_fee_paid' => $distribution['exam_fee_paid'],
        'library_fee_paid' => $distribution['library_fee_paid'],
        'sports_fee_paid' => $distribution['sports_fee_paid'],
        'lab_fee_paid' => $distribution['lab_fee_paid'],
        'transport_fee_paid' => $distribution['transport_fee_paid'],
        'other_charges_paid' => $distribution['other_charges_paid'],
        'transaction_id' => $upiPayment['utr_number'],
        'remarks' => 'UPI Payment - Student submission approved',
        'collected_by' => $adminId,
        'fee_month' => $feeMonth,
        'monthly_fee_structure_id' => $monthlyFeeStructureId
    ]);

    $paymentId = $db->lastInsertId();

    // 6. Update upi_payments status
    $db->query("UPDATE upi_payments SET
        status = 'Approved',
        reviewed_by = :admin_id,
        reviewed_at = NOW(),
        payment_id = :payment_id
        WHERE upi_payment_id = :id", [
        'admin_id' => $adminId,
        'payment_id' => $paymentId,
        'id' => $upiPaymentId
    ]);

    $db->commit();

    // 7. Audit log
    logAudit($adminId, 'UPI_PAYMENT_APPROVED', 'upi_payments', $upiPaymentId, null,
        json_encode([
            'payment_id' => $paymentId,
            'receipt_no' => $receiptNo,
            'amount' => $amount,
            'utr' => $upiPayment['utr_number'],
            'student_id' => $studentId
        ])
    );

    echo json_encode([
        'success' => true,
        'message' => 'Payment approved successfully. Receipt: ' . $receiptNo,
        'payment_id' => $paymentId,
        'receipt_no' => $receiptNo
    ]);

} catch (Exception $e) {
    // Rollback if in transaction
    try {
        $db = getDB();
        if ($db->getConnection()->inTransaction()) {
            $db->rollback();
        }
    } catch (Exception $ex) {
        // Ignore rollback errors
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
