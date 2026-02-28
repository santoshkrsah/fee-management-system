<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
requireRole(['sysadmin']); // Only system administrators

$pageTitle = 'Database Backup & Restore';
$success = '';
$error = '';

// Handle backup download
if (isset($_POST['create_backup'])) {
    try {
        $db = getDB();
        $backup = createDatabaseBackup($db);

        $filename = 'fee_management_system_db_backup_' . date('Y-m-d_His') . '.sql';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));

        echo $backup;
        exit();

    } catch (Exception $e) {
        $error = 'Backup failed: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Handle restore
if (isset($_POST['restore_backup']) && isset($_FILES['backup_file'])) {
    if ($_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $sqlContent = file_get_contents($_FILES['backup_file']['tmp_name']);

            if (empty($sqlContent)) {
                throw new Exception('Backup file is empty');
            }

            // Check if it looks like a valid SQL file
            if (strpos($sqlContent, 'CREATE TABLE') === false && strpos($sqlContent, 'INSERT INTO') === false) {
                throw new Exception('Invalid backup file - does not contain SQL statements');
            }

            // Restore database
            $db = getDB();
            restoreDatabaseBackup($db, $sqlContent);

            logAudit(getAdminId(), 'DATABASE_RESTORED', 'system', null, null, [
                'filename' => $_FILES['backup_file']['name']
            ]);

            $success = 'Database restored successfully! Please refresh the page to see restored data.';

        } catch (Exception $e) {
            $error = 'Restore failed: ' . $e->getMessage();
            error_log('Database restore error: ' . $e->getMessage());
            error_log('File: ' . $_FILES['backup_file']['name'] . ', Size: ' . $_FILES['backup_file']['size']);
        }
    } else {
        $error = 'Failed to upload file. Error code: ' . $_FILES['backup_file']['error'];
    }
}

// Get existing backups from backups directory
$backupsDir = '../backups/';
if (!is_dir($backupsDir)) {
    mkdir($backupsDir, 0755, true);
}

$existingBackups = [];
if (is_dir($backupsDir)) {
    $files = scandir($backupsDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $existingBackups[] = [
                'name' => $file,
                'size' => filesize($backupsDir . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backupsDir . $file))
            ];
        }
    }
}

/**
 * Create database backup
 * @param object $db Database connection
 * @return string SQL backup content
 */
function createDatabaseBackup($db) {
    $backup = "-- Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Database: fee_management_system\n\n";

    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tables = $db->fetchAll("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

    foreach ($tables as $table) {
        $tableName = array_values($table)[0];

        // Get table structure
        $createTable = $db->fetchOne("SHOW CREATE TABLE `$tableName`");
        $backup .= "\n-- Table structure for `$tableName`\n";
        $backup .= "DROP TABLE IF EXISTS `$tableName`;\n";
        $backup .= $createTable['Create Table'] . ";\n\n";

        // Get table data
        $rows = $db->fetchAll("SELECT * FROM `$tableName`");

        if (!empty($rows)) {
            $backup .= "-- Data for table `$tableName`\n";

            // Get PDO connection for quoting
            $pdo = $db->getConnection();

            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    // PDO::quote() already adds quotes around the value
                    return $pdo->quote($value);
                }, array_values($row));

                $backup .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup .= "\n";
        }
    }

    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Now backup views (just definitions, no data)
    $views = $db->fetchAll("SHOW FULL TABLES WHERE Table_type = 'VIEW'");

    if (!empty($views)) {
        $backup .= "\n-- =============================================\n";
        $backup .= "-- Views\n";
        $backup .= "-- =============================================\n\n";

        foreach ($views as $view) {
            $viewName = array_values($view)[0];

            try {
                $createView = $db->fetchOne("SHOW CREATE VIEW `$viewName`");
                $backup .= "\n-- View structure for `$viewName`\n";
                $backup .= "DROP VIEW IF EXISTS `$viewName`;\n";
                $backup .= $createView['Create View'] . ";\n\n";
            } catch (Exception $e) {
                error_log("Failed to backup view $viewName: " . $e->getMessage());
            }
        }
    }

    return $backup;
}

/**
 * Restore database from backup
 * @param object $db Database connection
 * @param string $sqlContent SQL backup content
 * @throws Exception if restore fails
 */
function restoreDatabaseBackup($db, $sqlContent) {
    // Get PDO connection
    $pdo = $db->getConnection();

    // Disable foreign key checks during restore
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $errors = [];
    $successCount = 0;
    $totalQueries = 0;

    try {
        // Better query parsing - handle multi-line statements
        $queries = [];
        $currentQuery = '';
        $lines = explode("\n", $sqlContent);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            $currentQuery .= $line . ' ';

            // Check if query is complete (ends with semicolon)
            if (substr(rtrim($line), -1) === ';') {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            }
        }

        // Execute each query
        foreach ($queries as $query) {
            if (empty($query)) continue;

            $totalQueries++;

            try {
                $pdo->exec($query);
                $successCount++;
            } catch (PDOException $e) {
                $error = "Query failed: " . $e->getMessage() . "\nQuery: " . substr($query, 0, 200);
                $errors[] = $error;
                error_log($error);

                // Continue with other queries but collect errors
            }
        }

        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // If too many errors, throw exception
        if (count($errors) > 0 && $successCount < ($totalQueries * 0.5)) {
            throw new Exception("Restore failed: Too many errors. Successfully executed $successCount out of $totalQueries queries. Check error log for details.");
        }

        if (count($errors) > 0) {
            error_log("Restore completed with warnings: $successCount/$totalQueries queries succeeded. " . count($errors) . " errors occurred.");
        }

    } catch (Exception $e) {
        // Re-enable foreign key checks even on error
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        throw $e;
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-database"></i> Database Backup & Restore
    </h2>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Create Backup -->
        <div class="col-md-6 mb-4">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-download"></i> Create New Backup</h5>
                </div>
                <div class="card-body">
                    <p>Download a complete backup of your database including all tables and data.</p>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Backup includes:</strong>
                        <ul class="mb-0 mt-2">
                            <li>All students data</li>
                            <li>Fee records and payments</li>
                            <li>User accounts</li>
                            <li>System settings</li>
                            <li>Audit logs</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <button type="submit" name="create_backup" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-download"></i> Download Backup Now
                        </button>
                    </form>

                    <p class="text-muted mt-3 mb-0">
                        <small><i class="fas fa-clock"></i> Estimated time: 1-5 seconds</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Restore Backup -->
        <div class="col-md-6 mb-4">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Restore from Backup</h5>
                </div>
                <div class="card-body">
                    <p>Restore database from a previously created backup file.</p>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong>
                        <ul class="mb-0 mt-2">
                            <li>This will OVERWRITE all current data</li>
                            <li>All users will be logged out</li>
                            <li>Create a backup before restoring</li>
                            <li>This action cannot be undone</li>
                        </ul>
                    </div>

                    <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Are you sure? This will replace ALL current data!');">
                        <div class="mb-3">
                            <label class="form-label-custom">Select Backup File (.sql)</label>
                            <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                        </div>

                        <button type="submit" name="restore_backup" class="btn btn-danger btn-custom w-100">
                            <i class="fas fa-upload"></i> Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History (if any) -->
    <?php if (!empty($existingBackups)): ?>
    <div class="card card-custom">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history"></i> Backup History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Date Created</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existingBackups as $backup): ?>
                        <tr>
                            <td><i class="fas fa-file-archive"></i> <?= htmlspecialchars($backup['name']) ?></td>
                            <td><?= $backup['date'] ?></td>
                            <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                            <td>
                                <a href="../backups/<?= urlencode($backup['name']) ?>" class="btn btn-sm btn-primary" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tips & Best Practices -->
    <div class="card card-custom mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Backup Best Practices</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Regular Backups</h6>
                    <ul>
                        <li>Create daily backups during active session</li>
                        <li>Weekly backups at minimum</li>
                        <li>Before major system updates</li>
                        <li>Before bulk operations</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Storage</h6>
                    <ul>
                        <li>Store backups on external drive</li>
                        <li>Keep multiple backup copies</li>
                        <li>Store offsite for disaster recovery</li>
                        <li>Test restore procedures regularly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
