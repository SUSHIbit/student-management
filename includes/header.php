<?php
/**
 * Header Template
 * Student Management System
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
require_login();

// Get current admin info
$current_admin = get_current_admin();
$page_title = isset($page_title) ? $page_title : 'Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --background: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
        }

        body {
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
        }

        .sidebar {
            background: var(--primary);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--secondary);
        }

        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .sidebar-menu .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .sidebar-menu .nav-link:hover {
            background: var(--secondary);
            color: white;
        }

        .sidebar-menu .nav-link.active {
            background: var(--accent);
            color: white;
        }

        .sidebar-menu .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .top-navbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: var(--accent);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--muted);
        }

        .content-area {
            padding: 2rem;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background: var(--card);
        }

        .card-header {
            background: var(--background);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        .btn-primary {
            background: var(--accent);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            border: none;
            border-radius: 8px;
        }

        .btn-warning {
            background: var(--warning);
            border: none;
            border-radius: 8px;
        }

        .btn-danger {
            background: var(--danger);
            border: none;
            border-radius: 8px;
        }

        .table {
            color: var(--text);
        }

        .table th {
            background: var(--background);
            border-bottom: 2px solid var(--border);
            font-weight: 600;
            color: var(--text);
        }

        .form-control {
            border: 2px solid var(--border);
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .alert {
            border: none;
            border-radius: 8px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--muted);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-mortarboard-fill"></i> SMS</h4>
        </div>
        <nav class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('dashboard'); ?>" href="<?php echo BASE_URL; ?>dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('index') && strpos($_SERVER['REQUEST_URI'], 'students') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>students/index.php">
                        <i class="bi bi-people-fill"></i>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('index') && strpos($_SERVER['REQUEST_URI'], 'courses') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>courses/index.php">
                        <i class="bi bi-book-fill"></i>
                        Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('index') && strpos($_SERVER['REQUEST_URI'], 'subjects') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>subjects/index.php">
                        <i class="bi bi-journal-text"></i>
                        Subjects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('index') && strpos($_SERVER['REQUEST_URI'], 'grades') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>grades/index.php">
                        <i class="bi bi-graph-up"></i>
                        Grades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_page('index') && strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>reports/index.php">
                        <i class="bi bi-file-earmark-text"></i>
                        Reports
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary d-md-none me-3" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>dashboard.php">Home</a></li>
                        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <?php if (isset($crumb['url'])): ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['name']; ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $crumb['name']; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?php echo $current_admin['full_name']; ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>

        <div class="content-area">
            <?php display_alert(); ?>