<?php
/**
 * Fee Collection - Collect Fee from Students
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/fee_type_helper.php';

requireLogin();

$pageTitle = 'Collect Fee';
$error = '';
$success = '';

$db = getDB();
$currentYear = getSelectedSession();
$feeMode = getFeeMode();
$academicMonths = getAcademicMonths();

// Load all active fee types; build lookup keyed by column_name
$allActiveFeeTypes = getAllFeeTypes(); // returns only is_active = 1
$activeFeeTypeMap  = array_column($allActiveFeeTypes, null, 'column_name');

// Custom (non-system) active fee types for dynamic rendering
$customFeeTypes = array_values(array_filter($allActiveFeeTypes, function($t) {
    return !$t['is_system_defined'];
}));

// Fetch students for selected session
$students = $db->fetchAll("
    SELECT
        s.*,
        CONCAT(s.first_name, ' ', s.last_name) as full_name,
        c.class_name,
        sec.section_name
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    JOIN sections sec ON s.section_id = sec.section_id
    WHERE s.status = 'active' AND s.academic_year = :academic_year
    ORDER BY c.class_numeric, sec.section_name, s.first_name
", ['academic_year' => $currentYear]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = (int)$_POST['student_id'];
        $fee_structure_id = !empty($_POST['fee_structure_id']) ? (int)$_POST['fee_structure_id'] : null;
        $payment_date = sanitize($_POST['payment_date']);
        $tuition_fee_paid   = (float)($_POST['tuition_fee_paid']   ?? 0);
        $exam_fee_paid      = (float)($_POST['exam_fee_paid']      ?? 0);
        $library_fee_paid   = (float)($_POST['library_fee_paid']   ?? 0);
        $sports_fee_paid    = (float)($_POST['sports_fee_paid']    ?? 0);
        $lab_fee_paid       = (float)($_POST['lab_fee_paid']       ?? 0);
        $transport_fee_paid = (float)($_POST['transport_fee_paid'] ?? 0);
        $other_charges_paid = (float)($_POST['other_charges_paid'] ?? 0);
        $fine = (float)($_POST['fine'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $payment_mode = sanitize($_POST['payment_mode']);
        $transaction_id = sanitize($_POST['transaction_id'] ?? '');
        $remarks = sanitize($_POST['remarks'] ?? '');

        // Collect custom fee type amounts dynamically
        $customFeeAmounts = [];
        foreach ($customFeeTypes as $ft) {
            $colPaid = $ft['column_name'] . '_paid';
            $customFeeAmounts[$colPaid] = (float)($_POST[$colPaid] ?? 0);
        }

        // Validate common required fields
        if (empty($student_id) || empty($payment_date) || empty($payment_mode)) {
            throw new Exception('Please fill all required fields.');
        }

        // Monthly mode validation
        $fee_month = null;
        $monthly_fee_structure_id = null;
        if ($feeMode === 'monthly') {
            $fee_month = (int)($_POST['fee_month'] ?? 0);
            $monthly_fee_structure_id = (int)($_POST['monthly_fee_structure_id'] ?? 0);
            if ($fee_month < 1 || $fee_month > 12) {
                throw new Exception('Please select a valid fee month.');
            }
            if ($monthly_fee_structure_id <= 0) {
                throw new Exception('Monthly fee structure not loaded. Please select a month.');
            }
        } else {
            // Annual mode: fee_structure_id is required
            if (empty($fee_structure_id)) {
                throw new Exception('Fee structure not loaded. Please select a student again.');
            }
        }

        // Generate unique receipt number
        $receipt_no = generateReceiptNo();

        // Check if receipt number is unique
        $existingReceipt = $db->fetchOne(
            "SELECT receipt_no FROM fee_collection WHERE receipt_no = :receipt_no",
            ['receipt_no' => $receipt_no]
        );

        if ($existingReceipt) {
            $receipt_no = generateReceiptNo() . '-' . rand(10, 99);
        }

        // Build dynamic column/value/param strings for custom fee types
        $customColsSql = '';
        $customValsSql = '';
        foreach ($customFeeTypes as $ft) {
            $customColsSql .= ", `{$ft['column_name']}_paid`";
            $customValsSql .= ", :{$ft['column_name']}_paid";
        }

        // Insert payment record
        $query = "INSERT INTO fee_collection (
            receipt_no, student_id, academic_year, fee_structure_id, payment_date,
            tuition_fee_paid, exam_fee_paid, library_fee_paid, sports_fee_paid,
            lab_fee_paid, transport_fee_paid, other_charges_paid{$customColsSql}, fine, discount,
            payment_mode, transaction_id, remarks, collected_by,
            fee_month, monthly_fee_structure_id
        ) VALUES (
            :receipt_no, :student_id, :academic_year, :fee_structure_id, :payment_date,
            :tuition_fee_paid, :exam_fee_paid, :library_fee_paid, :sports_fee_paid,
            :lab_fee_paid, :transport_fee_paid, :other_charges_paid{$customValsSql}, :fine, :discount,
            :payment_mode, :transaction_id, :remarks, :collected_by,
            :fee_month, :monthly_fee_structure_id
        )";

        $db->query($query, array_merge([
            'receipt_no' => $receipt_no,
            'student_id' => $student_id,
            'academic_year' => $currentYear,
            'fee_structure_id' => $fee_structure_id,
            'payment_date' => $payment_date,
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
            'transaction_id' => $transaction_id,
            'remarks' => $remarks,
            'collected_by' => getAdminId(),
            'fee_month' => $fee_month,
            'monthly_fee_structure_id' => $monthly_fee_structure_id
        ], $customFeeAmounts));

        $payment_id = $db->lastInsertId();

        // Redirect to receipt page
        header("Location: receipt.php?id=" . $payment_id);
        exit();

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-money-bill-wave"></i> Collect Fee
            <span class="badge bg-primary float-end"><?php echo htmlspecialchars($currentYear); ?></span>
        </h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                Fee Payment Form
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="feeCollectionForm">
                    <!-- Student Search Section -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label-custom">Search Student <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="student_search" class="form-control form-control-custom"
                                       placeholder="Enter admission no, name, or roll no...">
                                <button type="button" id="searchStudentBtn" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <small class="text-muted">Enter admission number, student name, or roll number and click Search</small>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div id="searchResults" class="mb-3" style="display:none;">
                        <div class="card">
                            <div class="card-header py-2">
                                <strong><i class="fas fa-list"></i> Search Results</strong>
                                <span id="resultCount" class="badge bg-primary ms-2"></span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Admission No</th>
                                                <th>Name</th>
                                                <th>Class</th>
                                                <th>Roll No</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="searchResultsBody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Student Display -->
                    <div id="selectedStudentInfo" class="alert alert-success mb-3" style="display:none;">
                        <strong><i class="fas fa-user-check"></i> Selected Student: </strong>
                        <span id="selectedStudentName"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger float-end" id="clearStudent">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>

                    <input type="hidden" name="student_id" id="student_id" required>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-custom max-today"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-control form-control-custom" required>
                                <option value="">Select Mode</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Net Banking">Net Banking</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                            <div id="paymentModeError" class="invalid-feedback">Please select a Payment Mode.</div>
                        </div>
                    </div>

                    <?php if ($feeMode === 'monthly'): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label-custom">Fee Month <span class="text-danger">*</span></label>
                            <select name="fee_month" id="fee_month" class="form-control form-control-custom" required>
                                <option value="">Select Month</option>
                                <?php foreach ($academicMonths as $mNum => $mLabel): ?>
                                <option value="<?php echo $mNum; ?>"><?php echo $mLabel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="monthly_fee_structure_id" id="monthly_fee_structure_id">
                    <?php endif; ?>

                    <input type="hidden" name="fee_structure_id" id="fee_structure_id">

                    <hr>
                    <h5 class="mb-3">Fee Details</h5>

                    <div class="row">
                        <?php if (isset($activeFeeTypeMap['tuition_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['tuition_fee']['label']); ?></label>
                            <input type="number" name="tuition_fee_paid" id="tuition_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['exam_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['exam_fee']['label']); ?></label>
                            <input type="number" name="exam_fee_paid" id="exam_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['library_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['library_fee']['label']); ?></label>
                            <input type="number" name="library_fee_paid" id="library_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['sports_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['sports_fee']['label']); ?></label>
                            <input type="number" name="sports_fee_paid" id="sports_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['lab_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['lab_fee']['label']); ?></label>
                            <input type="number" name="lab_fee_paid" id="lab_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['transport_fee'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['transport_fee']['label']); ?></label>
                            <input type="number" name="transport_fee_paid" id="transport_fee_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php if (isset($activeFeeTypeMap['other_charges'])): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($activeFeeTypeMap['other_charges']['label']); ?></label>
                            <input type="number" name="other_charges_paid" id="other_charges_paid"
                                   class="form-control form-control-custom fee-type-input" step="0.01" min="0" value="0">
                        </div>
                        <?php endif; ?>

                        <?php foreach ($customFeeTypes as $ft): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom"><?php echo htmlspecialchars($ft['label']); ?></label>
                            <input type="number"
                                   name="<?php echo $ft['column_name']; ?>_paid"
                                   id="<?php echo $ft['column_name']; ?>_paid"
                                   class="form-control form-control-custom fee-type-input"
                                   step="0.01" min="0" value="0">
                        </div>
                        <?php endforeach; ?>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Fine (if any)</label>
                            <input type="number" name="fine" id="fine"
                                   class="form-control form-control-custom" step="0.01" min="0" value="0">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Discount</label>
                            <input type="number" name="discount" id="discount"
                                   class="form-control form-control-custom" step="0.01" min="0" value="0">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label-custom">Total Amount</label>
                            <input type="number" id="total_paid" class="form-control form-control-custom"
                                   step="0.01" readonly value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Transaction ID / Cheque No</label>
                            <input type="text" name="transaction_id" class="form-control form-control-custom">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Remarks</label>
                            <input type="text" name="remarks" class="form-control form-control-custom">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="reviewTotalBtn" class="btn btn-info btn-custom">
                            <i class="fas fa-eye"></i> Review Total Amount
                        </button>
                        <button type="submit" class="btn btn-success btn-custom">
                            <i class="fas fa-save"></i> Collect Fee & Generate Receipt
                        </button>
                        <a href="view_payments.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Review Total Amount Modal -->
<div class="modal fade" id="reviewTotalModal" tabindex="-1" aria-labelledby="reviewTotalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="reviewTotalModalLabel">
                    <i class="fas fa-receipt"></i> Review Total Amount
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col-12 mb-4">
                        <small class="text-muted d-block mb-2">Payment Summary</small>
                        <div class="card border-primary">
                            <div class="card-body">
                                <h2 class="text-primary mb-0" id="modalTotalAmount">₹ 0.00</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3 border-light">
                    <div class="card-header bg-light">
                        <strong>Fee Breakdown</strong>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody id="feeBreakdownTable">
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    <strong>Please review the amount before proceeding.</strong><br>
                    <small>Click "Proceed to Collect" to finalize the payment and generate receipt.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="proceedCollectBtn">
                    <i class="fas fa-check"></i> Proceed to Collect
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// PHP-generated custom fee types for JS
var customFeeTypes = <?php echo json_encode(array_map(function($t) {
    return ['column_name' => $t['column_name'], 'label' => $t['label']];
}, $customFeeTypes)); ?>;

$(document).ready(function() {

    // ── Calculate total fee ────────────────────────────────────────────────────
    function calculateTotalFee() {
        var total = 0;
        $('.fee-type-input').each(function() {
            total += parseFloat($(this).val() || 0);
        });
        total += parseFloat($('#fine').val() || 0);
        total -= parseFloat($('#discount').val() || 0);
        if (total < 0) total = 0;
        $('#total_paid').val(total.toFixed(2));
    }

    // Auto-recalculate whenever any fee input changes
    $(document).on('input', '.fee-type-input, #fine, #discount', function() {
        calculateTotalFee();
    });

    // Student data from PHP
    var allStudents = [
        <?php foreach($students as $student): ?>
        {
            id: <?php echo $student['student_id']; ?>,
            admission: '<?php echo addslashes($student['admission_no']); ?>',
            name: '<?php echo addslashes($student['full_name']); ?>',
            className: '<?php echo addslashes($student['class_name']); ?>',
            section: '<?php echo addslashes($student['section_name']); ?>',
            roll: '<?php echo addslashes($student['roll_number'] ?? ''); ?>',
            classId: <?php echo $student['class_id']; ?>
        },
        <?php endforeach; ?>
    ];

    // Search button click
    $('#searchStudentBtn').on('click', function() {
        performSearch();
    });

    // Search on Enter key
    $('#student_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            performSearch();
        }
    });

    function performSearch() {
        var searchText = $('#student_search').val().toLowerCase().trim();
        if (searchText === '') {
            alert('Please enter admission number, name, or roll number to search.');
            return;
        }

        var results = allStudents.filter(function(s) {
            return s.admission.toLowerCase().indexOf(searchText) !== -1 ||
                   s.name.toLowerCase().indexOf(searchText) !== -1 ||
                   s.roll.toLowerCase().indexOf(searchText) !== -1;
        });

        var $tbody = $('#searchResultsBody');
        $tbody.empty();

        if (results.length > 0) {
            results.forEach(function(s) {
                $tbody.append(
                    '<tr>' +
                    '<td>' + escapeHtml(s.admission) + '</td>' +
                    '<td>' + escapeHtml(s.name) + '</td>' +
                    '<td>' + escapeHtml(s.className + ' - ' + s.section) + '</td>' +
                    '<td>' + escapeHtml(s.roll || '-') + '</td>' +
                    '<td><button type="button" class="btn btn-sm btn-success select-student-btn" ' +
                    'data-id="' + s.id + '" data-class-id="' + s.classId + '" ' +
                    'data-label="' + escapeHtml(s.admission + ' - ' + s.name + ' (' + s.className + ' - ' + s.section + ')') + '">' +
                    '<i class="fas fa-check"></i> Select</button></td>' +
                    '</tr>'
                );
            });
            $('#resultCount').text(results.length + ' found');
        } else {
            $tbody.append('<tr><td colspan="5" class="text-center text-muted">No students found matching your search.</td></tr>');
            $('#resultCount').text('0 found');
        }
        $('#searchResults').show();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Select student from results
    $(document).on('click', '.select-student-btn', function() {
        var studentId = $(this).data('id');
        var classId = $(this).data('class-id');
        var label = $(this).data('label');

        $('#student_id').val(studentId);
        $('#selectedStudentName').text(label);
        $('#selectedStudentInfo').show();
        $('#searchResults').hide();

        <?php if ($feeMode === 'annual'): ?>
        // Annual mode: Load fee structure directly
        loadFeeStructure(classId);
        <?php else: ?>
        // Monthly mode: Store class ID for monthly fee loading
        window.selectedClassId = classId;
        // If month already selected, load monthly fee
        if ($('#fee_month').val()) {
            loadMonthlyFee();
        }
        <?php endif; ?>
    });

    <?php if ($feeMode === 'monthly'): ?>
    // Monthly mode: load fee on month change
    $('#fee_month').on('change', function() {
        var studentId = $('#student_id').val();
        if (studentId && $(this).val()) {
            loadMonthlyFee();
        }
    });

    function loadMonthlyFee() {
        var classId = window.selectedClassId;
        var feeMonth = $('#fee_month').val();
        if (!classId || !feeMonth) return;

        $.ajax({
            url: '/modules/fee_structure/ajax_get_monthly_fee_structure.php',
            method: 'POST',
            data: { class_id: classId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var monthData = response.months.find(function(m) {
                        return m.fee_month == feeMonth;
                    });
                    if (monthData) {
                        $('#tuition_fee_paid').val(monthData.tuition_fee || 0);
                        $('#exam_fee_paid').val(monthData.exam_fee || 0);
                        $('#library_fee_paid').val(monthData.library_fee || 0);
                        $('#sports_fee_paid').val(monthData.sports_fee || 0);
                        $('#lab_fee_paid').val(monthData.lab_fee || 0);
                        $('#transport_fee_paid').val(monthData.transport_fee || 0);
                        $('#other_charges_paid').val(monthData.other_charges || 0);
                        $('#monthly_fee_structure_id').val(monthData.monthly_fee_id);
                        // Populate custom fee types from monthly structure
                        customFeeTypes.forEach(function(ft) {
                            $('#' + ft.column_name + '_paid').val(monthData[ft.column_name] || 0);
                        });
                        calculateTotalFee();
                    } else {
                        alert('Monthly fee structure not defined for this month. Please set it up first.');
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Clear student selection
    $('#clearStudent').on('click', function() {
        $('#student_id').val('');
        $('#selectedStudentInfo').hide();
        $('#fee_structure_id').val('');
        $('#tuition_fee_paid').val(0);
        $('#exam_fee_paid').val(0);
        $('#library_fee_paid').val(0);
        $('#sports_fee_paid').val(0);
        $('#lab_fee_paid').val(0);
        $('#transport_fee_paid').val(0);
        $('#other_charges_paid').val(0);
        // Clear custom fee type fields
        customFeeTypes.forEach(function(ft) {
            $('#' + ft.column_name + '_paid').val(0);
        });
        $('#fine').val(0);
        $('#discount').val(0);
        $('#total_paid').val(0);
        $('#student_search').val('').focus();
    });

    function loadFeeStructure(classId) {
        $.ajax({
            url: '/modules/fee_structure/ajax_get_fee_structure.php',
            method: 'POST',
            data: { class_id: classId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const fee = response.fee_structure;
                    $('#tuition_fee_paid').val(fee.tuition_fee || 0);
                    $('#exam_fee_paid').val(fee.exam_fee || 0);
                    $('#library_fee_paid').val(fee.library_fee || 0);
                    $('#sports_fee_paid').val(fee.sports_fee || 0);
                    $('#lab_fee_paid').val(fee.lab_fee || 0);
                    $('#transport_fee_paid').val(fee.transport_fee || 0);
                    $('#other_charges_paid').val(fee.other_charges || 0);
                    $('#fee_structure_id').val(fee.fee_structure_id);
                    // Populate custom fee types from fee structure
                    customFeeTypes.forEach(function(ft) {
                        $('#' + ft.column_name + '_paid').val(fee[ft.column_name] || 0);
                    });
                    calculateTotalFee();
                } else {
                    alert('Fee structure not found for this class. Please contact administrator.');
                }
            },
            error: function() {
                alert('Error loading fee structure. Please try again.');
            }
        });
    }

    // Prevent form submission if student not selected
    $('#feeCollectionForm').on('submit', function(e) {
        var studentId = $('#student_id').val();
        if (!studentId || studentId === '') {
            e.preventDefault();
            alert('Please search and select a student first.');
            return false;
        }
        // Payment mode inline validation
        if (!$('#payment_mode').val()) {
            e.preventDefault();
            $('#payment_mode').addClass('is-invalid').focus();
            return false;
        }
        <?php if ($feeMode === 'annual'): ?>
        var feeStructureId = $('#fee_structure_id').val();
        if (!feeStructureId || feeStructureId === '' || feeStructureId === '0') {
            e.preventDefault();
            alert('Fee structure not loaded. Please select a student again.');
            return false;
        }
        <?php else: ?>
        var feeMonth = $('#fee_month').val();
        if (!feeMonth || feeMonth === '') {
            e.preventDefault();
            alert('Please select a fee month.');
            return false;
        }
        var monthlyFsId = $('#monthly_fee_structure_id').val();
        if (!monthlyFsId || monthlyFsId === '' || monthlyFsId === '0') {
            e.preventDefault();
            alert('Monthly fee structure not loaded. Please select a month.');
            return false;
        }
        <?php endif; ?>
    });

    // Clear payment mode inline error when user makes a selection
    $('#payment_mode').on('change', function() {
        $(this).removeClass('is-invalid');
    });

    // Review Total Amount button
    $('#reviewTotalBtn').on('click', function() {
        calculateTotalFee();

        // 1. Check student selected
        var studentId = $('#student_id').val();
        if (!studentId || studentId === '') {
            alert('Please search and select a student first.');
            return;
        }

        // 2. Payment mode — inline error below dropdown
        if (!$('#payment_mode').val()) {
            $('#payment_mode').addClass('is-invalid').focus();
            return;
        }

        // 3. Fee structure checks — same as Collect Fee button
        <?php if ($feeMode === 'annual'): ?>
        var feeStructureId = $('#fee_structure_id').val();
        if (!feeStructureId || feeStructureId === '' || feeStructureId === '0') {
            alert('Fee structure not loaded. Please select a student again.');
            return;
        }
        <?php else: ?>
        var feeMonth = $('#fee_month').val();
        if (!feeMonth || feeMonth === '') {
            alert('Please select a fee month.');
            return;
        }
        var monthlyFsId = $('#monthly_fee_structure_id').val();
        if (!monthlyFsId || monthlyFsId === '' || monthlyFsId === '0') {
            alert('Monthly fee structure not loaded. Please select a month.');
            return;
        }
        <?php endif; ?>

        // Build fee breakdown table
        var breakdownHtml = '';
        var feeItems = [
            { name: 'Tuition Fee', id: 'tuition_fee_paid' },
            { name: 'Exam Fee', id: 'exam_fee_paid' },
            { name: 'Library Fee', id: 'library_fee_paid' },
            { name: 'Sports Fee', id: 'sports_fee_paid' },
            { name: 'Lab Fee', id: 'lab_fee_paid' },
            { name: 'Transport Fee', id: 'transport_fee_paid' },
            { name: 'Other Charges', id: 'other_charges_paid' }
        ];

        // Append custom fee types to breakdown list
        customFeeTypes.forEach(function(ft) {
            feeItems.push({ name: ft.label, id: ft.column_name + '_paid' });
        });

        feeItems.forEach(function(item) {
            var amount = parseFloat($('#' + item.id).val() || 0);
            if (amount > 0) {
                breakdownHtml += '<tr><td class="text-start">' + escapeHtml(item.name) + '</td>';
                breakdownHtml += '<td class="text-end"><strong>₹ ' + amount.toFixed(2) + '</strong></td></tr>';
            }
        });

        var fine = parseFloat($('#fine').val() || 0);
        if (fine > 0) {
            breakdownHtml += '<tr class="table-warning"><td class="text-start">Fine</td>';
            breakdownHtml += '<td class="text-end"><strong>₹ ' + fine.toFixed(2) + '</strong></td></tr>';
        }

        var discount = parseFloat($('#discount').val() || 0);
        if (discount > 0) {
            breakdownHtml += '<tr class="table-info"><td class="text-start">Discount</td>';
            breakdownHtml += '<td class="text-end"><strong>- ₹ ' + discount.toFixed(2) + '</strong></td></tr>';
        }

        var total = parseFloat($('#total_paid').val() || 0);
        breakdownHtml += '<tr class="table-success"><td class="text-start"><strong>Total</strong></td>';
        breakdownHtml += '<td class="text-end"><strong>₹ ' + total.toFixed(2) + '</strong></td></tr>';

        $('#feeBreakdownTable').html(breakdownHtml);
        $('#modalTotalAmount').text('₹ ' + total.toFixed(2));

        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('reviewTotalModal'));
        modal.show();
    });

    // Proceed to Collect button
    $('#proceedCollectBtn').on('click', function() {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('reviewTotalModal')).hide();

        // Submit the form
        document.getElementById('feeCollectionForm').submit();
    });

    // Auto-select student if ?student= parameter is present in URL
    var urlParams = new URLSearchParams(window.location.search);
    var preselectedStudentId = urlParams.get('student');
    if (preselectedStudentId) {
        preselectedStudentId = parseInt(preselectedStudentId);
        var student = allStudents.find(function(s) { return s.id === preselectedStudentId; });
        if (student) {
            var label = student.admission + ' - ' + student.name + ' (' + student.className + ' - ' + student.section + ')';
            $('#student_id').val(student.id);
            $('#student_search').val(student.name);
            $('#selectedStudentName').text(label);
            $('#selectedStudentInfo').show();

            <?php if ($feeMode === 'annual'): ?>
            loadFeeStructure(student.classId);
            <?php else: ?>
            window.selectedClassId = student.classId;
            <?php endif; ?>
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
