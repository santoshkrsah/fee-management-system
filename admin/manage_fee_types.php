<?php
/**
 * Manage Fee Types - Admin Interface
 * Add, edit, and delete custom fee types
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';
require_once '../../includes/fee_type_helper.php';

requireRole(['sysadmin', 'admin']);

$pageTitle = 'Manage Fee Types';
$error = '';
$success = '';

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        if ($_POST['action'] === 'update') {
            $fee_type_id = (int)$_POST['fee_type_id'];
            $label = sanitize($_POST['label'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            if (empty($label)) {
                throw new Exception('Fee type label is required');
            }

            if (!updateFeeType($fee_type_id, $label, $description, $sort_order)) {
                throw new Exception('Failed to update fee type');
            }

            $success = 'Fee type updated successfully!';

        } elseif ($_POST['action'] === 'deactivate' && isSysAdmin()) {
            $fee_type_id = (int)$_POST['fee_type_id'];

            $check = canDeactivateFeeType($fee_type_id);
            if (!$check['allowed']) {
                throw new Exception($check['reason']);
            }

            if (!deactivateFeeType($fee_type_id)) {
                throw new Exception('Failed to deactivate fee type');
            }

            $success = 'Fee type deactivated successfully!';

        } elseif ($_POST['action'] === 'reactivate' && isSysAdmin()) {
            $fee_type_id = (int)$_POST['fee_type_id'];

            if (!reactivateFeeType($fee_type_id)) {
                throw new Exception('Failed to reactivate fee type');
            }

            $success = 'Fee type reactivated successfully!';
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all fee types including inactive
$feeTypes = getAllFeeTypes(true);

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-list"></i> Manage Fee Types
        </h2>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Fee Types List -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-table"></i> All Fee Types
                <span class="badge bg-primary ms-2"><?php echo count($feeTypes); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($feeTypes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Display Order</th>
                                <th>Label</th>
                                <th>System Code</th>
                                <th>Database Column</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeTypes as $ft): ?>
                            <tr>
                                <td><strong><?php echo $ft['sort_order']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($ft['label']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($ft['code']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($ft['column_name']); ?></code></td>
                                <td>
                                    <?php if ($ft['is_system_defined']): ?>
                                    <span class="badge bg-info"><i class="fas fa-lock"></i> System</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ft['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-icon" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $ft['fee_type_id']; ?>" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$ft['is_system_defined'] && isSysAdmin()): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="fee_type_id" value="<?php echo $ft['fee_type_id']; ?>">
                                        <?php if ($ft['is_active']): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="btn btn-sm btn-warning btn-icon" title="Deactivate"
                                                onclick="return confirm('Are you sure you want to deactivate this fee type?');">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                        <?php else: ?>
                                        <input type="hidden" name="action" value="reactivate">
                                        <button type="submit" class="btn btn-sm btn-success btn-icon" title="Reactivate">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $ft['fee_type_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Fee Type</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="fee_type_id" value="<?php echo $ft['fee_type_id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label-custom">Label</label>
                                                    <input type="text" name="label" class="form-control form-control-custom"
                                                           value="<?php echo htmlspecialchars($ft['label']); ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label-custom">Description</label>
                                                    <textarea name="description" class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars($ft['description'] ?? ''); ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label-custom">Display Order</label>
                                                    <input type="number" name="sort_order" class="form-control form-control-custom"
                                                           value="<?php echo $ft['sort_order']; ?>" min="0" required>
                                                </div>

                                                <div class="alert alert-info">
                                                    <strong>Code:</strong> <code><?php echo htmlspecialchars($ft['code']); ?></code><br>
                                                    <strong>Column:</strong> <code><?php echo htmlspecialchars($ft['column_name']); ?></code>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No fee types found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Info Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Information
            </div>
            <div class="card-body">
                <h6>About Fee Types</h6>
                <ul>
                    <li><strong>System Fee Types</strong> are predefined and cannot be deleted. You can only edit their display labels and descriptions.</li>
                    <li><strong>Custom Fee Types</strong> (if added in future) can be deactivated if not in use. Deactivation is reversible.</li>
                    <li><strong>Display Order</strong> determines how fee types appear in forms, fee structures, and receipts.</li>
                    <li>Changes to fee type labels are immediately reflected across the system in:</li>
                    <ul>
                        <li>Fee Structure Management forms</li>
                        <li>Fee Collection pages</li>
                        <li>Payment Receipts</li>
                        <li>Reports</li>
                    </ul>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
