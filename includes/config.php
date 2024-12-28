<?php
// Start session and suppress errors from showing in output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

class Database {
    private static $instance = null;
    private $connection;
    private static $maxRetries = 3;
    private static $retryDelay = 1; // seconds
    
    private function __construct() {
        $retries = 0;
        while ($retries < self::$maxRetries) {
            try {
                $host = 'localhost';
                $username = 'root';
                $password = '';
                $database = 'sidequest_db';
                
                if (empty($host) || empty($username) || empty($database)) {
                    throw new Exception("Invalid database configuration");
                }
                
                $this->connection = new mysqli($host, $username, $password, $database);
                
                if ($this->connection->connect_error) {
                    throw new Exception("Connection failed: " . $this->connection->connect_error);
                }
                
                $this->connection->set_charset("utf8mb4");
                $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
                
                // Set connection parameters for better performance
                $this->connection->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES'");
                $this->connection->query("SET SESSION wait_timeout = 600");
                $this->connection->query("SET SESSION interactive_timeout = 600");
                
                return;
            } catch (Exception $e) {
                $retries++;
                if ($retries >= self::$maxRetries) {
                    error_log("Database connection error after {$retries} retries: " . $e->getMessage());
                    throw $e;
                }
                error_log("Database connection attempt {$retries} failed: " . $e->getMessage());
                sleep(self::$retryDelay);
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Check if connection is still alive
        if (!self::$instance->connection->ping()) {
            self::$instance = new self();
        }
        
        return self::$instance->connection;
    }
    
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Constants
define('BASE_URL', 'http://localhost/sidequest');

// Timezone
date_default_timezone_set('Asia/Manila');

define('ENVIRONMENT', 'production');

if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    
    // Set error handler for production
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An internal error occurred']);
        exit;
    });
}

function rateLimit($key, $limit = 60) {
    if (!class_exists('Redis')) {
        error_log('Redis not installed - rate limiting disabled');
        return;
    }
    
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $count = $redis->incr($key);
        if ($count > $limit) {
            http_response_code(429);
            exit('Too many requests');
        }
    } catch (Exception $e) {
        error_log('Redis error: ' . $e->getMessage());
    }
}
?>