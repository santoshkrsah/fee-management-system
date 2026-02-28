<?php
/**
 * Student UPI Payment Page
 * Allows students to pay fees via UPI QR code
 * Flow: Amount Selection → QR Code → Submit UTR → Confirmation
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upi_helper.php';

requireStudentLogin();

$pageTitle = 'Pay Fee via UPI';
$studentId = getStudentId();
$selectedSession = getActiveSessionName();
$admissionNo = getStudentAdmissionNo();
$error = '';

// Check if UPI is enabled
if (!isUpiEnabled()) {
    redirectWithMessage('/student/dashboard.php', 'warning', 'UPI payments are not currently available. Please contact administration.');
}

try {
    $db = getDB();

    // Fetch student details
    $student = $db->fetchOne("
        SELECT s.*, c.class_name, sec.section_name
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
        WHERE s.student_id = :id
    ", ['id' => $studentId]);

    if (!$student) {
        studentLogout('Your account could not be found.');
    }

    // Check for existing pending payment
    $pendingPayment = getStudentPendingUpiPayment($studentId, $selectedSession);

    // Fee summary
    $feeSummary = $db->fetchOne("
        SELECT * FROM vw_student_fee_summary
        WHERE student_id = :id AND academic_year = :year
    ", ['id' => $studentId, 'year' => $selectedSession]);

    $balance = (float)($feeSummary['balance_amount'] ?? 0);

    // Monthly fees (if applicable)
    $monthlyFees = [];
    if (isMonthlyFeeMode()) {
        $monthlyFees = $db->fetchAll("
            SELECT * FROM vw_student_monthly_fee_summary
            WHERE student_id = :id AND academic_year = :year
            ORDER BY fee_month
        ", ['id' => $studentId, 'year' => $selectedSession]);
    }

    // Get UPI settings
    $upiSettings = getUpiSettings();

} catch (Exception $e) {
    error_log("Pay fee page error: " . $e->getMessage());
    redirectWithMessage('/student/dashboard.php', 'error', 'An error occurred. Please try again.');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }

        // Check for existing pending payment again
        if (getStudentPendingUpiPayment($studentId, $selectedSession)) {
            throw new Exception('You already have a pending payment. Please wait for it to be reviewed.');
        }

        $amount = (float)($_POST['amount'] ?? 0);
        $utrNumber = strtoupper(trim(sanitize($_POST['utr_number'] ?? '')));
        $feeMonth = !empty($_POST['fee_month']) ? (int)$_POST['fee_month'] : null;

        // Validate amount
        if ($amount <= 0) {
            throw new Exception('Please enter a valid payment amount.');
        }

        // Re-fetch balance to prevent manipulation
        $currentSummary = $db->fetchOne("
            SELECT * FROM vw_student_fee_summary
            WHERE student_id = :id AND academic_year = :year
        ", ['id' => $studentId, 'year' => $selectedSession]);

        $currentBalance = (float)($currentSummary['balance_amount'] ?? 0);

        if (isMonthlyFeeMode() && $feeMonth) {
            // For monthly mode, validate against monthly balance
            $monthSummary = $db->fetchOne("
                SELECT * FROM vw_student_monthly_fee_summary
                WHERE student_id = :id AND academic_year = :year AND fee_month = :month
            ", ['id' => $studentId, 'year' => $selectedSession, 'month' => $feeMonth]);
            $monthBalance = (float)($monthSummary['monthly_balance'] ?? 0);
            if ($amount > $monthBalance + 0.01) {
                throw new Exception('Payment amount exceeds the balance for the selected month.');
            }
        } else {
            if ($amount > $currentBalance + 0.01) {
                throw new Exception('Payment amount exceeds the remaining balance.');
            }
        }

        // Validate UTR number format (alphanumeric, 12-22 chars)
        if (!preg_match('/^[A-Z0-9]{12,22}$/', $utrNumber)) {
            throw new Exception('Invalid UTR number. It should be 12-22 alphanumeric characters.');
        }

        // Check for duplicate UTR
        if (isUtrDuplicate($utrNumber)) {
            throw new Exception('This UTR number has already been submitted.');
        }

        // Handle screenshot upload
        $screenshotPath = handleUpiScreenshotUpload($_FILES['screenshot'] ?? null, $studentId);

        // Get client IP
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

        // Insert payment submission
        $db->query("INSERT INTO upi_payments (
            student_id, academic_year, amount, utr_number, screenshot_path,
            status, fee_month, ip_address
        ) VALUES (
            :student_id, :academic_year, :amount, :utr_number, :screenshot_path,
            'Pending', :fee_month, :ip_address
        )", [
            'student_id' => $studentId,
            'academic_year' => $selectedSession,
            'amount' => $amount,
            'utr_number' => $utrNumber,
            'screenshot_path' => $screenshotPath,
            'fee_month' => $feeMonth,
            'ip_address' => $ipAddress
        ]);

        $upiPaymentId = $db->lastInsertId();

        // Audit log
        logStudentAudit($studentId, 'UPI_PAYMENT_SUBMITTED', 'upi_payments', $upiPaymentId);

        redirectWithMessage('/student/my_upi_payments.php', 'success',
            'Payment submitted successfully! It will be reviewed by the administration. UTR: ' . $utrNumber);

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Re-check pending
        $pendingPayment = getStudentPendingUpiPayment($studentId, $selectedSession);
    }
}

$csrfToken = generateCSRFToken();

require_once '../includes/student_header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-qrcode"></i> Pay Fee via UPI
            <small class="text-muted fs-6 ms-2">
                <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($selectedSession); ?>
            </small>
        </h2>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($pendingPayment): ?>
<!-- Pending Payment Alert -->
<div class="row mb-4">
    <div class="col-lg-8 mx-auto">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-clock text-warning" style="font-size: 48px;"></i>
                <h4 class="mt-3 text-warning">Payment Pending Verification</h4>
                <p class="text-muted mb-2">
                    You have already submitted a payment of
                    <strong><?php echo formatCurrency($pendingPayment['amount']); ?></strong>
                    with UTR: <strong><?php echo htmlspecialchars($pendingPayment['utr_number']); ?></strong>
                </p>
                <p class="text-muted mb-3">
                    Submitted on <?php echo date('d M Y, h:i A', strtotime($pendingPayment['submitted_at'])); ?>
                </p>
                <p class="mb-3">Please wait for the school administration to verify your payment.</p>
                <a href="my_upi_payments.php" class="btn btn-warning">
                    <i class="fas fa-list"></i> View My UPI Payments
                </a>
                <a href="dashboard.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php elseif ($balance <= 0): ?>
<!-- No Balance -->
<div class="row mb-4">
    <div class="col-lg-8 mx-auto">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                <h4 class="mt-3 text-success">All Fees Paid!</h4>
                <p class="text-muted">You have no pending balance for the current session.</p>
                <a href="dashboard.php" class="btn btn-success">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Payment Flow -->
<div class="row">
    <div class="col-lg-8 mx-auto">

        <!-- Stage 1: Amount Selection -->
        <div id="stage1" class="card card-custom mb-4">
            <div class="card-header">
                <i class="fas fa-indian-rupee-sign"></i> Step 1: Select Payment Amount
            </div>
            <div class="card-body">
                <!-- Fee Summary -->
                <div class="row mb-3">
                    <div class="col-4 text-center">
                        <div class="text-muted small">Total Fee</div>
                        <div class="fw-bold text-primary"><?php echo formatCurrency($feeSummary['total_fee_amount'] ?? 0); ?></div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Total Paid</div>
                        <div class="fw-bold text-success"><?php echo formatCurrency($feeSummary['total_paid_amount'] ?? 0); ?></div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Balance Due</div>
                        <div class="fw-bold text-danger"><?php echo formatCurrency($balance); ?></div>
                    </div>
                </div>

                <hr>

                <?php if (isMonthlyFeeMode() && !empty($monthlyFees)): ?>
                <!-- Monthly Mode: Select Month -->
                <div class="mb-3">
                    <label class="form-label-custom">Select Month <span class="text-danger">*</span></label>
                    <select class="form-select form-control-custom" id="feeMonthSelect">
                        <option value="">-- Select a month --</option>
                        <?php foreach ($monthlyFees as $mf): ?>
                            <?php if ((float)$mf['monthly_balance'] > 0): ?>
                            <option value="<?php echo $mf['fee_month']; ?>"
                                    data-balance="<?php echo $mf['monthly_balance']; ?>">
                                <?php echo htmlspecialchars($mf['month_label']); ?>
                                - Balance: <?php echo formatCurrency($mf['monthly_balance']); ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Amount Type -->
                <div class="mb-3">
                    <label class="form-label-custom">Payment Amount</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="amountType" id="fullAmount" value="full" checked>
                        <label class="form-check-label" for="fullAmount">
                            Full Remaining Amount: <strong id="fullAmountDisplay"><?php echo formatCurrency($balance); ?></strong>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="amountType" id="customAmount" value="custom">
                        <label class="form-check-label" for="customAmount">
                            Custom Amount
                        </label>
                    </div>
                    <div id="customAmountDiv" style="display:none;" class="mt-2">
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control form-control-custom" id="customAmountInput"
                                   min="1" max="<?php echo $balance; ?>" step="0.01"
                                   placeholder="Enter amount">
                        </div>
                        <small class="text-muted">Enter amount between ₹ 1 and ₹ <?php echo number_format($balance, 2); ?></small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-custom" id="proceedToPayBtn">
                        <i class="fas fa-arrow-right"></i> Proceed to Pay
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary btn-custom">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </div>

        <!-- Stage 2: QR Code Display -->
        <div id="stage2" class="card card-custom mb-4" style="display:none;">
            <div class="card-header">
                <i class="fas fa-qrcode"></i> Step 2: Scan QR Code & Pay
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <div id="qrCodeDiv" style="display:inline-block;"></div>
                </div>

                <div class="row justify-content-center mb-3">
                    <div class="col-md-8">
                        <div class="alert alert-light border">
                            <div class="mb-2">
                                <strong>UPI ID:</strong>
                                <span id="displayUpiId" class="font-monospace"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="copyUpiBtn" title="Copy UPI ID">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="mb-0">
                                <strong>Amount:</strong> ₹ <span id="displayAmount" class="fw-bold text-primary"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center mb-3">
                    <div class="col-md-8">
                        <div class="alert alert-info text-start">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Payment Instructions</h6>
                            <ol class="mb-0 small">
                                <li>Scan the QR code with any UPI app (GPay, PhonePe, Paytm, etc.)</li>
                                <li>Or pay manually using the UPI ID shown above</li>
                                <li class="fw-bold">Use your Admission No <strong>(<?php echo htmlspecialchars($admissionNo); ?>)</strong> as the payment remark/note</li>
                                <li>After payment, note down the <strong>UTR / Transaction Reference Number</strong></li>
                                <li>Click "I Have Paid" below to submit your payment details</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-success btn-lg" id="iHavePaidBtn">
                        <i class="fas fa-check"></i> I Have Paid
                    </button>
                    <button type="button" class="btn btn-secondary" id="backToStage1Btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
            </div>
        </div>

        <!-- Stage 3: Payment Confirmation Form -->
        <div id="stage3" class="card card-custom mb-4" style="display:none;">
            <div class="card-header">
                <i class="fas fa-check-circle"></i> Step 3: Submit Payment Details
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="paymentForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="amount" id="formAmount" value="">
                    <input type="hidden" name="fee_month" id="formFeeMonth" value="">

                    <div class="mb-3">
                        <label class="form-label-custom">UTR / Transaction Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="utr_number" id="utrInput"
                               class="form-control form-control-custom"
                               placeholder="e.g., 123456789012"
                               maxlength="22" required
                               style="text-transform: uppercase;">
                        <small class="text-muted">Enter the 12-22 digit UTR number from your payment confirmation (alphanumeric only).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Payment Screenshot <small class="text-muted">(Optional but recommended)</small></label>
                        <input type="file" name="screenshot" id="screenshotInput"
                               class="form-control form-control-custom"
                               accept=".jpg,.jpeg,.png">
                        <small class="text-muted">Upload a screenshot of the payment confirmation. Max 5MB, JPG or PNG only.</small>
                    </div>

                    <div class="alert alert-secondary mb-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Amount:</small><br>
                                <strong>₹ <span id="confirmAmount"></span></strong>
                            </div>
                            <div class="col-6" id="confirmMonthDiv" style="display:none;">
                                <small class="text-muted">Month:</small><br>
                                <strong id="confirmMonth"></strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-custom" id="submitPaymentBtn">
                            <i class="fas fa-paper-plane"></i> Submit for Verification
                        </button>
                        <button type="button" class="btn btn-secondary btn-custom" id="backToStage2Btn">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- QR Code JS Library -->
<script src="/assets/js/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    var upiId = <?php echo json_encode($upiSettings['upi_id']); ?>;
    var payeeName = <?php echo json_encode($upiSettings['upi_payee_name']); ?>;
    var admissionNo = <?php echo json_encode($admissionNo); ?>;
    var totalBalance = <?php echo json_encode($balance); ?>;
    var isMonthly = <?php echo isMonthlyFeeMode() ? 'true' : 'false'; ?>;
    var selectedAmount = totalBalance;
    var selectedMonth = null;
    var selectedMonthLabel = '';
    var qrcodeObj = null;

    // Show/hide custom amount input
    $('input[name="amountType"]').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customAmountDiv').slideDown();
            $('#customAmountInput').focus();
        } else {
            $('#customAmountDiv').slideUp();
        }
    });

    // Monthly mode: update amounts on month selection
    <?php if (isMonthlyFeeMode()): ?>
    $('#feeMonthSelect').on('change', function() {
        var monthBalance = parseFloat($(this).find(':selected').data('balance')) || 0;
        selectedMonth = $(this).val() || null;
        selectedMonthLabel = $(this).find(':selected').text().split(' - ')[0] || '';

        if (monthBalance > 0) {
            totalBalance = monthBalance;
            $('#fullAmountDisplay').text('₹ ' + monthBalance.toFixed(2));
            $('#customAmountInput').attr('max', monthBalance);
        }
    });
    <?php endif; ?>

    // Stage 1 → Stage 2
    $('#proceedToPayBtn').on('click', function() {
        // Validate monthly mode month selection
        if (isMonthly && !$('#feeMonthSelect').val()) {
            alert('Please select a month first.');
            return;
        }

        // Get amount
        var amountType = $('input[name="amountType"]:checked').val();
        if (amountType === 'custom') {
            selectedAmount = parseFloat($('#customAmountInput').val());
            if (isNaN(selectedAmount) || selectedAmount <= 0) {
                alert('Please enter a valid amount.');
                $('#customAmountInput').focus();
                return;
            }
            if (selectedAmount > totalBalance) {
                alert('Amount cannot exceed the remaining balance of ₹ ' + totalBalance.toFixed(2));
                return;
            }
        } else {
            selectedAmount = totalBalance;
        }

        // Generate QR Code
        var upiUrl = 'upi://pay?pa=' + encodeURIComponent(upiId)
                   + '&pn=' + encodeURIComponent(payeeName)
                   + '&am=' + selectedAmount.toFixed(2)
                   + '&cu=INR'
                   + '&tn=' + encodeURIComponent(admissionNo);

        $('#qrCodeDiv').empty();
        qrcodeObj = new QRCode(document.getElementById('qrCodeDiv'), {
            text: upiUrl,
            width: 250,
            height: 250,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });

        $('#displayUpiId').text(upiId);
        $('#displayAmount').text(selectedAmount.toFixed(2));

        // Set form values
        $('#formAmount').val(selectedAmount.toFixed(2));
        if (isMonthly && selectedMonth) {
            $('#formFeeMonth').val(selectedMonth);
        }

        $('#stage1').slideUp(300, function() {
            $('#stage2').slideDown(300);
        });
    });

    // Copy UPI ID
    $('#copyUpiBtn').on('click', function() {
        navigator.clipboard.writeText(upiId).then(function() {
            var btn = $('#copyUpiBtn');
            btn.html('<i class="fas fa-check"></i>');
            setTimeout(function() { btn.html('<i class="fas fa-copy"></i>'); }, 2000);
        });
    });

    // Stage 2 → Stage 3
    $('#iHavePaidBtn').on('click', function() {
        $('#confirmAmount').text(selectedAmount.toFixed(2));
        if (isMonthly && selectedMonthLabel) {
            $('#confirmMonth').text(selectedMonthLabel);
            $('#confirmMonthDiv').show();
        }

        $('#stage2').slideUp(300, function() {
            $('#stage3').slideDown(300);
            $('#utrInput').focus();
        });
    });

    // Back buttons
    $('#backToStage1Btn').on('click', function() {
        $('#stage2').slideUp(300, function() {
            $('#stage1').slideDown(300);
        });
    });

    $('#backToStage2Btn').on('click', function() {
        $('#stage3').slideUp(300, function() {
            $('#stage2').slideDown(300);
        });
    });

    // UTR validation on input
    $('#utrInput').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Form submission validation
    $('#paymentForm').on('submit', function(e) {
        var utr = $('#utrInput').val().trim();
        if (!/^[A-Z0-9]{12,22}$/.test(utr)) {
            e.preventDefault();
            alert('UTR number must be 12-22 alphanumeric characters.');
            $('#utrInput').focus();
            return false;
        }

        // Validate screenshot size (client-side)
        var fileInput = $('#screenshotInput')[0];
        if (fileInput.files.length > 0) {
            var fileSize = fileInput.files[0].size;
            if (fileSize > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('Screenshot file size must be less than 5MB.');
                return false;
            }
        }

        // Disable submit button to prevent double submission
        $('#submitPaymentBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
    });
});
</script>

<?php require_once '../includes/student_footer.php'; ?>
