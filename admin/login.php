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

// Get school settings for login page
$siteSettings = getSettings();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check rate limiting
        $rateLimit = checkLoginRateLimit($username);

        if (!$rateLimit['allowed']) {
            $error = $rateLimit['message'];
            logAudit(null, 'LOGIN_RATE_LIMITED', 'admin', null, null, ['username' => $username]);
        } else {
            try {
                $db = getDB();

                // Fetch admin details (check without status filter first)
                $query = "SELECT * FROM admin WHERE username = :username LIMIT 1";
                $admin = $db->fetchOne($query, ['username' => $username]);

                if ($admin) {
                    // Check if account is inactive
                    if ($admin['status'] !== 'active') {
                        $statusMessage = match($admin['status']) {
                            'inactive' => 'Your account is inactive. Please contact the system administrator to activate your account.',
                            'blocked' => 'Your account has been blocked. Please contact the system administrator.',
                            default => 'Your account is not active. Please contact the system administrator.'
                        };
                        $error = $statusMessage;
                        logAudit(null, 'LOGIN_INACTIVE_USER', 'admin', $admin['admin_id'], null, ['username' => $username, 'status' => $admin['status']]);
                    }
                    // Check if account is locked
                    elseif ($admin['account_locked_until'] && strtotime($admin['account_locked_until']) > time()) {
                        $error = 'Your account has been temporarily locked due to too many failed login attempts. Please try again later.';
                        logLoginAttempt($username, false);
                        logAudit(null, 'LOGIN_ACCOUNT_LOCKED', 'admin', $admin['admin_id'], null, ['username' => $username]);
                    } elseif (password_verify($password, $admin['password'])) {
                        // Password is correct
                        setAdminSession($admin);
                        logLoginAttempt($username, true);

                        // Redirect to intended page or dashboard
                        $redirectUrl = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                        unset($_SESSION['redirect_after_login']);

                        header("Location: " . $redirectUrl);
                        exit();
                    } else {
                        // Wrong password
                        $error = 'Invalid username or password.';
                        logLoginAttempt($username, false);
                        logAudit(null, 'LOGIN_FAILED', 'admin', null, null, ['username' => $username]);

                        // Increment failed attempts
                        $failedAttempts = ($admin['failed_login_attempts'] ?? 0) + 1;

                        // Lock account after 5 failed attempts
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
                } else {
                    // Username not found
                    $error = 'Invalid username or password.';
                    logLoginAttempt($username, false);
                    logAudit(null, 'LOGIN_INVALID_USER', 'admin', null, null, ['username' => $username]);
                }
            } catch(Exception $e) {
                $error = 'An error occurred. Please try again.';
                error_log($e->getMessage());
            }
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
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
