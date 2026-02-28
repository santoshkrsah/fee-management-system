<?php
/**
 * Admin Review UPI Payment
 * View payment details and approve/reject
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/upi_helper.php';

requireLogin();

$pageTitle = 'Review UPI Payment';
$upiPaymentId = (int)($_GET['id'] ?? 0);

if ($upiPaymentId <= 0) {
    redirectWithMessage('/modules/fee_collection/upi_payments.php', 'error', 'Invalid payment ID.');
}

try {
    $db = getDB();

    // Fetch UPI payment with student details
    $payment = $db->fetchOne("
        SELECT up.*,
               s.admission_no, s.first_name, s.last_name, s.father_name,
               s.contact_number, s.email, s.class_id,
               c.class_name, sec.section_name,
               a.full_name as reviewer_name
        FROM upi_payments up
        JOIN students s ON up.student_id = s.student_id
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
        LEFT JOIN admin a ON up.reviewed_by = a.admin_id
        WHERE up.upi_payment_id = :id
    ", ['id' => $upiPaymentId]);

    if (!$payment) {
        redirectWithMessage('/modules/fee_collection/upi_payments.php', 'error', 'Payment not found.');
    }

    // Get student's fee summary for context
    $feeSummary = $db->fetchOne("
        SELECT * FROM vw_student_fee_summary
        WHERE student_id = :id AND academic_year = :year
    ", ['id' => $payment['student_id'], 'year' => $payment['academic_year']]);

    // Monthly fee info if applicable
    $monthlyFee = null;
    if (isMonthlyFeeMode() && $payment['fee_month']) {
        $academicMonths = getAcademicMonths();
        $monthlyFee = $db->fetchOne("
            SELECT * FROM vw_student_monthly_fee_summary
            WHERE student_id = :id AND academic_year = :year AND fee_month = :month
        ", ['id' => $payment['student_id'], 'year' => $payment['academic_year'], 'month' => $payment['fee_month']]);
    }

} catch (Exception $e) {
    error_log("Review UPI payment error: " . $e->getMessage());
    redirectWithMessage('/modules/fee_collection/upi_payments.php', 'error', 'An error occurred.');
}

$csrfToken = generateCSRFToken();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-qrcode"></i> Review UPI Payment
            <?php
            $statusBadge = match($payment['status']) {
                'Pending' => 'bg-warning text-dark',
                'Approved' => 'bg-success',
                'Rejected' => 'bg-danger',
                default => 'bg-secondary'
            };
            ?>
            <span class="badge <?php echo $statusBadge; ?> fs-6 ms-2"><?php echo htmlspecialchars($payment['status']); ?></span>
        </h2>
    </div>
</div>

<div class="row">
    <!-- Payment Details -->
    <div class="col-lg-8">
        <!-- Student Info Card -->
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-user-graduate"></i> Student Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Name:</span><br>
                        <strong><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
                    </div>
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Admission No:</span><br>
                        <strong><?php echo htmlspecialchars($payment['admission_no']); ?></strong>
                    </div>
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Father's Name:</span><br>
                        <strong><?php echo htmlspecialchars($payment['father_name']); ?></strong>
                    </div>
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Class / Section:</span><br>
                        <strong><?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section_name']); ?></strong>
                    </div>
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Contact:</span><br>
                        <strong><?php echo htmlspecialchars($payment['contact_number']); ?></strong>
                    </div>
                    <div class="col-md-6 mb-2">
                        <span class="text-muted small">Email:</span><br>
                        <strong><?php echo htmlspecialchars($payment['email'] ?? '-'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Details Card -->
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-indian-rupee-sign"></i> Payment Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">Amount:</span><br>
                        <strong class="text-primary fs-5"><?php echo formatCurrency($payment['amount']); ?></strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">UTR Number:</span><br>
                        <strong><code class="fs-6"><?php echo htmlspecialchars($payment['utr_number']); ?></code></strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">Academic Year:</span><br>
                        <strong><?php echo htmlspecialchars($payment['academic_year']); ?></strong>
                    </div>
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">Submitted On:</span><br>
                        <strong><?php echo date('d M Y, h:i A', strtotime($payment['submitted_at'])); ?></strong>
                    </div>
                    <?php if (isMonthlyFeeMode() && $payment['fee_month']): ?>
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">Fee Month:</span><br>
                        <strong><?php echo htmlspecialchars($academicMonths[$payment['fee_month']] ?? '-'); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4 mb-3">
                        <span class="text-muted small">IP Address:</span><br>
                        <strong class="small"><?php echo htmlspecialchars($payment['ip_address'] ?? '-'); ?></strong>
                    </div>
                </div>

                <!-- Screenshot -->
                <?php if (!empty($payment['screenshot_path'])): ?>
                <hr>
                <h6 class="text-muted"><i class="fas fa-image"></i> Payment Screenshot</h6>
                <div class="text-center p-3 border rounded bg-light">
                    <a href="view_upi_screenshot.php?id=<?php echo $payment['upi_payment_id']; ?>" target="_blank">
                        <img src="view_upi_screenshot.php?id=<?php echo $payment['upi_payment_id']; ?>"
                             alt="Payment Screenshot" class="img-fluid"
                             style="max-height: 400px; cursor: pointer;"
                             title="Click to open full size">
                    </a>
                </div>
                <?php else: ?>
                <hr>
                <p class="text-muted mb-0"><i class="fas fa-image"></i> No screenshot uploaded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Review Info (if already reviewed) -->
        <?php if ($payment['status'] !== 'Pending'): ?>
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-clipboard-check"></i> Review Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <span class="text-muted small">Reviewed By:</span><br>
                        <strong><?php echo htmlspecialchars($payment['reviewer_name'] ?? '-'); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">Reviewed On:</span><br>
                        <strong><?php echo $payment['reviewed_at'] ? date('d M Y, h:i A', strtotime($payment['reviewed_at'])) : '-'; ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">Decision:</span><br>
                        <span class="badge <?php echo $statusBadge; ?> fs-6"><?php echo htmlspecialchars($payment['status']); ?></span>
                    </div>
                </div>
                <?php if ($payment['status'] === 'Rejected' && !empty($payment['rejection_reason'])): ?>
                <hr>
                <div>
                    <span class="text-muted small">Rejection Reason:</span><br>
                    <div class="alert alert-danger mt-1 mb-0">
                        <?php echo htmlspecialchars($payment['rejection_reason']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar: Fee Summary + Actions -->
    <div class="col-lg-4">
        <!-- Fee Summary -->
        <?php if ($feeSummary): ?>
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-calculator"></i> Fee Summary
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted small">Total Fee:</span><br>
                    <strong class="text-primary"><?php echo formatCurrency($feeSummary['total_fee_amount'] ?? 0); ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted small">Total Paid:</span><br>
                    <strong class="text-success"><?php echo formatCurrency($feeSummary['total_paid_amount'] ?? 0); ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted small">Balance Due:</span><br>
                    <strong class="text-danger"><?php echo formatCurrency(max(0, $feeSummary['balance_amount'] ?? 0)); ?></strong>
                </div>
                <?php if ($monthlyFee): ?>
                <hr>
                <div class="mb-2">
                    <span class="text-muted small"><?php echo htmlspecialchars($academicMonths[$payment['fee_month']] ?? ''); ?> Balance:</span><br>
                    <strong class="text-danger"><?php echo formatCurrency(max(0, $monthlyFee['monthly_balance'] ?? 0)); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons (only for Pending) -->
        <?php if ($payment['status'] === 'Pending'): ?>
        <div class="card card-custom mb-3">
            <div class="card-header">
                <i class="fas fa-gavel"></i> Actions
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-success w-100 mb-2" id="approveBtn">
                    <i class="fas fa-check"></i> Approve Payment
                </button>
                <button type="button" class="btn btn-danger w-100" id="rejectBtn">
                    <i class="fas fa-times"></i> Reject Payment
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back to List -->
        <a href="upi_payments.php" class="btn btn-secondary w-100">
            <i class="fas fa-arrow-left"></i> Back to UPI Payments
        </a>
    </div>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check"></i> Confirm Approval</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>approve</strong> this payment?</p>
                <div class="alert alert-light border">
                    <strong>Student:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                    <strong>Amount:</strong> <?php echo formatCurrency($payment['amount']); ?><br>
                    <strong>UTR:</strong> <code><?php echo htmlspecialchars($payment['utr_number']); ?></code>
                </div>
                <p class="text-muted small mb-0">
                    This will create a fee collection record, generate a receipt, and update the student's fee balance.
                    The payment amount will be distributed proportionally across fee components.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApproveBtn">
                    <i class="fas fa-check"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times"></i> Reject Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Provide a reason for rejecting this payment:</p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectionReason" rows="3"
                              placeholder="e.g., UTR number not found, payment amount mismatch, etc."
                              required></textarea>
                </div>
                <div class="alert alert-light border">
                    <strong>Student:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                    <strong>Amount:</strong> <?php echo formatCurrency($payment['amount']); ?><br>
                    <strong>UTR:</strong> <code><?php echo htmlspecialchars($payment['utr_number']); ?></code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Result Alert -->
<div id="resultAlert" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:9999; display:none;">
    <div class="alert alert-dismissible fade show" role="alert">
        <span id="resultMessage"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>

<script>
$(document).ready(function() {
    var csrfToken = <?php echo json_encode($csrfToken); ?>;
    var upiPaymentId = <?php echo $upiPaymentId; ?>;

    // Show approve modal
    $('#approveBtn').on('click', function() {
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    });

    // Show reject modal
    $('#rejectBtn').on('click', function() {
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });

    // Confirm approve
    $('#confirmApproveBtn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: 'ajax_approve_upi.php',
            method: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                upi_payment_id: upiPaymentId
            },
            success: function(response) {
                bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
                if (response.success) {
                    showResult('success', response.message);
                    setTimeout(function() {
                        window.location.href = 'upi_payments.php?status=Pending';
                    }, 2000);
                } else {
                    showResult('danger', response.message);
                    btn.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
                }
            },
            error: function() {
                bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
                showResult('danger', 'An error occurred. Please try again.');
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
            }
        });
    });

    // Confirm reject
    $('#confirmRejectBtn').on('click', function() {
        var reason = $('#rejectionReason').val().trim();
        if (!reason) {
            alert('Please provide a rejection reason.');
            $('#rejectionReason').focus();
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: 'ajax_reject_upi.php',
            method: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                upi_payment_id: upiPaymentId,
                rejection_reason: reason
            },
            success: function(response) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                if (response.success) {
                    showResult('success', response.message);
                    setTimeout(function() {
                        window.location.href = 'upi_payments.php?status=Pending';
                    }, 2000);
                } else {
                    showResult('danger', response.message);
                    btn.prop('disabled', false).html('<i class="fas fa-times"></i> Reject');
                }
            },
            error: function() {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                showResult('danger', 'An error occurred. Please try again.');
                btn.prop('disabled', false).html('<i class="fas fa-times"></i> Reject');
            }
        });
    });

    function showResult(type, message) {
        var alert = $('#resultAlert');
        alert.find('.alert').removeClass('alert-success alert-danger').addClass('alert-' + type);
        $('#resultMessage').html(message);
        alert.fadeIn();
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
