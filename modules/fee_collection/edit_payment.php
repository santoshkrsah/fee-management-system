<?php
/**
 * Edit Fee Payment - Modify paid amounts
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin', 'admin']);

$pageTitle = 'Edit Payment';
$error = '';
$success = '';

$payment_id = (int)($_GET['id'] ?? 0);

if ($payment_id <= 0) {
    redirectWithMessage('view_payments.php', 'error', 'Invalid payment ID.');
}

$db = getDB();

// Fetch existing payment with student info
$payment = $db->fetchOne("
    SELECT
        fc.*,
        s.admission_no,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.father_name,
        c.class_name,
        sec.section_name
    FROM fee_collection fc
    JOIN students s ON fc.student_id = s.student_id
    JOIN classes c ON s.class_id = c.class_id
    JOIN sections sec ON s.section_id = sec.section_id
    WHERE fc.payment_id = :id
", ['id' => $payment_id]);

if (!$payment) {
    redirectWithMessage('view_payments.php', 'error', 'Payment record not found.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tuition_fee_paid = (float)$_POST['tuition_fee_paid'];
        $exam_fee_paid = (float)$_POST['exam_fee_paid'];
        $library_fee_paid = (float)$_POST['library_fee_paid'];
        $sports_fee_paid = (float)$_POST['sports_fee_paid'];
        $lab_fee_paid = (float)$_POST['lab_fee_paid'];
        $transport_fee_paid = (float)$_POST['transport_fee_paid'];
        $other_charges_paid = (float)$_POST['other_charges_paid'];
        $fine = (float)($_POST['fine'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $payment_mode = sanitize($_POST['payment_mode']);
        $payment_date = sanitize($_POST['payment_date']);
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        $remarks = sanitize($_POST['remarks'] ?? '');

        if (empty($payment_mode) || empty($payment_date)) {
            throw new Exception('Payment mode and date are required.');
        }

        $query = "UPDATE fee_collection SET
            tuition_fee_paid = :tuition_fee_paid,
            exam_fee_paid = :exam_fee_paid,
            library_fee_paid = :library_fee_paid,
            sports_fee_paid = :sports_fee_paid,
            lab_fee_paid = :lab_fee_paid,
            transport_fee_paid = :transport_fee_paid,
            other_charges_paid = :other_charges_paid,
            fine = :fine,
            discount = :discount,
            payment_mode = :payment_mode,
            payment_date = :payment_date,
            transaction_id = :transaction_id,
            remarks = :remarks
            WHERE payment_id = :payment_id";

        $db->query($query, [
            'tuition_fee_paid' => $tuition_fee_paid,
            'exam_fee_paid' => $exam_fee_paid,
            'library_fee_paid' => $library_fee_paid,
            'sports_fee_paid' => $sports_fee_paid,
            'lab_fee_paid' => $lab_fee_paid,
            'transport_fee_paid' => $transport_fee_paid,
            'other_charges_paid' => $other_charges_paid,
            'fine' => $fine,
            'discount' => $discount,
            'payment_mode' => $payment_mode,
            'payment_date' => $payment_date,
            'transaction_id' => $transaction_id,
            'remarks' => $remarks,
            'payment_id' => $payment_id
        ]);

        // Refresh payment data after update
        $payment = $db->fetchOne("
            SELECT
                fc.*,
                s.admission_no,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                s.father_name,
                c.class_name,
                sec.section_name
            FROM fee_collection fc
            JOIN students s ON fc.student_id = s.student_id
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE fc.payment_id = :id
        ", ['id' => $payment_id]);

        $success = 'Payment updated successfully.';

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-edit"></i> Edit Payment
            <a href="view_payments.php" class="btn btn-secondary btn-custom float-end">
                <i class="fas fa-arrow-left"></i> Back to Payments
            </a>
        </h2>
    </div>
</div>

<!-- Student Info Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-user"></i> Student Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Receipt No:</strong><br>
                        <?php echo htmlspecialchars($payment['receipt_no']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Admission No:</strong><br>
                        <?php echo htmlspecialchars($payment['admission_no']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Student Name:</strong><br>
                        <?php echo htmlspecialchars($payment['student_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Class:</strong><br>
                        <?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section_name']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-indian-rupee-sign"></i> Modify Fee Amounts
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="editPaymentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($payment['payment_date']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-control form-control-custom" required>
                                <option value="">Select Mode</option>
                                <option value="Cash" <?php echo ($payment['payment_mode'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="Card" <?php echo ($payment['payment_mode'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                                <option value="UPI" <?php echo ($payment['payment_mode'] == 'UPI') ? 'selected' : ''; ?>>UPI</option>
                                <option value="Net Banking" <?php echo ($payment['payment_mode'] == 'Net Banking') ? 'selected' : ''; ?>>Net Banking</option>
                                <option value="Cheque" <?php echo ($payment['payment_mode'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Fee Details</h5>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Tuition Fee</label>
                            <input type="number" name="tuition_fee_paid" id="tuition_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['tuition_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Exam Fee</label>
                            <input type="number" name="exam_fee_paid" id="exam_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['exam_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Library Fee</label>
                            <input type="number" name="library_fee_paid" id="library_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['library_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Sports Fee</label>
                            <input type="number" name="sports_fee_paid" id="sports_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['sports_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Lab Fee</label>
                            <input type="number" name="lab_fee_paid" id="lab_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['lab_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Transport Fee</label>
                            <input type="number" name="transport_fee_paid" id="transport_fee_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['transport_fee_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Other Charges</label>
                            <input type="number" name="other_charges_paid" id="other_charges_paid"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['other_charges_paid']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Fine</label>
                            <input type="number" name="fine" id="fine"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['fine']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Discount</label>
                            <input type="number" name="discount" id="discount"
                                   class="form-control form-control-custom" step="0.01" min="0"
                                   value="<?php echo $payment['discount']; ?>">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Total Amount</label>
                            <input type="number" id="total_paid" class="form-control form-control-custom"
                                   step="0.01" readonly value="<?php echo $payment['total_paid']; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Transaction ID / Cheque No</label>
                            <input type="text" name="transaction_id" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($payment['transaction_id'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Remarks</label>
                            <input type="text" name="remarks" class="form-control form-control-custom"
                                   value="<?php echo htmlspecialchars($payment['remarks'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="calculateTotal" class="btn btn-info btn-custom">
                            <i class="fas fa-calculator"></i> Calculate Total
                        </button>
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Update Payment
                        </button>
                        <a href="receipt.php?id=<?php echo $payment_id; ?>" class="btn btn-success btn-custom" target="_blank">
                            <i class="fas fa-receipt"></i> View Receipt
                        </a>
                        <a href="view_payments.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
