<?php
/**
 * Sidebar Navigation
 * Student Management System
 */

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

function is_nav_active($page, $folder = '') {
    global $current_page, $current_folder;
    
    if ($folder) {
        return $current_folder === $folder ? 'active' : '';
    }
    
    return $current_page === $page ? 'active' : '';
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-mortarboard-fill"></i> SMS</h4>
        <p class="small text-muted mb-0">Student Management System</p>
    </div>
    
    <nav class="sidebar-menu">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('dashboard'); ?>" href="<?php echo BASE_URL; ?>dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Students Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'students'); ?>" href="<?php echo BASE_URL; ?>students/index.php">
                    <i class="bi bi-people-fill"></i>
                    Students
                </a>
            </li>
            
            <?php if (is_nav_active('', 'students')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('index', 'students'); ?>" href="<?php echo BASE_URL; ?>students/index.php">
                                <i class="bi bi-list"></i> All Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('add', 'students'); ?>" href="<?php echo BASE_URL; ?>students/add.php">
                                <i class="bi bi-person-plus"></i> Add Student
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Courses Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'courses'); ?>" href="<?php echo BASE_URL; ?>courses/index.php">
                    <i class="bi bi-book-fill"></i>
                    Courses
                </a>
            </li>
            
            <?php if (is_nav_active('', 'courses')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('index', 'courses'); ?>" href="<?php echo BASE_URL; ?>courses/index.php">
                                <i class="bi bi-list"></i> All Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('add', 'courses'); ?>" href="<?php echo BASE_URL; ?>courses/add.php">
                                <i class="bi bi-plus-circle"></i> Add Course
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Subjects Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'subjects'); ?>" href="<?php echo BASE_URL; ?>subjects/index.php">
                    <i class="bi bi-journal-text"></i>
                    Subjects
                </a>
            </li>
            
            <?php if (is_nav_active('', 'subjects')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('index', 'subjects'); ?>" href="<?php echo BASE_URL; ?>subjects/index.php">
                                <i class="bi bi-list"></i> All Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('add', 'subjects'); ?>" href="<?php echo BASE_URL; ?>subjects/add.php">
                                <i class="bi bi-plus-circle"></i> Add Subject
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Grades Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'grades'); ?>" href="<?php echo BASE_URL; ?>grades/index.php">
                    <i class="bi bi-graph-up"></i>
                    Grades
                </a>
            </li>
            
            <?php if (is_nav_active('', 'grades')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('index', 'grades'); ?>" href="<?php echo BASE_URL; ?>grades/index.php">
                                <i class="bi bi-list"></i> All Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('add', 'grades'); ?>" href="<?php echo BASE_URL; ?>grades/add.php">
                                <i class="bi bi-plus-circle"></i> Add Grade
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Attendance Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'attendance'); ?>" href="<?php echo BASE_URL; ?>attendance/index.php">
                    <i class="bi bi-calendar-check"></i>
                    Attendance
                </a>
            </li>
            
            <?php if (is_nav_active('', 'attendance')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('index', 'attendance'); ?>" href="<?php echo BASE_URL; ?>attendance/index.php">
                                <i class="bi bi-list"></i> View Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small <?php echo is_nav_active('mark', 'attendance'); ?>" href="<?php echo BASE_URL; ?>attendance/mark.php">
                                <i class="bi bi-check-square"></i> Mark Attendance
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Reports Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_nav_active('', 'reports'); ?>" href="<?php echo BASE_URL; ?>reports/index.php">
                    <i class="bi bi-file-earmark-text"></i>
                    Reports
                </a>
            </li>
            
            <?php if (is_nav_active('', 'reports')): ?>
                <li class="nav-item submenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link small" href="<?php echo BASE_URL; ?>reports/student-report.php">
                                <i class="bi bi-person-lines-fill"></i> Student Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small" href="<?php echo BASE_URL; ?>reports/class-report.php">
                                <i class="bi bi-bar-chart"></i> Class Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small" href="<?php echo BASE_URL; ?>reports/grade-report.php">
                                <i class="bi bi-graph-up"></i> Grade Analysis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link small" href="<?php echo BASE_URL; ?>reports/attendance-report.php">
                                <i class="bi bi-calendar-check"></i> Attendance Report
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Divider -->
            <li class="nav-item">
                <hr class="sidebar-divider my-3">
            </li>

            <!-- System Section -->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>profile.php">
                    <i class="bi bi-person-circle"></i>
                    Profile
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>settings.php">
                    <i class="bi bi-gear"></i>
                    Settings
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
.sidebar-divider {
    border-color: var(--secondary);
    opacity: 0.3;
}

.submenu .nav-link {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    color: #a1a8b3;
}

.submenu .nav-link:hover {
    background: var(--secondary);
    color: white;
}

.submenu .nav-link.active {
    background: var(--accent);
    color: white;
}
</style>