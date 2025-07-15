<?php
/**
 * Database Configuration
 * Student Management System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_management');

// Application configuration
define('APP_NAME', 'Student Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/student-management/');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Database connection function
function get_db_connection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

// Initialize database connection
$pdo = get_db_connection();

// Test database connection
function test_db_connection() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Auto-detect base URL for flexibility
function auto_detect_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // Extract the directory path (remove the filename)
    $dir_path = dirname($script_name);
    
    // Ensure it ends with student-management
    if (strpos($dir_path, 'student-management') === false) {
        // If we're in a subdirectory, go up until we find student-management
        $path_parts = explode('/', trim($dir_path, '/'));
        $base_parts = [];
        
        foreach ($path_parts as $part) {
            $base_parts[] = $part;
            if ($part === 'student-management') {
                break;
            }
        }
        
        $dir_path = '/' . implode('/', $base_parts);
    }
    
    return $protocol . '://' . $host . $dir_path . '/';
}

// Override BASE_URL with auto-detected one if needed
if (!defined('BASE_URL_OVERRIDE')) {
    $auto_base_url = auto_detect_base_url();
    if ($auto_base_url !== BASE_URL) {
        define('BASE_URL_OVERRIDE', $auto_base_url);
    }
}

// Function to get the correct base URL
function get_base_url() {
    return defined('BASE_URL_OVERRIDE') ? BASE_URL_OVERRIDE : BASE_URL;
}
?>