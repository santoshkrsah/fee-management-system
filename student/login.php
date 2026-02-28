<?php
/**
 * Student Login Page
 */
require_once '../config/database.php';
require_once '../includes/session.php';

// Redirect to dashboard if already logged in
if (isStudentLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$showCaptcha = false;
$captchaQuestion = '';

// Get school settings for login page
$siteSettings = getSettings();
$studentLoginDisabled = ($siteSettings['student_login_enabled'] ?? '1') === '0';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($studentLoginDisabled) {
        $error = 'Student login is currently disabled by the administrator. Please contact your school administration for assistance.';
    } elseif (empty(sanitize($_POST['admission_no'] ?? '')) || empty($_POST['password'] ?? '')) {
        $error = 'Please enter both Admission Number and Password.';
    } else {
        $admission_no = sanitize($_POST['admission_no'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            $db = getDB();

            // Check failed attempt count for CAPTCHA decision
            $failCountRow = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM login_attempts
                 WHERE username = :u AND success = 0
                 AND attempted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
                ['u' => $admission_no]
            );
            $failCount = $failCountRow['cnt'] ?? 0;
            $showCaptcha = ($failCount >= 3);

            // Verify CAPTCHA if required and was shown to user
            if ($showCaptcha && isset($_POST['captcha_answer'])) {
                if (!verifyCaptcha($_POST['captcha_answer'])) {
                    $error = 'Incorrect security answer. Please try again.';
                    $captchaQuestion = generateMathCaptcha();
                    goto render;
                }
            }
            // If CAPTCHA is required but wasn't shown on the form,
            // proceed with login — CAPTCHA will appear on next render if login fails

            // Check rate limiting
            $rateLimit = checkLoginRateLimit($admission_no);
            if (!$rateLimit['allowed']) {
                $error = $rateLimit['message'];
                logStudentAudit(null, 'STUDENT_LOGIN_RATE_LIMITED', 'students', null, null, ['admission_no' => $admission_no]);
            } else {
                // Look up student with class/section info
                $student = $db->fetchOne("
                    SELECT s.*, c.class_name, sec.section_name
                    FROM students s
                    JOIN classes c ON s.class_id = c.class_id
                    JOIN sections sec ON s.section_id = sec.section_id
                    WHERE s.admission_no = :admission_no AND s.status = 'active'
                    LIMIT 1
                ", ['admission_no' => $admission_no]);

                if ($student) {
                    // Check if password is set
                    if (empty($student['password'])) {
                        $error = 'Your account has not been activated yet. Please contact the school administration.';
                        logLoginAttempt($admission_no, false);
                    } elseif (password_verify($password, $student['password'])) {
                        // Successful login
                        setStudentSession($student);
                        logLoginAttempt($admission_no, true);

                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Wrong password
                        $error = 'Invalid Admission Number or Password.';
                        logLoginAttempt($admission_no, false);
                        logStudentAudit(null, 'STUDENT_LOGIN_FAILED', 'students', null, null, ['admission_no' => $admission_no]);
                    }
                } else {
                    // Student not found
                    $error = 'Invalid Admission Number or Password.';
                    logLoginAttempt($admission_no, false);
                    logStudentAudit(null, 'STUDENT_LOGIN_INVALID_USER', 'students', null, null, ['admission_no' => $admission_no]);
                }
            }

            // Recheck fail count for CAPTCHA on next render
            $failCountRow = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM login_attempts
                 WHERE username = :u AND success = 0
                 AND attempted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
                ['u' => $admission_no]
            );
            $showCaptcha = (($failCountRow['cnt'] ?? 0) >= 3);

        } catch(Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log("Student login error: " . $e->getMessage());
        }
    }
}

render:
// Generate CAPTCHA question if needed
if ($showCaptcha && empty($captchaQuestion)) {
    $captchaQuestion = generateMathCaptcha();
}

$pageTitle = 'Student Login - ' . $siteSettings['school_name'];
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
    <link href="/assets/css/student.css" rel="stylesheet">
</head>
<body>
    <div class="student-login-container">
        <div class="login-card">
            <div class="login-header">
                <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="School Logo" style="height: 60px; margin-bottom: 10px;">
                <?php else: ?>
                    <i class="fas fa-user-graduate"></i>
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($siteSettings['school_name']); ?></h2>
                <p class="mb-0">Student Portal Login</p>
            </div>
            <div class="login-body">
                <?php if ($studentLoginDisabled): ?>
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-ban"></i> <strong>Student login is currently disabled.</strong><br>
                    Please contact your school administration for assistance.
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="admission_no" class="form-label">
                            <i class="fas fa-id-card"></i> Admission Number
                        </label>
                        <input type="text" class="form-control" id="admission_no" name="admission_no"
                               placeholder="Enter your Admission Number" required autofocus
                               value="<?php echo htmlspecialchars($_POST['admission_no'] ?? ''); ?>"
                               <?php echo $studentLoginDisabled ? 'disabled' : ''; ?>>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required
                               <?php echo $studentLoginDisabled ? 'disabled' : ''; ?>>
                    </div>

                    <?php if ($showCaptcha && !empty($captchaQuestion)): ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-shield-alt"></i> Security Check
                        </label>
                        <div class="mb-2">
                            <span class="captcha-question"><?php echo htmlspecialchars($captchaQuestion); ?> = ?</span>
                        </div>
                        <input type="number" class="form-control" name="captcha_answer"
                               placeholder="Enter the answer" required>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-login w-100"
                            <?php echo $studentLoginDisabled ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt"></i> Student Login
                    </button>

                    <div class="text-center mt-3">
                        <a href="/admin/login.php" class="text-muted">
                            <i class="fas fa-user-shield"></i> Admin Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
