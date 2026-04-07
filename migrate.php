<?php
/**
 * Direct Migration Executor
 * Adds missing columns to students table
 */

// Database Configuration
$host = 'localhost';
$db = 'fee_management_system';
$user = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Database connected successfully\n\n";

    // Migration SQL
    $migrations = [
        "Adding whatsapp_number column" => "ALTER TABLE `students` ADD COLUMN `whatsapp_number` VARCHAR(15) DEFAULT NULL AFTER `contact_number`",
        "Adding aadhar_number column" => "ALTER TABLE `students` ADD COLUMN `aadhar_number` VARCHAR(12) DEFAULT NULL AFTER `whatsapp_number`",
        "Adding unique index on aadhar_number" => "ALTER TABLE `students` ADD UNIQUE KEY `idx_aadhar_unique` (`aadhar_number`)"
    ];

    foreach ($migrations as $description => $sql) {
        try {
            echo "Executing: $description\n";
            $pdo->exec($sql);
            echo "  ✓ Success\n\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "  ℹ Column already exists\n\n";
            } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "  ℹ Index already exists\n\n";
            } else {
                throw $e;
            }
        }
    }

    echo "==============================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "==============================================\n";
    echo "\nYou can now add students with WhatsApp and Aadhaar numbers.\n";

} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
