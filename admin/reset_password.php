<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$tokenValid = false;

// Verify token
if (!empty($token)) {
    try {
        $db = getDB();

        $resetToken = $db->fetchOne("SELECT * FROM password_reset_tokens
                                     WHERE token = :token AND used = 0
                                     AND expires_at > NOW()",
                                     ['token' => $token]);

        if ($resetToken) {
            $tokenValid = true;

            // Handle password reset submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Both password fields are required.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Passwords do not match.';
                } else {
                    // Validate password strength
                    $passwordCheck = validatePasswordStrength($newPassword);

                    if (!$passwordCheck['valid']) {
                        $error = $passwordCheck['message'];
                    } else {
                        // Hash new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                        // Update password
                        $db->query("UPDATE admin SET password = :password,
                                   failed_login_attempts = 0, account_locked_until = NULL
                                   WHERE admin_id = :id",
                                   [
                                       'password' => $hashedPassword,
                                       'id' => $resetToken['user_id']
                                   ]);

                        // Mark token as used
                        $db->query("UPDATE password_reset_tokens SET used = 1 WHERE token_id = :id",
                                  ['id' => $resetToken['token_id']]);

                        // Log the change
                        logAudit($resetToken['user_id'], 'PASSWORD_RESET_COMPLETED', 'admin',
                                $resetToken['user_id'], null, null);

                        $success = 'Your password has been reset successfully. You can now login with your new password.';
                        $tokenValid = false;
                    }
                }
            }
        } else {
            $error = 'Invalid or expired reset link. Please request a new password reset.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again.';
        error_log($e->getMessage());
    }
} else {
    $error = 'No reset token provided.';
}

$pageTitle = 'Reset Password';
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
                <i class="fas fa-key"></i>
                <h2>Reset Password</h2>
                <p>Enter your new password</p>
            </div>

            <div class="login-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $success ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    </div>
                <?php elseif (!$tokenValid): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="forgot_password.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-redo"></i> Request New Reset Link
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
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <input type="password" class="form-control" id="new_password"
                                   name="new_password" required minlength="8"
                                   placeholder="Enter new password">
                            <small class="form-text text-muted">
                                At least 8 characters with uppercase, lowercase, number, and special character
                            </small>
                            <div id="password-strength" class="mt-2"></div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password"
                                   name="confirm_password" required minlength="8"
                                   placeholder="Confirm new password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                            <i class="fas fa-save"></i> Reset Password
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
    <script>
    // Password strength indicator
    document.getElementById('new_password')?.addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('password-strength');

        let strength = 0;
        let feedback = [];

        if (password.length >= 8) strength++; else feedback.push('8+ characters');
        if (/[A-Z]/.test(password)) strength++; else feedback.push('Uppercase');
        if (/[a-z]/.test(password)) strength++; else feedback.push('Lowercase');
        if (/[0-9]/.test(password)) strength++; else feedback.push('Number');
        if (/[^A-Za-z0-9]/.test(password)) strength++; else feedback.push('Special char');

        const colors = ['danger', 'danger', 'warning', 'info', 'success', 'success'];
        const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];

        strengthDiv.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-${colors[strength]}"
                     style="width: ${strength * 20}%"></div>
            </div>
            <small class="text-${colors[strength]}">
                Strength: ${labels[strength]}
                ${feedback.length > 0 ? ' (Need: ' + feedback.join(', ') + ')' : ''}
            </small>
        `;
    });

    // Confirm password match
    document.getElementById('confirm_password')?.addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        if (this.value && newPassword !== this.value) {
            this.setCustomValidity('Passwords do not match');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });
    </script>
</body>
</html>
