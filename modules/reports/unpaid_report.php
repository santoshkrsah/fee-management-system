<?php
/**
 * Unpaid Students Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Unpaid Students Report';

$selectedSession = getSelectedSession();

// Filters
$class_filter = (int)($_GET['class_id'] ?? 0);

try {
    $db = getDB();

    // Build query
    $query = "
        SELECT
            s.student_id,
            s.admission_no,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.father_name,
            s.contact_number,
            c.class_name,
            sec.section_name,
            COALESCE(MAX(fs.total_fee), 0) as total_fee,
            COALESCE(SUM(fc.total_paid), 0) as total_paid,
            (COALESCE(MAX(fs.total_fee), 0) - COALESCE(SUM(fc.total_paid), 0)) as balance,
            'Unpaid' as payment_status
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
        LEFT JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = :year1
        LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year2
        WHERE s.status = 'active'
    ";

    $params = ['year1' => $selectedSession, 'year2' => $selectedSession];

    if ($class_filter > 0) {
        $query .= " AND s.class_id = :class_id";
        $params['class_id'] = $class_filter;
    }

    $query .= " GROUP BY s.student_id
                HAVING total_paid = 0
                ORDER BY c.class_numeric, sec.section_name, s.first_name";

    $students = $db->fetchAll($query, $params);

    // Calculate totals
    $totalFee = 0;
    $totalPaid = 0;
    $totalBalance = 0;
    foreach($students as $student) {
        $totalFee += $student['total_fee'];
        $totalPaid += $student['total_paid'];
        $totalBalance += $student['balance'];
    }

    // Aggregate unpaid students by class for chart
    $classWiseUnpaid = [];
    $classWiseBalance = [];
    foreach ($students as $student) {
        $cn = $student['class_name'];
        if (!isset($classWiseUnpaid[$cn])) {
            $classWiseUnpaid[$cn] = 0;
            $classWiseBalance[$cn] = 0;
        }
        $classWiseUnpaid[$cn]++;
        $classWiseBalance[$cn] += $student['balance'];
    }

    // Get classes for filter
    $classes = $db->fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY class_numeric");

} catch(Exception $e) {
    error_log($e->getMessage());
    $students = [];
    $classes = [];
    $classWiseUnpaid = [];
    $classWiseBalance = [];
    $totalFee = $totalPaid = $totalBalance = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-exclamation-circle text-danger"></i> Unpaid Students Report
            <span class="badge bg-danger float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
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
                    <a href="unpaid_report.php" class="btn btn-secondary btn-sm btn-custom">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Unpaid Students</h6>
                <h3 class="text-danger"><?php echo number_format(count($students)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Fee</h6>
                <h3><?php echo formatCurrency($totalFee); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Collected</h6>
                <h3 class="text-success"><?php echo formatCurrency($totalPaid); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Balance Due</h6>
                <h3 class="text-danger"><?php echo formatCurrency($totalBalance); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<?php if (!empty($classWiseUnpaid)): ?>
<div class="row mb-4 no-print">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Class-wise Unpaid Students & Balance Due
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="classWiseBarChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Unpaid Distribution by Class
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="classUnpaidDoughnutChart"></canvas></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Total Unpaid Students: <?php echo count($students); ?></span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('unpaid_students_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
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
                                <th>Contact</th>
                                <th class="text-end">Total Fee</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
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
                                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_fee']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($student['total_paid']); ?></td>
                                <td class="text-end"><strong class="text-danger"><?php echo formatCurrency($student['balance']); ?></strong></td>
                                <td class="text-center">
                                    <span class="badge status-unpaid">UNPAID</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-warning">
                                <th colspan="5" class="text-end">Total:</th>
                                <th class="text-end"><?php echo formatCurrency($totalFee); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalPaid); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalBalance); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No unpaid students found. All students have paid their fees!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($classWiseUnpaid)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartColors = [
    'rgba(239, 68, 68, 0.8)',
    'rgba(245, 158, 11, 0.8)',
    'rgba(99, 102, 241, 0.8)',
    'rgba(6, 182, 212, 0.8)',
    'rgba(139, 92, 246, 0.8)',
    'rgba(236, 72, 153, 0.8)',
    'rgba(34, 197, 94, 0.8)',
    'rgba(249, 115, 22, 0.8)',
    'rgba(14, 165, 233, 0.8)',
    'rgba(168, 85, 247, 0.8)',
    'rgba(244, 63, 94, 0.8)',
    'rgba(20, 184, 166, 0.8)'
];
const borderColors = [
    'rgb(239, 68, 68)',
    'rgb(245, 158, 11)',
    'rgb(99, 102, 241)',
    'rgb(6, 182, 212)',
    'rgb(139, 92, 246)',
    'rgb(236, 72, 153)',
    'rgb(34, 197, 94)',
    'rgb(249, 115, 22)',
    'rgb(14, 165, 233)',
    'rgb(168, 85, 247)',
    'rgb(244, 63, 94)',
    'rgb(20, 184, 166)'
];

// Bar Chart: Class-wise Unpaid Students & Balance
const barCtx = document.getElementById('classWiseBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach($classWiseUnpaid as $className => $count): ?>
                '<?php echo htmlspecialchars($className); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Unpaid Students',
            data: [
                <?php foreach($classWiseUnpaid as $className => $count): ?>
                    <?php echo $count; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 1,
            borderRadius: 4,
            yAxisID: 'y'
        }, {
            label: 'Balance Due',
            data: [
                <?php foreach($classWiseBalance as $className => $amount): ?>
                    <?php echo $amount; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(245, 158, 11, 0.5)',
            borderColor: 'rgb(245, 158, 11)',
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
                    text: 'Balance'
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

// Doughnut Chart: Unpaid Distribution by Class
const doughnutCtx = document.getElementById('classUnpaidDoughnutChart').getContext('2d');
new Chart(doughnutCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php foreach($classWiseUnpaid as $className => $count): ?>
                '<?php echo htmlspecialchars($className); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach($classWiseUnpaid as $className => $count): ?>
                    <?php echo $count; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: chartColors.slice(0, <?php echo count($classWiseUnpaid); ?>),
            borderColor: borderColors.slice(0, <?php echo count($classWiseUnpaid); ?>),
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
                        return context.label + ': ' + context.parsed + ' students (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
