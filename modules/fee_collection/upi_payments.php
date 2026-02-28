<?php
/**
 * Admin UPI Payments List
 * View and manage UPI payment submissions from students
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/upi_helper.php';

requireLogin();

$pageTitle = 'UPI Payments';

// Filters
$status = sanitize($_GET['status'] ?? 'Pending');
$search = sanitize($_GET['search'] ?? '');
$from_date = sanitize($_GET['from_date'] ?? '');
$to_date = sanitize($_GET['to_date'] ?? '');

// Validate status filter
$validStatuses = ['Pending', 'Approved', 'Rejected', 'All'];
if (!in_array($status, $validStatuses)) {
    $status = 'Pending';
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();

    // Build WHERE clause
    $where = " WHERE 1=1";
    $params = [];

    if ($status !== 'All') {
        $where .= " AND up.status = :status";
        $params['status'] = $status;
    }

    if (!empty($search)) {
        $where .= " AND (
            s.admission_no LIKE :search1 OR
            s.first_name LIKE :search2 OR
            s.last_name LIKE :search3 OR
            up.utr_number LIKE :search4
        )";
        $searchVal = '%' . $search . '%';
        $params['search1'] = $searchVal;
        $params['search2'] = $searchVal;
        $params['search3'] = $searchVal;
        $params['search4'] = $searchVal;
    }

    if (!empty($from_date)) {
        $where .= " AND DATE(up.submitted_at) >= :from_date";
        $params['from_date'] = $from_date;
    }

    if (!empty($to_date)) {
        $where .= " AND DATE(up.submitted_at) <= :to_date";
        $params['to_date'] = $to_date;
    }

    $joinClause = "
        FROM upi_payments up
        JOIN students s ON up.student_id = s.student_id
        JOIN classes c ON s.class_id = c.class_id
        JOIN sections sec ON s.section_id = sec.section_id
    ";

    // Count total records
    $countResult = $db->fetchOne("SELECT COUNT(*) as total $joinClause $where", $params);
    $totalRecords = (int)($countResult['total'] ?? 0);
    $totalPages = max(1, ceil($totalRecords / $perPage));

    // Get status counts
    $statusCounts = $db->fetchAll("
        SELECT status, COUNT(*) as cnt FROM upi_payments GROUP BY status
    ");
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    foreach ($statusCounts as $sc) {
        $counts[$sc['status']] = (int)$sc['cnt'];
    }

    // Fetch payments
    $payments = $db->fetchAll("
        SELECT up.*, s.admission_no, s.first_name, s.last_name,
               c.class_name, sec.section_name,
               a.full_name as reviewer_name
        $joinClause
        LEFT JOIN admin a ON up.reviewed_by = a.admin_id
        $where
        ORDER BY
            CASE up.status WHEN 'Pending' THEN 0 WHEN 'Rejected' THEN 1 ELSE 2 END,
            up.submitted_at DESC
        LIMIT $perPage OFFSET $offset
    ", $params);

} catch (Exception $e) {
    error_log("UPI payments list error: " . $e->getMessage());
    $payments = [];
    $totalRecords = 0;
    $totalPages = 1;
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-qrcode"></i> UPI Payments
            <?php if ($counts['Pending'] > 0): ?>
            <span class="badge bg-warning text-dark fs-6 ms-2"><?php echo $counts['Pending']; ?> Pending</span>
            <?php endif; ?>
        </h2>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="row mb-3">
    <div class="col-12">
        <div class="btn-group" role="group">
            <a href="?status=Pending" class="btn btn-<?php echo $status === 'Pending' ? 'warning' : 'outline-warning'; ?>">
                Pending <span class="badge bg-dark"><?php echo $counts['Pending']; ?></span>
            </a>
            <a href="?status=Approved" class="btn btn-<?php echo $status === 'Approved' ? 'success' : 'outline-success'; ?>">
                Approved <span class="badge bg-dark"><?php echo $counts['Approved']; ?></span>
            </a>
            <a href="?status=Rejected" class="btn btn-<?php echo $status === 'Rejected' ? 'danger' : 'outline-danger'; ?>">
                Rejected <span class="badge bg-dark"><?php echo $counts['Rejected']; ?></span>
            </a>
            <a href="?status=All" class="btn btn-<?php echo $status === 'All' ? 'primary' : 'outline-primary'; ?>">
                All <span class="badge bg-dark"><?php echo array_sum($counts); ?></span>
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <div class="col-md-4">
                        <label class="form-label small mb-0">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm form-control-custom"
                               placeholder="Student name, admission no, or UTR"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm form-control-custom"
                               value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm form-control-custom"
                               value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th class="text-end">Amount</th>
                                <th>UTR Number</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sn = $offset + 1; foreach ($payments as $p): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($p['submitted_at'])); ?>
                                    <br><small class="text-muted"><?php echo date('h:i A', strtotime($p['submitted_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($p['admission_no']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($p['class_name'] . ' - ' . $p['section_name']); ?></td>
                                <td class="text-end"><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($p['utr_number']); ?></code></td>
                                <td class="text-center">
                                    <?php
                                    $badge = match($p['status']) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                                    <?php if ($p['status'] !== 'Pending' && !empty($p['reviewer_name'])): ?>
                                    <br><small class="text-muted">by <?php echo htmlspecialchars($p['reviewer_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="review_upi_payment.php?id=<?php echo $p['upi_payment_id']; ?>"
                                       class="btn btn-sm btn-<?php echo $p['status'] === 'Pending' ? 'primary' : 'outline-secondary'; ?>"
                                       title="Review">
                                        <i class="fas fa-eye"></i>
                                        <?php echo $p['status'] === 'Pending' ? 'Review' : 'View'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>">Previous</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <p class="text-center text-muted small mt-1">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> records
                </p>
                <?php endif; ?>

                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox text-muted" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="text-muted mt-3 mb-0">No UPI payment submissions found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
