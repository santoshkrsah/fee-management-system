<?php
/**
 * Student UPI Payments List
 * Shows history of UPI payment submissions and their status
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upi_helper.php';

requireStudentLogin();

$pageTitle = 'My UPI Payments';
$studentId = getStudentId();

try {
    $db = getDB();

    // Fetch all UPI payments for this student
    $upiPayments = $db->fetchAll("
        SELECT up.*, fc.receipt_no
        FROM upi_payments up
        LEFT JOIN fee_collection fc ON up.payment_id = fc.payment_id
        WHERE up.student_id = :sid
        ORDER BY up.submitted_at DESC
    ", ['sid' => $studentId]);

} catch (Exception $e) {
    error_log("My UPI payments error: " . $e->getMessage());
    $upiPayments = [];
}

require_once '../includes/student_header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-qrcode"></i> My UPI Payments
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Payment Submissions</span>
                <?php if (isUpiEnabled()): ?>
                <a href="pay_fee.php" class="btn btn-sm btn-success">
                    <i class="fas fa-qrcode"></i> Pay Now
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($upiPayments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>UTR Number</th>
                                <th class="text-center">Status</th>
                                <th>Details</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sn = 1; foreach ($upiPayments as $payment): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo date('d M Y', strtotime($payment['submitted_at'])); ?>
                                    <br><small class="text-muted"><?php echo date('h:i A', strtotime($payment['submitted_at'])); ?></small>
                                </td>
                                <td class="text-end"><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($payment['utr_number']); ?></code></td>
                                <td class="text-center">
                                    <?php
                                    $statusBadge = match($payment['status']) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusBadge; ?>">
                                        <?php echo htmlspecialchars($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['status'] === 'Rejected' && !empty($payment['rejection_reason'])): ?>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($payment['rejection_reason']); ?>
                                        </small>
                                    <?php elseif ($payment['status'] === 'Approved' && !empty($payment['receipt_no'])): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                            Receipt: <?php echo htmlspecialchars($payment['receipt_no']); ?>
                                        </small>
                                    <?php elseif ($payment['status'] === 'Pending'): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> Awaiting verification
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($payment['screenshot_path'])): ?>
                                    <a href="view_upi_screenshot.php?id=<?php echo $payment['upi_payment_id']; ?>"
                                       class="btn btn-sm btn-outline-secondary" title="View Screenshot" target="_blank">
                                        <i class="fas fa-image"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($payment['status'] === 'Approved' && $payment['payment_id']): ?>
                                    <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>"
                                       class="btn btn-sm btn-outline-primary" title="View Receipt">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-qrcode text-muted" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="text-muted mt-3 mb-0">No UPI payment submissions found.</p>
                    <?php if (isUpiEnabled()): ?>
                    <a href="pay_fee.php" class="btn btn-success mt-3">
                        <i class="fas fa-qrcode"></i> Pay Fee via UPI
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/student_footer.php'; ?>
