<?php
// Try alternative Hostinger SMTP server
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings_helper.php';

try {
    $db = getDB()->getConnection();

    // Update SMTP host to domain-specific mail server
    $stmt = $db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'smtp_host'");
    $stmt->execute(['value' => 'mail.santoshkr.in']);

    echo "✅ SMTP host updated to: mail.santoshkr.in\n";
    echo "This is your domain-specific mail server.\n\n";
    echo "Try sending a test email now!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
