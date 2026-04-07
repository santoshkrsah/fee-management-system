<?php
/**
 * Manage Fee Types - Admin Interface
 */
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/fee_type_helper.php';

requireRole(['sysadmin', 'admin']);

$pageTitle = 'Manage Fee Types';
$error   = '';
$success = '';

$db = getDB();

// ── Handle form submissions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }

        $action = $_POST['action'];

        // ── Edit label / description / sort order ─────────────────────────────
        if ($action === 'update') {
            $fee_type_id = (int)$_POST['fee_type_id'];
            $label       = trim($_POST['label'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sort_order  = (int)($_POST['sort_order'] ?? 0);

            if ($label === '') {
                throw new Exception('Fee type label cannot be empty.');
            }
            if (!updateFeeType($fee_type_id, $label, $description, $sort_order)) {
                throw new Exception('Failed to update fee type. Please try again.');
            }
            $success = 'Fee type updated successfully!';

        // ── Deactivate (hide from forms) ──────────────────────────────────────
        } elseif ($action === 'deactivate') {
            $fee_type_id = (int)$_POST['fee_type_id'];
            $check = canDeactivateFeeType($fee_type_id);
            if (!$check['allowed']) {
                throw new Exception($check['reason']);
            }
            if (!deactivateFeeType($fee_type_id)) {
                throw new Exception('Failed to deactivate fee type.');
            }
            $success = 'Fee type hidden successfully.';

        // ── Reactivate ────────────────────────────────────────────────────────
        } elseif ($action === 'reactivate') {
            $fee_type_id = (int)$_POST['fee_type_id'];
            if (!reactivateFeeType($fee_type_id)) {
                throw new Exception('Failed to reactivate fee type.');
            }
            $success = 'Fee type restored successfully.';

        // ── Add new fee type ──────────────────────────────────────────────────
        } elseif ($action === 'add_type') {
            $label       = trim($_POST['label'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sort_order  = (int)($_POST['sort_order'] ?? 99);

            if ($label === '') throw new Exception('Label is required.');
            addFeeType($label, $description, $sort_order);
            $success = "Fee type \"{$label}\" added successfully! The new column is now available in Fee Structure.";

        // ── Delete fee type permanently ───────────────────────────────────────
        } elseif ($action === 'delete_type') {
            $fee_type_id = (int)$_POST['fee_type_id'];
            deleteFeeType($fee_type_id);
            $success = 'Fee type deleted permanently.';
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ── Fetch all fee types (including inactive) ──────────────────────────────────
$feeTypes = getAllFeeTypes(true);

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-1">
            <i class="fas fa-tags"></i> Manage Fee Types
        </h2>
        <p class="text-muted mb-4">Edit the display label, description, and sort order of each fee type. Changes reflect immediately across Fee Structure, Collection, and Receipts.</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($feeTypes)): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-1"></i>
    No fee types found. This usually means the <code>fee_types</code> table has not been seeded yet.
    <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="alert-link">Refresh this page</a> — the system will auto-create and seed the default types.
</div>
<?php else: ?>

<!-- Add New Fee Type -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-custom border-primary">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-plus-circle me-1"></i> Add New Fee Type
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Adding a fee type creates a new column in the database and makes it available in
                    Fee Structure, Fee Collection, and Receipts.
                </p>
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="action"     value="add_type">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="col-md-4">
                        <label class="form-label-custom">Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control form-control-custom"
                               placeholder="e.g. Activity Fee, Hostel Fee"
                               maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Description <small class="text-muted fw-normal">(optional)</small></label>
                        <input type="text" name="description" class="form-control form-control-custom"
                               placeholder="Brief description" maxlength="255">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-custom">Display Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-custom"
                               value="<?php echo count($feeTypes) + 1; ?>" min="1" max="99">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Add Fee Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Fee Types Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-list"></i> Fee Types
                    <span class="badge bg-primary ms-2"><?php echo count($feeTypes); ?></span>
                </span>
                <small class="text-muted">Click <i class="fas fa-edit"></i> to edit any fee type</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Label <small class="text-muted fw-normal">(shown everywhere)</small></th>
                                <th>Description</th>
                                <th style="width:80px" class="text-center">Order</th>
                                <th style="width:90px" class="text-center">Type</th>
                                <th style="width:90px" class="text-center">Status</th>
                                <th style="width:110px" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeTypes as $ft): ?>
                            <tr class="<?php echo $ft['is_active'] ? '' : 'table-secondary opacity-75'; ?>">
                                <td class="text-muted"><?php echo (int)$ft['sort_order']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ft['label']); ?></strong>
                                    <br><small class="text-muted font-monospace"><?php echo htmlspecialchars($ft['column_name']); ?></small>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($ft['description'] ?? '—'); ?></small></td>
                                <td class="text-center"><?php echo (int)$ft['sort_order']; ?></td>
                                <td class="text-center">
                                    <?php if ($ft['is_system_defined']): ?>
                                    <span class="badge bg-info"><i class="fas fa-lock me-1"></i>System</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($ft['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <!-- Edit button — available for ALL types -->
                                    <button class="btn btn-sm btn-warning btn-icon"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $ft['fee_type_id']; ?>"
                                            title="Edit label &amp; description">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Hide / Restore — available for ALL types -->
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="fee_type_id" value="<?php echo $ft['fee_type_id']; ?>">
                                        <?php if ($ft['is_active']): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"
                                                title="Hide this fee type from forms"
                                                onclick="return confirm('Hide \'<?php echo htmlspecialchars($ft['label']); ?>\' from all forms?\n\nExisting payment data will not be affected.');">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                        <?php else: ?>
                                        <input type="hidden" name="action" value="reactivate">
                                        <button type="submit" class="btn btn-sm btn-outline-success btn-icon" title="Restore this fee type">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if (!$ft['is_system_defined']): ?>
                                        <!-- Permanent delete — custom types only -->
                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Permanently delete \'<?php echo addslashes(htmlspecialchars($ft['label'])); ?>\'?\n\nThis will drop its column from the database and cannot be undone.');">
                                            <input type="hidden" name="action"      value="delete_type">
                                            <input type="hidden" name="fee_type_id" value="<?php echo $ft['fee_type_id']; ?>">
                                            <input type="hidden" name="csrf_token"  value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Delete permanently">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- ── Edit Modal ──────────────────────────────────── -->
                            <div class="modal fade" id="editModal<?php echo $ft['fee_type_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-edit me-1"></i> Edit Fee Type
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action"       value="update">
                                            <input type="hidden" name="fee_type_id"  value="<?php echo $ft['fee_type_id']; ?>">
                                            <input type="hidden" name="csrf_token"   value="<?php echo generateCSRFToken(); ?>">

                                            <div class="modal-body">

                                                <div class="alert alert-light border mb-3 py-2">
                                                    <small>
                                                        <strong>System code:</strong>
                                                        <code><?php echo htmlspecialchars($ft['code']); ?></code>
                                                        &nbsp;|&nbsp;
                                                        <strong>DB column:</strong>
                                                        <code><?php echo htmlspecialchars($ft['column_name']); ?></code>
                                                    </small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label-custom">
                                                        Label <span class="text-danger">*</span>
                                                        <small class="text-muted fw-normal">(shown in fee structure, receipts, reports)</small>
                                                    </label>
                                                    <input type="text" name="label"
                                                           class="form-control form-control-custom"
                                                           value="<?php echo htmlspecialchars($ft['label']); ?>"
                                                           maxlength="100" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label-custom">Description <small class="text-muted fw-normal">(optional)</small></label>
                                                    <textarea name="description"
                                                              class="form-control form-control-custom"
                                                              rows="2"
                                                              maxlength="255"><?php echo htmlspecialchars($ft['description'] ?? ''); ?></textarea>
                                                </div>

                                                <div class="mb-0">
                                                    <label class="form-label-custom">Display Order <small class="text-muted fw-normal">(lower = appears first)</small></label>
                                                    <input type="number" name="sort_order"
                                                           class="form-control form-control-custom"
                                                           value="<?php echo (int)$ft['sort_order']; ?>"
                                                           min="0" max="99" required>
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- ── /Edit Modal ─────────────────────────────────── -->

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /card-body -->
        </div>
    </div>
</div>

<!-- Info box -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom border-0 bg-light">
            <div class="card-body py-3">
                <h6 class="mb-2"><i class="fas fa-info-circle text-primary me-1"></i> How it works</h6>
                <ul class="mb-0 small text-muted">
                    <li><strong>System types</strong> (locked badge) — you can rename the label, update the description, and hide/restore them from forms, but cannot delete them because they are tied to core database columns.</li>
                    <li><strong>Hiding a type</strong> (eye-slash button) removes it from all fee entry forms. Existing payment records and amounts are not affected.</li>
                    <li><strong>Deleting a type</strong> (trash button — custom types only) permanently drops its database column. Only allowed when no payment records or fee structures carry a non-zero amount for that type.</li>
                    <li><strong>Display Order</strong> controls the column order in Fee Structure, Collection forms, and printed receipts.</li>
                    <li>Label changes take effect immediately across <em>Fee Structure, Fee Collection, Receipts,</em> and <em>Reports</em>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
