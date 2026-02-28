<?php
/**
 * Email Helper Class with Custom SMTP Implementation
 * Send emails via SMTP without external dependencies
 */

require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/settings_helper.php';

class EmailHelper {

    /**
     * Get email configuration (from database or fallback to constants)
     */
    private static function getConfig() {
        static $config = null;

        if ($config === null) {
            try {
                // Try to get settings from database
                $dbSettings = SettingsHelper::getEmailSettings();

                // Use database settings if available, otherwise fallback to constants
                $config = [
                    'enabled' => $dbSettings['smtp_enabled'] ?? (defined('SMTP_ENABLED') ? SMTP_ENABLED : true),
                    'host' => $dbSettings['smtp_host'] ?? (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com'),
                    'port' => $dbSettings['smtp_port'] ?? (defined('SMTP_PORT') ? SMTP_PORT : 587),
                    'encryption' => $dbSettings['smtp_encryption'] ?? (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls'),
                    'username' => $dbSettings['smtp_username'] ?? (defined('SMTP_USERNAME') ? SMTP_USERNAME : ''),
                    'password' => $dbSettings['smtp_password'] ?? (defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''),
                    'from_email' => $dbSettings['smtp_from_email'] ?? (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@school.com'),
                    'from_name' => $dbSettings['smtp_from_name'] ?? (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Fee Management System')
                ];
            } catch (Exception $e) {
                // Fallback to constants if database access fails
                $config = [
                    'enabled' => defined('SMTP_ENABLED') ? SMTP_ENABLED : true,
                    'host' => defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com',
                    'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
                    'encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls',
                    'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
                    'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
                    'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@school.com',
                    'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Fee Management System'
                ];
                error_log("Email config error: " . $e->getMessage());
            }
        }

        return $config;
    }

    /**
     * Send SMTP command and read response
     * @param resource $socket
     * @param string $command
     * @param int $expectedCode
     * @return bool
     */
    private static function smtpCommand($socket, $command, $expectedCode = 250) {
        fwrite($socket, $command . "\r\n");
        $response = fgets($socket, 515);

        // Check for timeout
        $info = stream_get_meta_data($socket);
        if ($info['timed_out']) {
            error_log("SMTP timeout during command: $command");
            return false;
        }

        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            error_log("SMTP Command: $command");
            error_log("SMTP Response: $response");
        }

        if (!$response) {
            error_log("No response from SMTP server for command: $command");
            return false;
        }

        $code = (int)substr($response, 0, 3);
        return $code === $expectedCode;
    }

    /**
     * Send email using custom SMTP implementation
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Optional parameters (cc, bcc)
     * @return bool Success status
     */
    private static function sendViaSMTP($to, $subject, $body, $options = []) {
        $config = self::getConfig();

        // Validate configuration
        if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            error_log("SMTP configuration incomplete. Host, username, and password are required.");
            return false;
        }

        $socket = null;

        try {
            // Establish connection with shorter timeout
            $timeout = 10;
            $errno = 0;
            $errstr = '';

            // Create SSL/TLS context with proper options
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            if ($config['encryption'] === 'ssl') {
                // SSL connection (port 465)
                $host = 'ssl://' . $config['host'];
                $socket = @stream_socket_client(
                    $host . ':' . $config['port'],
                    $errno,
                    $errstr,
                    $timeout,
                    STREAM_CLIENT_CONNECT,
                    $context
                );
            } else {
                // Plain connection for TLS (port 587)
                $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, $timeout);
            }

            if (!$socket) {
                throw new Exception("Failed to connect to SMTP server {$config['host']}:{$config['port']} - $errstr ($errno)");
            }

            // Set shorter stream timeout to prevent hanging
            stream_set_timeout($socket, 10);
            stream_set_blocking($socket, true);

            // Read welcome message with timeout check
            $response = fgets($socket, 515);

            // Check for timeout
            $info = stream_get_meta_data($socket);
            if ($info['timed_out']) {
                throw new Exception("SMTP server timeout - no response from {$config['host']}");
            }

            if (!$response || (int)substr($response, 0, 3) !== 220) {
                throw new Exception("SMTP connection failed: " . ($response ?: "No response"));
            }

            // Send EHLO
            if (!self::smtpCommand($socket, "EHLO " . $config['host'], 250)) {
                throw new Exception("EHLO command failed");
            }

            // Enable TLS if needed (STARTTLS for port 587)
            if ($config['encryption'] === 'tls') {
                if (!self::smtpCommand($socket, "STARTTLS", 220)) {
                    throw new Exception("STARTTLS command failed");
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("Failed to enable TLS encryption");
                }

                // Send EHLO again after STARTTLS
                if (!self::smtpCommand($socket, "EHLO " . $config['host'], 250)) {
                    throw new Exception("EHLO after STARTTLS failed");
                }
            }

            // Authenticate using AUTH PLAIN (more widely supported)
            // AUTH PLAIN format: base64("\0username\0password")
            $authString = base64_encode("\0" . $config['username'] . "\0" . $config['password']);

            if (!self::smtpCommand($socket, "AUTH PLAIN " . $authString, 235)) {
                // If AUTH PLAIN fails, try AUTH LOGIN as fallback
                if (!self::smtpCommand($socket, "AUTH LOGIN", 334)) {
                    throw new Exception("Authentication failed. Server rejected both AUTH PLAIN and AUTH LOGIN methods.");
                }

                // Send username (base64 encoded)
                if (!self::smtpCommand($socket, base64_encode($config['username']), 334)) {
                    throw new Exception("Username authentication failed");
                }

                // Send password (base64 encoded)
                if (!self::smtpCommand($socket, base64_encode($config['password']), 235)) {
                    throw new Exception("Password authentication failed. Check your credentials.");
                }
            }

            // MAIL FROM
            $from = $config['from_email'];
            if (!self::smtpCommand($socket, "MAIL FROM:<$from>", 250)) {
                throw new Exception("MAIL FROM command failed");
            }

            // RCPT TO (main recipient)
            if (!self::smtpCommand($socket, "RCPT TO:<$to>", 250)) {
                throw new Exception("RCPT TO command failed");
            }

            // Add CC recipients
            if (!empty($options['cc'])) {
                $ccList = is_array($options['cc']) ? $options['cc'] : explode(',', $options['cc']);
                foreach ($ccList as $cc) {
                    $cc = trim($cc);
                    if (!empty($cc)) {
                        self::smtpCommand($socket, "RCPT TO:<$cc>", 250);
                    }
                }
            }

            // Add BCC recipients
            if (!empty($options['bcc'])) {
                $bccList = is_array($options['bcc']) ? $options['bcc'] : explode(',', $options['bcc']);
                foreach ($bccList as $bcc) {
                    $bcc = trim($bcc);
                    if (!empty($bcc)) {
                        self::smtpCommand($socket, "RCPT TO:<$bcc>", 250);
                    }
                }
            }

            // DATA command
            if (!self::smtpCommand($socket, "DATA", 354)) {
                throw new Exception("DATA command failed");
            }

            // Build email headers
            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">";
            $headers[] = "To: <$to>";

            if (!empty($options['cc'])) {
                $headers[] = "Cc: " . (is_array($options['cc']) ? implode(', ', $options['cc']) : $options['cc']);
            }

            $headers[] = "Subject: " . $subject;
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 8bit";
            $headers[] = "X-Mailer: Fee Management System";

            // Send headers and body
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
            fwrite($socket, $message . "\r\n");

            // Read response
            $response = fgets($socket, 515);
            if ((int)substr($response, 0, 3) !== 250) {
                throw new Exception("Email sending failed: $response");
            }

            // Send QUIT
            self::smtpCommand($socket, "QUIT", 221);

            fclose($socket);

            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                error_log("Email sent successfully via SMTP to: $to");
            }

            return true;

        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());

            if ($socket && is_resource($socket)) {
                @fclose($socket);
            }

            // Re-throw the exception so the caller can see the actual error
            throw $e;
        }
    }

    /**
     * Send email using SMTP
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Optional parameters (cc, bcc)
     * @return bool Success status
     */
    public static function send($to, $subject, $body, $options = []) {
        $config = self::getConfig();

        if (!$config['enabled']) {
            error_log("Email sending is disabled in configuration");
            return false;
        }

        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid recipient email address: $to");
            return false;
        }

        return self::sendViaSMTP($to, $subject, $body, $options);
    }

    /**
     * Load email template
     * @param string $templateName Template file name (without .php)
     * @param array $data Variables to pass to template
     * @return string Rendered HTML
     */
    public static function loadTemplate($templateName, $data = []) {
        $templatePath = EMAIL_TEMPLATES_DIR . $templateName . '.php';

        if (!file_exists($templatePath)) {
            error_log("Email template not found: $templatePath");
            return '';
        }

        // Extract data to variables
        extract($data);

        // Capture template output
        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Send fee receipt email
     * @param string $to Recipient email
     * @param array $data Receipt data
     * @return bool Success status
     */
    public static function sendFeeReceipt($to, $data) {
        $subject = "Fee Receipt - " . $data['receipt_no'];
        $body = self::loadTemplate('fee_receipt', $data);
        return self::send($to, $subject, $body);
    }

    /**
     * Send password reset email
     * @param string $to Recipient email
     * @param array $data Reset data (token, link, name)
     * @return bool Success status
     */
    public static function sendPasswordReset($to, $data) {
        $subject = "Password Reset Request";
        $body = self::loadTemplate('password_reset', $data);
        return self::send($to, $subject, $body);
    }

    /**
     * Send password changed notification
     * @param string $to Recipient email
     * @param array $data User data
     * @return bool Success status
     */
    public static function sendPasswordChanged($to, $data) {
        $subject = "Password Changed Successfully";
        $body = self::loadTemplate('password_changed', $data);
        return self::send($to, $subject, $body);
    }

    /**
     * Send fee reminder email
     * @param string $to Recipient email
     * @param array $data Student and fee data
     * @return bool Success status
     */
    public static function sendFeeReminder($to, $data) {
        $subject = "Fee Payment Reminder - " . $data['student_name'];
        $body = self::loadTemplate('fee_reminder', $data);
        return self::send($to, $subject, $body);
    }

    /**
     * Send welcome email to new user
     * @param string $to Recipient email
     * @param array $data User data
     * @return bool Success status
     */
    public static function sendWelcome($to, $data) {
        $subject = "Welcome to Fee Management System";
        $body = self::loadTemplate('welcome', $data);
        return self::send($to, $subject, $body);
    }

    /**
     * Send test email
     * @param string $to Recipient email
     * @return bool Success status
     */
    public static function sendTest($to) {
        $subject = "Test Email - Fee Management System";
        $body = self::loadTemplate('test', [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to
        ]);
        return self::send($to, $subject, $body);
    }
}
?>
