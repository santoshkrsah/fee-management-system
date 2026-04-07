<?php
/**
 * Class-wise Collection Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Class-wise Collection Report';

$selectedSession = getSelectedSession();

try {
    $db = getDB();

    // Class-wise collection
    $classWiseData = $db->fetchAll("
        SELECT
            c.class_name,
            COUNT(DISTINCT s.student_id) as total_students,
            COUNT(DISTINCT fc.student_id) as paid_students,
            (COUNT(DISTINCT s.student_id) - COUNT(DISTINCT fc.student_id)) as unpaid_students,
            COALESCE(SUM(fs.total_fee), 0) as expected_collection,
            COALESCE(SUM(fc.total_paid), 0) as actual_collection,
            (COALESCE(SUM(fs.total_fee), 0) - COALESCE(SUM(fc.total_paid), 0)) as pending_collection
        FROM classes c
        LEFT JOIN students s ON c.class_id = s.class_id AND s.status = 'active'
        LEFT JOIN fee_structure fs ON c.class_id = fs.class_id AND fs.academic_year = :year1
        LEFT JOIN fee_collection fc ON s.student_id = fc.student_id AND fc.academic_year = :year2
        WHERE c.status = 'active'
        GROUP BY c.class_id, c.class_name
        ORDER BY c.class_numeric
    ", ['year1' => $selectedSession, 'year2' => $selectedSession]);

    // Calculate totals
    $totalStudents = 0;
    $totalPaidStudents = 0;
    $totalUnpaidStudents = 0;
    $totalExpected = 0;
    $totalActual = 0;
    $totalPending = 0;

    foreach($classWiseData as $row) {
        $totalStudents += $row['total_students'];
        $totalPaidStudents += $row['paid_students'];
        $totalUnpaidStudents += $row['unpaid_students'];
        $totalExpected += $row['expected_collection'];
        $totalActual += $row['actual_collection'];
        $totalPending += $row['pending_collection'];
    }

} catch(Exception $e) {
    error_log($e->getMessage());
    $classWiseData = [];
    $totalStudents = $totalPaidStudents = $totalUnpaidStudents = 0;
    $totalExpected = $totalActual = $totalPending = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-chart-bar"></i> Class-wise Collection Report
            <span class="badge bg-primary float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Students</h6>
                <h3><?php echo number_format($totalStudents); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Expected Collection</h6>
                <h3><?php echo formatCurrency($totalExpected); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Actual Collection</h6>
                <h3 class="text-success"><?php echo formatCurrency($totalActual); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Pending</h6>
                <h3 class="text-danger"><?php echo formatCurrency($totalPending); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<?php if (count($classWiseData) > 0): ?>
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Collection Per Class
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="classCollectionChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Paid vs Unpaid Students
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="paidUnpaidChart"></canvas></div>
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
                <span><i class="fas fa-list"></i> Class-wise Collection Summary</span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('class_wise_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($classWiseData) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th class="text-center">Total Students</th>
                                <th class="text-center">Paid</th>
                                <th class="text-center">Unpaid</th>
                                <th class="text-end">Expected Collection</th>
                                <th class="text-end">Actual Collection</th>
                                <th class="text-end">Pending</th>
                                <th class="text-center">Collection %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($classWiseData as $row): ?>
                            <?php
                                $collectionPercent = $row['expected_collection'] > 0
                                    ? ($row['actual_collection'] / $row['expected_collection']) * 100
                                    : 0;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                                <td class="text-center"><?php echo number_format($row['total_students']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo number_format($row['paid_students']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?php echo number_format($row['unpaid_students']); ?></span>
                                </td>
                                <td class="text-end"><?php echo formatCurrency($row['expected_collection']); ?></td>
                                <td class="text-end text-success"><strong><?php echo formatCurrency($row['actual_collection']); ?></strong></td>
                                <td class="text-end text-danger"><strong><?php echo formatCurrency($row['pending_collection']); ?></strong></td>
                                <td class="text-center">
                                    <?php
                                        $badgeClass = 'bg-danger';
                                        if ($collectionPercent >= 75) $badgeClass = 'bg-success';
                                        elseif ($collectionPercent >= 50) $badgeClass = 'bg-warning';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo number_format($collectionPercent, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>Total</th>
                                <th class="text-center"><?php echo number_format($totalStudents); ?></th>
                                <th class="text-center"><span class="badge bg-success"><?php echo number_format($totalPaidStudents); ?></span></th>
                                <th class="text-center"><span class="badge bg-danger"><?php echo number_format($totalUnpaidStudents); ?></span></th>
                                <th class="text-end"><?php echo formatCurrency($totalExpected); ?></th>
                                <th class="text-end text-success"><strong><?php echo formatCurrency($totalActual); ?></strong></th>
                                <th class="text-end text-danger"><strong><?php echo formatCurrency($totalPending); ?></strong></th>
                                <th class="text-center">
                                    <?php
                                        $overallPercent = $totalExpected > 0 ? ($totalActual / $totalExpected) * 100 : 0;
                                        $badgeClass = 'bg-danger';
                                        if ($overallPercent >= 75) $badgeClass = 'bg-success';
                                        elseif ($overallPercent >= 50) $badgeClass = 'bg-warning';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo number_format($overallPercent, 1); ?>%
                                    </span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($classWiseData) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Bar Chart: Collection Per Class
const barCtx = document.getElementById('classCollectionChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach($classWiseData as $row): ?>
                '<?php echo htmlspecialchars($row['class_name']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Expected',
            data: [
                <?php foreach($classWiseData as $row): ?>
                    <?php echo $row['expected_collection']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Collected',
            data: [
                <?php foreach($classWiseData as $row): ?>
                    <?php echo $row['actual_collection']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '\u20B9 ' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});

// Doughnut Chart: Paid vs Unpaid Students
const doughnutCtx = document.getElementById('paidUnpaidChart').getContext('2d');
new Chart(doughnutCtx, {
    type: 'doughnut',
    data: {
        labels: ['Paid Students', 'Unpaid Students'],
        datasets: [{
            data: [
                <?php echo $totalPaidStudents; ?>,
                <?php echo $totalUnpaidStudents; ?>
            ],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
                'rgb(16, 185, 129)',
                'rgb(239, 68, 68)'
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
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
