<?php
/**
 * Common Functions
 * Student Management System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect to login page if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

/**
 * Get current admin info
 */
function get_current_admin() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT admin_id, username, full_name, email FROM admins WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * Clean and sanitize input
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 */
function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Get student count
 */
function get_student_count() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Get course count
 */
function get_course_count() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Get subject count
 */
function get_subject_count() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects WHERE status = 'active'");
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Get all courses for dropdown
 */
function get_all_courses() {
    global $pdo;
    $stmt = $pdo->query("SELECT course_id, course_code, course_name FROM courses WHERE status = 'active' ORDER BY course_name");
    return $stmt->fetchAll();
}

/**
 * Get course by ID
 */
function get_course_by_id($course_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    return $stmt->fetch();
}

/**
 * Get student by ID
 */
function get_student_by_id($student_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name, c.course_code 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetch();
}

/**
 * Generate student number
 */
function generate_student_number() {
    global $pdo;
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE student_number LIKE ?");
    $stmt->execute([$year . '%']);
    $result = $stmt->fetch();
    $next_number = $result['count'] + 1;
    return $year . str_pad($next_number, 3, '0', STR_PAD_LEFT);
}

/**
 * Show alert message
 */
function show_alert($message, $type = 'success') {
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = $type;
}

/**
 * Display alert message
 */
function display_alert() {
    if (isset($_SESSION['alert_message'])) {
        $message = $_SESSION['alert_message'];
        $type = $_SESSION['alert_type'];
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
    }
}

/**
 * Pagination function
 */
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records
    ];
}

/**
 * Generate pagination HTML
 */
function generate_pagination($pagination_data, $base_url) {
    $total_pages = $pagination_data['total_pages'];
    $current_page = $pagination_data['current_page'];
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a>';
        $html .= '</li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Log activity
 */
function log_activity($action, $details = '') {
    global $pdo;
    
    if (!is_logged_in()) {
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get page title
 */
function get_page_title($page) {
    $titles = [
        'dashboard' => 'Dashboard',
        'students' => 'Students',
        'courses' => 'Courses',
        'subjects' => 'Subjects',
        'grades' => 'Grades',
        'reports' => 'Reports',
        'profile' => 'Profile'
    ];
    
    return isset($titles[$page]) ? $titles[$page] : 'Student Management System';
}

/**
 * Check if current page is active
 */
function is_active_page($page) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    return $current_page === $page ? 'active' : '';
}
?>