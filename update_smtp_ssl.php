<?php
// Update SMTP configuration to SSL/465
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings_helper.php';

try {
    $db = getDB()->getConnection();

    // Update to SSL/465
    $updates = [
        'smtp_port' => 465,
        'smtp_encryption' => 'ssl'
    ];

    foreach ($updates as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        $stmt->execute(['value' => $value, 'key' => $key]);
    }

    echo "✅ Configuration updated successfully!\n\n";

    // Display current config
    $config = SettingsHelper::getEmailSettings();
    echo "=== Updated Configuration ===\n";
    echo "Host: {$config['smtp_host']}\n";
    echo "Port: {$config['smtp_port']}\n";
    echo "Encryption: " . strtoupper($config['smtp_encryption']) . "\n";
    echo "Username: {$config['smtp_username']}\n";
    echo "\n✅ Ready to test! Now try sending a test email.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
