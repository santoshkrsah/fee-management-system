<?php
/**
 * Manage Account Lockouts
 * SysAdmin can view locked users, unlock accounts, clear rate limits, and force logout sessions.
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
requireRole(['sysadmin']);

$pageTitle = 'Account Lockouts';
$currentAdminId = getAdminId();

/**
 * Format remaining lockout time for display
 */
function formatTimeRemaining($lockedUntil) {
    if (!$lockedUntil) return '--';
    $remaining = strtotime($lockedUntil) - time();
    if ($remaining <= 0) return 'Expired';
    $minutes = ceil($remaining / 60);
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . $mins . 'm';
    }
    return $minutes . ' min';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirectWithMessage('manage_lockouts.php', 'error', 'Invalid security token. Please try again.');
    }

    $action = $_POST['action'] ?? '';
    $db = getDB();

    // ---- UNLOCK ACCOUNT ----
    if ($action === 'unlock_account') {
        $userId = intval($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            redirectWithMessage('manage_lockouts.php', 'error', 'Invalid user ID.');
        }

        try {
            $targetUser = $db->fetchOne("SELECT admin_id, username, failed_login_attempts, account_locked_until FROM admin WHERE admin_id = :id", ['id' => $userId]);
            if (!$targetUser) {
                redirectWithMessage('manage_lockouts.php', 'error', 'User not found.');
            }

            $db->query("UPDATE admin SET failed_login_attempts = 0, account_locked_until = NULL WHERE admin_id = :id",
                ['id' => $userId]);

            logAudit(getAdminId(), 'UNLOCK_ACCOUNT', 'admin', $userId,
                ['failed_login_attempts' => $targetUser['failed_login_attempts'], 'account_locked_until' => $targetUser['account_locked_until']],
                ['failed_login_attempts' => 0, 'account_locked_until' => null]);

            redirectWithMessage('manage_lockouts.php', 'success', 'Account "' . $targetUser['username'] . '" has been unlocked successfully.');

        } catch (Exception $e) {
            error_log("Unlock Account Error: " . $e->getMessage());
            redirectWithMessage('manage_lockouts.php', 'error', 'Failed to unlock account. Please try again.');
        }
    }

    // ---- CLEAR RATE LIMIT ----
    elseif ($action === 'clear_rate_limit') {
        $targetUsername = sanitize($_POST['target_username'] ?? '');

        if (empty($targetUsername)) {
            redirectWithMessage('manage_lockouts.php', 'error', 'Username is required.');
        }

        try {
            $db->query("DELETE FROM login_attempts WHERE username = :username AND success = 0",
                ['username' => $targetUsername]);

            logAudit(getAdminId(), 'CLEAR_RATE_LIMIT', 'login_attempts', null, null,
                ['target_username' => $targetUsername]);

            redirectWithMessage('manage_lockouts.php', 'success', 'Rate limit cleared for "' . $targetUsername . '".');

        } catch (Exception $e) {
            error_log("Clear Rate Limit Error: " . $e->getMessage());
            redirectWithMessage('manage_lockouts.php', 'error', 'Failed to clear rate limit. Please try again.');
        }
    }

    // ---- FORCE LOGOUT ----
    elseif ($action === 'force_logout') {
        $userId = intval($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            redirectWithMessage('manage_lockouts.php', 'error', 'Invalid user ID.');
        }

        if ($userId === $currentAdminId) {
            redirectWithMessage('manage_lockouts.php', 'error', 'You cannot force logout your own session.');
        }

        try {
            $targetUser = $db->fetchOne("SELECT admin_id, username FROM admin WHERE admin_id = :id", ['id' => $userId]);
            if (!$targetUser) {
                redirectWithMessage('manage_lockouts.php', 'error', 'User not found.');
            }

            $db->query("UPDATE admin SET session_id = NULL WHERE admin_id = :id", ['id' => $userId]);

            logAudit(getAdminId(), 'FORCE_LOGOUT', 'admin', $userId, null,
                ['target_username' => $targetUser['username']]);

            redirectWithMessage('manage_lockouts.php', 'success', 'Session terminated for "' . $targetUser['username'] . '".');

        } catch (Exception $e) {
            error_log("Force Logout Error: " . $e->getMessage());
            redirectWithMessage('manage_lockouts.php', 'error', 'Failed to terminate session. Please try again.');
        }
    }

    // ---- UNLOCK ALL ----
    elseif ($action === 'unlock_all') {
        try {
            $db->query("UPDATE admin SET failed_login_attempts = 0, account_locked_until = NULL
                        WHERE account_locked_until IS NOT NULL OR failed_login_attempts > 0");

            $db->query("DELETE FROM login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)");

            logAudit(getAdminId(), 'UNLOCK_ALL_ACCOUNTS', 'admin', null, null, null);

            redirectWithMessage('manage_lockouts.php', 'success', 'All accounts have been unlocked and rate limits cleared.');

        } catch (Exception $e) {
            error_log("Unlock All Error: " . $e->getMessage());
            redirectWithMessage('manage_lockouts.php', 'error', 'Failed to unlock accounts. Please try again.');
        }
    }

    // ---- RESET ALL LOCKOUTS (Full Reset) ----
    elseif ($action === 'reset_all_lockouts') {
        try {
            // Reset failed attempts and lockout for ALL admin users
            $db->query("UPDATE admin SET failed_login_attempts = 0, account_locked_until = NULL");

            // Delete ALL failed login attempt records from rate limiting table
            $db->query("DELETE FROM login_attempts WHERE success = 0");

            logAudit(getAdminId(), 'RESET_ALL_LOCKOUTS', 'admin', null, null, null);

            redirectWithMessage('manage_lockouts.php', 'success', 'All lockouts and failed login attempts have been completely reset.');

        } catch (Exception $e) {
            error_log("Reset All Lockouts Error: " . $e->getMessage());
            redirectWithMessage('manage_lockouts.php', 'error', 'Failed to reset lockouts. Please try again.');
        }
    }
}

// Fetch data for display
try {
    $db = getDB();

    // Currently locked accounts
    $lockedUsers = $db->fetchAll(
        "SELECT admin_id, username, full_name, role, status,
                failed_login_attempts, account_locked_until, last_login
         FROM admin
         WHERE account_locked_until > NOW()
            OR failed_login_attempts >= 5
         ORDER BY account_locked_until DESC"
    );

    // Rate-limited usernames/IPs (5+ failed attempts in last 60 min)
    $rateLimited = $db->fetchAll(
        "SELECT username, ip_address, COUNT(*) as attempt_count,
                MAX(attempted_at) as last_attempt
         FROM login_attempts
         WHERE success = 0
           AND attempted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)
         GROUP BY username, ip_address
         HAVING COUNT(*) >= 5
         ORDER BY attempt_count DESC"
    );

    // All users overview
    $allUsers = $db->fetchAll(
        "SELECT admin_id, username, full_name, role, status,
                failed_login_attempts, account_locked_until,
                session_id IS NOT NULL as has_active_session, last_login
         FROM admin
         ORDER BY
            CASE WHEN account_locked_until > NOW() THEN 0 ELSE 1 END,
            failed_login_attempts DESC"
    );

} catch (Exception $e) {
    error_log("Manage Lockouts Fetch Error: " . $e->getMessage());
    $lockedUsers = [];
    $rateLimited = [];
    $allUsers = [];
}

$csrfToken = generateCSRFToken();

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h2>
                <i class="fas fa-user-lock"></i> Account Lockouts
            </h2>
            <div>
                <form method="POST" action="manage_lockouts.php" class="d-inline"
                      onsubmit="return confirm('This will completely reset ALL lockouts and delete ALL failed login attempt records. Are you sure?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="reset_all_lockouts">
                    <button type="submit" class="btn btn-danger btn-custom">
                        <i class="fas fa-redo"></i> Reset All Lockouts
                    </button>
                </form>
                <?php if (count($lockedUsers) > 0 || count($rateLimited) > 0): ?>
                <form method="POST" action="manage_lockouts.php" class="d-inline ms-2"
                      onsubmit="return confirm('Are you sure you want to unlock ALL locked accounts and clear all rate limits?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="unlock_all">
                    <button type="submit" class="btn btn-warning btn-custom">
                        <i class="fas fa-unlock"></i> Unlock All Accounts
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Currently Locked Accounts -->
<div class="row">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-lock"></i> Currently Locked Accounts
                <span class="badge bg-danger ms-2"><?php echo count($lockedUsers); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($lockedUsers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Failed Attempts</th>
                                <th>Locked Until</th>
                                <th>Time Remaining</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lockedUsers as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <?php
                                    $roleBadgeClass = match($user['role']) {
                                        'sysadmin' => 'bg-purple',
                                        'admin'    => 'bg-primary',
                                        'operator' => 'bg-info',
                                        default    => 'bg-secondary'
                                    };
                                    $roleLabel = match($user['role']) {
                                        'sysadmin' => 'System Admin',
                                        'admin'    => 'Admin',
                                        'operator' => 'Staff',
                                        default    => ucfirst($user['role'])
                                    };
                                    ?>
                                    <span class="badge <?php echo $roleBadgeClass; ?>"
                                          <?php if ($user['role'] === 'sysadmin'): ?>style="background-color: #6f42c1;"<?php endif; ?>>
                                        <?php echo $roleLabel; ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-danger"><?php echo (int)$user['failed_login_attempts']; ?></span></td>
                                <td>
                                    <?php if ($user['account_locked_until']): ?>
                                        <?php echo formatDate($user['account_locked_until'], 'd-m-Y H:i'); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $remaining = formatTimeRemaining($user['account_locked_until']);
                                    $badgeClass = ($remaining === 'Expired' || $remaining === '--') ? 'bg-secondary' : 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $remaining; ?></span>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="manage_lockouts.php" class="d-inline"
                                          onsubmit="return confirm('Unlock account for <?php echo htmlspecialchars($user['username']); ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="unlock_account">
                                        <input type="hidden" name="user_id" value="<?php echo $user['admin_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Unlock Account">
                                            <i class="fas fa-unlock"></i> Unlock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0"><i class="fas fa-check-circle text-success"></i> No accounts are currently locked.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Rate-Limited Users/IPs -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-shield-alt"></i> Rate-Limited Users / IPs
                <span class="badge bg-warning text-dark ms-2"><?php echo count($rateLimited); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($rateLimited) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>IP Address</th>
                                <th>Failed Attempts (60 min)</th>
                                <th>Last Attempt</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rateLimited as $entry): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($entry['username']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($entry['ip_address']); ?></code></td>
                                <td><span class="badge bg-danger"><?php echo (int)$entry['attempt_count']; ?></span></td>
                                <td><?php echo formatDate($entry['last_attempt'], 'd-m-Y H:i:s'); ?></td>
                                <td class="text-center">
                                    <form method="POST" action="manage_lockouts.php" class="d-inline"
                                          onsubmit="return confirm('Clear rate limit for <?php echo htmlspecialchars($entry['username']); ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="clear_rate_limit">
                                        <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($entry['username']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Clear Rate Limit">
                                            <i class="fas fa-eraser"></i> Clear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0"><i class="fas fa-check-circle text-success"></i> No rate-limited users or IPs.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- All Users - Lockout Status -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-users"></i> All Users - Lockout Status
                <span class="badge bg-secondary ms-2"><?php echo count($allUsers); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($allUsers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Failed Attempts</th>
                                <th>Lock Status</th>
                                <th>Active Session</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['admin_id'] == $currentAdminId): ?>
                                        <span class="badge bg-secondary ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <?php
                                    $roleBadgeClass = match($user['role']) {
                                        'sysadmin' => 'bg-purple',
                                        'admin'    => 'bg-primary',
                                        'operator' => 'bg-info',
                                        default    => 'bg-secondary'
                                    };
                                    $roleLabel = match($user['role']) {
                                        'sysadmin' => 'System Admin',
                                        'admin'    => 'Admin',
                                        'operator' => 'Staff',
                                        default    => ucfirst($user['role'])
                                    };
                                    ?>
                                    <span class="badge <?php echo $roleBadgeClass; ?>"
                                          <?php if ($user['role'] === 'sysadmin'): ?>style="background-color: #6f42c1;"<?php endif; ?>>
                                        <?php echo $roleLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['failed_login_attempts'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo (int)$user['failed_login_attempts']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()): ?>
                                        <span class="badge bg-danger"><i class="fas fa-lock"></i> Locked</span>
                                    <?php elseif ($user['failed_login_attempts'] >= 5): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> At Limit</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-unlock"></i> OK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['has_active_session']): ?>
                                        <span class="badge bg-info"><i class="fas fa-plug"></i> Active</span>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['failed_login_attempts'] > 0 || $user['account_locked_until']): ?>
                                    <form method="POST" action="manage_lockouts.php" class="d-inline"
                                          onsubmit="return confirm('Reset lockout for <?php echo htmlspecialchars($user['username']); ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="unlock_account">
                                        <input type="hidden" name="user_id" value="<?php echo $user['admin_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success btn-icon me-1" title="Reset Lockout">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($user['has_active_session'] && $user['admin_id'] != $currentAdminId): ?>
                                    <form method="POST" action="manage_lockouts.php" class="d-inline"
                                          onsubmit="return confirm('Force logout <?php echo htmlspecialchars($user['username']); ?>? Their current session will be terminated.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="force_logout">
                                        <input type="hidden" name="user_id" value="<?php echo $user['admin_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Force Logout">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ((!$user['failed_login_attempts'] && !$user['account_locked_until']) && (!$user['has_active_session'] || $user['admin_id'] == $currentAdminId)): ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted mb-0">No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
