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
define('BASE_URL', 'http://localhost/student_management/');

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
?>