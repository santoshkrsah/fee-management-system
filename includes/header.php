<?php
$siteSettings = getSettings();
require_once __DIR__ . '/upi_helper.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <script>(function(){var t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? htmlspecialchars($siteSettings['school_name']); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <!-- jQuery (loaded early for inline scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
    <?php if(isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-md navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard.php">
                <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="Logo" style="height: 22px; margin-right: 6px; vertical-align: middle;">
                <?php else: ?>
                    <i class="fas fa-school"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($siteSettings['school_name']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-graduate"></i> Students
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/modules/student/add_student.php">Add Student</a></li>
                            <li><a class="dropdown-item" href="/modules/student/view_students.php">View Students</a></li>
                            <?php if (canEdit()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/student/init_passwords.php"><i class="fas fa-key"></i> Student Login</a></li>
                            <?php endif; ?>
                            <?php if (isSysAdmin()): ?>
                            <li><a class="dropdown-item" href="/modules/student/bulk_upload.php"><i class="fas fa-file-upload"></i> Bulk Upload</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="feeDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-indian-rupee-sign"></i> Fee Management
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (canEdit()): ?>
                            <li><a class="dropdown-item" href="/modules/fee_structure/view_fee_structure.php">Fee Structure</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/modules/fee_collection/collect_fee.php">Collect Fee</a></li>
                            <li><a class="dropdown-item" href="/modules/fee_collection/view_payments.php">View Payments</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php $headerPendingUpi = getPendingUpiCount(); ?>
                            <li>
                                <a class="dropdown-item" href="/modules/fee_collection/upi_payments.php">
                                    <i class="fas fa-qrcode"></i> UPI Payments
                                    <?php if ($headerPendingUpi > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $headerPendingUpi; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/modules/reports/paid_report.php">Paid Students</a></li>
                            <li><a class="dropdown-item" href="/modules/reports/unpaid_report.php">Unpaid Students</a></li>
                            <li><a class="dropdown-item" href="/modules/reports/student_wise_report.php">Student-wise Report</a></li>
                            <li><a class="dropdown-item" href="/modules/reports/class_wise_report.php">Class-wise Report</a></li>
                            <li><a class="dropdown-item" href="/modules/reports/date_wise_report.php">Date-wise Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/modules/reports/month_wise_report.php">Month-wise Report</a></li>
                            <li><a class="dropdown-item" href="/modules/reports/year_wise_report.php">Year-wise Report</a></li>
                        </ul>
                    </li>
                    <?php if (isSysAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/settings.php"><i class="fas fa-school"></i> School Settings</a></li>
                            <li><a class="dropdown-item" href="/admin/manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                            <li><a class="dropdown-item" href="/admin/manage_lockouts.php"><i class="fas fa-user-lock"></i> Account Lockouts</a></li>
                            <li><a class="dropdown-item" href="/admin/manage_classes.php"><i class="fas fa-th-list"></i> Manage Classes</a></li>
                            <li><a class="dropdown-item" href="/admin/manage_sessions.php"><i class="fas fa-calendar-alt"></i> Academic Sessions</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/email_test.php"><i class="fas fa-envelope"></i> Email Configuration</a></li>
                            <li><a class="dropdown-item" href="/admin/database_backup.php"><i class="fas fa-database"></i> Database Backup</a></li>
                            <li><a class="dropdown-item" href="/admin/audit_log.php"><i class="fas fa-clipboard-list"></i> Audit Log</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/manage_subscription.php"><i class="fas fa-id-card"></i> Subscription</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/upi_settings.php"><i class="fas fa-qrcode"></i> UPI Settings</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <?php
                    $headerSubStatus = getSubscriptionStatus();
                    if ($headerSubStatus):
                    ?>
                    <li class="nav-item me-2">
                        <a class="btn nav-link" href="#" title="Subscription Info" style="border:none;background:none;" data-bs-toggle="modal" data-bs-target="#subscriptionInfoModal">
                            <i class="fas fa-id-card"></i>
                            <?php if ($headerSubStatus['expired']): ?>
                                <span class="badge bg-danger" style="font-size:0.6rem;vertical-align:top;">Expired</span>
                            <?php elseif ($headerSubStatus['warning']): ?>
                                <span class="badge bg-warning text-dark" style="font-size:0.6rem;vertical-align:top;"><?php echo $headerSubStatus['days_remaining']; ?>d</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item me-2">
                        <button class="btn nav-link" id="darkModeToggle" title="Toggle Dark Mode" style="border:none;background:none;">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo getAdminName(); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/admin/change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="<?php echo (isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') ? 'container-fluid mt-4' : ''; ?>">
        <?php if(isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
        <?php
        $headerSessions = getAllSessions();
        $headerSelectedSession = getSelectedSession();
        ?>
        <div class="session-selector-wrapper">
            <span class="session-label"><i class="fas fa-calendar-alt"></i> Academic Session :</span>
            <div class="dropdown">
                <button class="btn dropdown-toggle session-selector-btn" type="button" id="sessionDropdown" data-bs-toggle="dropdown">
                    <?php echo htmlspecialchars($headerSelectedSession); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($headerSessions as $hs): ?>
                    <li>
                        <a class="dropdown-item session-switch-item <?php echo ($headerSelectedSession === $hs['session_name']) ? 'active' : ''; ?>"
                           href="#" data-session="<?php echo htmlspecialchars($hs['session_name']); ?>">
                            <?php echo htmlspecialchars($hs['session_name']); ?>
                            <?php if ($hs['is_active']): ?>
                                <span class="badge bg-success ms-1">Active</span>
                            <?php endif; ?>
                            <?php if ($headerSelectedSession === $hs['session_name']): ?>
                                <i class="fas fa-check ms-1"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <!-- Print Header (hidden on screen, shown on print) -->
        <div class="print-header" style="display:none;">
            <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="" class="print-school-logo"><br>
            <?php endif; ?>
            <div class="print-school-name"><?php echo htmlspecialchars($siteSettings['school_name']); ?></div>
            <?php if (!empty($siteSettings['school_address'])): ?>
            <div class="print-school-address"><?php echo htmlspecialchars($siteSettings['school_address']); ?></div>
            <?php endif; ?>
            <div class="print-report-title"><?php echo htmlspecialchars($pageTitle ?? 'Report'); ?></div>
            <div class="print-session">Academic Session: <?php echo htmlspecialchars($headerSelectedSession ?? ''); ?></div>
        </div>
        <?php
        // Display flash messages
        $flashMessage = getFlashMessage();
        if ($flashMessage):
        ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<?php if(isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
<?php if (isset($headerSubStatus) && $headerSubStatus): ?>
<!-- Subscription Info Modal (accessible to all users) -->
<div class="modal fade" id="subscriptionInfoModal" tabindex="-1" aria-labelledby="subscriptionInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="subscriptionInfoModalLabel">
                    <i class="fas fa-id-card"></i> Subscription Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:40%;">Start Date</td>
                        <td><strong><?php echo formatDate($headerSubStatus['start_date']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Expiry Date</td>
                        <td><strong><?php echo formatDate($headerSubStatus['expiry_date']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Remaining</td>
                        <td><strong><?php echo htmlspecialchars($headerSubStatus['remaining_text']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            <?php if ($headerSubStatus['expired']): ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Expired</span>
                            <?php elseif ($headerSubStatus['warning']): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Subscription warning modal (shown once per login for non-sysadmin when < 30 days remaining)
if (isset($_SESSION['subscription_warning']) && !isSysAdmin()):
    $swData = $_SESSION['subscription_warning'];
    unset($_SESSION['subscription_warning']);
?>
<!-- Subscription Warning Modal -->
<div class="modal fade" id="subscriptionWarningModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="subscriptionWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="subscriptionWarningModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Subscription Expiring Soon
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-clock text-warning" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Your subscription is about to expire</h5>
                <p class="text-muted mb-1">Expiry Date: <strong><?php echo formatDate($swData['expiry_date']); ?></strong></p>
                <p class="text-muted mb-3">Remaining: <strong><?php echo (int)$swData['days_remaining']; ?> day(s)</strong></p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle"></i>
                    Your subscription is about to expire. Please contact the developer for renewal before the expiry date.
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var warningModal = new bootstrap.Modal(document.getElementById('subscriptionWarningModal'));
        warningModal.show();
    });
</script>
<?php endif; ?>
<?php endif; ?>
