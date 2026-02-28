<?php
/**
 * System Settings Helper
 * Manages system configuration settings stored in database
 */

require_once __DIR__ . '/../config/database.php';

class SettingsHelper {

    /**
     * Get a setting value
     */
    public static function get($key, $default = null) {
        try {
            $db = getDB()->getConnection();
            $stmt = $db->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return $default;
            }

            // Convert based on type
            return self::convertValue($result['setting_value'], $result['setting_type']);

        } catch (PDOException $e) {
            error_log("Settings Error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Get multiple settings at once
     */
    public static function getMultiple($keys) {
        try {
            $db = getDB()->getConnection();
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM system_settings WHERE setting_key IN ($placeholders)");
            $stmt->execute($keys);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = self::convertValue($row['setting_value'], $row['setting_type']);
            }

            return $settings;

        } catch (PDOException $e) {
            error_log("Settings Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all email settings
     */
    public static function getEmailSettings() {
        $keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption',
                 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];

        $settings = self::getMultiple($keys);

        // Return with fallback defaults
        return [
            'smtp_enabled' => $settings['smtp_enabled'] ?? true,
            'smtp_host' => $settings['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_port' => $settings['smtp_port'] ?? 587,
            'smtp_encryption' => $settings['smtp_encryption'] ?? 'tls',
            'smtp_username' => $settings['smtp_username'] ?? 'your-email@gmail.com',
            'smtp_password' => $settings['smtp_password'] ?? 'your-app-password',
            'smtp_from_email' => $settings['smtp_from_email'] ?? 'noreply@school.com',
            'smtp_from_name' => $settings['smtp_from_name'] ?? 'Fee Management System'
        ];
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string', $updatedBy = null) {
        try {
            $db = getDB()->getConnection();

            // Convert value to string for storage
            $stringValue = self::convertToString($value, $type);

            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP
            ");

            return $stmt->execute([$key, $stringValue, $type, $updatedBy]);

        } catch (PDOException $e) {
            error_log("Settings Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set multiple settings at once
     */
    public static function setMultiple($settings, $updatedBy = null) {
        $db = getDB()->getConnection();

        try {
            $db->beginTransaction();

            foreach ($settings as $key => $data) {
                $value = $data['value'];
                $type = $data['type'] ?? 'string';

                if (!self::set($key, $value, $type, $updatedBy)) {
                    $db->rollBack();
                    return false;
                }
            }

            $db->commit();
            return true;

        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Settings Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save email settings
     */
    public static function saveEmailSettings($settings, $updatedBy = null) {
        $emailSettings = [
            'smtp_enabled' => ['value' => $settings['smtp_enabled'] ?? true, 'type' => 'boolean'],
            'smtp_host' => ['value' => $settings['smtp_host'] ?? '', 'type' => 'string'],
            'smtp_port' => ['value' => $settings['smtp_port'] ?? 587, 'type' => 'int'],
            'smtp_encryption' => ['value' => $settings['smtp_encryption'] ?? 'tls', 'type' => 'string'],
            'smtp_username' => ['value' => $settings['smtp_username'] ?? '', 'type' => 'string'],
            'smtp_password' => ['value' => $settings['smtp_password'] ?? '', 'type' => 'string'],
            'smtp_from_email' => ['value' => $settings['smtp_from_email'] ?? '', 'type' => 'string'],
            'smtp_from_name' => ['value' => $settings['smtp_from_name'] ?? '', 'type' => 'string']
        ];

        return self::setMultiple($emailSettings, $updatedBy);
    }

    /**
     * Convert database value to correct type
     */
    private static function convertValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'int':
                return (int)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Convert value to string for database storage
     */
    private static function convertToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string)$value;
        }
    }

    /**
     * Check if email is configured
     */
    public static function isEmailConfigured() {
        $settings = self::getEmailSettings();
        return $settings['smtp_username'] !== 'your-email@gmail.com' &&
               $settings['smtp_password'] !== 'your-app-password' &&
               !empty($settings['smtp_username']) &&
               !empty($settings['smtp_password']);
    }
}
?>
