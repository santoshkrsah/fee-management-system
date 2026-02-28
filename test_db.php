<?php
/**
 * Advanced Database Diagnostic Tool
 * Use this to identify the exact issue
 * IMPORTANT: Delete this file after fixing the issue!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$checks_passed = 0;
$checks_failed = 0;

function check($condition, $pass_message, $fail_message) {
    global $checks_passed, $checks_failed;
    echo "<tr>";
    if ($condition) {
        echo "<td style='color: green;'><strong>✅ PASS</strong></td>";
        echo "<td>" . $pass_message . "</td>";
        $checks_passed++;
    } else {
        echo "<td style='color: red;'><strong>❌ FAIL</strong></td>";
        echo "<td style='color: red;'><strong>" . $fail_message . "</strong></td>";
        $checks_failed++;
    }
    echo "</tr>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 8px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        .info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 15px 0;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .solution {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Database Connection Diagnostic Tool</h1>

    <h2>📋 Configuration Being Tested</h2>
    <table>
        <tr>
            <th>Setting</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Database Host</strong></td>
            <td><code>localhost</code></td>
        </tr>
        <tr>
            <td><strong>Database Name</strong></td>
            <td><code>u638211070_demo_fms</code></td>
        </tr>
        <tr>
            <td><strong>Database User</strong></td>
            <td><code>u638211070_demo_fms</code></td>
        </tr>
        <tr>
            <td><strong>Database Password</strong></td>
            <td><code>***hidden***</code></td>
        </tr>
    </table>

    <h2>✅ Diagnostic Checks</h2>
    <table>
        <tr>
            <th>Status</th>
            <th>Details</th>
        </tr>

        <?php
        // Check 1: Can we connect at all?
        $connection = null;
        $error_message = null;

        try {
            $dsn = "mysql:host=localhost;port=3306;charset=utf8mb4";
            $connection = new PDO($dsn, 'u638211070_demo_fms', 'Te@5219981998');
            check(true, "✅ Can connect to MySQL server", "Connection successful");
        } catch(Exception $e) {
            $error_message = $e->getMessage();
            check(false, "N/A", "Cannot connect: " . $error_message);
        }

        // Check 2: Can we select the database?
        if ($connection) {
            try {
                $dsn = "mysql:host=localhost;port=3306;dbname=u638211070_demo_fms;charset=utf8mb4";
                $connection = new PDO($dsn, 'u638211070_demo_fms', 'Te@5219981998');
                check(true, "✅ Can access database: u638211070_demo_fms", "Database exists and user has access");
            } catch(Exception $e) {
                check(false, "N/A", "Cannot access database: " . $e->getMessage());
                $connection = null;
            }
        }

        // Check 3: Are there any tables?
        if ($connection) {
            try {
                $stmt = $connection->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                check(count($tables) > 0,
                      "✅ Tables found: " . count($tables) . " tables",
                      "❌ NO TABLES FOUND - Database is empty, import failed");
            } catch(Exception $e) {
                check(false, "N/A", "Cannot query tables: " . $e->getMessage());
            }
        }

        // Check 4: Is there a users/admin table?
        if ($connection && isset($tables) && count($tables) > 0) {
            $has_users = in_array('users', $tables) || in_array('admin', $tables);
            check($has_users,
                  "✅ Admin/Users table exists",
                  "❌ No admin/users table found");
        }

        ?>
    </table>

    <?php if (!$connection || ($error_message !== null)): ?>

    <h2>🔧 SOLUTION</h2>

    <div class="error">
        <h3>Database Connection Failed!</h3>
        <p><strong>Error:</strong> <?php echo $error_message ?? "Unknown error"; ?></p>
    </div>

    <div class="solution">
        <h3>Step 1: Verify Database Exists in Hostinger</h3>
        <ol>
            <li>Go to <strong>Hostinger hPanel</strong></li>
            <li>Click <strong>Databases</strong> → <strong>MySQL Databases</strong></li>
            <li>Look for database: <code>u638211070_demo_fms</code></li>
            <li>If it's NOT listed:
                <ol type="a">
                    <li>Click <strong>Create New Database</strong></li>
                    <li>Database Name: <code>u638211070_demo_fms</code></li>
                    <li>Database User: <code>u638211070_demo_fms</code></li>
                    <li>Password: <code>Te@5219981998</code></li>
                    <li>Click <strong>Create</strong></li>
                </ol>
            </li>
        </ol>
    </div>

    <div class="solution">
        <h3>Step 2: Verify User Permissions</h3>
        <ol>
            <li>In phpMyAdmin (hPanel → Databases → phpMyAdmin)</li>
            <li>Click <strong>User accounts</strong> at the top</li>
            <li>Find user: <code>u638211070_demo_fms</code></li>
            <li>Check that it has access to: <code>u638211070_demo_fms</code></li>
            <li>If not, edit user and grant all privileges to that database</li>
        </ol>
    </div>

    <div class="solution">
        <h3>Step 3: Verify Password</h3>
        <ol>
            <li>In Hostinger hPanel → <strong>Databases</strong> → <strong>MySQL Databases</strong></li>
            <li>Find your database: <code>u638211070_demo_fms</code></li>
            <li>Look at the username/password field</li>
            <li>Verify password is exactly: <code>Te@5219981998</code></li>
            <li>Check your <code>config/database.php</code> file matches exactly</li>
        </ol>
    </div>

    <?php elseif (isset($tables) && count($tables) === 0): ?>

    <h2>🔧 SOLUTION</h2>

    <div class="warning">
        <h3>Database is Empty!</h3>
        <p>The database <code>u638211070_demo_fms</code> exists and is accessible, but it has no tables.</p>
        <p><strong>This means your import failed or wasn't done yet.</strong></p>
    </div>

    <div class="solution">
        <h3>Import Database Schema</h3>
        <ol>
            <li>Go to <strong>Hostinger hPanel</strong></li>
            <li>Click <strong>Databases</strong> → <strong>phpMyAdmin</strong></li>
            <li>Login with: <code>u638211070_demo_fms</code> / <code>Te@5219981998</code></li>
            <li>Left side: Click on <code>u638211070_demo_fms</code> database</li>
            <li>Top menu: Click <strong>Import</strong> tab</li>
            <li>Click <strong>Choose File</strong> button</li>
            <li>Select: <code>database_schema.sql</code> (from your local computer)</li>
            <li>Click <strong>Import</strong> button at bottom</li>
            <li>Wait for message: <strong>"Import has been successfully finished"</strong></li>
            <li>Refresh this page (F5)</li>
        </ol>
    </div>

    <?php else: ?>

    <h2>✅ SUCCESS!</h2>

    <div class="success">
        <h3>Your Database is Connected and Ready!</h3>
        <p>The database connection is working properly.</p>
        <p><strong>Tables found:</strong> <?php echo count($tables); ?></p>
        <ul>
            <?php foreach($tables as $table): ?>
                <li><code><?php echo $table; ?></code></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="info">
        <h3>Next Steps:</h3>
        <ol>
            <li>Delete this test file (<code>test_db.php</code>) from public_html/</li>
            <li>Go to your website: <strong>https://yourdomain.com</strong></li>
            <li>Login with: <code>admin</code> / <code>admin123</code></li>
            <li><strong>Change your admin password immediately!</strong></li>
        </ol>
    </div>

    <?php endif; ?>

    <h2>⚠️ AFTER YOU FIX THE ISSUE</h2>

    <div class="warning">
        <h3>DELETE THIS TEST FILE!</h3>
        <p>This file contains debugging information and sensitive connection details.</p>
        <ol>
            <li>Go to Hostinger File Manager</li>
            <li>Navigate to <code>public_html/</code></li>
            <li>Find <code>test_db.php</code></li>
            <li>Right-click → <strong>Delete</strong></li>
        </ol>
    </div>

    <h2>📞 Still Having Issues?</h2>

    <div class="info">
        <p><strong>What to tell support (copy the errors below):</strong></p>
        <pre>
Database Connection Test Results:
Status: TESTING
Error (if any): <?php echo $error_message ?? "None"; ?>
Tables Found: <?php echo isset($tables) ? count($tables) : "N/A"; ?>
        </pre>
    </div>

</div>
</body>
</html>
