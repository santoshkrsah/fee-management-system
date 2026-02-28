<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/email_helper.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';
$step = isset($_POST['step']) ? $_POST['step'] : 1;

// Step 1: Request reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = getDB();

            // Find user by email
            $user = $db->fetchOne("SELECT * FROM admin WHERE email = :email AND status = 'active'",
                                 ['email' => $email]);

            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token
                $db->query("INSERT INTO password_reset_tokens
                           (user_id, token, expires_at, ip_address)
                           VALUES (:user_id, :token, :expires_at, :ip_address)",
                           [
                               'user_id' => $user['admin_id'],
                               'token' => $token,
                               'expires_at' => $expiresAt,
                               'ip_address' => getRealIPAddress()
                           ]);

                // Log the request
                logAudit($user['admin_id'], 'PASSWORD_RESET_REQUESTED', 'admin', $user['admin_id'], null, null);

                // Generate reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/admin/reset_password.php?token=" . $token;

                // Send email with reset link
                $emailSent = EmailHelper::sendPasswordReset($email, [
                    'name' => $user['full_name'],
                    'reset_link' => $resetLink,
                    'token' => $token
                ]);

                if ($emailSent) {
                    $success = "Password reset link has been sent to your email address. Please check your inbox.";
                } else {
                    // Show link if email failed (fallback for development)
                    $success = "Email sending failed. Please use this link to reset your password:<br><br>
                               <strong>Reset link:</strong><br>
                               <a href='$resetLink'>$resetLink</a><br><br>
                               <small>(Link expires in 1 hour)</small>";
                }
            } else {
                // Don't reveal that the email doesn't exist (security)
                $success = "If an account exists with that email, a password reset link has been sent.";
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log($e->getMessage());
        }
    }
}

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-lock"></i>
                <h2>Forgot Password</h2>
                <p>Enter your email to reset your password</p>
            </div>

            <div class="login-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $success ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-arrow-left"></i> Back to Login
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="step" value="1">

                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Enter your email" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>

                        <div class="text-center">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
