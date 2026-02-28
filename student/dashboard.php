<?php
/**
 * Student Dashboard
 * Shows personal details, fee summary, and recent payments
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upi_helper.php';

requireStudentLogin();

$pageTitle = 'Student Dashboard';
$studentId = getStudentId();
$selectedSession = getActiveSessionName();

try {
    $db = getDB();

    // Fetch personal details with class/section
    $student = $db->fetchOne("
        SELECT s.*, c.class_name, sec.section_name
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
        WHERE s.student_id = :id
    ", ['id' => $studentId]);

    if (!$student) {
        studentLogout('Your account could not be found. Please contact administration.');
    }

    // Fee summary (annual mode)
    $feeSummary = $db->fetchOne("
        SELECT * FROM vw_student_fee_summary
        WHERE student_id = :id AND academic_year = :year
    ", ['id' => $studentId, 'year' => $selectedSession]);

    // Monthly fee breakdown (if monthly mode)
    $monthlyFees = [];
    if (isMonthlyFeeMode()) {
        $monthlyFees = $db->fetchAll("
            SELECT * FROM vw_student_monthly_fee_summary
            WHERE student_id = :id AND academic_year = :year
            ORDER BY fee_month
        ", ['id' => $studentId, 'year' => $selectedSession]);
    }

    // Recent payments (last 10)
    $recentPayments = $db->fetchAll("
        SELECT fc.payment_id, fc.receipt_no, fc.payment_date, fc.total_paid,
               fc.payment_mode, fc.fee_month, fc.academic_year
        FROM fee_collection fc
        WHERE fc.student_id = :id AND fc.academic_year = :year
        ORDER BY fc.payment_date DESC, fc.payment_id DESC
        LIMIT 10
    ", ['id' => $studentId, 'year' => $selectedSession]);

} catch (Exception $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $student = null;
    $feeSummary = null;
    $monthlyFees = [];
    $recentPayments = [];
}

require_once '../includes/student_header.php';
?>

<?php if (!$_SESSION['student_password_changed']): ?>
<div class="password-change-banner">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <strong>Security Recommendation:</strong> You are using the default password.
        <a href="change_password.php">Change your password now</a> for better security.
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Student Dashboard
            <small class="text-muted fs-6 ms-2">
                <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($selectedSession); ?>
            </small>
        </h2>
    </div>
</div>

<?php if ($student): ?>

<!-- Personal Details -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-user"></i> Personal Details
            </div>
            <div class="card-body">
                <div class="student-detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Admission No</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Class / Section</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Roll Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Father's Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['father_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Mother's Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['mother_name'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Contact Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['contact_number']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['email'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['address']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Admission Date</span>
                        <span class="detail-value"><?php echo formatDate($student['admission_date']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Summary Cards -->
<?php if ($feeSummary): ?>
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
        <div class="card student-fee-card">
            <div class="card-body text-center">
                <div class="icon-box bg-gradient-primary mb-2" style="margin: 0 auto;">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
                <div class="fee-amount text-primary"><?php echo formatCurrency($feeSummary['total_fee_amount'] ?? 0); ?></div>
                <p class="text-muted mb-0">Total Fee</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
        <div class="card student-fee-card">
            <div class="card-body text-center">
                <div class="icon-box bg-gradient-success mb-2" style="margin: 0 auto;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="fee-amount text-success"><?php echo formatCurrency($feeSummary['total_paid_amount'] ?? 0); ?></div>
                <p class="text-muted mb-0">Total Paid</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
        <div class="card student-fee-card">
            <div class="card-body text-center">
                <div class="icon-box bg-gradient-danger mb-2" style="margin: 0 auto;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <?php $balance = $feeSummary['balance_amount'] ?? 0; ?>
                <div class="fee-amount text-danger"><?php echo formatCurrency(max(0, $balance)); ?></div>
                <p class="text-muted mb-0">Balance Due</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
        <div class="card student-fee-card">
            <div class="card-body text-center">
                <div class="icon-box bg-gradient-info mb-2" style="margin: 0 auto;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <?php
                $status = $feeSummary['payment_status'] ?? 'Unpaid';
                $statusClass = match($status) {
                    'Paid' => 'fee-status-paid',
                    'Partial' => 'fee-status-partial',
                    default => 'fee-status-unpaid'
                };
                ?>
                <div class="mt-2 mb-1">
                    <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                </div>
                <p class="text-muted mb-0">Payment Status</p>
            </div>
        </div>
    </div>
</div>

<!-- UPI Payment Option -->
<?php
try {
    if (isUpiEnabled()):
        $upiBalance = $feeSummary['balance_amount'] ?? 0;
        $pendingUpi = getStudentPendingUpiPayment($studentId, $selectedSession);
?>
<?php if ($upiBalance > 0): ?>
<div class="row mb-4">
    <div class="col-12 text-center">
        <?php if ($pendingUpi): ?>
        <div class="alert alert-warning mb-0">
            <i class="fas fa-clock"></i>
            You have a pending UPI payment of <strong><?php echo formatCurrency($pendingUpi['amount']); ?></strong>
            (UTR: <strong><?php echo htmlspecialchars($pendingUpi['utr_number']); ?></strong>).
            It is being reviewed by the administration.
            <a href="my_upi_payments.php" class="btn btn-sm btn-outline-warning ms-2">
                <i class="fas fa-eye"></i> View Status
            </a>
        </div>
        <?php else: ?>
        <a href="pay_fee.php" class="btn btn-lg btn-success">
            <i class="fas fa-qrcode"></i> Pay Now via UPI
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php
    endif;
} catch (Exception $e) {
    // Silently skip UPI section if table/settings not available
}
?>

<?php else: ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No fee structure has been assigned for the current academic session (<?php echo htmlspecialchars($selectedSession); ?>).
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Fee Breakdown (if monthly mode) -->
<?php if (!empty($monthlyFees)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-calendar-check"></i> Monthly Fee Breakdown
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Fee Amount</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyFees as $mf): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($mf['month_label']); ?></strong></td>
                                <td class="text-end"><?php echo formatCurrency($mf['monthly_fee_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($mf['monthly_paid_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency(max(0, $mf['monthly_balance'])); ?></td>
                                <td class="text-center">
                                    <?php
                                    $mStatus = $mf['payment_status'] ?? 'Unpaid';
                                    $mClass = match($mStatus) {
                                        'Paid' => 'fee-status-paid',
                                        'Partial' => 'fee-status-partial',
                                        default => 'fee-status-unpaid'
                                    };
                                    ?>
                                    <span class="<?php echo $mClass; ?>"><?php echo htmlspecialchars($mStatus); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Payments -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Recent Payments</span>
                <a href="receipts.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-receipt"></i> View All Receipts
                </a>
            </div>
            <div class="card-body">
                <?php if (count($recentPayments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <?php if (isMonthlyFeeMode()): ?>
                                <th>Month</th>
                                <?php endif; ?>
                                <th class="text-end">Amount</th>
                                <th>Mode</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $academicMonths = getAcademicMonths();
                            foreach ($recentPayments as $payment):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <?php if (isMonthlyFeeMode()): ?>
                                <td><?php echo htmlspecialchars($academicMonths[$payment['fee_month']] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td class="text-end"><strong><?php echo formatCurrency($payment['total_paid']); ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['payment_mode']); ?></td>
                                <td class="text-center">
                                    <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Receipt">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No payment records found for this session.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i> Unable to load your profile. Please contact the school administration.
</div>
<?php endif; ?>

<?php require_once '../includes/student_footer.php'; ?>
