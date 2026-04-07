<?php
/**
 * Date-wise Collection Report
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'Date-wise Collection Report';

$selectedSession = getSelectedSession();

// Filters
$from_date = sanitize($_GET['from_date'] ?? date('Y-m-01')); // First day of current month
$to_date = sanitize($_GET['to_date'] ?? date('Y-m-d')); // Today

try {
    $db = getDB();

    // Date-wise collection
    $dateWiseData = $db->fetchAll("
        SELECT
            fc.payment_date,
            COUNT(fc.payment_id) as total_payments,
            COUNT(DISTINCT fc.student_id) as total_students,
            SUM(fc.tuition_fee_paid + fc.exam_fee_paid + fc.library_fee_paid +
                fc.sports_fee_paid + fc.lab_fee_paid + fc.transport_fee_paid +
                fc.other_charges_paid) as fee_amount,
            SUM(fc.fine) as fine_amount,
            SUM(fc.discount) as discount_amount,
            SUM(fc.total_paid) as total_collected
        FROM fee_collection fc
        WHERE fc.payment_date BETWEEN :from_date AND :to_date
              AND fc.academic_year = :year
        GROUP BY fc.payment_date
        ORDER BY fc.payment_date DESC
    ", [
        'from_date' => $from_date,
        'to_date' => $to_date,
        'year' => $selectedSession
    ]);

    // Calculate totals
    $totalPayments = 0;
    $totalStudents = 0;
    $totalFeeAmount = 0;
    $totalFineAmount = 0;
    $totalDiscountAmount = 0;
    $totalCollected = 0;

    foreach($dateWiseData as $row) {
        $totalPayments += $row['total_payments'];
        $totalStudents += $row['total_students'];
        $totalFeeAmount += $row['fee_amount'];
        $totalFineAmount += $row['fine_amount'];
        $totalDiscountAmount += $row['discount_amount'];
        $totalCollected += $row['total_collected'];
    }

} catch(Exception $e) {
    error_log($e->getMessage());
    $dateWiseData = [];
    $totalPayments = $totalStudents = 0;
    $totalFeeAmount = $totalFineAmount = $totalDiscountAmount = $totalCollected = 0;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-calendar-alt"></i> Date-wise Collection Report
            <span class="badge bg-primary float-end"><?php echo htmlspecialchars($selectedSession); ?></span>
        </h2>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-custom">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($from_date); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-custom max-today"
                               value="<?php echo htmlspecialchars($to_date); ?>" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-search"></i> Generate
                        </button>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">&nbsp;</label>
                        <a href="date_wise_report.php" class="btn btn-secondary btn-custom w-100">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
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

<!-- Chart Section -->
<?php if (count($dateWiseData) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Collection Trend
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="collectionChart"></canvas></div>
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
                <span><i class="fas fa-list"></i> Date-wise Collection Summary</span>
                <span>
                    <small class="me-3">Period: <?php echo formatDate($from_date); ?> to <?php echo formatDate($to_date); ?></small>
                    <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV('date_wise_report.csv')"><i class="fas fa-file-csv"></i> CSV</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($dateWiseData) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-center">No. of Payments</th>
                                <th class="text-center">No. of Students</th>
                                <th class="text-end">Fee Amount</th>
                                <th class="text-end">Fine</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dateWiseData as $row): ?>
                            <tr>
                                <td><strong><?php echo formatDate($row['payment_date']); ?></strong></td>
                                <td class="text-center"><?php echo number_format($row['total_payments']); ?></td>
                                <td class="text-center"><?php echo number_format($row['total_students']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['fee_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['fine_amount']); ?></td>
                                <td class="text-end text-danger"><?php echo formatCurrency($row['discount_amount']); ?></td>
                                <td class="text-end text-success"><strong><?php echo formatCurrency($row['total_collected']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th>Total</th>
                                <th class="text-center"><?php echo number_format($totalPayments); ?></th>
                                <th class="text-center"><?php echo number_format($totalStudents); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalFeeAmount); ?></th>
                                <th class="text-end"><?php echo formatCurrency($totalFineAmount); ?></th>
                                <th class="text-end text-danger"><?php echo formatCurrency($totalDiscountAmount); ?></th>
                                <th class="text-end text-success"><strong><?php echo formatCurrency($totalCollected); ?></strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No collection data found for the selected date range.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (count($dateWiseData) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('collectionChart').getContext('2d');
    const collectionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach($dateWiseData as $row): ?>
                    '<?php echo formatDate($row['payment_date'], 'd M'); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Daily Collection',
                data: [
                    <?php foreach($dateWiseData as $row): ?>
                        <?php echo $row['total_collected']; ?>,
                    <?php endforeach; ?>
                ],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹ ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
