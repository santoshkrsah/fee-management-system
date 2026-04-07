<?php
/**
 * Student Password Initialization Script
 * One-time utility to hash all existing students' contact numbers as passwords.
 * Must be run by a sysadmin user (requires admin login).
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
requireRole(['sysadmin', 'admin']);

$pageTitle = 'Initialize Student Passwords';
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'init_passwords';

        if ($action === 'toggle_student_login') {
            // Toggle student login enabled/disabled
            try {
                $db = getDB();
                $newValue = ($_POST['student_login_enabled'] ?? '1') === '1' ? '1' : '0';
                $db->query(
                    "INSERT INTO settings (setting_key, setting_value) VALUES ('student_login_enabled', :val)
                     ON DUPLICATE KEY UPDATE setting_value = :val2",
                    ['val' => $newValue, 'val2' => $newValue]
                );
                $statusLabel = $newValue === '1' ? 'enabled' : 'disabled';
                logAudit(getAdminId(), 'STUDENT_LOGIN_TOGGLED', 'settings', null, null, ['student_login_enabled' => $newValue]);
                redirectWithMessage('init_passwords.php', 'success', "Student login has been $statusLabel.");
            } catch (Exception $e) {
                $error = 'Failed to update student login setting.';
                error_log("Toggle student login error: " . $e->getMessage());
            }
        } else {
            // Original password initialization logic
            try {
                $db = getDB();

                // Fetch students without a password set
                $students = $db->fetchAll("SELECT student_id, contact_number FROM students WHERE password IS NULL");
                $count = 0;

                foreach ($students as $s) {
                    $hash = password_hash($s['contact_number'], PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->query("UPDATE students SET password = :pw WHERE student_id = :id",
                        ['pw' => $hash, 'id' => $s['student_id']]);
                    $count++;
                }

                logAudit(getAdminId(), 'STUDENT_PASSWORDS_INITIALIZED', 'students', null, null, ['count' => $count]);
                $result = "Successfully initialized passwords for $count student(s).";

            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
                error_log("Student password init error: " . $e->getMessage());
            }
        }
    }
}

// Count students needing initialization
try {
    $db = getDB();
    $pendingRow = $db->fetchOne("SELECT COUNT(*) as cnt FROM students WHERE password IS NULL");
    $pendingCount = $pendingRow['cnt'] ?? 0;
} catch (Exception $e) {
    $pendingCount = 0;
    $error = 'Could not check pending students: ' . $e->getMessage();
}

// Get current student login enabled setting
$settings = getSettings();
$studentLoginEnabled = ($settings['student_login_enabled'] ?? '1') === '1';

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-key"></i> Initialize Student Passwords
        </h2>
    </div>
</div>

<!-- Student Login Toggle -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-sign-in-alt"></i> Student Login Access</span>
                <?php if ($studentLoginEnabled): ?>
                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Enabled</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Disabled</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="toggleStudentLoginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="toggle_student_login">
                    <input type="hidden" name="student_login_enabled" id="studentLoginEnabledInput"
                           value="<?php echo $studentLoginEnabled ? '0' : '1'; ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1"><strong>Allow students to log in to the Student Portal</strong></p>
                            <p class="text-muted mb-0">
                                <?php if ($studentLoginEnabled): ?>
                                    Students can currently access the Student Portal login page.
                                <?php else: ?>
                                    Student login is currently blocked. No student can log in.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="studentLoginToggle"
                                   <?php echo $studentLoginEnabled ? 'checked' : ''; ?>
                                   onchange="toggleStudentLogin(this)">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-shield-alt"></i> Login Control
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-toggle-on text-success"></i> <strong>Enabled:</strong> Students can log in normally</li>
                    <li class="mb-2"><i class="fas fa-toggle-off text-danger"></i> <strong>Disabled:</strong> All student logins are blocked</li>
                    <li class="mb-2"><i class="fas fa-info-circle text-info"></i> Admin login is not affected</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStudentLogin(el) {
    var enabling = el.checked;
    var msg = enabling
        ? 'Enable student login? Students will be able to log in again.'
        : 'Disable student login? No student will be able to log in until re-enabled.';
    if (confirm(msg)) {
        document.getElementById('studentLoginEnabledInput').value = enabling ? '1' : '0';
        document.getElementById('toggleStudentLoginForm').submit();
    } else {
        el.checked = !el.checked;
    }
}
</script>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-user-graduate"></i> Student Password Setup
            </div>
            <div class="card-body">
                <?php if ($result): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($result); ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    This tool initializes passwords for students who don't have one set yet.
                    The default password will be set to the student's registered <strong>Contact Number</strong>.
                    Students can change this after their first login.
                </div>

                <p class="mb-3">
                    <strong>Students pending password initialization:</strong>
                    <span class="badge bg-<?php echo $pendingCount > 0 ? 'warning' : 'success'; ?> fs-6">
                        <?php echo $pendingCount; ?>
                    </span>
                </p>

                <?php if ($pendingCount > 0): ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('This will set passwords for <?php echo $pendingCount; ?> student(s). Continue?');">
                        <i class="fas fa-key"></i> Initialize Passwords Now
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle"></i> All student passwords are already initialized.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Information
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-check text-success"></i> Default password = Contact Number</li>
                    <li class="mb-2"><i class="fas fa-check text-success"></i> Passwords are encrypted (bcrypt)</li>
                    <li class="mb-2"><i class="fas fa-check text-success"></i> Students can change their password after first login</li>
                    <li class="mb-2"><i class="fas fa-check text-success"></i> New students added later will get passwords automatically</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
