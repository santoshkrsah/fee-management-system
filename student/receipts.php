<?php
/**
 * Student Receipts List
 * View all payment receipts for the logged-in student
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireStudentLogin();

$pageTitle = 'My Receipts';
$studentId = getStudentId();

try {
    $db = getDB();

    // Fetch all payments for this student
    $payments = $db->fetchAll("
        SELECT fc.payment_id, fc.receipt_no, fc.payment_date, fc.total_paid,
               fc.payment_mode, fc.fee_month, fc.academic_year
        FROM fee_collection fc
        WHERE fc.student_id = :id
        ORDER BY fc.payment_date DESC, fc.payment_id DESC
    ", ['id' => $studentId]);

} catch (Exception $e) {
    error_log("Student receipts error: " . $e->getMessage());
    $payments = [];
}

require_once '../includes/student_header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-receipt"></i> My Receipts
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> Payment Receipts
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Receipt No</th>
                                <th>Academic Year</th>
                                <th>Date</th>
                                <?php if (isMonthlyFeeMode()): ?>
                                <th>Fee Month</th>
                                <?php endif; ?>
                                <th class="text-end">Amount</th>
                                <th>Payment Mode</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $academicMonths = getAcademicMonths();
                            $sn = 1;
                            foreach ($payments as $payment):
                            ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <?php if (isMonthlyFeeMode()): ?>
                                <td><?php echo htmlspecialchars($academicMonths[$payment['fee_month']] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td class="text-end"><strong><?php echo formatCurrency($payment['total_paid']); ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['payment_mode']); ?></td>
                                <td class="text-center">
                                    <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Receipt">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No payment receipts found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/student_footer.php'; ?>
