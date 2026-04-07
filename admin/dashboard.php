<?php
/**
 * Admin Dashboard
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/upi_helper.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard - Fee Management System';

$feeMode = getFeeMode();
$academicMonths = getAcademicMonths();
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';

try {
    $db = getDB();

    // Get statistics
    $stats = [];

    // Total Students
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stats['total_students'] = $result['count'];

    // Total Classes
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM classes WHERE status = 'active'");
    $stats['total_classes'] = $result['count'];

    // Total Collection (selected session)
    $selectedSession = getSelectedSession();

    if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated') {
        // --- Monthly mode: specific month ---
        $monthNum = (int)$selectedMonth;

        $result = $db->fetchOne(
            "SELECT COALESCE(SUM(total_paid), 0) as total FROM fee_collection WHERE academic_year = :year AND fee_month = :month",
            ['year' => $selectedSession, 'month' => $monthNum]
        );
        $stats['total_collection'] = $result['total'];

        // Per-month: compare each student's payment for this month vs monthly fee structure
        // Uses correlated subqueries to avoid cross-join row multiplication
        $paymentStats = $db->fetchOne("
            SELECT
                COUNT(CASE WHEN paid_total > 0 AND paid_total >= fee_total THEN 1 END) as fully_paid,
                COUNT(CASE WHEN paid_total > 0 AND paid_total < fee_total THEN 1 END) as partial_paid
            FROM (
                SELECT
                    s.student_id,
                    COALESCE((
                        SELECT mfs.total_fee
                        FROM monthly_fee_structure mfs
                        WHERE mfs.class_id = s.class_id
                            AND mfs.academic_year = :year1
                            AND mfs.fee_month = :month1
                            AND mfs.status = 'active'
                        LIMIT 1
                    ), 0) as fee_total,
                    COALESCE((
                        SELECT SUM(fc.total_paid)
                        FROM fee_collection fc
                        WHERE fc.student_id = s.student_id
                            AND fc.academic_year = :year2
                            AND fc.fee_month = :month2
                    ), 0) as paid_total
                FROM students s
                WHERE s.status = 'active'
            ) sub
        ", ['year1' => $selectedSession, 'month1' => $monthNum, 'year2' => $selectedSession, 'month2' => $monthNum]);

        // Unpaid = Total Students - Fully Paid - Partially Paid
        $paymentStats['unpaid'] = $stats['total_students'] - $paymentStats['fully_paid'] - $paymentStats['partial_paid'];

        // Class-wise collection for specific month
        $classWiseCollection = $db->fetchAll("
            SELECT
                c.class_name,
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(DISTINCT fc.student_id) as paid_students,
                COALESCE(SUM(fc.total_paid), 0) as total_collected
            FROM classes c
            LEFT JOIN students s ON c.class_id = s.class_id AND s.status = 'active'
            LEFT JOIN fee_collection fc ON s.student_id = fc.student_id
                AND fc.academic_year = :year AND fc.fee_month = :month
            WHERE c.status = 'active'
            GROUP BY c.class_id, c.class_name
            ORDER BY c.class_numeric
        ", ['year' => $selectedSession, 'month' => $monthNum]);

        // Recent payments for specific month
        $recentPayments = $db->fetchAll("
            SELECT
                fc.receipt_no, fc.payment_date, fc.total_paid,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                c.class_name, sec.section_name
            FROM fee_collection fc
            JOIN students s ON fc.student_id = s.student_id
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE fc.academic_year = :year AND fc.fee_month = :month
            ORDER BY fc.payment_date DESC, fc.payment_id DESC
            LIMIT 10
        ", ['year' => $selectedSession, 'month' => $monthNum]);

    } elseif ($feeMode === 'monthly') {
        // --- Monthly mode: consolidated (default when no month, or explicit ?month=consolidated) ---
        $selectedMonth = 'consolidated';

        $result = $db->fetchOne(
            "SELECT COALESCE(SUM(total_paid), 0) as total FROM fee_collection WHERE academic_year = :year",
            ['year' => $selectedSession]
        );
        $stats['total_collection'] = $result['total'];

        // Consolidated: sum of per-month fully_paid & partial_paid across all months
        // Uses CROSS JOIN with correlated subqueries (same logic as specific-month query)
        // to guarantee consistent counts
        $paymentStats = $db->fetchOne("
            SELECT
                COALESCE(SUM(CASE WHEN paid_total > 0 AND paid_total >= fee_total THEN 1 ELSE 0 END), 0) as fully_paid,
                COALESCE(SUM(CASE WHEN paid_total > 0 AND paid_total < fee_total THEN 1 ELSE 0 END), 0) as partial_paid
            FROM (
                SELECT
                    s.student_id,
                    m.fee_month,
                    COALESCE((
                        SELECT mfs.total_fee
                        FROM monthly_fee_structure mfs
                        WHERE mfs.class_id = s.class_id
                            AND mfs.academic_year = :year1
                            AND mfs.fee_month = m.fee_month
                            AND mfs.status = 'active'
                        LIMIT 1
                    ), 0) as fee_total,
                    COALESCE((
                        SELECT SUM(fc.total_paid)
                        FROM fee_collection fc
                        WHERE fc.student_id = s.student_id
                            AND fc.academic_year = :year2
                            AND fc.fee_month = m.fee_month
                    ), 0) as paid_total
                FROM students s
                CROSS JOIN (
                    SELECT DISTINCT fee_month
                    FROM monthly_fee_structure
                    WHERE academic_year = :year3 AND status = 'active'
                ) m
                WHERE s.status = 'active'
            ) sub
        ", ['year1' => $selectedSession, 'year2' => $selectedSession, 'year3' => $selectedSession]);

        // Consolidated unpaid: count unique students with zero payments across all months
        // (matches unpaid_report.php consolidated logic)
        $unpaidResult = $db->fetchOne("
            SELECT COUNT(*) as count FROM students s
            WHERE s.status = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM fee_collection fc
                WHERE fc.student_id = s.student_id AND fc.academic_year = :year
            )
        ", ['year' => $selectedSession]);
        $paymentStats['unpaid'] = $unpaidResult['count'];

        $classWiseCollection = $db->fetchAll("
            SELECT
                c.class_name,
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(DISTINCT fc.student_id) as paid_students,
                COALESCE(SUM(fc.total_paid), 0) as total_collected
            FROM classes c
            LEFT JOIN students s ON c.class_id = s.class_id AND s.status = 'active'
            LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year
            WHERE c.status = 'active'
            GROUP BY c.class_id, c.class_name
            ORDER BY c.class_numeric
        ", ['year' => $selectedSession]);

        $recentPayments = $db->fetchAll("
            SELECT
                fc.receipt_no, fc.payment_date, fc.total_paid,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                c.class_name, sec.section_name
            FROM fee_collection fc
            JOIN students s ON fc.student_id = s.student_id
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE fc.academic_year = :year
            ORDER BY fc.payment_date DESC, fc.payment_id DESC
            LIMIT 10
        ", ['year' => $selectedSession]);

    } else {
        // --- Annual mode ---
        $result = $db->fetchOne(
            "SELECT COALESCE(SUM(total_paid), 0) as total FROM fee_collection WHERE academic_year = :year",
            ['year' => $selectedSession]
        );
        $stats['total_collection'] = $result['total'];

        $paymentStats = $db->fetchOne("
            SELECT
                COUNT(CASE WHEN paid_total > 0 AND paid_total >= fee_total THEN 1 END) as fully_paid,
                COUNT(CASE WHEN paid_total > 0 AND paid_total < fee_total THEN 1 END) as partial_paid,
                COUNT(CASE WHEN paid_total = 0 THEN 1 END) as unpaid
            FROM (
                SELECT
                    s.student_id,
                    COALESCE(MAX(fs.total_fee), 0) as fee_total,
                    COALESCE(SUM(fc.total_paid), 0) as paid_total
                FROM students s
                LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = :year1
                LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year2
                WHERE s.status = 'active'
                GROUP BY s.student_id
            ) sub
        ", ['year1' => $selectedSession, 'year2' => $selectedSession]);

        $classWiseCollection = $db->fetchAll("
            SELECT
                c.class_name,
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(DISTINCT fc.student_id) as paid_students,
                COALESCE(SUM(fc.total_paid), 0) as total_collected
            FROM classes c
            LEFT JOIN students s ON c.class_id = s.class_id AND s.status = 'active'
            LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year
            WHERE c.status = 'active'
            GROUP BY c.class_id, c.class_name
            ORDER BY c.class_numeric
        ", ['year' => $selectedSession]);

        $recentPayments = $db->fetchAll("
            SELECT
                fc.receipt_no, fc.payment_date, fc.total_paid,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                c.class_name, sec.section_name
            FROM fee_collection fc
            JOIN students s ON fc.student_id = s.student_id
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            WHERE fc.academic_year = :year
            ORDER BY fc.payment_date DESC, fc.payment_id DESC
            LIMIT 10
        ", ['year' => $selectedSession]);
    }

    $stats['fully_paid'] = $paymentStats['fully_paid'];
    $stats['partial_paid'] = $paymentStats['partial_paid'];
    $stats['unpaid_students'] = $paymentStats['unpaid'];

} catch(Exception $e) {
    error_log($e->getMessage());
    $stats = [
        'total_students' => 0,
        'total_classes' => 0,
        'total_collection' => 0,
        'fully_paid' => 0,
        'partial_paid' => 0,
        'unpaid_students' => 0
    ];
    $recentPayments = [];
    $classWiseCollection = [];
}

// Check for locked accounts (sysadmin only)
$lockedAccountCount = 0;
$lockedAccountsList = [];
if (isSysAdmin()) {
    try {
        $db = getDB();
        $lockedAccountsList = $db->fetchAll(
            "SELECT username, full_name, account_locked_until FROM admin
             WHERE account_locked_until > NOW() OR failed_login_attempts >= 5
             ORDER BY account_locked_until DESC"
        );
        $lockedAccountCount = count($lockedAccountsList);
    } catch (Exception $e) {
        error_log("Locked accounts check: " . $e->getMessage());
    }
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h2>
    </div>
</div>

<?php if ($lockedAccountCount > 0): ?>
<!-- Locked Accounts Alert -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
            <i class="fas fa-user-lock"></i>
            <strong><?php echo $lockedAccountCount; ?> user account<?php echo $lockedAccountCount > 1 ? 's are' : ' is'; ?> currently locked</strong>
            due to failed login attempts:
            <?php
            $names = array_map(function($u) { return htmlspecialchars($u['username']); }, $lockedAccountsList);
            echo implode(', ', $names);
            ?>
            <a href="/admin/manage_users.php" class="btn btn-sm btn-warning ms-2">
                <i class="fas fa-unlock"></i> Manage Users
            </a>
            <a href="/admin/manage_lockouts.php" class="btn btn-sm btn-outline-warning ms-1">
                <i class="fas fa-user-lock"></i> Account Lockouts
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$dashPendingUpi = getPendingUpiCount();
if ($dashPendingUpi > 0):
?>
<!-- Pending UPI Payments Alert -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show mb-0" role="alert">
            <i class="fas fa-qrcode"></i>
            <strong><?php echo $dashPendingUpi; ?> UPI payment<?php echo $dashPendingUpi > 1 ? 's are' : ' is'; ?> pending verification.</strong>
            <a href="/modules/fee_collection/upi_payments.php?status=Pending" class="btn btn-sm btn-info ms-2">
                <i class="fas fa-eye"></i> Review Now
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<?php
// Build month query param for tile links
$monthParam = '';
if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated') {
    $monthParam = '&month=' . urlencode($selectedMonth);
} elseif ($feeMode === 'monthly') {
    $monthParam = '&month=consolidated';
}
?>
<div class="row dashboard-stats-row">
    <div class="col-6 col-md-4 col-lg">
        <a href="/modules/student/view_students.php" class="text-decoration-none">
            <div class="dashboard-card card">
                <div class="card-body">
                    <div class="icon-box bg-gradient-primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                    <p class="text-muted mb-0">Total Students</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-lg">
        <a href="/modules/reports/paid_report.php<?php echo $monthParam ? '?'.ltrim($monthParam,'&') : ''; ?>" class="text-decoration-none">
            <div class="dashboard-card card">
                <div class="card-body">
                    <div class="icon-box bg-gradient-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo number_format($stats['fully_paid']); ?></h3>
                    <p class="text-muted mb-0">Fully Paid</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-lg">
        <a href="/modules/reports/student_wise_report.php?status=partial<?php echo $monthParam; ?>" class="text-decoration-none">
            <div class="dashboard-card card">
                <div class="card-body">
                    <div class="icon-box bg-gradient-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-0"><?php echo number_format($stats['partial_paid']); ?></h3>
                    <p class="text-muted mb-0">Partial Paid</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-lg">
        <a href="/modules/reports/unpaid_report.php<?php echo $monthParam ? '?'.ltrim($monthParam,'&') : ''; ?>" class="text-decoration-none">
            <div class="dashboard-card card">
                <div class="card-body">
                    <div class="icon-box bg-gradient-danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo number_format($stats['unpaid_students']); ?></h3>
                    <p class="text-muted mb-0">Unpaid</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-lg">
        <a href="/modules/fee_collection/view_payments.php<?php echo $monthParam ? '?'.ltrim($monthParam,'&') : ''; ?>" class="text-decoration-none">
            <div class="dashboard-card card">
                <div class="card-body">
                    <div class="icon-box bg-gradient-info">
                        <i class="fas fa-indian-rupee-sign"></i>
                    </div>
                    <h3 class="mb-0">₹ <?php echo number_format($stats['total_collection'], 0); ?></h3>
                    <p class="text-muted mb-0">Total Collection</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Recent Payments -->
<div class="row mt-4">
    <div class="col-lg-8 order-2 order-lg-1">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Payments
            </div>
            <div class="card-body">
                <?php if (count($recentPayments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentPayments as $payment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section_name']); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($payment['total_paid']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No payment records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 order-1 order-lg-2">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-lg-12 d-flex">
                        <a href="/modules/student/add_student.php" class="btn btn-primary btn-custom w-100 h-100">
                            <i class="fas fa-user-plus"></i> Add New Student
                        </a>
                    </div>
                    <div class="col-6 col-lg-12 d-flex">
                        <a href="/modules/fee_collection/collect_fee.php" class="btn btn-success btn-custom w-100 h-100">
                            <i class="fas fa-money-bill-wave"></i> Collect Fee
                        </a>
                    </div>
                    <div class="col-6 col-lg-12 d-flex">
                        <a href="/modules/reports/unpaid_report.php" class="btn btn-warning btn-custom w-100 h-100">
                            <i class="fas fa-file-invoice"></i> Unpaid Report
                        </a>
                    </div>
                    <div class="col-6 col-lg-12 d-flex">
                        <a href="/modules/student/view_students.php" class="btn btn-info btn-custom w-100 h-100">
                            <i class="fas fa-users"></i> View All Students
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Class-wise Collection -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Class-wise Collection Summary
            </div>
            <div class="card-body">
                <?php if (count($classWiseCollection) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Total Students</th>
                                <th>Paid Students</th>
                                <th>Unpaid Students</th>
                                <th class="text-end">Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($classWiseCollection as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                                <td><?php echo number_format($row['total_students']); ?></td>
                                <td><span class="badge bg-success"><?php echo number_format($row['paid_students']); ?></span></td>
                                <td><span class="badge bg-danger"><?php echo number_format($row['total_students'] - $row['paid_students']); ?></span></td>
                                <td class="text-end"><strong><?php echo formatCurrency($row['total_collected']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
