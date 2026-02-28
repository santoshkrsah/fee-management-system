<?php
/**
 * Student Change Password
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireStudentLogin();

$pageTitle = 'Change Password';
$success = '';
$error = '';
$studentId = getStudentId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {
            // Validate password strength
            $passwordCheck = validatePasswordStrength($newPassword);

            if (!$passwordCheck['valid']) {
                $error = $passwordCheck['message'];
            } else {
                try {
                    $db = getDB();

                    // Get current student's password hash
                    $student = $db->fetchOne("SELECT password FROM students WHERE student_id = :id",
                                         ['id' => $studentId]);

                    if (!$student || !password_verify($currentPassword, $student['password'])) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Hash new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                        // Update password and mark as changed
                        $db->query("UPDATE students SET password = :password, password_changed = 1 WHERE student_id = :id",
                                  [
                                      'password' => $hashedPassword,
                                      'id' => $studentId
                                  ]);

                        // Update session flag
                        $_SESSION['student_password_changed'] = true;

                        // Log the change
                        logStudentAudit($studentId, 'STUDENT_PASSWORD_CHANGED', 'students', $studentId);

                        $success = 'Password changed successfully!';
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again.';
                    error_log("Student password change error: " . $e->getMessage());
                }
            }
        }
    }
}

require_once '../includes/student_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-key"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="current_password" class="form-label-custom">
                            <i class="fas fa-lock"></i> Current Password
                        </label>
                        <input type="password" class="form-control form-control-custom"
                               id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label-custom">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <input type="password" class="form-control form-control-custom"
                               id="new_password" name="new_password" required
                               minlength="8">
                        <small class="form-text text-muted">
                            Must be at least 8 characters with uppercase, lowercase, number, and special character
                        </small>
                        <div id="password-strength" class="mt-2"></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label-custom">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <input type="password" class="form-control form-control-custom"
                               id="confirm_password" name="confirm_password" required
                               minlength="8">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="change_password" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-custom">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-custom mt-3">
            <div class="card-body">
                <h6><i class="fas fa-info-circle"></i> Password Requirements:</h6>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Contains uppercase letter (A-Z)</li>
                    <li>Contains lowercase letter (a-z)</li>
                    <li>Contains number (0-9)</li>
                    <li>Contains special character (!@#$%^&*)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
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

// Confirm password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;

    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        if (confirmPassword === newPassword && newPassword) {
            this.classList.add('is-valid');
        }
    }
});
</script>

<?php require_once '../includes/student_footer.php'; ?>
