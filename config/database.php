<?php
/**
 * Database Configuration and Connection
 * Provides secure database connectivity using PDO
 */

// Database Configuration
// Auto-detect if running in Docker container
$isDocker = getenv('DOCKER_ENV') !== false || gethostbyname('db') !== 'db';
define('DB_HOST', $isDocker ? 'db' : 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'fee_management_system');
define('DB_USER', 'root');
define('DB_PASS', 'Te@5219981998');
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Connection Class
 * Uses PDO with prepared statements for security
 */
class Database {
    private static $instance = null;
    private $connection;

    // Private constructor to prevent direct instantiation
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }

    // Prevent cloning of instance
    private function __clone() {}

    // Prevent unserialization of instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a query with prepared statements
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    /**
     * Fetch single row
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get last insert ID
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
}

// Initialize database connection
function getDB() {
    return Database::getInstance();
}
?>
