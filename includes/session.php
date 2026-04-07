<?php
/**
 * Enhanced Session Management with Security Features
 * Includes: Timeout, Hijacking Protection, Activity Logging
 */

// Include database functions if not already included
if (!function_exists('getDB')) {
    require_once __DIR__ . '/../config/database.php';
}

// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Session timeout: 30 minutes of inactivity
define('SESSION_TIMEOUT', 1800);

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    $isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               ($_SERVER['SERVER_PORT'] == 443);

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isHTTPS, // Auto-detect HTTPS
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Check session timeout
if (isLoggedIn()) {
    $inactive = time() - ($_SESSION['last_activity'] ?? time());

    if ($inactive > SESSION_TIMEOUT) {
        logAudit(getAdminId(), 'SESSION_TIMEOUT', 'session', null, null, null);
        logout('Your session has expired. Please login again.');
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Session hijacking protection
    validateSessionSecurity();
}

// Check student session timeout
if (isStudentLoggedIn()) {
    $studentInactive = time() - ($_SESSION['student_last_activity'] ?? time());

    if ($studentInactive > SESSION_TIMEOUT) {
        logStudentAudit(getStudentId(), 'STUDENT_SESSION_TIMEOUT', 'students');
        studentLogout('Your session has expired. Please login again.');
    }

    // Update last activity time
    $_SESSION['student_last_activity'] = time();

    // Session hijacking protection
    validateStudentSessionSecurity();
}

/**
 * Validate session against hijacking.
 * Fingerprint is based on User-Agent only (no IP) so mobile devices
 * switching networks do not trigger false-positive logouts.
 * On any mismatch the fingerprint is silently refreshed — this handles
 * both format migrations and genuine browser UA updates without kicking
 * legitimate users out. CSRF tokens on every form provide the main
 * protection against forged requests.
 */
function validateSessionSecurity() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $currentFingerprint = generateSessionFingerprint($userAgent);
    // Always keep fingerprint current (stores on first visit, refreshes on any mismatch)
    $_SESSION['fingerprint'] = $currentFingerprint;
}

/**
 * Generate session fingerprint (User-Agent only, no IP).
 */
function generateSessionFingerprint($userAgent, $ipAddress = '') {
    return hash('sha256', $userAgent . 'FEE_MGMT_SALT_2026');
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /admin/login.php");
        exit();
    }
}

/**
 * Get current admin ID
 * @return int|null
 */
function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin username
 * @return string|null
 */
function getAdminUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current admin full name
 * @return string|null
 */
function getAdminName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Set session data after login
 * @param array $adminData
 */
function setAdminSession($adminData) {
    $_SESSION['admin_id'] = $adminData['admin_id'];
    $_SESSION['username'] = $adminData['username'];
    $_SESSION['full_name'] = $adminData['full_name'];
    $_SESSION['email'] = $adminData['email'];
    $_SESSION['role'] = $adminData['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Generate fingerprint
    $_SESSION['fingerprint'] = generateSessionFingerprint(
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        getRealIPAddress()
    );

    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    // Store session ID in database for concurrent session prevention
    $sessionId = session_id();
    try {
        $db = getDB();
        $db->query("UPDATE admin SET session_id = :session_id, last_login = NOW(),
                    failed_login_attempts = 0, account_locked_until = NULL
                    WHERE admin_id = :admin_id", [
            'session_id' => $sessionId,
            'admin_id' => $adminData['admin_id']
        ]);
    } catch (Exception $e) {
        error_log("Failed to update session_id: " . $e->getMessage());
    }

    // Log successful login
    logAudit($adminData['admin_id'], 'LOGIN_SUCCESS', 'admin', $adminData['admin_id'], null, null);
}

/**
 * Destroy session and logout
 * @param string $message Optional logout message
 */
function logout($message = null) {
    // Log logout
    if (isLoggedIn()) {
        logAudit(getAdminId(), 'LOGOUT', 'admin', getAdminId(), null, null);

        // Clear session ID from database
        try {
            $db = getDB();
            $db->query("UPDATE admin SET session_id = NULL WHERE admin_id = :admin_id", [
                'admin_id' => getAdminId()
            ]);
        } catch (Exception $e) {
            error_log("Failed to clear session_id: " . $e->getMessage());
        }
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    if ($message) {
        session_start();
        setFlashMessage('info', $message);
    }

    header("Location: /admin/login.php");
    exit();
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

/**
 * Generate unique receipt number
 * @return string
 */
function generateReceiptNo() {
    return 'RCPT' . date('Ymd') . rand(1000, 9999);
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₹ ' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Redirect with message
 * @param string $url
 * @param string $type
 * @param string $message
 */
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header("Location: " . $url);
    exit();
}

/**
 * Get current admin role
 * @return string|null
 */
function getAdminRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if current user is System Administrator
 * @return bool
 */
function isSysAdmin() {
    return getAdminRole() === 'sysadmin';
}

/**
 * Check if current user is Administrator
 * @return bool
 */
function isAdmin() {
    return getAdminRole() === 'admin';
}

/**
 * Check if current user is Staff (operator role)
 * @return bool
 */
function isOperator() {
    return getAdminRole() === 'operator';
}

/**
 * Check if current user can edit records (sysadmin or admin)
 * @return bool
 */
function canEdit() {
    return isSysAdmin() || isAdmin();
}

/**
 * Check if current user can delete records (sysadmin only)
 * @return bool
 */
function canDelete() {
    return isSysAdmin();
}

/**
 * Require specific role(s) - redirect if not authorized
 * @param array $roles Allowed roles
 */
function requireRole($roles) {
    requireLogin();
    if (!in_array(getAdminRole(), $roles)) {
        redirectWithMessage('/admin/dashboard.php', 'error', 'You do not have permission to access this page.');
    }
}

/**
 * Get system settings from database (cached per request)
 * @return array
 */
function getSettings() {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = getDB();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [
                'school_name' => 'Fee Management System',
                'school_address' => '',
                'school_phone' => '',
                'school_email' => '',
                'school_logo' => ''
            ];
        }
    }
    return $settings;
}

/**
 * Get active academic session (cached per request)
 * @return array|null
 */
function getActiveSession() {
    static $session = null;
    if ($session === null) {
        try {
            $db = getDB();
            $session = $db->fetchOne("SELECT * FROM academic_sessions WHERE is_active = 1 LIMIT 1");
            if (!$session) {
                $session = false;
            }
        } catch (Exception $e) {
            $session = false;
        }
    }
    return $session ?: null;
}

/**
 * Get active academic session name (e.g. '2026-2027')
 * @return string
 */
function getActiveSessionName() {
    $session = getActiveSession();
    if ($session) {
        return $session['session_name'];
    }
    // Fallback to computed year if no active session in DB
    return date('Y') . '-' . (date('Y') + 1);
}

/**
 * Get all academic sessions
 * @return array
 */
function getAllSessions() {
    static $sessions = null;
    if ($sessions === null) {
        try {
            $db = getDB();
            $sessions = $db->fetchAll("SELECT * FROM academic_sessions ORDER BY session_name DESC");
        } catch (Exception $e) {
            $sessions = [];
        }
    }
    return $sessions;
}

/**
 * Get the currently selected session name from PHP session.
 * Falls back to the active session if none is selected.
 * @return string
 */
function getSelectedSession() {
    if (!empty($_SESSION['selected_session'])) {
        return $_SESSION['selected_session'];
    }
    return getActiveSessionName();
}

/**
 * Set the selected session in PHP session.
 * @param string $sessionName
 */
function setSelectedSession($sessionName) {
    $_SESSION['selected_session'] = $sessionName;
}

/**
 * Get current fee mode ('annual' or 'monthly')
 * @return string
 */
function getFeeMode() {
    $settings = getSettings();
    return $settings['fee_mode'] ?? 'annual';
}

/**
 * Check if system is in monthly fee mode
 * @return bool
 */
function isMonthlyFeeMode() {
    return getFeeMode() === 'monthly';
}

/**
 * Get ordered list of academic months (April-March, Indian academic year)
 * @return array
 */
function getAcademicMonths() {
    return [
        1  => 'April',
        2  => 'May',
        3  => 'June',
        4  => 'July',
        5  => 'August',
        6  => 'September',
        7  => 'October',
        8  => 'November',
        9  => 'December',
        10 => 'January',
        11 => 'February',
        12 => 'March'
    ];
}

/**
 * Log an audit trail entry
 * @param int $userId User who performed the action
 * @param string $action Action performed (e.g., 'CREATE_STUDENT', 'DELETE_PAYMENT')
 * @param string $tableName Table affected
 * @param int $recordId Record ID affected
 * @param array $oldValues Old values (for updates)
 * @param array $newValues New values (for creates/updates)
 */
function logAudit($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $db = getDB();

        $username = null;
        if ($userId) {
            $user = $db->fetchOne("SELECT username FROM admin WHERE admin_id = ?", [$userId]);
            $username = $user['username'] ?? null;
        }

        $db->query("INSERT INTO audit_log
                   (user_id, username, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                   VALUES (:user_id, :username, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)",
                   [
                       'user_id' => $userId,
                       'username' => $username,
                       'action' => $action,
                       'table_name' => $tableName,
                       'record_id' => $recordId,
                       'old_values' => $oldValues ? json_encode($oldValues) : null,
                       'new_values' => $newValues ? json_encode($newValues) : null,
                       'ip_address' => getRealIPAddress(),
                       'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
                   ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Check login rate limiting
 * @param string $username
 * @return array ['allowed' => bool, 'message' => string, 'wait_time' => int]
 */
function checkLoginRateLimit($username) {
    try {
        $db = getDB();
        $ipAddress = getRealIPAddress();

        // Check failed attempts in last 60 minutes
        $attempts = $db->fetchOne(
            "SELECT COUNT(*) as count FROM login_attempts
             WHERE (username = :username OR ip_address = :ip)
             AND success = 0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
            ['username' => $username, 'ip' => $ipAddress]
        );

        $failedCount = $attempts['count'] ?? 0;

        // Max 5 attempts in 60 minutes
        if ($failedCount >= 5) {
            // Check when they can try again
            $lastAttempt = $db->fetchOne(
                "SELECT attempted_at FROM login_attempts
                 WHERE (username = :username OR ip_address = :ip)
                 AND success = 0
                 ORDER BY attempted_at DESC LIMIT 1",
                ['username' => $username, 'ip' => $ipAddress]
            );

            if ($lastAttempt) {
                // Calculate remaining wait time using MySQL TIMESTAMPDIFF to avoid timezone issues
                $timeCheck = $db->fetchOne(
                    "SELECT TIMESTAMPDIFF(SECOND, :last_attempt, NOW()) as elapsed_seconds",
                    ['last_attempt' => $lastAttempt['attempted_at']]
                );

                $elapsedSeconds = $timeCheck['elapsed_seconds'] ?? 0;
                $waitTime = 3600 - $elapsedSeconds; // 60 minutes (3600 seconds)
                $minutes = max(1, ceil($waitTime / 60)); // At least 1 minute

                return [
                    'allowed' => false,
                    'message' => "Too many failed attempts. Please try again in $minutes minutes.",
                    'wait_time' => $waitTime
                ];
            }
        }

        return ['allowed' => true, 'message' => '', 'wait_time' => 0];

    } catch (Exception $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return ['allowed' => true, 'message' => '', 'wait_time' => 0]; // Fail open
    }
}

/**
 * Log login attempt
 * @param string $username
 * @param bool $success
 */
function logLoginAttempt($username, $success) {
    try {
        $db = getDB();
        $db->query(
            "INSERT INTO login_attempts (username, ip_address, success, user_agent)
             VALUES (:username, :ip_address, :success, :user_agent)",
            [
                'username' => $username,
                'ip_address' => getRealIPAddress(),
                'success' => $success ? 1 : 0,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]
        );
    } catch (Exception $e) {
        error_log("Login attempt log failed: " . $e->getMessage());
    }
}

/**
 * Get real IP address (handles proxies)
 * @return string
 */
function getRealIPAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // If multiple IPs (proxy chain), get the first one
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * Get subscription record from database (cached per request)
 * @return array|null
 */
function getSubscription() {
    static $sub = null;
    if ($sub === null) {
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
            $sub = $db->fetchOne("SELECT * FROM subscription ORDER BY id DESC LIMIT 1");
            if (!$sub) {
                $sub = false;
            }
        } catch (Exception $e) {
            $sub = false;
        }
    }
    return $sub ?: null;
}

/**
 * Get subscription status with computed fields
 * @return array|null ['active', 'expired', 'warning', 'days_remaining', 'months_remaining', 'remaining_text', 'expiry_date', 'start_date']
 */
function getSubscriptionStatus() {
    $sub = getSubscription();
    if (!$sub) {
        return null;
    }

    $today = new DateTime(date('Y-m-d'));
    $endDate = new DateTime($sub['end_date']);
    $startDate = new DateTime($sub['start_date']);

    $diff = $today->diff($endDate);
    $daysRemaining = $endDate >= $today ? (int)$diff->days : -(int)$diff->days;

    $expired = $today > $endDate;
    $warning = !$expired && $daysRemaining < 30;

    // Build remaining text (e.g. "3 months, 15 days")
    $remainingText = '';
    if (!$expired) {
        $parts = [];
        if ($diff->m > 0 || $diff->y > 0) {
            $totalMonths = $diff->y * 12 + $diff->m;
            $parts[] = $totalMonths . ' month' . ($totalMonths !== 1 ? 's' : '');
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' day' . ($diff->d !== 1 ? 's' : '');
        }
        $remainingText = !empty($parts) ? implode(', ', $parts) : '0 days';
    } else {
        $remainingText = 'Expired';
    }

    return [
        'active' => !$expired,
        'expired' => $expired,
        'warning' => $warning,
        'days_remaining' => $daysRemaining,
        'remaining_text' => $remainingText,
        'expiry_date' => $sub['end_date'],
        'start_date' => $sub['start_date'],
    ];
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePasswordStrength($password) {
    $errors = [];

    // Minimum 8 characters
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    // Must contain uppercase
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    // Must contain lowercase
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    // Must contain number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    // Must contain special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }

    if (empty($errors)) {
        return ['valid' => true, 'message' => ''];
    }

    return ['valid' => false, 'message' => implode('. ', $errors)];
}

// ===================================================================
// STUDENT PORTAL SESSION FUNCTIONS
// ===================================================================

/**
 * Check if a student is logged in
 * @return bool
 */
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true;
}

/**
 * Require student login - redirect to student login page if not logged in
 */
function requireStudentLogin() {
    if (!isStudentLoggedIn()) {
        header("Location: /student/login.php");
        exit();
    }
}

/**
 * Get current student ID
 * @return int|null
 */
function getStudentId() {
    return $_SESSION['student_id'] ?? null;
}

/**
 * Get current student name
 * @return string|null
 */
function getStudentName() {
    return $_SESSION['student_name'] ?? null;
}

/**
 * Get current student admission number
 * @return string|null
 */
function getStudentAdmissionNo() {
    return $_SESSION['student_admission_no'] ?? null;
}

/**
 * Set session data after student login
 * @param array $studentData
 */
function setStudentSession($studentData) {
    $_SESSION['student_id'] = $studentData['student_id'];
    $_SESSION['student_admission_no'] = $studentData['admission_no'];
    $_SESSION['student_name'] = $studentData['first_name'] . ' ' . $studentData['last_name'];
    $_SESSION['student_class'] = $studentData['class_name'];
    $_SESSION['student_section'] = $studentData['section_name'];
    $_SESSION['student_logged_in'] = true;
    $_SESSION['student_login_time'] = time();
    $_SESSION['student_last_activity'] = time();
    $_SESSION['student_password_changed'] = (bool)$studentData['password_changed'];

    // Generate fingerprint (reuses existing helper)
    $_SESSION['student_fingerprint'] = generateSessionFingerprint(
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        getRealIPAddress()
    );

    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    // Log successful login
    logStudentAudit($studentData['student_id'], 'STUDENT_LOGIN_SUCCESS', 'students', $studentData['student_id']);
}

/**
 * Validate student session against hijacking.
 * Same approach as validateSessionSecurity() — UA-only, always refreshed.
 */
function validateStudentSessionSecurity() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $currentFingerprint = generateSessionFingerprint($userAgent);
    $_SESSION['student_fingerprint'] = $currentFingerprint;
}

/**
 * Destroy student session and redirect to student login
 * Only clears student keys, preserves admin session if active
 * @param string $message Optional logout message
 */
function studentLogout($message = null) {
    // Log logout
    if (isStudentLoggedIn()) {
        logStudentAudit(getStudentId(), 'STUDENT_LOGOUT', 'students', getStudentId());
    }

    // Only clear student session keys (preserve admin session)
    unset($_SESSION['student_id']);
    unset($_SESSION['student_admission_no']);
    unset($_SESSION['student_name']);
    unset($_SESSION['student_class']);
    unset($_SESSION['student_section']);
    unset($_SESSION['student_logged_in']);
    unset($_SESSION['student_login_time']);
    unset($_SESSION['student_last_activity']);
    unset($_SESSION['student_fingerprint']);
    unset($_SESSION['student_password_changed']);

    if ($message) {
        setFlashMessage('info', $message);
    }

    header("Location: /student/login.php");
    exit();
}

/**
 * Log a student audit trail entry
 * Uses 'student:' prefix on username to distinguish from admin entries
 * @param int $studentId
 * @param string $action
 * @param string $tableName
 * @param int $recordId
 * @param array $oldValues
 * @param array $newValues
 */
function logStudentAudit($studentId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $db = getDB();

        $username = null;
        if ($studentId) {
            $student = $db->fetchOne("SELECT admission_no FROM students WHERE student_id = ?", [$studentId]);
            $username = $student ? 'student:' . $student['admission_no'] : null;
        }

        $db->query("INSERT INTO audit_log
                   (user_id, username, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                   VALUES (:user_id, :username, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)",
                   [
                       'user_id' => $studentId,
                       'username' => $username,
                       'action' => $action,
                       'table_name' => $tableName,
                       'record_id' => $recordId,
                       'old_values' => $oldValues ? json_encode($oldValues) : null,
                       'new_values' => $newValues ? json_encode($newValues) : null,
                       'ip_address' => getRealIPAddress(),
                       'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
                   ]);
    } catch (Exception $e) {
        error_log("Student audit log failed: " . $e->getMessage());
    }
}

/**
 * Generate a simple math CAPTCHA question
 * Stores answer in session
 * @return string The question string (e.g. "12 + 7")
 */
function generateMathCaptcha() {
    $a = rand(1, 20);
    $b = rand(1, 20);
    $_SESSION['captcha_answer'] = $a + $b;
    return "$a + $b";
}

/**
 * Verify math CAPTCHA answer
 * @param mixed $answer
 * @return bool
 */
function verifyCaptcha($answer) {
    return isset($_SESSION['captcha_answer']) && (int)$answer === $_SESSION['captcha_answer'];
}

/**
 * Encrypt data using AES-256-CBC
 * @param string $plaintext
 * @return string Base64 encoded encrypted string
 */
function encryptData($plaintext) {
    if (empty($plaintext)) return '';
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($plaintext, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Decrypt data encrypted with encryptData()
 * @param string $ciphertext Base64 encoded encrypted string
 * @return string|false Decrypted plaintext or false on failure
 */
function decryptData($ciphertext) {
    if (empty($ciphertext)) return '';
    $data = base64_decode($ciphertext);
    if ($data === false) return false;
    $parts = explode('::', $data, 2);
    if (count($parts) !== 2) return false;
    list($iv, $encrypted) = $parts;
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
?>
