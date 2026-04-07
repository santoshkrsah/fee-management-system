<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/email_helper.php';
require_once '../includes/settings_helper.php';

requireLogin();
requireRole(['sysadmin']); // Only system administrators

$pageTitle = 'Email Configuration Test';
$testResult = '';
$testStatus = '';
$saveResult = '';
$saveStatus = '';

// Handle save configuration
if (isset($_POST['save_config'])) {
    $settings = [
        'smtp_enabled' => isset($_POST['smtp_enabled']) ? 1 : 0,
        'smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
        'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
        'smtp_encryption' => sanitize($_POST['smtp_encryption'] ?? 'tls'),
        'smtp_username' => sanitize($_POST['smtp_username'] ?? ''),
        'smtp_password' => $_POST['smtp_password'] ?? '', // Don't sanitize password
        'smtp_from_email' => sanitize($_POST['smtp_from_email'] ?? ''),
        'smtp_from_name' => sanitize($_POST['smtp_from_name'] ?? '')
    ];

    // Validation
    $errors = [];

    if (empty($settings['smtp_host'])) {
        $errors[] = 'SMTP Host is required';
    }

    if ($settings['smtp_port'] < 1 || $settings['smtp_port'] > 65535) {
        $errors[] = 'Invalid SMTP Port';
    }

    if (!in_array($settings['smtp_encryption'], ['tls', 'ssl'])) {
        $errors[] = 'SMTP Encryption must be TLS or SSL';
    }

    if (!empty($settings['smtp_username']) && !isValidEmail($settings['smtp_username'])) {
        $errors[] = 'Invalid SMTP Username (must be an email address)';
    }

    if (!empty($settings['smtp_from_email']) && !isValidEmail($settings['smtp_from_email'])) {
        $errors[] = 'Invalid From Email address';
    }

    if (empty($errors)) {
        // Save settings to database
        $saved = SettingsHelper::saveEmailSettings($settings, getAdminId());

        // Also save to config file
        $configFileSaved = false;
        $configFilePath = __DIR__ . '/../config/email_config.php';

        try {
            // Generate new config file content
            $configContent = "<?php\n";
            $configContent .= "/**\n";
            $configContent .= " * Email Configuration\n";
            $configContent .= " * Configure SMTP settings for sending emails\n";
            $configContent .= " * Last updated: " . date('Y-m-d H:i:s') . "\n";
            $configContent .= " */\n\n";
            $configContent .= "// SMTP Configuration\n";
            $configContent .= "define('SMTP_ENABLED', " . ($settings['smtp_enabled'] ? 'true' : 'false') . "); // Set to false to disable email sending\n";
            $configContent .= "define('SMTP_HOST', '" . addslashes($settings['smtp_host']) . "'); // SMTP server (Gmail, Outlook, etc.)\n";
            $configContent .= "define('SMTP_PORT', " . $settings['smtp_port'] . "); // SMTP port (587 for TLS, 465 for SSL)\n";
            $configContent .= "define('SMTP_ENCRYPTION', '" . $settings['smtp_encryption'] . "'); // 'tls' or 'ssl'\n";
            $configContent .= "define('SMTP_USERNAME', '" . addslashes($settings['smtp_username']) . "'); // Your email address\n";
            $configContent .= "define('SMTP_PASSWORD', '" . addslashes($settings['smtp_password']) . "'); // Your app password (not regular password!)\n";
            $configContent .= "define('SMTP_FROM_EMAIL', '" . addslashes($settings['smtp_from_email']) . "'); // From email address\n";
            $configContent .= "define('SMTP_FROM_NAME', '" . addslashes($settings['smtp_from_name']) . "'); // From name\n\n";
            $configContent .= "/**\n";
            $configContent .= " * IMPORTANT SETUP INSTRUCTIONS:\n";
            $configContent .= " *\n";
            $configContent .= " * For Gmail:\n";
            $configContent .= " * 1. Enable 2-factor authentication on your Google account\n";
            $configContent .= " * 2. Go to: https://myaccount.google.com/apppasswords\n";
            $configContent .= " * 3. Generate an \"App Password\" for \"Mail\"\n";
            $configContent .= " * 4. Use that 16-character password as SMTP_PASSWORD\n";
            $configContent .= " * 5. DO NOT use your regular Gmail password!\n";
            $configContent .= " *\n";
            $configContent .= " * For Outlook/Hotmail:\n";
            $configContent .= " * - SMTP_HOST: smtp.office365.com\n";
            $configContent .= " * - SMTP_PORT: 587\n";
            $configContent .= " * - SMTP_ENCRYPTION: tls\n";
            $configContent .= " *\n";
            $configContent .= " * For Yahoo:\n";
            $configContent .= " * - SMTP_HOST: smtp.mail.yahoo.com\n";
            $configContent .= " * - SMTP_PORT: 587\n";
            $configContent .= " * - SMTP_ENCRYPTION: tls\n";
            $configContent .= " *\n";
            $configContent .= " * For Custom Domain (like Hostinger):\n";
            $configContent .= " * - SMTP_HOST: smtp.hostinger.com (or your hosting provider's SMTP)\n";
            $configContent .= " * - SMTP_PORT: 465 (SSL) or 587 (TLS)\n";
            $configContent .= " * - Use your email account credentials\n";
            $configContent .= " */\n\n";
            $configContent .= "// Email Templates Directory\n";
            $configContent .= "define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');\n\n";
            $configContent .= "// Email Settings\n";
            $configContent .= "define('EMAIL_DEBUG', false); // Set to true for debugging\n\n";
            $configContent .= "?>\n";

            // Write to file
            $configFileSaved = @file_put_contents($configFilePath, $configContent);

            if (!$configFileSaved) {
                throw new Exception("Failed to write config file. Check file permissions.");
            }
        } catch (Exception $e) {
            error_log("Config file save error: " . $e->getMessage());
        }

        if ($saved && $configFileSaved) {
            $saveResult = 'Email configuration saved successfully to both database and config file!';
            $saveStatus = 'success';

            logAudit(getAdminId(), 'EMAIL_CONFIG_UPDATED', 'system_settings', null, null, [
                'smtp_host' => $settings['smtp_host'],
                'smtp_username' => $settings['smtp_username'],
                'saved_to_file' => true
            ]);
        } elseif ($saved) {
            $saveResult = 'Email configuration saved to database, but failed to update config file. Check file permissions on /config/email_config.php';
            $saveStatus = 'warning';

            logAudit(getAdminId(), 'EMAIL_CONFIG_UPDATED', 'system_settings', null, null, [
                'smtp_host' => $settings['smtp_host'],
                'smtp_username' => $settings['smtp_username'],
                'saved_to_file' => false
            ]);
        } else {
            $saveResult = 'Failed to save configuration. Please check database permissions.';
            $saveStatus = 'error';
        }
    } else {
        $saveResult = 'Validation errors: ' . implode(', ', $errors);
        $saveStatus = 'error';
    }
}

// Handle test email sending
if (isset($_POST['send_test_email'])) {
    $testEmail = sanitize($_POST['test_email'] ?? '');

    if (empty($testEmail)) {
        $testResult = 'Please enter an email address';
        $testStatus = 'error';
    } elseif (!isValidEmail($testEmail)) {
        $testResult = 'Please enter a valid email address';
        $testStatus = 'error';
    } else {
        try {
            $emailSent = EmailHelper::sendTest($testEmail);

            if ($emailSent) {
                $testResult = "Test email sent successfully to $testEmail! Check your inbox (and spam folder).";
                $testStatus = 'success';

                logAudit(getAdminId(), 'TEST_EMAIL_SENT', 'system', null, null, [
                    'recipient' => $testEmail
                ]);
            } else {
                $testResult = "Failed to send email. Please check your SMTP configuration in /config/email_config.php";
                $testStatus = 'error';
            }
        } catch (Exception $e) {
            $testResult = 'Error: ' . $e->getMessage();
            $testStatus = 'error';
            error_log($e->getMessage());
        }
    }
}

// Get current email configuration (from database)
$currentConfig = SettingsHelper::getEmailSettings();
$currentConfig['configured'] = SettingsHelper::isEmailConfigured();

include '../includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-envelope"></i> Email Configuration & Testing
    </h2>

    <!-- Configuration Status -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i> Current Configuration
                        <?php if ($currentConfig['configured']): ?>
                            <span class="badge bg-success">Configured</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Not Configured</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Email Sending:</strong></td>
                                    <td><?= $currentConfig['smtp_enabled'] ? 'Yes' : 'No' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Host:</strong></td>
                                    <td><?= htmlspecialchars($currentConfig['smtp_host']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SMTP Port:</strong></td>
                                    <td><?= $currentConfig['smtp_port'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Encryption:</strong></td>
                                    <td><?= strtoupper($currentConfig['smtp_encryption']) ?></td>
                                </tr>
                            </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td><?= htmlspecialchars($currentConfig['smtp_username']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>From Email:</strong></td>
                                    <td><?= htmlspecialchars($currentConfig['smtp_from_email']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>From Name:</strong></td>
                                    <td><?= htmlspecialchars($currentConfig['smtp_from_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <?php if ($currentConfig['configured']): ?>
                                            <span class="badge bg-success">Ready</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Needs Setup</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                            </div>
                        </div>
                    </div>

                    <?php if (!$currentConfig['configured']): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Email not configured!</strong> Please follow the setup instructions below.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Configuration Form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Edit Email Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($saveResult): ?>
                        <?php
                        $alertClass = $saveStatus === 'success' ? 'success' : ($saveStatus === 'warning' ? 'warning' : 'danger');
                        $iconClass = $saveStatus === 'success' ? 'check-circle' : ($saveStatus === 'warning' ? 'exclamation-triangle' : 'exclamation-circle');
                        ?>
                        <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show">
                            <i class="fas fa-<?= $iconClass ?>"></i>
                            <?= htmlspecialchars($saveResult) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="smtp_enabled"
                                           name="smtp_enabled" <?= $currentConfig['smtp_enabled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="smtp_enabled">
                                        <strong>Enable Email Sending</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Turn this off to disable all email sending
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">SMTP Host <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="smtp_host"
                                       value="<?= htmlspecialchars($currentConfig['smtp_host']) ?>"
                                       placeholder="smtp.gmail.com" required>
                                <small class="form-text text-muted">
                                    Gmail: smtp.gmail.com | Outlook: smtp.office365.com
                                </small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label-custom">SMTP Port <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="smtp_port"
                                       value="<?= $currentConfig['smtp_port'] ?>"
                                       min="1" max="65535" required>
                                <small class="form-text text-muted">
                                    Usually 587 (TLS) or 465 (SSL)
                                </small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label-custom">Encryption <span class="text-danger">*</span></label>
                                <select class="form-control" name="smtp_encryption" required>
                                    <option value="tls" <?= $currentConfig['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= $currentConfig['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                                <small class="form-text text-muted">
                                    TLS (recommended for port 587)
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">SMTP Username (Email) <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="smtp_username"
                                       value="<?= htmlspecialchars($currentConfig['smtp_username']) ?>"
                                       placeholder="your-email@gmail.com" required>
                                <small class="form-text text-muted">
                                    Your full email address
                                </small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">SMTP Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="smtp_password"
                                       value="<?= htmlspecialchars($currentConfig['smtp_password']) ?>"
                                       placeholder="App-specific password" required>
                                <small class="form-text text-muted">
                                    For Gmail: Use 16-character app password (not your regular password)
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">From Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="smtp_from_email"
                                       value="<?= htmlspecialchars($currentConfig['smtp_from_email']) ?>"
                                       placeholder="noreply@yourschool.com" required>
                                <small class="form-text text-muted">
                                    Email address that appears in "From" field
                                </small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">From Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="smtp_from_name"
                                       value="<?= htmlspecialchars($currentConfig['smtp_from_name']) ?>"
                                       placeholder="Your School Name" required>
                                <small class="form-text text-muted">
                                    Name that appears in "From" field
                                </small>
                            </div>
                        </div>

                        <?php if (strpos(strtolower($currentConfig['smtp_host']), 'gmail') !== false): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Gmail Setup:</strong> You must enable 2-factor authentication and generate an app password.
                            Visit: <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="save_config" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Email -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Send Test Email</h5>
                </div>
                <div class="card-body">
                    <?php if ($testResult): ?>
                        <div class="alert alert-<?= $testStatus === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                            <i class="fas fa-<?= $testStatus === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <?= htmlspecialchars($testResult) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label-custom">Recipient Email Address</label>
                            <input type="email" class="form-control" name="test_email"
                                   placeholder="your-email@gmail.com" required
                                   value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>">
                            <small class="form-text text-muted">
                                Enter your email address to receive a test email
                            </small>
                        </div>

                        <button type="submit" name="send_test_email" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </form>

                    <p class="text-muted mt-3 mb-0">
                        <small><i class="fas fa-info-circle"></i> This will send a test email to verify your SMTP configuration is working correctly.</small>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> Quick Test</h5>
                </div>
                <div class="card-body">
                    <h6>What happens when you send a test email:</h6>
                    <ol>
                        <li>System connects to SMTP server</li>
                        <li>Authenticates with your credentials</li>
                        <li>Sends formatted HTML email</li>
                        <li>Shows success/error message</li>
                    </ol>

                    <h6 class="mt-3">If test fails:</h6>
                    <ul>
                        <li>Check SMTP credentials</li>
                        <li>Verify app password is correct</li>
                        <li>Check firewall/network settings</li>
                        <li>Enable "Less secure app access" (Gmail)</li>
                        <li>Check spam folder</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <strong>Note:</strong> PHP's mail() function is used. Some servers may require additional configuration.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Instructions -->
    <div class="card card-custom">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-book"></i> Gmail Setup Instructions</h5>
        </div>
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-google"></i> Step-by-Step Gmail Configuration</h6>

            <div class="alert alert-primary">
                <strong>Prerequisites:</strong>
                <ul class="mb-0">
                    <li>You have a Gmail account</li>
                    <li>You have admin access to this server</li>
                    <li>You can edit PHP files</li>
                </ul>
            </div>

            <div class="setup-steps">
                <div class="step-item mb-4">
                    <h5><span class="badge bg-primary">1</span> Enable 2-Factor Authentication</h5>
                    <p>Go to: <a href="https://myaccount.google.com/security" target="_blank">https://myaccount.google.com/security</a></p>
                    <ul>
                        <li>Click "2-Step Verification"</li>
                        <li>Follow the prompts to enable it</li>
                        <li>You'll need your phone for verification</li>
                    </ul>
                </div>

                <div class="step-item mb-4">
                    <h5><span class="badge bg-primary">2</span> Generate App Password</h5>
                    <p>Go to: <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a></p>
                    <ul>
                        <li>Select app: "Mail"</li>
                        <li>Select device: "Other (Custom name)"</li>
                        <li>Enter name: "Fee Management System"</li>
                        <li>Click "Generate"</li>
                        <li>Copy the 16-character password (e.g., "abcd efgh ijkl mnop")</li>
                    </ul>
                    <div class="alert alert-warning">
                        <strong>Important:</strong> Keep this password safe! You won't be able to see it again.
                    </div>
                </div>

                <div class="step-item mb-4">
                    <h5><span class="badge bg-primary">3</span> Update Configuration File</h5>
                    <p>Edit file: <code>/config/email_config.php</code></p>
                    <pre class="bg-dark text-light p-3 rounded"><code>define('SMTP_USERNAME', '<span class="text-warning">your-email@gmail.com</span>'); // Your Gmail address
define('SMTP_PASSWORD', '<span class="text-warning">abcd efgh ijkl mnop</span>'); // The 16-char app password
define('SMTP_FROM_EMAIL', '<span class="text-warning">noreply@yourschool.com</span>'); // Any email
define('SMTP_FROM_NAME', '<span class="text-warning">Your School Name</span>'); // School name</code></pre>
                </div>

                <div class="step-item mb-4">
                    <h5><span class="badge bg-primary">4</span> Test Configuration</h5>
                    <ul>
                        <li>Use the test form above</li>
                        <li>Enter your email address</li>
                        <li>Click "Send Test Email"</li>
                        <li>Check your inbox (and spam folder)</li>
                    </ul>
                </div>
            </div>

            <div class="alert alert-success">
                <h6><i class="fas fa-check-circle"></i> Once configured, your system can:</h6>
                <ul class="mb-0">
                    <li>Send password reset emails automatically</li>
                    <li>Email fee receipts to parents</li>
                    <li>Send payment reminders</li>
                    <li>Notify users of account changes</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alternative Providers -->
    <div class="card card-custom mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-server"></i> Other Email Providers</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6><i class="fas fa-envelope"></i> Outlook / Hotmail</h6>
                    <pre class="bg-light p-2 rounded"><code>SMTP_HOST: smtp.office365.com
SMTP_PORT: 587
SMTP_ENCRYPTION: tls
Username: your-email@outlook.com
Password: your-password</code></pre>
                </div>

                <div class="col-md-6 mb-3">
                    <h6><i class="fas fa-envelope"></i> Yahoo Mail</h6>
                    <pre class="bg-light p-2 rounded"><code>SMTP_HOST: smtp.mail.yahoo.com
SMTP_PORT: 587
SMTP_ENCRYPTION: tls
Username: your-email@yahoo.com
Password: your-app-password</code></pre>
                </div>

                <div class="col-md-6 mb-3">
                    <h6><i class="fas fa-server"></i> Hostinger</h6>
                    <pre class="bg-light p-2 rounded"><code>SMTP_HOST: smtp.hostinger.com
SMTP_PORT: 587
SMTP_ENCRYPTION: tls
Username: your-email@yourdomain.com
Password: your-email-password</code></pre>
                </div>

                <div class="col-md-6 mb-3">
                    <h6><i class="fas fa-server"></i> Custom Domain</h6>
                    <pre class="bg-light p-2 rounded"><code>SMTP_HOST: mail.yourdomain.com
SMTP_PORT: 587
SMTP_ENCRYPTION: tls
Username: your-email@yourdomain.com
Password: your-email-password</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-item {
    border-left: 3px solid #6366f1;
    padding-left: 20px;
}
.step-item h5 .badge {
    margin-right: 10px;
}
</style>

<?php include '../includes/footer.php'; ?>
