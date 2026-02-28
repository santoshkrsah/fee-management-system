<?php
/**
 * Student-wise Fee Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$selectedSession = getSelectedSession();

// Filters
$search = sanitize($_GET['search'] ?? '');
$class_filter = (int)($_GET['class_id'] ?? 0);
$student_id = (int)($_GET['student_id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? '');

$pageTitle = ($status_filter === 'partial') ? 'Partial Paid Students Report' : 'Student-wise Fee Report';

try {
    $db = getDB();

    // Get all students with fee summary
    $query = "
        SELECT
            s.student_id,
            s.admission_no,
            s.roll_number,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.father_name,
            s.contact_number,
            c.class_name,
            sec.section_name,
            COALESCE(MAX(fs.total_fee), 0) as total_fee,
            COALESCE(SUM(fc.total_paid), 0) as total_paid,
            (COALESCE(MAX(fs.total_fee), 0) - COALESCE(SUM(fc.total_paid), 0)) as balance,
            COUNT(fc.payment_id) as payment_count,
            MAX(fc.payment_date) as last_payment_date
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
        LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = :year1
        LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year2
        WHERE s.status = 'active'
    ";

    $params = ['year1' => $selectedSession, 'year2' => $selectedSession];

    if (!empty($search)) {
        $query .= " AND (
            s.admission_no LIKE :search1 OR
            s.first_name LIKE :search2 OR
            s.last_name LIKE :search3 OR
            s.father_name LIKE :search4 OR
            s.roll_number LIKE :search5
        )";
        $searchVal = '%' . $search . '%';
        $params['search1'] = $searchVal;
        $params['search2'] = $searchVal;
        $params['search3'] = $searchVal;
        $params['search4'] = $searchVal;
        $params['search5'] = $searchVal;
    }

    if ($class_filter > 0) {
        $query .= " AND s.class_id = :class_id";
        $params['class_id'] = $class_filter;
    }

    $query .= " GROUP BY s.student_id";

    if ($status_filter === 'partial') {
        $query .= " HAVING COALESCE(SUM(fc.total_paid), 0) > 0 AND COALESCE(SUM(fc.total_paid), 0) < COALESCE(MAX(fs.total_fee), 0)";
    }

    $query .= " ORDER BY c.class_numeric, sec.section_name, s.first_name";

    $students = $db->fetchAll($query, $params);

    // Get classes for filter
    $classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");

    // If specific student selected, get their payment history
    $paymentHistory = [];
    if ($student_id > 0) {
        $paymentHistory = $db->fetchAll("
            SELECT
                fc.receipt_no,
                fc.payment_date,
                fc.tuition_fee_paid,
                fc.exam_fee_paid,
                fc.library_fee_paid,
                fc.sports_fee_paid,
                fc.lab_fee_paid,
                fc.transport_fee_paid,
                fc.other_charges_paid,
                fc.fine,
                fc.discount,
                fc.total_paid,
                fc.payment_mode,
                fc.transaction_id,
                fc.remarks
            FROM fee_collection fc
            WHERE fc.student_id = :student_id AND fc.academic_year = :year
            ORDER BY fc.payment_date DESC, fc.payment_id DESC
        ", ['student_id' => $student_id, 'year' => $selectedSession]);

        // Get student details
        $selectedStudent = $db->fetchOne("
            SELECT
                s.*,
                CONCAT(s.first_name, ' ', s.last_name) as full_name,
                c.class_name,
                sec.section_name,
                COALESCE(fs.total_fee, 0) as total_fee
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = :year
            WHERE s.student_id = :student_id
        ", ['student_id' => $student_id, 'year' => $selectedSession]);
    }

    // Calculate totals
    $totalFee = 0;
    $totalPaid = 0;
    $totalBalance = 0;
    foreach ($students as $student) {
        $totalFee += $student['total_fee'];
        $totalPaid += $student['total_paid'];
        $totalBalance += $student['balance'];
    }

} catch(Exception $e) {
    error_log($e->getMessage());
    $students = [];
    $classes = [];
    $paymentHistory = [];
    $selectedStudent = null;
    $totalFee = $totalPaid = $totalBalance = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <?php if ($status_filter === 'partial'): ?>
                <i class="fas fa-clock text-warning"></i> Partial Paid Students Report
                <span class="badge bg-warning float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
            <?php else: ?>
                <i class="fas fa-user-check"></i> Student-wise Fee Report
                <span class="badge bg-primary float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
            <?php endif; ?>
        </h2>
    </div>
</div>

<!-- Search & Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <?php if ($status_filter): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label-custom">Search Student</label>
                        <input type="text" name="search" class="form-control form-control-custom"
                               placeholder="Admission No, Name, Roll No..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">Filter by Class</label>
                        <select name="class_id" class="form-control form-control-custom">
                            <option value="">All Classes</option>
                            <?php foreach($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"
                                <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-custom">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="student_wise_report.php<?php echo $status_filter ? '?status=' . urlencode($status_filter) : ''; ?>" class="btn btn-secondary btn-custom">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($student_id > 0 && $selectedStudent): ?>
<!-- Individual Student Detail -->
<div class="row mb-4">
    <div class="col-12">
        <a href="student_wise_report.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-custom mb-3">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>

        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-user"></i> Student Details - <?php echo htmlspecialchars($selectedStudent['full_name']); ?>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Admission No:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['admission_no']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Student Name:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['full_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Father Name:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['father_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Class:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['class_name'] . ' - ' . $selectedStudent['section_name']); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Roll No:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['roll_number'] ?? '-'); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Contact:</strong><br>
                        <?php echo htmlspecialchars($selectedStudent['contact_number']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Fee:</strong><br>
                        <span class="text-primary"><strong><?php echo formatCurrency($selectedStudent['total_fee']); ?></strong></span>
                    </div>
                    <div class="col-md-3">
                        <?php
                            $studentTotalPaid = 0;
                            foreach ($paymentHistory as $p) $studentTotalPaid += $p['total_paid'];
                            $studentBalance = $selectedStudent['total_fee'] - $studentTotalPaid;
                        ?>
                        <strong>Balance:</strong><br>
                        <?php if ($studentBalance <= 0): ?>
                            <span class="badge status-paid">FULLY PAID</span>
                        <?php else: ?>
                            <span class="text-danger"><strong><?php echo formatCurrency($studentBalance); ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Payment History (<?php echo count($paymentHistory); ?> payments)</span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('student_wise_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($paymentHistory) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <th>Tuition</th>
                                <th>Exam</th>
                                <th>Library</th>
                                <th>Sports</th>
                                <th>Lab</th>
                                <th>Transport</th>
                                <th>Other</th>
                                <th>Fine</th>
                                <th>Discount</th>
                                <th class="text-end">Total</th>
                                <th>Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paymentHistory as $payment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo formatCurrency($payment['tuition_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['exam_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['library_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['sports_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['lab_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['transport_fee_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['other_charges_paid']); ?></td>
                                <td><?php echo formatCurrency($payment['fine']); ?></td>
                                <td><?php echo formatCurrency($payment['discount']); ?></td>
                                <td class="text-end"><strong><?php echo formatCurrency($payment['total_paid']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($payment['payment_mode']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="11" class="text-end">Total Paid:</th>
                                <th class="text-end"><?php echo formatCurrency($studentTotalPaid); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No payments recorded for this student.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Fee</h6>
                <h3><?php echo formatCurrency($totalFee); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Collected</h6>
                <h3 class="text-success"><?php echo formatCurrency($totalPaid); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Balance</h6>
                <h3 class="text-danger"><?php echo formatCurrency($totalBalance); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> Total Students: <?php echo count($students); ?>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>Class</th>
                                <th>Roll No</th>
                                <th class="text-end">Total Fee</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_fee']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_paid']); ?></td>
                                <td class="text-end">
                                    <strong class="<?php echo $student['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatCurrency($student['balance']); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($student['balance'] <= 0): ?>
                                        <span class="badge status-paid">PAID</span>
                                    <?php elseif ($student['total_paid'] > 0): ?>
                                        <span class="badge status-partial">PARTIAL</span>
                                    <?php else: ?>
                                        <span class="badge status-unpaid">UNPAID</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="student_wise_report.php?student_id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-info btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="5" class="text-end">Total:</th>
                                <th class="text-end"><?php echo formatCurrency($totalFee); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalPaid); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalBalance); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No students found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
