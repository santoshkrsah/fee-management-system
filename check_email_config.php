<?php
// Quick script to check SMTP configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings_helper.php';

try {
    $config = SettingsHelper::getEmailSettings();

    echo "=== Current Email Configuration ===\n";
    echo "Enabled: " . ($config['smtp_enabled'] ? 'Yes' : 'No') . "\n";
    echo "Host: " . $config['smtp_host'] . "\n";
    echo "Port: " . $config['smtp_port'] . "\n";
    echo "Encryption: " . $config['smtp_encryption'] . "\n";
    echo "Username: " . $config['smtp_username'] . "\n";
    echo "Password: " . (empty($config['smtp_password']) ? '(not set)' : str_repeat('*', strlen($config['smtp_password']))) . "\n";
    echo "From Email: " . $config['smtp_from_email'] . "\n";
    echo "From Name: " . $config['smtp_from_name'] . "\n";
    echo "\n";

    if (empty($config['smtp_username'])) {
        echo "⚠️  WARNING: SMTP username is not configured!\n";
        echo "Please go to /admin/email_test.php and save your configuration first.\n";
    } else {
        echo "✅ Configuration looks good!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
