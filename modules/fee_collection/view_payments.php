<?php
/**
 * View All Fee Payments (Paginated)
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$pageTitle = 'View Payments';

$feeMode = getFeeMode();
$academicMonths = getAcademicMonths();
$selectedSession = getSelectedSession();

// Month filter (monthly mode only; consolidated = no month filter)
$selectedMonthFilter = '';
if ($feeMode === 'monthly' && isset($_GET['month']) && $_GET['month'] !== '' && $_GET['month'] !== 'consolidated') {
    $selectedMonthFilter = (int)$_GET['month'];
}

// Filters
$search = sanitize($_GET['search'] ?? '');
$from_date = sanitize($_GET['from_date'] ?? '');
$to_date = sanitize($_GET['to_date'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();

    // Build WHERE clause (shared by COUNT, SUM, and SELECT)
    $where = " WHERE 1=1";
    $params = [];

    // Always filter by selected academic year
    $where .= " AND fc.academic_year = :session";
    $params['session'] = $selectedSession;

    // In monthly mode with a specific month selected, filter by fee_month
    if ($feeMode === 'monthly' && $selectedMonthFilter !== '') {
        $where .= " AND fc.fee_month = :fee_month";
        $params['fee_month'] = $selectedMonthFilter;
    }

    if (!empty($search)) {
        $where .= " AND (
            fc.receipt_no LIKE :search1 OR
            s.admission_no LIKE :search2 OR
            s.first_name LIKE :search3 OR
            s.last_name LIKE :search4
        )";
        $searchVal = '%' . $search . '%';
        $params['search1'] = $searchVal;
        $params['search2'] = $searchVal;
        $params['search3'] = $searchVal;
        $params['search4'] = $searchVal;
    }

    if (!empty($from_date)) {
        $where .= " AND fc.payment_date >= :from_date";
        $params['from_date'] = $from_date;
    }

    if (!empty($to_date)) {
        $where .= " AND fc.payment_date <= :to_date";
        $params['to_date'] = $to_date;
    }

    $joinClause = "
        FROM fee_collection fc
        JOIN students s ON fc.student_id = s.student_id
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
    ";

    // COUNT query for total
    $countQuery = "SELECT COUNT(*) AS total" . $joinClause . $where;
    $countRow = $db->fetchOne($countQuery, $params);
    $totalPayments = (int)($countRow['total'] ?? 0);
    $totalPages = max(1, (int)ceil($totalPayments / $perPage));

    // SUM query for total amount (full filtered set, not just current page)
    $sumQuery = "SELECT COALESCE(SUM(fc.total_paid), 0) AS total_amount" . $joinClause . $where;
    $sumRow = $db->fetchOne($sumQuery, $params);
    $totalAmount = (float)($sumRow['total_amount'] ?? 0);

    // Clamp page
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    // Main query with LIMIT/OFFSET
    $query = "
        SELECT
            fc.*,
            s.admission_no,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            c.class_name,
            sec.section_name
    " . $joinClause . $where . "
        ORDER BY fc.payment_date DESC, fc.payment_id DESC
        LIMIT " . intval($perPage) . " OFFSET " . intval($offset);

    $payments = $db->fetchAll($query, $params);

} catch(Exception $e) {
    error_log($e->getMessage());
    $payments = [];
    $totalAmount = 0;
    $totalPayments = 0;
    $totalPages = 1;
}

// Helper: build pagination URL preserving current filters
function buildPaymentPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return '?' . http_build_query($params);
}

$showingFrom = $totalPayments > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $totalPayments);

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-list-alt"></i> View Payments
            <a href="collect_fee.php" class="btn btn-success btn-custom float-end">
                <i class="fas fa-plus"></i> Collect Fee
            </a>
        </h2>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <?php if ($feeMode === 'monthly' && isset($_GET['month'])): ?>
                    <input type="hidden" name="month" value="<?php echo htmlspecialchars($_GET['month']); ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label-custom">Search</label>
                        <input type="text" name="search" class="form-control form-control-custom"
                               placeholder="Receipt no, student name, admission no..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-custom"
                               value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label-custom">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
                <div class="mt-2">
                    <a href="view_payments.php" class="btn btn-secondary btn-sm btn-custom">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="fas fa-list"></i> Total Payments: <?php echo $totalPayments; ?>
                    <?php if ($feeMode === 'monthly' && $selectedMonthFilter !== ''): ?>
                        <small class="text-muted ms-1">(<?php echo htmlspecialchars($academicMonths[$selectedMonthFilter] ?? ''); ?>)</small>
                    <?php elseif ($feeMode === 'monthly'): ?>
                        <small class="text-muted ms-1">(All Months)</small>
                    <?php endif; ?>
                    <?php if ($totalPayments > 0): ?>
                        <small class="text-muted ms-2">(Showing <?php echo $showingFrom; ?>-<?php echo $showingTo; ?>)</small>
                    <?php endif; ?>
                </span>
                <span>
                    <?php if (canDelete() && count($payments) > 0): ?>
                    <button type="submit" form="bulkDeleteForm" class="btn btn-danger btn-sm me-2" id="bulkDeleteBtn" disabled onclick="return confirm('Are you sure you want to delete the selected payment records? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <?php endif; ?>
                    Total Amount: <?php echo formatCurrency($totalAmount); ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                <?php if (canDelete()): ?>
                <form id="bulkDeleteForm" method="POST" action="bulk_delete_payments.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <?php if (canDelete()): ?>
                                <th style="width: 40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                <?php endif; ?>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <?php if ($feeMode === 'monthly'): ?>
                                <th>Month</th>
                                <?php endif; ?>
                                <th>Payment Mode</th>
                                <th class="text-end">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payments as $payment): ?>
                            <tr>
                                <?php if (canDelete()): ?>
                                <td><input type="checkbox" name="payment_ids[]" value="<?php echo $payment['payment_id']; ?>" class="form-check-input row-checkbox"></td>
                                <?php endif; ?>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['student_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['admission_no']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section_name']); ?></td>
                                <?php if ($feeMode === 'monthly'): ?>
                                <td>
                                    <?php if (!empty($payment['fee_month'])): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($academicMonths[$payment['fee_month']] ?? '-'); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($payment['payment_mode']); ?></span>
                                </td>
                                <td class="text-end"><strong><?php echo formatCurrency($payment['total_paid']); ?></strong></td>
                                <td>
                                    <?php if (canEdit()): ?>
                                    <a href="edit_payment.php?id=<?php echo $payment['payment_id']; ?>"
                                       class="btn btn-sm btn-warning btn-icon" title="Edit Payment">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>"
                                       class="btn btn-sm btn-primary btn-icon" title="View Receipt" target="_blank">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                    <?php if (canDelete()): ?>
                                    <a href="delete_payment.php?id=<?php echo $payment['payment_id']; ?>"
                                       class="btn btn-sm btn-danger btn-icon delete-btn" title="Delete Payment">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <?php
                                    $baseColspan = 5;
                                    if (canDelete()) $baseColspan++;
                                    if ($feeMode === 'monthly') $baseColspan++;
                                ?>
                                <th colspan="<?php echo $baseColspan; ?>" class="text-end">Total:</th>
                                <th class="text-end"><?php echo formatCurrency($totalAmount); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php if (canDelete()): ?>
                </form>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Payment pagination" class="mt-3">
                    <ul class="pagination pagination-custom justify-content-center mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaymentPageUrl(1); ?>" title="First">&laquo;</a>
                        </li>
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaymentPageUrl($page - 1); ?>" title="Previous">&lsaquo;</a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        if ($startPage > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaymentPageUrl($i); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor;

                        if ($endPage < $totalPages): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>

                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaymentPageUrl($page + 1); ?>" title="Next">&rsaquo;</a>
                        </li>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaymentPageUrl($totalPages); ?>" title="Last">&raquo;</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-center text-muted">No payment records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (canDelete()): ?>
<script>
$(document).ready(function() {
    function updateSelectedCount() {
        var count = $('.row-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#bulkDeleteBtn').prop('disabled', count === 0);
    }

    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    $(document).on('change', '.row-checkbox', function() {
        var total = $('.row-checkbox').length;
        var checked = $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked);
        updateSelectedCount();
    });
});
</script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
