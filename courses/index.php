<?php
/**
 * Course Listing Page
 * Student Management System - Phase 3
 */

$page_title = 'Courses';
$breadcrumbs = [
    ['name' => 'Courses']
];

require_once '../includes/header.php';

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(course_code LIKE ? OR course_name LIKE ? OR description LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM courses $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// Calculate pagination
$pagination = paginate($total_records, $records_per_page, $current_page);
$offset = $pagination['offset'];

// Get courses with student count
$sql = "
    SELECT c.*, 
           COUNT(s.student_id) as student_count,
           COUNT(sub.subject_id) as subject_count
    FROM courses c 
    LEFT JOIN students s ON c.course_id = s.course_id AND s.status = 'active'
    LEFT JOIN subjects sub ON c.course_id = sub.course_id AND sub.status = 'active'
    $where_clause
    GROUP BY c.course_id
    ORDER BY c.created_at DESC 
    LIMIT $records_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Course Management</h1>
        <p class="text-muted">Manage academic courses and programs</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-book-fill"></i> Add New Course
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Course code, name, or description..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Courses Grid -->
<div class="row mb-4">
    <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $course): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card h-100 course-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <span class="badge bg-primary"><?php echo $course['course_code']; ?></span>
                        </h6>
                        <?php
                        $status_color = $course['status'] === 'active' ? 'success' : 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_color; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $course['course_name']; ?></h5>
                        <p class="card-text text-muted">
                            <?php echo $course['description'] ? substr($course['description'], 0, 100) . '...' : 'No description available'; ?>
                        </p>
                        
                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-primary"><?php echo $course['student_count']; ?></div>
                                    <div class="stat-label">Students</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?php echo $course['subject_count']; ?></div>
                                    <div class="stat-label">Subjects</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-info"><?php echo $course['duration_years']; ?></div>
                                    <div class="stat-label">Years</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Created: <?php echo format_date($course['created_at']); ?>
                            </small>
                            <div class="btn-group" role="group">
                                <a href="view.php?id=<?php echo $course['course_id']; ?>" 
                                   class="btn btn-sm btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $course['course_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-book" style="font-size: 4rem; color: var(--muted);"></i>
                <h4 class="mt-3">No Courses Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_term) || !empty($status_filter)): ?>
                        No courses match your current filters. <a href="index.php">Clear filters</a> to see all courses.
                    <?php else: ?>
                        Start by adding your first course to the system.
                    <?php endif; ?>
                </p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-book-fill"></i> Add First Course
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if (!empty($courses)): ?>
    <?php echo generate_pagination($pagination, 'index.php'); ?>
<?php endif; ?>

<!-- Summary Statistics -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Course Statistics
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Get overall statistics
        $stats_sql = "
            SELECT 
                COUNT(*) as total_courses,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_courses,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_courses,
                AVG(duration_years) as avg_duration
            FROM courses
        ";
        $stats = $pdo->query($stats_sql)->fetch();
        
        // Get student distribution
        $student_dist_sql = "
            SELECT c.course_code, c.course_name, COUNT(s.student_id) as student_count
            FROM courses c
            LEFT JOIN students s ON c.course_id = s.course_id AND s.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.course_id
            ORDER BY student_count DESC
            LIMIT 5
        ";
        $student_distribution = $pdo->query($student_dist_sql)->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-md-6">
                <h6>Overview</h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-primary text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-success text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['active_courses']; ?></div>
                            <div class="stat-label">Active Courses</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-secondary text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['inactive_courses']; ?></div>
                            <div class="stat-label">Inactive Courses</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-info text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo number_format($stats['avg_duration'], 1); ?></div>
                            <div class="stat-label">Avg Duration (Years)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Student Distribution by Course</h6>
                <?php if (!empty($student_distribution)): ?>
                    <?php foreach ($student_distribution as $dist): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <strong><?php echo $dist['course_code']; ?></strong>
                                <?php echo substr($dist['course_name'], 0, 20); ?>...
                            </span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 80px; height: 8px;">
                                    <?php 
                                    $max_students = $student_distribution[0]['student_count'];
                                    $percentage = $max_students > 0 ? ($dist['student_count'] / $max_students) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="badge bg-primary"><?php echo $dist['student_count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No active courses with students.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.course-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--border);
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-item {
    padding: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .stat-number {
    font-size: 1.8rem;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.8rem;
    opacity: 0.9;
}
</style>

<?php require_once '../includes/footer.php'; ?>