<?php
/**
 * Database Migration Runner
 * Run pending migrations from the migrations folder
 */

require_once '../config/database.php';

try {
    $db = getDB();
    $migrationsDir = __DIR__ . '/migrations';

    if (!is_dir($migrationsDir)) {
        echo "Migrations directory not found.\n";
        exit(1);
    }

    $migrations = glob($migrationsDir . '/*.sql');

    if (empty($migrations)) {
        echo "No migration files found.\n";
        exit(0);
    }

    echo "Running migrations...\n";
    echo str_repeat("=", 50) . "\n";

    foreach ($migrations as $migration) {
        $filename = basename($migration);
        echo "\nExecuting: $filename\n";

        $sql = file_get_contents($migration);

        // Split SQL statements by semicolon and filter empty ones
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            'strlen'
        );

        foreach ($statements as $statement) {
            try {
                $db->query($statement);
                echo "  ✓ Query executed successfully\n";
            } catch (Exception $e) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\nMigration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
