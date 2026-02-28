<?php
/**
 * View and Manage Fee Structure
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireRole(['sysadmin', 'admin']);

$pageTitle = 'Fee Structure';
$error = '';
$success = '';

$db = getDB();

// Get selected academic session
$selectedSession = getSelectedSession();

// Determine fee mode
$feeMode = getFeeMode();
$academicMonths = getAcademicMonths();

// Fetch all fee structures (annual)
$feeStructures = $db->fetchAll("
    SELECT
        fs.*,
        c.class_name
    FROM fee_structure fs
    JOIN classes c ON fs.class_id = c.class_id
    WHERE fs.academic_year = :year AND fs.status = 'active'
    ORDER BY c.class_numeric
", ['year' => $selectedSession]);

// Fetch monthly fee structures if in monthly mode
$monthlyFeeStructures = [];
$monthlyByClass = [];
if ($feeMode === 'monthly') {
    $monthlyFeeStructures = $db->fetchAll("
        SELECT
            mfs.*,
            c.class_name,
            c.class_numeric
        FROM monthly_fee_structure mfs
        JOIN classes c ON mfs.class_id = c.class_id
        WHERE mfs.academic_year = :year AND mfs.status = 'active'
        ORDER BY c.class_numeric, mfs.fee_month
    ", ['year' => $selectedSession]);

    foreach ($monthlyFeeStructures as $mfs) {
        $monthlyByClass[$mfs['class_id']]['class_name'] = $mfs['class_name'];
        $monthlyByClass[$mfs['class_id']]['months'][$mfs['fee_month']] = $mfs;
    }
}

// Handle form submission for updating fee structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update') {
            $fee_structure_id = (int)$_POST['fee_structure_id'];
            $tuition_fee = (float)$_POST['tuition_fee'];
            $exam_fee = (float)$_POST['exam_fee'];
            $library_fee = (float)$_POST['library_fee'];
            $sports_fee = (float)$_POST['sports_fee'];
            $lab_fee = (float)$_POST['lab_fee'];
            $transport_fee = (float)$_POST['transport_fee'];
            $other_charges = (float)$_POST['other_charges'];

            $query = "UPDATE fee_structure SET
                tuition_fee = :tuition_fee,
                exam_fee = :exam_fee,
                library_fee = :library_fee,
                sports_fee = :sports_fee,
                lab_fee = :lab_fee,
                transport_fee = :transport_fee,
                other_charges = :other_charges
                WHERE fee_structure_id = :id
            ";

            $db->query($query, [
                'tuition_fee' => $tuition_fee,
                'exam_fee' => $exam_fee,
                'library_fee' => $library_fee,
                'sports_fee' => $sports_fee,
                'lab_fee' => $lab_fee,
                'transport_fee' => $transport_fee,
                'other_charges' => $other_charges,
                'id' => $fee_structure_id
            ]);

            redirectWithMessage('view_fee_structure.php', 'success', 'Fee structure updated successfully!');

        } elseif ($_POST['action'] === 'add') {
            $class_id = (int)$_POST['class_id'];
            $tuition_fee = (float)$_POST['tuition_fee'];
            $exam_fee = (float)$_POST['exam_fee'];
            $library_fee = (float)$_POST['library_fee'];
            $sports_fee = (float)$_POST['sports_fee'];
            $lab_fee = (float)$_POST['lab_fee'];
            $transport_fee = (float)$_POST['transport_fee'];
            $other_charges = (float)$_POST['other_charges'];

            // Check if fee structure already exists
            $existing = $db->fetchOne(
                "SELECT fee_structure_id FROM fee_structure WHERE class_id = :class_id AND academic_year = :year",
                ['class_id' => $class_id, 'year' => $selectedSession]
            );

            if ($existing) {
                throw new Exception('Fee structure for this class already exists.');
            }

            $query = "INSERT INTO fee_structure (
                class_id, academic_year, tuition_fee, exam_fee, library_fee,
                sports_fee, lab_fee, transport_fee, other_charges
            ) VALUES (
                :class_id, :academic_year, :tuition_fee, :exam_fee, :library_fee,
                :sports_fee, :lab_fee, :transport_fee, :other_charges
            )";

            $db->query($query, [
                'class_id' => $class_id,
                'academic_year' => $selectedSession,
                'tuition_fee' => $tuition_fee,
                'exam_fee' => $exam_fee,
                'library_fee' => $library_fee,
                'sports_fee' => $sports_fee,
                'lab_fee' => $lab_fee,
                'transport_fee' => $transport_fee,
                'other_charges' => $other_charges
            ]);

            redirectWithMessage('view_fee_structure.php', 'success', 'Fee structure added successfully!');

        } elseif ($_POST['action'] === 'delete' && isSysAdmin()) {
            $fee_structure_id = (int)$_POST['fee_structure_id'];

            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token.');
            }

            // Check if any payments reference this fee structure
            $paymentCount = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM fee_collection WHERE fee_structure_id = :id",
                ['id' => $fee_structure_id]
            );

            if ($paymentCount && $paymentCount['cnt'] > 0) {
                throw new Exception('Cannot delete: this fee structure has ' . $paymentCount['cnt'] . ' payment record(s) linked to it.');
            }

            $db->query(
                "DELETE FROM fee_structure WHERE fee_structure_id = :id",
                ['id' => $fee_structure_id]
            );

            redirectWithMessage('view_fee_structure.php', 'success', 'Fee structure deleted successfully!');

        } elseif ($_POST['action'] === 'save_monthly') {
            $class_id = (int)$_POST['class_id'];
            if ($class_id <= 0) {
                throw new Exception('Please select a class.');
            }

            $db->beginTransaction();
            try {
                foreach ($academicMonths as $monthNum => $monthLabel) {
                    $tuition = (float)($_POST["tuition_fee_m{$monthNum}"] ?? 0);
                    $exam = (float)($_POST["exam_fee_m{$monthNum}"] ?? 0);
                    $library = (float)($_POST["library_fee_m{$monthNum}"] ?? 0);
                    $sports = (float)($_POST["sports_fee_m{$monthNum}"] ?? 0);
                    $lab = (float)($_POST["lab_fee_m{$monthNum}"] ?? 0);
                    $transport = (float)($_POST["transport_fee_m{$monthNum}"] ?? 0);
                    $other = (float)($_POST["other_charges_m{$monthNum}"] ?? 0);

                    $db->query("
                        INSERT INTO monthly_fee_structure
                            (class_id, academic_year, fee_month, month_label,
                             tuition_fee, exam_fee, library_fee, sports_fee,
                             lab_fee, transport_fee, other_charges)
                        VALUES
                            (:class_id, :year, :month, :label,
                             :tuition, :exam, :library, :sports,
                             :lab, :transport, :other)
                        ON DUPLICATE KEY UPDATE
                            tuition_fee = VALUES(tuition_fee),
                            exam_fee = VALUES(exam_fee),
                            library_fee = VALUES(library_fee),
                            sports_fee = VALUES(sports_fee),
                            lab_fee = VALUES(lab_fee),
                            transport_fee = VALUES(transport_fee),
                            other_charges = VALUES(other_charges),
                            month_label = VALUES(month_label)
                    ", [
                        'class_id' => $class_id, 'year' => $selectedSession,
                        'month' => $monthNum, 'label' => $monthLabel,
                        'tuition' => $tuition, 'exam' => $exam,
                        'library' => $library, 'sports' => $sports,
                        'lab' => $lab, 'transport' => $transport,
                        'other' => $other
                    ]);
                }
                $db->commit();
                redirectWithMessage('view_fee_structure.php', 'success', 'Monthly fee structure saved successfully!');
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get classes without fee structure
$classesWithoutFee = $db->fetchAll("
    SELECT c.*
    FROM classes c
    LEFT JOIN fee_structure fs ON c.class_id = fs.class_id AND fs.academic_year = :year
    WHERE c.status = 'active' AND fs.fee_structure_id IS NULL
    ORDER BY c.class_numeric
", ['year' => $selectedSession]);

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-indian-rupee-sign"></i> Fee Structure Management
            <span class="badge bg-primary float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Fee Mode Toggle -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="fas fa-calendar-alt"></i> <strong>Fee Mode:</strong></span>
                    <div class="form-check form-switch fee-mode-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                               id="feeModeToggle"
                               <?php echo ($feeMode === 'monthly') ? 'checked' : ''; ?>
                               <?php echo (!isSysAdmin()) ? 'disabled title="Only System Administrators can change the fee mode"' : ''; ?>>
                        <label class="form-check-label" for="feeModeToggle">
                            <span id="feeModeToggleLabel">
                                <?php echo ($feeMode === 'monthly') ? 'Monthly Mode' : 'Annual Mode'; ?>
                            </span>
                        </label>
                    </div>
                    <?php if (!isSysAdmin()): ?>
                        <span class="badge bg-info ms-2" title="Only System Administrators can change the fee mode">
                            <i class="fas fa-lock"></i> View Only
                        </span>
                    <?php endif; ?>
                </div>
                <small class="text-muted">
                    <?php if ($feeMode === 'annual'): ?>
                        One fee structure per class for the entire year
                    <?php else: ?>
                        Different fee amounts for each month (April - March)
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($feeMode === 'annual'): ?>
<!-- ==================== ANNUAL MODE ==================== -->

<!-- Add New Fee Structure -->
<?php if (count($classesWithoutFee) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-plus"></i> Add Fee Structure for New Class
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action" value="add">

                    <div class="col-md-3">
                        <label class="form-label-custom">Select Class</label>
                        <select name="class_id" class="form-control form-control-custom" required>
                            <option value="">Select Class</option>
                            <?php foreach($classesWithoutFee as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">Tuition Fee</label>
                        <input type="number" name="tuition_fee" class="form-control form-control-custom" step="0.01" min="0" value="0" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">Exam Fee</label>
                        <input type="number" name="exam_fee" class="form-control form-control-custom" step="0.01" min="0" value="0" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">Library Fee</label>
                        <input type="number" name="library_fee" class="form-control form-control-custom" step="0.01" min="0" value="0" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-secondary w-100 mb-1" onclick="generateRandomFees('add')">
                            <i class="fas fa-random"></i> Random Amounts
                        </button>
                        <button type="button" class="btn btn-sm btn-info w-100" data-bs-toggle="modal" data-bs-target="#addMoreFeesModal">
                            More Fees...
                        </button>
                    </div>

                    <!-- Hidden fields for other fees -->
                    <input type="hidden" name="sports_fee" id="add_sports_fee" value="0">
                    <input type="hidden" name="lab_fee" id="add_lab_fee" value="0">
                    <input type="hidden" name="transport_fee" id="add_transport_fee" value="0">
                    <input type="hidden" name="other_charges" id="add_other_charges" value="0">

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-plus"></i> Add Fee Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Existing Fee Structures -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> Class-wise Fee Structure
            </div>
            <div class="card-body">
                <?php if (count($feeStructures) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Tuition</th>
                                <th>Exam</th>
                                <th>Library</th>
                                <th>Sports</th>
                                <th>Lab</th>
                                <th>Transport</th>
                                <th>Others</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($feeStructures as $fee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($fee['class_name']); ?></strong></td>
                                <td><?php echo formatCurrency($fee['tuition_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['exam_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['library_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['sports_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['lab_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['transport_fee']); ?></td>
                                <td><?php echo formatCurrency($fee['other_charges']); ?></td>
                                <td><strong><?php echo formatCurrency($fee['total_fee']); ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-icon" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $fee['fee_structure_id']; ?>" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (isSysAdmin()): ?>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the fee structure for <?php echo htmlspecialchars($fee['class_name']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="fee_structure_id" value="<?php echo $fee['fee_structure_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $fee['fee_structure_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Fee Structure - <?php echo htmlspecialchars($fee['class_name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="fee_structure_id" value="<?php echo $fee['fee_structure_id']; ?>">

                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Tuition Fee</label>
                                                        <input type="number" name="tuition_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['tuition_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Exam Fee</label>
                                                        <input type="number" name="exam_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['exam_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Library Fee</label>
                                                        <input type="number" name="library_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['library_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Sports Fee</label>
                                                        <input type="number" name="sports_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['sports_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Lab Fee</label>
                                                        <input type="number" name="lab_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['lab_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Transport Fee</label>
                                                        <input type="number" name="transport_fee" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['transport_fee']; ?>" required>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label-custom">Other Charges</label>
                                                        <input type="number" name="other_charges" class="form-control form-control-custom"
                                                               step="0.01" min="0" value="<?php echo $fee['other_charges']; ?>" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary me-auto" onclick="generateRandomFees('edit', 'editModal<?php echo $fee['fee_structure_id']; ?>')">
                                                    <i class="fas fa-random"></i> Random Amounts
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No fee structure defined yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ==================== MONTHLY MODE ==================== -->

<!-- Set Monthly Fee Structure -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-plus"></i> Set Monthly Fee Structure
            </div>
            <div class="card-body monthly-fee-form">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_monthly">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label-custom">Select Class</label>
                            <select name="class_id" id="monthlyClassSelect" class="form-control form-control-custom" required>
                                <option value="">Select Class</option>
                                <?php
                                $allClasses = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");
                                foreach ($allClasses as $cls): ?>
                                <option value="<?php echo $cls['class_id']; ?>">
                                    <?php echo htmlspecialchars($cls['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateRandomFees('monthly')" title="Generate random fee amounts for all months">
                                <i class="fas fa-random"></i> Random Amounts
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="copyFirstMonthBtn" title="Copy April values to all months">
                                <i class="fas fa-copy"></i> Copy April to All
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Tuition</th>
                                    <th>Exam</th>
                                    <th>Library</th>
                                    <th>Sports</th>
                                    <th>Lab</th>
                                    <th>Transport</th>
                                    <th>Others</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($academicMonths as $mNum => $mLabel): ?>
                                <tr>
                                    <td><strong><?php echo $mLabel; ?></strong></td>
                                    <td><input type="number" name="tuition_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="exam_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="library_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="sports_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="lab_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="transport_fee_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><input type="number" name="other_charges_m<?php echo $mNum; ?>" class="form-control form-control-sm monthly-fee-input" data-month="<?php echo $mNum; ?>" step="0.01" min="0" value="0"></td>
                                    <td><strong class="month-total" id="month-total-<?php echo $mNum; ?>">0.00</strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th>Annual Total</th>
                                    <th colspan="7"></th>
                                    <th id="grandMonthlyTotal">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary btn-custom mt-2">
                        <i class="fas fa-save"></i> Save Monthly Fee Structure
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Existing Monthly Fee Structures by Class -->
<?php if (count($monthlyByClass) > 0): ?>
<?php foreach ($monthlyByClass as $classId => $classData): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($classData['class_name']); ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th><th>Tuition</th><th>Exam</th><th>Library</th>
                                <th>Sports</th><th>Lab</th><th>Transport</th><th>Others</th><th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $classAnnualTotal = 0;
                            foreach ($academicMonths as $mNum => $mLabel):
                                $mData = $classData['months'][$mNum] ?? null;
                                if ($mData) $classAnnualTotal += (float)$mData['total_fee'];
                            ?>
                            <tr>
                                <td><strong><?php echo $mLabel; ?></strong></td>
                                <td><?php echo $mData ? formatCurrency($mData['tuition_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['exam_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['library_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['sports_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['lab_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['transport_fee']) : '-'; ?></td>
                                <td><?php echo $mData ? formatCurrency($mData['other_charges']) : '-'; ?></td>
                                <td><strong><?php echo $mData ? formatCurrency($mData['total_fee']) : '-'; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th>Annual Total</th>
                                <th colspan="7"></th>
                                <th><?php echo formatCurrency($classAnnualTotal); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <p class="text-center text-muted mb-0">No monthly fee structures defined yet. Select a class above to start.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Load existing monthly data when class is selected
    $('#monthlyClassSelect').on('change', function() {
        var classId = $(this).val();
        if (!classId) return;

        // Reset all fields first
        $('.monthly-fee-input').val(0);
        calculateMonthlyTotals();

        $.ajax({
            url: '/modules/fee_structure/ajax_get_monthly_fee_structure.php',
            method: 'POST',
            data: { class_id: classId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.months && response.months.length > 0) {
                    response.months.forEach(function(m) {
                        var n = m.fee_month;
                        $('input[name="tuition_fee_m' + n + '"]').val(m.tuition_fee);
                        $('input[name="exam_fee_m' + n + '"]').val(m.exam_fee);
                        $('input[name="library_fee_m' + n + '"]').val(m.library_fee);
                        $('input[name="sports_fee_m' + n + '"]').val(m.sports_fee);
                        $('input[name="lab_fee_m' + n + '"]').val(m.lab_fee);
                        $('input[name="transport_fee_m' + n + '"]').val(m.transport_fee);
                        $('input[name="other_charges_m' + n + '"]').val(m.other_charges);
                    });
                    calculateMonthlyTotals();
                }
            }
        });
    });

    // Auto-calculate monthly row totals
    $(document).on('input', '.monthly-fee-input', function() {
        calculateMonthlyTotals();
    });

    // Copy April values to all months
    $('#copyFirstMonthBtn').on('click', function() {
        var feeTypes = ['tuition_fee_m', 'exam_fee_m', 'library_fee_m', 'sports_fee_m', 'lab_fee_m', 'transport_fee_m', 'other_charges_m'];
        feeTypes.forEach(function(prefix) {
            var aprilVal = $('input[name="' + prefix + '1"]').val() || 0;
            for (var i = 2; i <= 12; i++) {
                $('input[name="' + prefix + i + '"]').val(aprilVal);
            }
        });
        calculateMonthlyTotals();
    });

    function calculateMonthlyTotals() {
        var grandTotal = 0;
        var feeTypes = ['tuition_fee_m', 'exam_fee_m', 'library_fee_m', 'sports_fee_m', 'lab_fee_m', 'transport_fee_m', 'other_charges_m'];

        for (var n = 1; n <= 12; n++) {
            var rowTotal = 0;
            feeTypes.forEach(function(prefix) {
                rowTotal += parseFloat($('input[name="' + prefix + n + '"]').val() || 0);
            });
            $('#month-total-' + n).text(rowTotal.toFixed(2));
            grandTotal += rowTotal;
        }
        $('#grandMonthlyTotal').text(grandTotal.toFixed(2));
    }
});
</script>

<?php endif; ?>

<!-- Fee Mode Toggle Script (always active) -->
<script>
$(document).ready(function() {
    $('#feeModeToggle').on('change', function() {
        var isMonthly = $(this).is(':checked');
        var newMode = isMonthly ? 'monthly' : 'annual';
        var $toggle = $(this);
        $toggle.prop('disabled', true);
        $('#feeModeToggleLabel').text('Switching...');

        $.ajax({
            url: 'ajax_save_fee_mode.php',
            method: 'POST',
            data: { fee_mode: newMode },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reload page to show the correct view
                    window.location.reload();
                } else {
                    alert(response.message || 'Failed to change fee mode.');
                    // Revert toggle
                    $toggle.prop('checked', !isMonthly);
                    $toggle.prop('disabled', false);
                    $('#feeModeToggleLabel').text(isMonthly ? 'Annual Mode' : 'Monthly Mode');
                }
            },
            error: function() {
                alert('Error changing fee mode. Please try again.');
                $toggle.prop('checked', !isMonthly);
                $toggle.prop('disabled', false);
                $('#feeModeToggleLabel').text(isMonthly ? 'Annual Mode' : 'Monthly Mode');
            }
        });
    });
});
</script>

<!-- Random Fee Generation Script -->
<script>
/**
 * Generate random fee amounts for testing/demo purposes
 */
function generateRandomFees(mode, modalId = null) {
    // Random amount ranges for different fee types
    const feeRanges = {
        tuition_fee: { min: 2000, max: 10000 },
        exam_fee: { min: 200, max: 2000 },
        library_fee: { min: 100, max: 1000 },
        sports_fee: { min: 100, max: 1500 },
        lab_fee: { min: 200, max: 2000 },
        transport_fee: { min: 500, max: 3000 },
        other_charges: { min: 100, max: 1000 }
    };

    // Generate random amount within range
    function randomAmount(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    // Round to nearest 50 or 100 for cleaner numbers
    function roundAmount(amount) {
        return Math.round(amount / 50) * 50;
    }

    if (mode === 'add') {
        // For add form (annual mode)
        $('input[name="tuition_fee"]').val(roundAmount(randomAmount(feeRanges.tuition_fee.min, feeRanges.tuition_fee.max)));
        $('input[name="exam_fee"]').val(roundAmount(randomAmount(feeRanges.exam_fee.min, feeRanges.exam_fee.max)));
        $('input[name="library_fee"]').val(roundAmount(randomAmount(feeRanges.library_fee.min, feeRanges.library_fee.max)));

        // Set hidden fields (shown in "More Fees" modal)
        $('#add_sports_fee').val(roundAmount(randomAmount(feeRanges.sports_fee.min, feeRanges.sports_fee.max)));
        $('#add_lab_fee').val(roundAmount(randomAmount(feeRanges.lab_fee.min, feeRanges.lab_fee.max)));
        $('#add_transport_fee').val(roundAmount(randomAmount(feeRanges.transport_fee.min, feeRanges.transport_fee.max)));
        $('#add_other_charges').val(roundAmount(randomAmount(feeRanges.other_charges.min, feeRanges.other_charges.max)));

        // Also update the "More Fees" modal fields if they exist
        $('#modal_sports_fee').val($('#add_sports_fee').val());
        $('#modal_lab_fee').val($('#add_lab_fee').val());
        $('#modal_transport_fee').val($('#add_transport_fee').val());
        $('#modal_other_charges').val($('#add_other_charges').val());
    }
    else if (mode === 'edit' && modalId) {
        // For edit modal
        $(`#${modalId} input[name="tuition_fee"]`).val(roundAmount(randomAmount(feeRanges.tuition_fee.min, feeRanges.tuition_fee.max)));
        $(`#${modalId} input[name="exam_fee"]`).val(roundAmount(randomAmount(feeRanges.exam_fee.min, feeRanges.exam_fee.max)));
        $(`#${modalId} input[name="library_fee"]`).val(roundAmount(randomAmount(feeRanges.library_fee.min, feeRanges.library_fee.max)));
        $(`#${modalId} input[name="sports_fee"]`).val(roundAmount(randomAmount(feeRanges.sports_fee.min, feeRanges.sports_fee.max)));
        $(`#${modalId} input[name="lab_fee"]`).val(roundAmount(randomAmount(feeRanges.lab_fee.min, feeRanges.lab_fee.max)));
        $(`#${modalId} input[name="transport_fee"]`).val(roundAmount(randomAmount(feeRanges.transport_fee.min, feeRanges.transport_fee.max)));
        $(`#${modalId} input[name="other_charges"]`).val(roundAmount(randomAmount(feeRanges.other_charges.min, feeRanges.other_charges.max)));
    }
    else if (mode === 'monthly') {
        // For monthly mode - all visible fee inputs in the table
        $('input[name^="tuition_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.tuition_fee.min, feeRanges.tuition_fee.max)));
        });
        $('input[name^="exam_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.exam_fee.min, feeRanges.exam_fee.max)));
        });
        $('input[name^="library_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.library_fee.min, feeRanges.library_fee.max)));
        });
        $('input[name^="sports_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.sports_fee.min, feeRanges.sports_fee.max)));
        });
        $('input[name^="lab_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.lab_fee.min, feeRanges.lab_fee.max)));
        });
        $('input[name^="transport_fee"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.transport_fee.min, feeRanges.transport_fee.max)));
        });
        $('input[name^="other_charges"]').each(function() {
            $(this).val(roundAmount(randomAmount(feeRanges.other_charges.min, feeRanges.other_charges.max)));
        });

        // Trigger calculation if it exists
        if (typeof calculateMonthlyTotals === 'function') {
            calculateMonthlyTotals();
        }
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
