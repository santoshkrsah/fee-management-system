<?php
/**
 * Paid Students Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Paid Students Report';

$selectedSession = getSelectedSession();
$feeMode = getFeeMode();
$academicMonths = getAcademicMonths();
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : '';

// Filters
$class_filter = (int)($_GET['class_id'] ?? 0);

try {
    $db = getDB();

    if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated') {
        // --- Monthly mode: specific month ---
        $monthNum = (int)$selectedMonth;

        $query = "
            SELECT
                s.student_id,
                s.admission_no,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                s.father_name,
                s.contact_number,
                c.class_name,
                sec.section_name,
                COALESCE(MAX(mfs.total_fee), 0) as total_fee,
                COALESCE(SUM(fc.total_paid), 0) as total_paid,
                (COALESCE(MAX(mfs.total_fee), 0) - COALESCE(SUM(fc.total_paid), 0)) as balance
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN monthly_fee_structure mfs ON s.class_id = mfs.class_id
                AND mfs.academic_year = :year1 AND mfs.fee_month = :month1 AND mfs.status = 'active'
            LEFT JOIN fee_collection fc ON s.student_id = fc.student_id
                AND fc.academic_year = :year2 AND fc.fee_month = :month2
            WHERE s.status = 'active'
        ";
        $params = ['year1' => $selectedSession, 'month1' => $monthNum, 'year2' => $selectedSession, 'month2' => $monthNum];

    } elseif ($feeMode === 'monthly') {
        // --- Monthly mode: consolidated (per-month logic matching dashboard) ---
        // Uses subquery wrapper so we can filter with WHERE on computed columns
        $classFilterSql = '';
        if ($class_filter > 0) {
            $classFilterSql = ' AND s.class_id = :class_id';
        }

        $query = "
            SELECT * FROM (
                SELECT
                    s.student_id,
                    s.admission_no,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.father_name,
                    s.contact_number,
                    c.class_name,
                    c.class_numeric,
                    sec.section_name,
                    s.first_name as sort_name,
                    m.fee_month,
                    COALESCE((
                        SELECT mfs.total_fee FROM monthly_fee_structure mfs
                        WHERE mfs.class_id = s.class_id AND mfs.academic_year = :year1
                            AND mfs.fee_month = m.fee_month AND mfs.status = 'active'
                        LIMIT 1
                    ), 0) as total_fee,
                    COALESCE((
                        SELECT SUM(fc.total_paid) FROM fee_collection fc
                        WHERE fc.student_id = s.student_id AND fc.academic_year = :year2
                            AND fc.fee_month = m.fee_month
                    ), 0) as total_paid
                FROM students s
                JOIN classes c ON s.class_id = c.class_id
                JOIN sections sec ON s.section_id = sec.section_id
                CROSS JOIN (
                    SELECT DISTINCT fee_month
                    FROM monthly_fee_structure
                    WHERE academic_year = :year3 AND status = 'active'
                ) m
                WHERE s.status = 'active'" . $classFilterSql . "
            ) sub
            WHERE total_paid > 0 AND total_paid >= total_fee
            ORDER BY class_numeric, section_name, sort_name, fee_month
        ";
        $params = ['year1' => $selectedSession, 'year2' => $selectedSession, 'year3' => $selectedSession];
        if ($class_filter > 0) {
            $params['class_id'] = $class_filter;
        }

        $students = $db->fetchAll($query, $params);

        // Skip the shared filter/HAVING logic below
        $skipSharedFilters = true;

    } else {
        // --- Annual mode ---
        $query = "
            SELECT
                s.student_id,
                s.admission_no,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                s.father_name,
                s.contact_number,
                c.class_name,
                sec.section_name,
                MAX(fs.total_fee) as total_fee,
                COALESCE(SUM(fc.total_paid), 0) as total_paid,
                (MAX(fs.total_fee) - COALESCE(SUM(fc.total_paid), 0)) as balance
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            JOIN sections sec ON s.section_id = sec.section_id
            LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = :year1
            LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year2
            WHERE s.status = 'active'
        ";
        $params = ['year1' => $selectedSession, 'year2' => $selectedSession];
    }

    if (empty($skipSharedFilters)) {
        if ($class_filter > 0) {
            $query .= " AND s.class_id = :class_id";
            $params['class_id'] = $class_filter;
        }

        if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated') {
            // Specific month: GROUP BY needed for LEFT JOINs
            $query .= " GROUP BY s.student_id
                        HAVING total_paid > 0 AND total_paid >= total_fee
                        ORDER BY c.class_numeric, sec.section_name, s.first_name";
        } else {
            // Annual
            $query .= " GROUP BY s.student_id
                        HAVING total_paid >= total_fee
                        ORDER BY c.class_numeric, sec.section_name, s.first_name";
        }

        $students = $db->fetchAll($query, $params);
    }

    // Calculate totals
    $totalFee = 0;
    $totalPaid = 0;
    foreach ($students as $student) {
        $totalFee += $student['total_fee'];
        $totalPaid += $student['total_paid'];
    }

    // Aggregate paid students by class for bar chart
    $classWisePaid = [];
    $classWisePaidAmount = [];
    foreach ($students as $student) {
        $cn = $student['class_name'];
        if (!isset($classWisePaid[$cn])) {
            $classWisePaid[$cn] = 0;
            $classWisePaidAmount[$cn] = 0;
        }
        $classWisePaid[$cn]++;
        $classWisePaidAmount[$cn] += $student['total_paid'];
    }

    // Payment mode distribution for paid students
    $paidStudentIds = array_unique(array_column($students, 'student_id'));
    $paymentModeData = [];
    if (!empty($paidStudentIds)) {
        $placeholders = implode(',', array_fill(0, count($paidStudentIds), '?'));
        $pmQuery = "
            SELECT
                payment_mode,
                COUNT(payment_id) as mode_count,
                SUM(total_paid) as mode_total
            FROM fee_collection
            WHERE student_id IN ($placeholders)
                  AND academic_year = ?
        ";
        $pmParams = array_merge($paidStudentIds, [$selectedSession]);
        if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated') {
            $pmQuery .= " AND fee_month = ?";
            $pmParams[] = (int)$selectedMonth;
        }
        $pmQuery .= " GROUP BY payment_mode ORDER BY mode_total DESC";
        $paymentModeData = $db->fetchAll($pmQuery, $pmParams);
    }

    // Get classes for filter
    $classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");

} catch(Exception $e) {
    error_log($e->getMessage());
    $students = [];
    $classes = [];
    $classWisePaid = [];
    $classWisePaidAmount = [];
    $paymentModeData = [];
    $totalFee = $totalPaid = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-check-circle text-success"></i> Paid Students Report
            <?php if ($feeMode === 'monthly' && $selectedMonth !== '' && $selectedMonth !== 'consolidated'): ?>
                <small class="text-muted">(<?php echo htmlspecialchars(getAcademicMonths()[(int)$selectedMonth] ?? ''); ?>)</small>
            <?php elseif ($feeMode === 'monthly'): ?>
                <small class="text-muted">(Consolidated)</small>
            <?php endif; ?>
            <span class="badge bg-success float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <?php if ($selectedMonth !== ''): ?>
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
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
                        <button type="submit" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
                <div class="mt-2">
                    <a href="paid_report.php<?php echo $selectedMonth !== '' ? '?month=' . urlencode($selectedMonth) : ''; ?>" class="btn btn-secondary btn-sm btn-custom">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Paid Students</h6>
                <h3 class="text-success"><?php echo number_format(count($students)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Fee Amount</h6>
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
</div>

<!-- Charts Section -->
<?php if (count($students) > 0): ?>
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Class-wise Paid Students & Collection
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="classWiseBarChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Payment Mode Distribution
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="paymentModeDoughnutChart"></canvas></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-list"></i> Total Paid Students: <?php echo count($students); ?></span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('paid_students_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
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
                                <?php if ($feeMode === 'monthly' && ($selectedMonth === '' || $selectedMonth === 'consolidated')): ?>
                                <th>Month</th>
                                <?php endif; ?>
                                <th>Contact</th>
                                <th class="text-end">Total Fee</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></td>
                                <?php if ($feeMode === 'monthly' && ($selectedMonth === '' || $selectedMonth === 'consolidated')): ?>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($academicMonths[$student['fee_month']] ?? '-'); ?></span></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_fee']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_paid']); ?></td>
                                <td class="text-center">
                                    <span class="badge status-paid">PAID</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No paid students found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($students) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Bar Chart: Class-wise Paid Students & Collection
const barCtx = document.getElementById('classWiseBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach($classWisePaid as $className => $count): ?>
                '<?php echo htmlspecialchars($className); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Paid Students',
            data: [
                <?php foreach($classWisePaid as $className => $count): ?>
                    <?php echo $count; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 4,
            yAxisID: 'y'
        }, {
            label: 'Amount Collected',
            data: [
                <?php foreach($classWisePaidAmount as $className => $amount): ?>
                    <?php echo $amount; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1,
            borderRadius: 4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Students'
                },
                ticks: {
                    stepSize: 1
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: {
                    display: true,
                    text: 'Amount'
                },
                ticks: {
                    callback: function(value) {
                        return '\u20B9 ' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});

// Doughnut Chart: Payment Mode Distribution
<?php if (!empty($paymentModeData)): ?>
const doughnutCtx = document.getElementById('paymentModeDoughnutChart').getContext('2d');
new Chart(doughnutCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php foreach($paymentModeData as $mode): ?>
                '<?php echo htmlspecialchars($mode['payment_mode']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach($paymentModeData as $mode): ?>
                    <?php echo $mode['mode_total']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                'rgba(99, 102, 241, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(139, 92, 246, 0.8)'
            ],
            borderColor: [
                'rgb(99, 102, 241)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(6, 182, 212)',
                'rgb(139, 92, 246)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': \u20B9 ' + context.parsed.toLocaleString('en-IN') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
