<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
requireRole(['sysadmin']); // Only system administrators can view logs

$pageTitle = 'Audit Log';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';

try {
    $db = getDB();

    // Build query
    $whereConditions = [];
    $params = [];

    if (!empty($filterUser)) {
        $whereConditions[] = "username LIKE :user";
        $params['user'] = "%$filterUser%";
    }

    if (!empty($filterAction)) {
        $whereConditions[] = "action LIKE :action";
        $params['action'] = "%$filterAction%";
    }

    if (!empty($filterDate)) {
        $whereConditions[] = "DATE(created_at) = :date";
        $params['date'] = $filterDate;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get total count
    $totalQuery = "SELECT COUNT(*) as total FROM audit_log $whereClause";
    $totalResult = $db->fetchOne($totalQuery, $params);
    $totalRecords = $totalResult['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Get audit logs
    $query = "SELECT * FROM audit_log $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $perPage;
    $params['offset'] = $offset;

    $logs = $db->fetchAll($query, $params);

    // Get unique actions for filter
    $actions = $db->fetchAll("SELECT DISTINCT action FROM audit_log ORDER BY action");

} catch (Exception $e) {
    $error = "Error loading audit logs: " . $e->getMessage();
    $logs = [];
    $actions = [];
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-clipboard-list"></i> Audit Log
        <span class="badge bg-secondary"><?= number_format($totalRecords) ?> entries</span>
    </h2>

    <!-- Filters -->
    <div class="card card-custom mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label-custom">Username</label>
                    <input type="text" class="form-control" name="user"
                           value="<?= htmlspecialchars($filterUser) ?>" placeholder="Search username">
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">Action</label>
                    <select class="form-select" name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act['action']) ?>"
                                    <?= $filterAction === $act['action'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act['action']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">Date</label>
                    <input type="date" class="form-control" name="date"
                           value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="audit_log.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="card card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    No audit logs found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= formatDate($log['created_at'], 'd-m-Y H:i:s') ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                    <td>
                                        <span class="badge <?= getActionBadgeClass($log['action']) ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['record_id'] ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($log['ip_address']) ?></small></td>
                                    <td>
                                        <?php if ($log['old_values'] || $log['new_values']): ?>
                                            <button class="btn btn-sm btn-info"
                                                    onclick="showDetails(<?= $log['log_id'] ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                    data-old='<?= htmlspecialchars($log['old_values'] ?? '{}') ?>'
                                                    data-new='<?= htmlspecialchars($log['new_values'] ?? '{}') ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination pagination-custom justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&user=<?= urlencode($filterUser) ?>&action=<?= urlencode($filterAction) ?>&date=<?= urlencode($filterDate) ?>">
                                Previous
                            </a>
                        </li>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&user=<?= urlencode($filterUser) ?>&action=<?= urlencode($filterAction) ?>&date=<?= urlencode($filterDate) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&user=<?= urlencode($filterUser) ?>&action=<?= urlencode($filterAction) ?>&date=<?= urlencode($filterDate) ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Audit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(logId) {
    // Get button that was clicked
    var button = event.target.closest('button');
    var oldValues = button.getAttribute('data-old');
    var newValues = button.getAttribute('data-new');

    var html = '';

    if (oldValues && oldValues !== '{}' && oldValues !== 'null') {
        html += '<h6>Old Values:</h6><pre class="bg-light p-3">' +
                JSON.stringify(JSON.parse(oldValues), null, 2) + '</pre>';
    }

    if (newValues && newValues !== '{}' && newValues !== 'null') {
        html += '<h6>New Values:</h6><pre class="bg-light p-3">' +
                JSON.stringify(JSON.parse(newValues), null, 2) + '</pre>';
    }

    document.getElementById('modalContent').innerHTML = html || '<p>No details available</p>';
}
</script>

<?php
function getActionBadgeClass($action) {
    if (strpos($action, 'LOGIN') !== false) return 'bg-info';
    if (strpos($action, 'CREATE') !== false || strpos($action, 'ADD') !== false) return 'bg-success';
    if (strpos($action, 'DELETE') !== false) return 'bg-danger';
    if (strpos($action, 'UPDATE') !== false || strpos($action, 'EDIT') !== false) return 'bg-warning';
    if (strpos($action, 'FAILED') !== false || strpos($action, 'ERROR') !== false) return 'bg-danger';
    return 'bg-secondary';
}

include '../includes/footer.php';
?>
