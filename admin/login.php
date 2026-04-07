<?php
/**
 * Admin Login Page
 */
require_once '../config/database.php';
require_once '../includes/session.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$showExpiredModal = false;
$expiredDate = '';

// Get school settings for login page
$siteSettings = getSettings();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db = getDB();

            // Step 1: Look up user BEFORE rate limit check (needed for sysadmin bypass)
            $query = "SELECT * FROM admin WHERE username = :username LIMIT 1";
            $admin = $db->fetchOne($query, ['username' => $username]);

            // Step 2: Pre-verify password for timing consistency
            // Always call password_verify when user exists so response time
            // is identical regardless of role (prevents timing-based enumeration)
            $passwordCorrect = false;
            if ($admin) {
                $passwordCorrect = password_verify($password, $admin['password']);
            }

            // Step 3: Determine sysadmin bypass
            // Bypass ONLY activates with correct password + active sysadmin account
            $isSysAdminUser = ($admin && $admin['role'] === 'sysadmin');
            $sysadminBypass = ($isSysAdminUser && $admin['status'] === 'active' && $passwordCorrect);

            // Step 4: Check rate limiting (always runs for consistent behavior)
            $rateLimit = checkLoginRateLimit($username);

            // Step 5: Enforce rate limit - bypass only for sysadmin with correct password
            if (!$rateLimit['allowed'] && !$sysadminBypass) {
                $error = $rateLimit['message'];
                logAudit(null, 'LOGIN_RATE_LIMITED', 'admin', null, null, ['username' => $username]);
            } else {
                if ($admin) {
                    // Check if account is inactive (applies to ALL roles including sysadmin)
                    if ($admin['status'] !== 'active') {
                        $statusMessage = match($admin['status']) {
                            'inactive' => 'Your account is inactive. Please contact the system administrator to activate your account.',
                            'blocked' => 'Your account has been blocked. Please contact the system administrator.',
                            default => 'Your account is not active. Please contact the system administrator.'
                        };
                        $error = $statusMessage;
                        logAudit(null, 'LOGIN_INACTIVE_USER', 'admin', $admin['admin_id'], null, ['username' => $username, 'status' => $admin['status']]);
                    }
                    // Check if account is locked - bypass for sysadmin with correct password
                    elseif ($admin['account_locked_until'] && strtotime($admin['account_locked_until']) > time() && !$sysadminBypass) {
                        $error = 'Your account has been temporarily locked due to too many failed login attempts. Please try again later.';
                        logLoginAttempt($username, false);
                        logAudit(null, 'LOGIN_ACCOUNT_LOCKED', 'admin', $admin['admin_id'], null, ['username' => $username]);
                    } elseif ($passwordCorrect) {
                        // Password is correct - check subscription before proceeding
                        $subStatus = getSubscriptionStatus();

                        // Block non-sysadmin users if subscription expired
                        if ($subStatus && $subStatus['expired'] && $admin['role'] !== 'sysadmin') {
                            $showExpiredModal = true;
                            $expiredDate = formatDate($subStatus['expiry_date']);
                            logLoginAttempt($username, false);
                            logAudit(null, 'LOGIN_SUBSCRIPTION_EXPIRED', 'admin', $admin['admin_id'], null, ['username' => $username]);
                        } else {
                            // Login allowed
                            setAdminSession($admin);
                            logLoginAttempt($username, true);

                            // Log bypass event for audit trail if sysadmin bypassed lockout
                            if ($sysadminBypass && (!$rateLimit['allowed']
                                || ($admin['account_locked_until'] && strtotime($admin['account_locked_until']) > time()))) {
                                logAudit($admin['admin_id'], 'SYSADMIN_LOCKOUT_BYPASS', 'admin', $admin['admin_id'], null,
                                    ['rate_limited' => !$rateLimit['allowed'],
                                     'account_locked' => (bool)$admin['account_locked_until']]);
                            }

                            // Set subscription warning flag for non-sysadmin if < 30 days remaining
                            if ($subStatus && $subStatus['warning'] && $admin['role'] !== 'sysadmin') {
                                $_SESSION['subscription_warning'] = [
                                    'days_remaining' => $subStatus['days_remaining'],
                                    'expiry_date' => $subStatus['expiry_date'],
                                ];
                            }

                            // Redirect to intended page or dashboard
                            $redirectUrl = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                            unset($_SESSION['redirect_after_login']);

                            header("Location: " . $redirectUrl);
                            exit();
                        }
                    } else {
                        // Wrong password
                        $error = 'Invalid username or password.';
                        logLoginAttempt($username, false);
                        logAudit(null, 'LOGIN_FAILED', 'admin', null, null, ['username' => $username]);

                        // Increment failed attempts and lock account - only for non-sysadmin
                        if (!$isSysAdminUser) {
                            $failedAttempts = ($admin['failed_login_attempts'] ?? 0) + 1;

                            $lockUntil = null;
                            if ($failedAttempts >= 5) {
                                $lockUntil = date('Y-m-d H:i:s', strtotime('+60 minutes'));
                                $error = 'Too many failed login attempts. Your account has been locked for 60 minutes.';
                            }

                            $db->query("UPDATE admin SET failed_login_attempts = :attempts,
                                       account_locked_until = :lock_until WHERE admin_id = :id",
                                       [
                                           'attempts' => $failedAttempts,
                                           'lock_until' => $lockUntil,
                                           'id' => $admin['admin_id']
                                       ]);
                        }
                    }
                } else {
                    // Username not found
                    $error = 'Invalid username or password.';
                    logLoginAttempt($username, false);
                    logAudit(null, 'LOGIN_INVALID_USER', 'admin', null, null, ['username' => $username]);
                }
            }
        } catch(Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log($e->getMessage());
        }
    }
}

$pageTitle = 'Login - ' . $siteSettings['school_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-particles">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="login-shimmer"></div>
        <div class="login-card">
            <div class="login-header">
                <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="School Logo" style="height: 60px; margin-bottom: 10px;">
                <?php else: ?>
                    <i class="fas fa-school"></i>
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($siteSettings['school_name']); ?></h2>
                <p class="mb-0">Login</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Enter your username" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>

                    <div class="text-center mt-3">
                        <a href="forgot_password.php" class="text-muted">
                            <i class="fas fa-question-circle"></i> Forgot Password?
                        </a>
                    </div>

                    <div class="text-center mt-2">
                        <a href="/student/login.php" class="text-muted">
                            <i class="fas fa-user-graduate"></i> Student Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($showExpiredModal): ?>
    <!-- Subscription Expired Modal (non-dismissible) -->
    <div class="modal fade" id="subscriptionExpiredModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="subscriptionExpiredModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="subscriptionExpiredModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Subscription Expired
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-ban text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Your subscription has expired</h5>
                    <p class="text-muted mb-2">Expiry Date: <strong><?php echo htmlspecialchars($expiredDate); ?></strong></p>
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="fas fa-info-circle"></i>
                        Your subscription has expired. Please contact the developer immediately for renewal. Otherwise, all data may be deleted.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('subscriptionExpiredModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>
