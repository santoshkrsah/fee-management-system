<?php
/**
 * Manage Academic Sessions
 * Accessible only by sysadmin role
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'Academic Sessions';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('manage_sessions.php', 'error', 'Invalid security token. Please try again.');
    }

    $action = $_POST['action'] ?? '';
    $db = getDB();

    // ---- ADD SESSION ----
    if ($action === 'add') {
        $sessionName = sanitize($_POST['session_name'] ?? '');
        $startDate   = sanitize($_POST['start_date'] ?? '');
        $endDate     = sanitize($_POST['end_date'] ?? '');

        if (empty($sessionName) || empty($startDate) || empty($endDate)) {
            redirectWithMessage('manage_sessions.php', 'error', 'All fields are required.');
        }

        // Validate session name format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $sessionName)) {
            redirectWithMessage('manage_sessions.php', 'error', 'Session name must be in YYYY-YYYY format (e.g. 2026-2027).');
        }

        // Validate that second year = first year + 1
        $parts = explode('-', $sessionName);
        if ((int)$parts[1] !== (int)$parts[0] + 1) {
            redirectWithMessage('manage_sessions.php', 'error', 'The second year must be exactly one more than the first year.');
        }

        // Validate dates
        if (!strtotime($startDate) || !strtotime($endDate)) {
            redirectWithMessage('manage_sessions.php', 'error', 'Invalid date format.');
        }

        if (strtotime($endDate) <= strtotime($startDate)) {
            redirectWithMessage('manage_sessions.php', 'error', 'End date must be after start date.');
        }

        try {
            $existing = $db->fetchOne(
                "SELECT session_id FROM academic_sessions WHERE session_name = :name",
                ['name' => $sessionName]
            );
            if ($existing) {
                redirectWithMessage('manage_sessions.php', 'error', 'Session "' . $sessionName . '" already exists.');
            }

            $db->query(
                "INSERT INTO academic_sessions (session_name, start_date, end_date, is_active) VALUES (:name, :start, :end, 0)",
                ['name' => $sessionName, 'start' => $startDate, 'end' => $endDate]
            );

            redirectWithMessage('manage_sessions.php', 'success', 'Academic session "' . $sessionName . '" added successfully.');

        } catch (Exception $e) {
            error_log("Add Session Error: " . $e->getMessage());
            redirectWithMessage('manage_sessions.php', 'error', 'Failed to add session. Please try again.');
        }
    }

    // ---- SET ACTIVE ----
    elseif ($action === 'set_active') {
        $sessionId = intval($_POST['session_id'] ?? 0);

        if ($sessionId <= 0) {
            redirectWithMessage('manage_sessions.php', 'error', 'Invalid session ID.');
        }

        try {
            $target = $db->fetchOne(
                "SELECT session_name, is_active FROM academic_sessions WHERE session_id = :id",
                ['id' => $sessionId]
            );
            if (!$target) {
                redirectWithMessage('manage_sessions.php', 'error', 'Session not found.');
            }

            if ($target['is_active']) {
                redirectWithMessage('manage_sessions.php', 'info', 'Session "' . $target['session_name'] . '" is already active.');
            }

            $db->beginTransaction();
            $db->query("UPDATE academic_sessions SET is_active = 0 WHERE is_active = 1");
            $db->query(
                "UPDATE academic_sessions SET is_active = 1 WHERE session_id = :id",
                ['id' => $sessionId]
            );
            $db->commit();

            redirectWithMessage('manage_sessions.php', 'success', 'Session "' . $target['session_name'] . '" is now the active session.');

        } catch (Exception $e) {
            $db->rollback();
            error_log("Set Active Session Error: " . $e->getMessage());
            redirectWithMessage('manage_sessions.php', 'error', 'Failed to update active session. Please try again.');
        }
    }

    // ---- DELETE SESSION ----
    elseif ($action === 'delete') {
        $sessionId = intval($_POST['session_id'] ?? 0);

        if ($sessionId <= 0) {
            redirectWithMessage('manage_sessions.php', 'error', 'Invalid session ID.');
        }

        try {
            $target = $db->fetchOne(
                "SELECT session_name, is_active FROM academic_sessions WHERE session_id = :id",
                ['id' => $sessionId]
            );
            if (!$target) {
                redirectWithMessage('manage_sessions.php', 'error', 'Session not found.');
            }

            if ($target['is_active']) {
                redirectWithMessage('manage_sessions.php', 'error', 'Cannot delete the active session. Set another session as active first.');
            }

            // Check for linked fee structures
            $feeCount = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM fee_structure WHERE academic_year = :name",
                ['name' => $target['session_name']]
            );
            if ($feeCount && $feeCount['cnt'] > 0) {
                redirectWithMessage('manage_sessions.php', 'error', 'Cannot delete: ' . $feeCount['cnt'] . ' fee structure(s) are linked to this session.');
            }

            // Check for linked fee collections
            $collectionCount = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM fee_collection WHERE academic_year = :name",
                ['name' => $target['session_name']]
            );
            if ($collectionCount && $collectionCount['cnt'] > 0) {
                redirectWithMessage('manage_sessions.php', 'error', 'Cannot delete: ' . $collectionCount['cnt'] . ' payment(s) are linked to this session.');
            }

            $db->query("DELETE FROM academic_sessions WHERE session_id = :id", ['id' => $sessionId]);

            redirectWithMessage('manage_sessions.php', 'success', 'Session "' . $target['session_name'] . '" deleted successfully.');

        } catch (Exception $e) {
            error_log("Delete Session Error: " . $e->getMessage());
            redirectWithMessage('manage_sessions.php', 'error', 'Failed to delete session. Please try again.');
        }
    }
}

// Fetch all sessions
try {
    $db = getDB();
    $sessions = $db->fetchAll("
        SELECT s.*,
               (SELECT COUNT(*) FROM fee_structure fs WHERE fs.academic_year = s.session_name) as fee_structure_count,
               (SELECT COUNT(*) FROM fee_collection fc WHERE fc.academic_year = s.session_name) as payment_count
        FROM academic_sessions s
        ORDER BY s.session_name DESC
    ");
} catch (Exception $e) {
    error_log("Fetch Sessions Error: " . $e->getMessage());
    $sessions = [];
}

$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-calendar-alt"></i> Academic Sessions
            </h2>
            <button type="button" class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                <i class="fas fa-plus"></i> Add New Session
            </button>
        </div>
    </div>
</div>

<!-- Sessions Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-list"></i> All Academic Sessions
                <span class="badge bg-secondary ms-2"><?php echo count($sessions); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($sessions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Session Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Fee Structures</th>
                                <th>Payments</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $index => $sess): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($sess['session_name']); ?></strong></td>
                                <td><?php echo formatDate($sess['start_date']); ?></td>
                                <td><?php echo formatDate($sess['end_date']); ?></td>
                                <td>
                                    <?php if ($sess['fee_structure_count'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $sess['fee_structure_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sess['payment_count'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $sess['payment_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sess['is_active']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($sess['created_at'], 'd-m-Y'); ?></td>
                                <td class="text-center">
                                    <?php if (!$sess['is_active']): ?>
                                        <form method="POST" action="manage_sessions.php" class="d-inline"
                                              onsubmit="return confirm('Set &quot;<?php echo htmlspecialchars($sess['session_name']); ?>&quot; as the active session?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="session_id" value="<?php echo $sess['session_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success btn-icon me-1" title="Set as Active">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>

                                        <?php if ($sess['fee_structure_count'] == 0 && $sess['payment_count'] == 0): ?>
                                        <form method="POST" action="manage_sessions.php" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete session &quot;<?php echo htmlspecialchars($sess['session_name']); ?>&quot;?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="session_id" value="<?php echo $sess['session_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete Session">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No academic sessions found. Add a session to get started.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-labelledby="addSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_sessions.php" id="addSessionForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSessionModalLabel">
                        <i class="fas fa-plus"></i> Add New Academic Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_session_name" class="form-label form-label-custom">Session Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="add_session_name" name="session_name"
                               required maxlength="20" placeholder="e.g. 2027-2028" pattern="\d{4}-\d{4}">
                        <div class="form-text">Format: YYYY-YYYY (e.g. 2027-2028)</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_start_date" class="form-label form-label-custom">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-custom" id="add_start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_end_date" class="form-label form-label-custom">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-custom" id="add_end_date" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save"></i> Add Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-fill dates based on session name
    $('#add_session_name').on('input', function() {
        var val = $(this).val();
        var match = val.match(/^(\d{4})-(\d{4})$/);
        if (match) {
            $('#add_start_date').val(match[1] + '-04-01');
            $('#add_end_date').val(match[2] + '-03-31');
        }
    });

    // Reset form on modal close
    $('#addSessionModal').on('hidden.bs.modal', function() {
        $('#addSessionForm')[0].reset();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
