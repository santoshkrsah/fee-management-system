<?php
/**
 * Month-wise Collection Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Month-wise Collection Report';

$selectedSession = getSelectedSession();
$academicMonths = getAcademicMonths();

try {
    $db = getDB();

    // Query fee collection grouped by calendar month
    $rawData = $db->fetchAll("
        SELECT
            MONTH(fc.payment_date) as calendar_month,
            YEAR(fc.payment_date) as calendar_year,
            COUNT(fc.payment_id) as total_payments,
            COUNT(DISTINCT fc.student_id) as total_students,
            SUM(fc.tuition_fee_paid + fc.exam_fee_paid + fc.library_fee_paid +
                fc.sports_fee_paid + fc.lab_fee_paid + fc.transport_fee_paid +
                fc.other_charges_paid) as fee_amount,
            SUM(fc.fine) as fine_amount,
            SUM(fc.discount) as discount_amount,
            SUM(fc.total_paid) as total_collected
        FROM fee_collection fc
        WHERE fc.academic_year = :year
        GROUP BY YEAR(fc.payment_date), MONTH(fc.payment_date)
        ORDER BY YEAR(fc.payment_date), MONTH(fc.payment_date)
    ", ['year' => $selectedSession]);

    // Initialize monthly data in academic year order (April-March)
    $monthlyData = [];
    foreach ($academicMonths as $pos => $monthName) {
        $monthlyData[$pos] = [
            'month_name' => $monthName,
            'total_payments' => 0,
            'total_students' => 0,
            'fee_amount' => 0,
            'fine_amount' => 0,
            'discount_amount' => 0,
            'total_collected' => 0
        ];
    }

    // Map calendar months to academic positions
    foreach ($rawData as $row) {
        $calMonth = (int)$row['calendar_month'];
        // Apr(4)->1, May(5)->2, ..., Dec(12)->9, Jan(1)->10, Feb(2)->11, Mar(3)->12
        $academicPos = ($calMonth >= 4) ? ($calMonth - 3) : ($calMonth + 9);
        if (isset($monthlyData[$academicPos])) {
            $monthlyData[$academicPos]['total_payments'] += $row['total_payments'];
            $monthlyData[$academicPos]['total_students'] += $row['total_students'];
            $monthlyData[$academicPos]['fee_amount'] += $row['fee_amount'];
            $monthlyData[$academicPos]['fine_amount'] += $row['fine_amount'];
            $monthlyData[$academicPos]['discount_amount'] += $row['discount_amount'];
            $monthlyData[$academicPos]['total_collected'] += $row['total_collected'];
        }
    }

    // Payment mode distribution
    $paymentModeData = $db->fetchAll("
        SELECT
            fc.payment_mode,
            COUNT(fc.payment_id) as mode_count,
            SUM(fc.total_paid) as mode_total
        FROM fee_collection fc
        WHERE fc.academic_year = :year
        GROUP BY fc.payment_mode
        ORDER BY mode_total DESC
    ", ['year' => $selectedSession]);

    // Calculate totals
    $totalPayments = 0;
    $totalStudents = 0;
    $totalFeeAmount = 0;
    $totalFineAmount = 0;
    $totalDiscountAmount = 0;
    $totalCollected = 0;

    foreach ($monthlyData as $row) {
        $totalPayments += $row['total_payments'];
        $totalStudents += $row['total_students'];
        $totalFeeAmount += $row['fee_amount'];
        $totalFineAmount += $row['fine_amount'];
        $totalDiscountAmount += $row['discount_amount'];
        $totalCollected += $row['total_collected'];
    }

} catch(Exception $e) {
    error_log($e->getMessage());
    $monthlyData = [];
    $paymentModeData = [];
    $totalPayments = $totalStudents = 0;
    $totalFeeAmount = $totalFineAmount = $totalDiscountAmount = $totalCollected = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-calendar-check"></i> Month-wise Collection Report
            <span class="badge bg-primary float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Payments</h6>
                <h3><?php echo number_format($totalPayments); ?></h3>
            </div>
        </div>
    </div>
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
                <h6>Total Fine</h6>
                <h3 class="text-warning"><?php echo formatCurrency($totalFineAmount); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-light">
            <div class="card-body text-center">
                <h6>Total Collection</h6>
                <h3 class="text-success"><?php echo formatCurrency($totalCollected); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<?php if ($totalPayments > 0): ?>
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Monthly Collection Comparison
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="monthlyBarChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Payment Mode Distribution
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="paymentModePieChart"></canvas></div>
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
                <span><i class="fas fa-list"></i> Month-wise Collection Summary</span>
                <span>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('month_wise_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
            </div>
            <div class="card-body">
                <?php if ($totalPayments > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Students</th>
                                <th class="text-end">Fee Amount</th>
                                <th class="text-end">Fine</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyData as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['month_name']); ?></strong></td>
                                <td class="text-end"><?php echo number_format($row['total_payments']); ?></td>
                                <td class="text-end"><?php echo number_format($row['total_students']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['fee_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['fine_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['discount_amount']); ?></td>
                                <td class="text-end"><strong><?php echo formatCurrency($row['total_collected']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th>Total</th>
                                <th class="text-end"><?php echo number_format($totalPayments); ?></th>
                                <th class="text-end"><?php echo number_format($totalStudents); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalFeeAmount); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalFineAmount); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalDiscountAmount); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalCollected); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted my-4">
                    <i class="fas fa-info-circle"></i> No collection data found for the selected academic year.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($totalPayments > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Bar Chart: Monthly Collection Comparison
const barCtx = document.getElementById('monthlyBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach($monthlyData as $row): ?>
                '<?php echo $row['month_name']; ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Fee Collection',
            data: [
                <?php foreach($monthlyData as $row): ?>
                    <?php echo $row['fee_amount']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(99, 102, 241, 0.7)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Fine',
            data: [
                <?php foreach($monthlyData as $row): ?>
                    <?php echo $row['fine_amount']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(245, 158, 11, 0.7)',
            borderColor: 'rgb(245, 158, 11)',
            borderWidth: 1,
            borderRadius: 4
        }, {
            label: 'Discount',
            data: [
                <?php foreach($monthlyData as $row): ?>
                    <?php echo $row['discount_amount']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: false }
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

// Pie Chart: Payment Mode Distribution
<?php if (!empty($paymentModeData)): ?>
const pieCtx = document.getElementById('paymentModePieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
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
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((context.parsed / total) * 100).toFixed(1);
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
