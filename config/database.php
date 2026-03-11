<?php
/**
 * Database Configuration
 * Supports both MySQL and SQLite for different hosting environments
 */

// Check for environment variables (Render, etc.)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: 3308;
$dbName = getenv('DB_NAME') ?: 'menu_scanner';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

// Use SQLite for Render free tier (file-based, no MySQL needed)
$useSQLite = getenv('USE_SQLITE') === 'true' || !extension_loaded('pdo_mysql');

if ($useSQLite) {
    // SQLite configuration
    define('DB_TYPE', 'sqlite');
    define('DB_FILE', __DIR__ . '/../data/menu_scanner.sqlite');
    
    // Create data directory if it doesn't exist
    if (!is_dir(__DIR__ . '/../data')) {
        mkdir(__DIR__ . '/../data', 0755, true);
    }
    
    function getDbConnection() {
        static $pdo = null;
        
        if ($pdo === null) {
            try {
                $pdo = new PDO('sqlite:' . DB_FILE);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Enable foreign keys
                $pdo->exec('PRAGMA foreign_keys = ON');
                
                // Initialize database if empty
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                if (empty($tables)) {
                    initializeSQLite($pdo);
                }
            } catch (PDOException $e) {
                error_log("SQLite connection failed: " . $e->getMessage());
                die("Database connection failed. Please check permissions on data/ directory.");
            }
        }
        
        return $pdo;
    }
} else {
    // MySQL configuration
    define('DB_TYPE', 'mysql');
    define('DB_HOST', $dbHost);
    define('DB_PORT', (int)$dbPort);
    define('DB_NAME', $dbName);
    define('DB_USER', $dbUser);
    define('DB_PASS', $dbPass);
    
    function getDbConnection() {
        static $pdo = null;
        
        if ($pdo === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                    DB_HOST,
                    DB_PORT,
                    DB_NAME
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Try alternative ports
                $alternativePorts = [3306, 3307, 3309, 3310];
                $connected = false;
                
                foreach ($alternativePorts as $altPort) {
                    try {
                        $dsn = sprintf(
                            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                            DB_HOST,
                            $altPort,
                            DB_NAME
                        );
                        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                        define('DB_PORT_ACTUAL', $altPort);
                        $connected = true;
                        break;
                    } catch (PDOException $e2) {
                        continue;
                    }
                }
                
                if (!$connected) {
                    error_log("Database connection failed: " . $e->getMessage());
                    die("Database connection failed. Please ensure MySQL is running. Error: " . $e->getMessage());
                }
            }
        }
        
        return $pdo;
    }
}

// Initialize SQLite database with schema
function initializeSQLite($pdo) {
    $schema = file_get_contents(__DIR__ . '/../database/schema-sqlite.sql');
    if ($schema) {
        $pdo->exec($schema);
    }
}

// Helper function for prepared statements
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Helper function to fetch single row
function dbFetchOne($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

// Helper function to fetch all rows
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

// Helper function for insert with last insert ID
function dbInsert($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

// Helper function for update/delete
function dbExecute($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->rowCount();
}
?>
