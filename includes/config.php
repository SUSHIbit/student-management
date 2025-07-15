<?php
/**
 * Application Configuration
 * Student Management System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'Student Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/sms/');
define('SITE_URL', 'http://localhost/sms/');

// File upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Pagination settings
define('DEFAULT_RECORDS_PER_PAGE', 10);
define('MAX_RECORDS_PER_PAGE', 100);

// Email settings (for future implementation)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@sms.local');
define('FROM_NAME', 'Student Management System');

// Grade settings
define('PASSING_GRADE', 60.0);
define('DEAN_LIST_REQUIREMENT', 85.0);
define('PROBATION_THRESHOLD', 50.0);

// Academic year settings
define('CURRENT_ACADEMIC_YEAR', '2024-2025');
define('SEMESTERS_PER_YEAR', 2);
define('MAX_YEAR_LEVEL', 4);

// System settings
define('TIMEZONE', 'Asia/Kuala_Lumpur');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('CURRENCY', 'RM');

// Debug settings
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('LOG_PATH', __DIR__ . '/../logs/');

// Create necessary directories if they don't exist
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . 'profiles/',
    UPLOAD_PATH . 'documents/',
    LOG_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log errors to file
if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'error.log');
}
?>