<?php
/**
 * Subject Listing Page
 * Student Management System - Phase 3
 */

$page_title = 'Subjects';
$breadcrumbs = [
    ['name' => 'Subjects']
];

require_once '../includes/header.php';

// Pagination settings
$records_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(s.subject_code LIKE ? OR s.subject_name LIKE ? OR s.description LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($course_filter > 0) {
    $where_conditions[] = "s.course_id = ?";
    $params[] = $course_filter;
}

if ($semester_filter > 0) {
    $where_conditions[] = "s.semester = ?";
    $params[] = $semester_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM subjects s $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// Calculate pagination
$pagination = paginate($total_records, $records_per_page, $current_page);
$offset = $pagination['offset'];

// Get subjects with course information
$sql = "
    SELECT s.*, c.course_name, c.course_code,
           COUNT(g.grade_id) as grade_count
    FROM subjects s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    LEFT JOIN grades g ON s.subject_id = g.subject_id
    $where_clause
    GROUP BY s.subject_id
    ORDER BY c.course_code, s.semester, s.subject_code 
    LIMIT $records_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Get courses for filter dropdown
$courses = get_all_courses();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Subject Management</h1>
        <p class="text-muted">Manage course subjects and curriculum</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-journal-plus"></i> Add New Subject
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Subject code, name, or description..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="col-md-2">
                <label for="course" class="form-label">Course</label>
                <select class="form-select" id="course" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                            <?php echo $course['course_code']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
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

<!-- Subjects Grid -->
<div class="row mb-4">
    <?php if (!empty($subjects)): ?>
        <?php foreach ($subjects as $subject): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 subject-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary"><?php echo $subject['subject_code']; ?></span>
                            <span class="badge bg-info">Semester <?php echo $subject['semester']; ?></span>
                        </div>
                        <?php
                        $status_color = $subject['status'] === 'active' ? 'success' : 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_color; ?>">
                            <?php echo ucfirst($subject['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo $subject['subject_name']; ?></h6>
                        <p class="card-text text-muted small">
                            <?php echo $subject['description'] ? substr($subject['description'], 0, 80) . '...' : 'No description available'; ?>
                        </p>
                        
                        <div class="subject-details mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-primary"><?php echo $subject['credits']; ?></div>
                                        <div class="stat-label">Credits</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-success"><?php echo $subject['grade_count']; ?></div>
                                        <div class="stat-label">Grades</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-info"><?php echo $subject['semester']; ?></div>
                                        <div class="stat-label">Semester</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="course-info">
                            <small class="text-muted">Course:</small>
                            <br>
                            <strong><?php echo $subject['course_code'] ?? 'N/A'; ?></strong>
                            <?php if ($subject['course_name']): ?>
                                <br><small class="text-muted"><?php echo substr($subject['course_name'], 0, 30) . '...'; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Added: <?php echo format_date($subject['created_at']); ?>
                            </small>
                            <div class="btn-group" role="group">
                                <a href="view.php?id=<?php echo $subject['subject_id']; ?>" 
                                   class="btn btn-sm btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $subject['subject_id']; ?>" 
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $subject['subject_id']; ?>" 
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
                <i class="bi bi-journal-text" style="font-size: 4rem; color: var(--muted);"></i>
                <h4 class="mt-3">No Subjects Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_term) || $course_filter || $semester_filter || !empty($status_filter)): ?>
                        No subjects match your current filters. <a href="index.php">Clear filters</a> to see all subjects.
                    <?php else: ?>
                        Start by adding your first subject to the system.
                    <?php endif; ?>
                </p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-journal-plus"></i> Add First Subject
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if (!empty($subjects)): ?>
    <?php echo generate_pagination($pagination, 'index.php'); ?>
<?php endif; ?>

<!-- Summary Statistics -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Subject Statistics
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Get overall statistics
        $stats_sql = "
            SELECT 
                COUNT(*) as total_subjects,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subjects,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_subjects,
                SUM(credits) as total_credits,
                AVG(credits) as avg_credits
            FROM subjects
        ";
        $stats = $pdo->query($stats_sql)->fetch();
        
        // Get subject distribution by course
        $course_dist_sql = "
            SELECT c.course_code, c.course_name, 
                   COUNT(s.subject_id) as subject_count,
                   SUM(s.credits) as total_credits
            FROM courses c
            LEFT JOIN subjects s ON c.course_id = s.course_id AND s.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.course_id
            ORDER BY subject_count DESC
            LIMIT 5
        ";
        $course_distribution = $pdo->query($course_dist_sql)->fetchAll();

        // Get semester distribution
        $semester_dist_sql = "
            SELECT semester, COUNT(*) as subject_count, SUM(credits) as total_credits
            FROM subjects 
            WHERE status = 'active'
            GROUP BY semester 
            ORDER BY semester
        ";
        $semester_distribution = $pdo->query($semester_dist_sql)->fetchAll();
        ?>
        
        <div class="row">
            <!-- Overview Stats -->
            <div class="col-md-4">
                <h6>Overview</h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-primary text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['total_subjects']; ?></div>
                            <div class="stat-label">Total Subjects</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-success text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['active_subjects']; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-warning text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo $stats['total_credits']; ?></div>
                            <div class="stat-label">Total Credits</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card bg-info text-white text-center p-3 rounded">
                            <div class="stat-number"><?php echo number_format($stats['avg_credits'], 1); ?></div>
                            <div class="stat-label">Avg Credits</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Distribution -->
            <div class="col-md-4">
                <h6>Subjects by Course</h6>
                <?php if (!empty($course_distribution)): ?>
                    <?php foreach ($course_distribution as $dist): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <strong><?php echo $dist['course_code']; ?></strong>
                                <br><small class="text-muted"><?php echo $dist['total_credits']; ?> credits</small>
                            </span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                    <?php 
                                    $max_subjects = $course_distribution[0]['subject_count'];
                                    $percentage = $max_subjects > 0 ? ($dist['subject_count'] / $max_subjects) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="badge bg-primary"><?php echo $dist['subject_count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No active subjects found.</p>
                <?php endif; ?>
            </div>

            <!-- Semester Distribution -->
            <div class="col-md-4">
                <h6>Subjects by Semester</h6>
                <?php if (!empty($semester_distribution)): ?>
                    <?php foreach ($semester_distribution as $dist): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <strong>Semester <?php echo $dist['semester']; ?></strong>
                                <br><small class="text-muted"><?php echo $dist['total_credits']; ?> credits</small>
                            </span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                    <?php 
                                    $max_semester = max(array_column($semester_distribution, 'subject_count'));
                                    $percentage = $max_semester > 0 ? ($dist['subject_count'] / $max_semester) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="badge bg-success"><?php echo $dist['subject_count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No semester data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.subject-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--border);
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-item {
    padding: 0.5rem;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.7rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .stat-number {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.75rem;
    opacity: 0.9;
}

.subject-details {
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 0.75rem 0;
    margin: 0.75rem 0;
}

.course-info {
    background: var(--background);
    padding: 0.5rem;
    border-radius: 6px;
    margin-top: 0.5rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>