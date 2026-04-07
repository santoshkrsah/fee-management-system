<?php
/**
 * Year-wise Collection Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Year-wise Collection Report';

try {
    $db = getDB();

    // Year-wise collection summary across all academic years
    $yearWiseData = $db->fetchAll("
        SELECT
            asn.session_name as academic_year,
            COALESCE(student_counts.total_students, 0) as total_students,
            COALESCE(expected.total_expected, 0) as total_expected,
            COALESCE(collected.total_collected, 0) as total_collected
        FROM academic_sessions asn
        LEFT JOIN (
            SELECT academic_year, COUNT(*) as total_students
            FROM students WHERE status = 'active'
            GROUP BY academic_year
        ) student_counts ON student_counts.academic_year = asn.session_name
        LEFT JOIN (
            SELECT
                s.academic_year,
                SUM(fs.total_fee) as total_expected
            FROM students s
            JOIN fee_structure fs ON s.class_id = fs.class_id AND fs.academic_year = s.academic_year
            WHERE s.status = 'active'
            GROUP BY s.academic_year
        ) expected ON expected.academic_year = asn.session_name
        LEFT JOIN (
            SELECT academic_year, SUM(total_paid) as total_collected
            FROM fee_collection
            GROUP BY academic_year
        ) collected ON collected.academic_year = asn.session_name
        ORDER BY asn.session_name ASC
    ");

    // Calculate derived values and totals
    $grandTotalStudents = 0;
    $grandTotalExpected = 0;
    $grandTotalCollected = 0;
    $grandTotalPending = 0;

    foreach ($yearWiseData as &$row) {
        $row['pending'] = $row['total_expected'] - $row['total_collected'];
        $row['collection_percent'] = $row['total_expected'] > 0
            ? ($row['total_collected'] / $row['total_expected']) * 100
            : 0;
        $grandTotalStudents += $row['total_students'];
        $grandTotalExpected += $row['total_expected'];
        $grandTotalCollected += $row['total_collected'];
        $grandTotalPending += $row['pending'];
    }
    unset($row);

    $grandCollectionPercent = $grandTotalExpected > 0
        ? ($grandTotalCollected / $grandTotalExpected) * 100
        : 0;

} catch(Exception $e) {
    error_log($e->getMessage());
    $yearWiseData = [];
    $grandTotalStudents = $grandTotalExpected = $grandTotalCollected = $grandTotalPending = 0;
    $grandCollectionPercent = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-chart-line"></i> Year-wise Collection Report
            <span class="badge bg-info float-end">All Academic Years</span>
        </h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Students</h6>
                <h3><?php echo number_format($grandTotalStudents); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Expected</h6>
                <h3><?php echo formatCurrency($grandTotalExpected); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Collected</h6>
                <h3 class="text-success"><?php echo formatCurrency($grandTotalCollected); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Pending</h6>
                <h3 class="text-danger"><?php echo formatCurrency($grandTotalPending); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<?php if (count($yearWiseData) > 0): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Year-on-Year Collection Comparison
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="yearBarChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Collection Trend Over Years
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="yearLineChart"></canvas></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-list"></i> Year-wise Collection Summary</span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('year_wise_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($yearWiseData) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th class="text-end">Total Students</th>
                                <th class="text-end">Expected Collection</th>
                                <th class="text-end">Actual Collection</th>
                                <th class="text-end">Pending</th>
                                <th class="text-center">Collection %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearWiseData as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['academic_year']); ?></strong></td>
                                <td class="text-end"><?php echo number_format($row['total_students']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_expected']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_collected']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['pending']); ?></td>
                                <td class="text-center">
                                    <?php
                                        $pct = round($row['collection_percent'], 1);
                                        if ($pct >= 75) $badge = 'bg-success';
                                        elseif ($pct >= 50) $badge = 'bg-warning';
                                        else $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $pct; ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>Grand Total</th>
                                <th class="text-end"><?php echo number_format($grandTotalStudents); ?></th>
                                <th class="text-end"><?php echo formatCurrency($grandTotalExpected); ?></th>
                                <th class="text-end"><?php echo formatCurrency($grandTotalCollected); ?></th>
                                <th class="text-end"><?php echo formatCurrency($grandTotalPending); ?></th>
                                <th class="text-center">
                                    <?php
                                        $gpct = round($grandCollectionPercent, 1);
                                        if ($gpct >= 75) $badge = 'bg-success';
                                        elseif ($gpct >= 50) $badge = 'bg-warning';
                                        else $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $gpct; ?>%</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted my-4">
                    <i class="fas fa-info-circle"></i> No academic session data found.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($yearWiseData) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Bar Chart: Year-on-Year Collection Comparison
const barCtx = document.getElementById('yearBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach($yearWiseData as $row): ?>
                '<?php echo htmlspecialchars($row['academic_year']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Expected',
            data: [
                <?php foreach($yearWiseData as $row): ?>
                    <?php echo $row['total_expected']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Collected',
            data: [
                <?php foreach($yearWiseData as $row): ?>
                    <?php echo $row['total_collected']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Pending',
            data: [
                <?php foreach($yearWiseData as $row): ?>
                    <?php echo $row['pending']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(239, 68, 68, 0.5)',
            borderColor: 'rgb(239, 68, 68)',
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

// Line Chart: Collection Trend (dual-axis)
const lineCtx = document.getElementById('yearLineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach($yearWiseData as $row): ?>
                '<?php echo htmlspecialchars($row['academic_year']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Total Collected',
            data: [
                <?php foreach($yearWiseData as $row): ?>
                    <?php echo $row['total_collected']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.3,
            fill: true,
            pointBackgroundColor: 'rgb(16, 185, 129)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            yAxisID: 'y'
        }, {
            label: 'Collection %',
            data: [
                <?php foreach($yearWiseData as $row): ?>
                    <?php echo round($row['collection_percent'], 1); ?>,
                <?php endforeach; ?>
            ],
            borderColor: 'rgb(245, 158, 11)',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            tension: 0.3,
            fill: false,
            pointBackgroundColor: 'rgb(245, 158, 11)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
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
                ticks: {
                    callback: function(value) {
                        return '\u20B9 ' + value.toLocaleString('en-IN');
                    }
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                max: 100,
                grid: { drawOnChartArea: false },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
