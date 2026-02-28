<?php
/**
 * Manage Subscription
 * Accessible only by sysadmin role
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireRole(['sysadmin']);

$pageTitle = 'Subscription Management';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('manage_subscription.php', 'error', 'Invalid security token. Please try again.');
    }

    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');

    if (empty($startDate) || empty($endDate)) {
        redirectWithMessage('manage_subscription.php', 'error', 'Both start date and end date are required.');
    }

    if (!strtotime($startDate) || !strtotime($endDate)) {
        redirectWithMessage('manage_subscription.php', 'error', 'Invalid date format.');
    }

    if (strtotime($endDate) <= strtotime($startDate)) {
        redirectWithMessage('manage_subscription.php', 'error', 'End date must be after start date.');
    }

    try {
        $db = getDB();

        // Ensure subscription table exists
        $db->query("CREATE TABLE IF NOT EXISTS subscription (
            id INT PRIMARY KEY AUTO_INCREMENT,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            updated_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES admin(admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Check if a subscription record exists
        $existing = $db->fetchOne("SELECT id, start_date, end_date FROM subscription ORDER BY id DESC LIMIT 1");

        if ($existing) {
            $oldValues = ['start_date' => $existing['start_date'], 'end_date' => $existing['end_date']];
            $db->query(
                "UPDATE subscription SET start_date = :start_date, end_date = :end_date, updated_by = :updated_by WHERE id = :id",
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'updated_by' => getAdminId(),
                    'id' => $existing['id']
                ]
            );
            logAudit(getAdminId(), 'UPDATE_SUBSCRIPTION', 'subscription', $existing['id'], $oldValues, ['start_date' => $startDate, 'end_date' => $endDate]);
        } else {
            $db->query(
                "INSERT INTO subscription (start_date, end_date, updated_by) VALUES (:start_date, :end_date, :updated_by)",
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'updated_by' => getAdminId()
                ]
            );
            logAudit(getAdminId(), 'CREATE_SUBSCRIPTION', 'subscription', null, null, ['start_date' => $startDate, 'end_date' => $endDate]);
        }

        redirectWithMessage('manage_subscription.php', 'success', 'Subscription updated successfully.');

    } catch (Exception $e) {
        error_log("Subscription Update Error: " . $e->getMessage());
        redirectWithMessage('manage_subscription.php', 'error', 'Failed to update subscription. Please try again.');
    }
}

// Fetch current subscription
$subscription = getSubscription();
$subStatus = getSubscriptionStatus();
$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-id-card"></i> Subscription Management
            </h2>
        </div>
    </div>
</div>

<!-- Current Subscription Status -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Current Subscription Status
            </div>
            <div class="card-body">
                <?php if ($subscription && $subStatus): ?>
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <small class="text-muted d-block">Start Date</small>
                            <strong class="fs-5"><?php echo formatDate($subStatus['start_date']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <small class="text-muted d-block">Expiry Date</small>
                            <strong class="fs-5"><?php echo formatDate($subStatus['expiry_date']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <small class="text-muted d-block">Remaining</small>
                            <strong class="fs-5"><?php echo htmlspecialchars($subStatus['remaining_text']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3">
                            <small class="text-muted d-block">Status</small>
                            <?php if ($subStatus['expired']): ?>
                                <span class="badge bg-danger fs-6"><i class="fas fa-times-circle"></i> Expired</span>
                            <?php elseif ($subStatus['warning']): ?>
                                <span class="badge bg-warning text-dark fs-6"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</span>
                            <?php else: ?>
                                <span class="badge bg-success fs-6"><i class="fas fa-check-circle"></i> Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($subStatus['expired']): ?>
                <div class="alert alert-danger mt-2 mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Subscription has expired.</strong> Non-admin users are currently blocked from logging in. Please renew the subscription.
                </div>
                <?php elseif ($subStatus['warning']): ?>
                <div class="alert alert-warning mt-2 mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Subscription expiring soon.</strong> Only <?php echo $subStatus['days_remaining']; ?> day(s) remaining. Users will see a warning at login.
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> No subscription configured. Please set the subscription dates below.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Update Subscription Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-edit"></i> <?php echo $subscription ? 'Update' : 'Set'; ?> Subscription
            </div>
            <div class="card-body">
                <form method="POST" action="manage_subscription.php" id="subscriptionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="start_date" class="form-label form-label-custom">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-custom" id="start_date" name="start_date"
                                   value="<?php echo $subscription ? htmlspecialchars($subscription['start_date']) : ''; ?>" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="end_date" class="form-label form-label-custom">
                                End Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-custom" id="end_date" name="end_date"
                                   value="<?php echo $subscription ? htmlspecialchars($subscription['end_date']) : ''; ?>" required>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-custom w-100"
                                    onclick="return confirm('Are you sure you want to update the subscription dates?');">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
