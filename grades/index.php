<?php
/**
 * Grade Listing Page
 * Student Management System - Phase 4
 */

$page_title = 'Grades';
$breadcrumbs = [
    ['name' => 'Grades']
];

require_once '../includes/header.php';

// Pagination settings
$records_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$subject_filter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$academic_year_filter = isset($_GET['academic_year']) ? clean_input($_GET['academic_year']) : '';

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(st.first_name LIKE ? OR st.last_name LIKE ? OR st.student_number LIKE ? OR sub.subject_code LIKE ? OR sub.subject_name LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($course_filter > 0) {
    $where_conditions[] = "st.course_id = ?";
    $params[] = $course_filter;
}

if ($subject_filter > 0) {
    $where_conditions[] = "g.subject_id = ?";
    $params[] = $subject_filter;
}

if ($semester_filter > 0) {
    $where_conditions[] = "g.semester = ?";
    $params[] = $semester_filter;
}

if (!empty($academic_year_filter)) {
    $where_conditions[] = "g.academic_year = ?";
    $params[] = $academic_year_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM grades g 
    JOIN students st ON g.student_id = st.student_id 
    JOIN subjects sub ON g.subject_id = sub.subject_id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// Calculate pagination
$pagination = paginate($total_records, $records_per_page, $current_page);
$offset = $pagination['offset'];

// Get grades with student and subject information
$sql = "
    SELECT g.*, 
           st.student_number, st.first_name, st.last_name, st.year_level,
           sub.subject_code, sub.subject_name, sub.credits,
           c.course_code, c.course_name
    FROM grades g 
    JOIN students st ON g.student_id = st.student_id 
    JOIN subjects sub ON g.subject_id = sub.subject_id 
    LEFT JOIN courses c ON st.course_id = c.course_id
    $where_clause
    ORDER BY g.created_at DESC, st.last_name, st.first_name
    LIMIT $records_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$grades = $stmt->fetchAll();

// Get filter options
$courses = get_all_courses();

// Get subjects for filter
$subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_code";
$subjects = $pdo->query($subjects_sql)->fetchAll();

// Get academic years
$years_sql = "SELECT DISTINCT academic_year FROM grades WHERE academic_year IS NOT NULL ORDER BY academic_year DESC";
$academic_years = $pdo->query($years_sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Grade Management</h1>
        <p class="text-muted">Manage student grades and academic performance</p>
    </div>
    <div class="btn-group" role="group">
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Grade
        </a>
        <a href="bulk-entry.php" class="btn btn-success">
            <i class="bi bi-table"></i> Bulk Entry
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Student name, number, subject..." 
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
                <label for="subject" class="form-label">Subject</label>
                <select class="form-select" id="subject" name="subject">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" 
                                <?php echo ($subject_filter == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo $subject['subject_code']; ?>
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
                <label for="academic_year" class="form-label">Academic Year</label>
                <select class="form-select" id="academic_year" name="academic_year">
                    <option value="">All Years</option>
                    <?php foreach ($academic_years as $year): ?>
                        <option value="<?php echo $year['academic_year']; ?>" 
                                <?php echo ($academic_year_filter == $year['academic_year']) ? 'selected' : ''; ?>>
                            <?php echo $year['academic_year']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
        <div class="row mt-2">
            <div class="col-12">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Grades Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Grade Records (<?php echo number_format($total_records); ?> total)
        </h5>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="../reports/grades-export.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($grades)): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="gradesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Assignment</th>
                            <th>Quiz</th>
                            <th>Midterm</th>
                            <th>Final</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <?php echo strtoupper(substr($grade['first_name'], 0, 1) . substr($grade['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?></div>
                                            <small class="text-muted"><?php echo $grade['student_number']; ?></small>
                                            <br><small class="text-info"><?php echo $grade['course_code']; ?> - Year <?php echo $grade['year_level']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $grade['subject_code']; ?></strong>
                                        <br><small class="text-muted"><?php echo $grade['subject_name']; ?></small>
                                        <br><span class="badge bg-info"><?php echo $grade['credits']; ?> Credits</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="grade-score"><?php echo number_format($grade['assignment_marks'], 1); ?></span>
                                </td>
                                <td>
                                    <span class="grade-score"><?php echo number_format($grade['quiz_marks'], 1); ?></span>
                                </td>
                                <td>
                                    <span class="grade-score"><?php echo number_format($grade['midterm_marks'], 1); ?></span>
                                </td>
                                <td>
                                    <span class="grade-score"><?php echo number_format($grade['final_marks'], 1); ?></span>
                                </td>
                                <td>
                                    <strong class="grade-score total-score"><?php echo number_format($grade['total_marks'], 1); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $grade_colors = [
                                        'A+' => 'success', 'A' => 'success', 'A-' => 'success',
                                        'B+' => 'primary', 'B' => 'primary', 'B-' => 'primary',
                                        'C+' => 'warning', 'C' => 'warning', 'C-' => 'warning',
                                        'D' => 'danger', 'F' => 'danger'
                                    ];
                                    $grade_color = $grade_colors[$grade['grade_letter']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $grade_color; ?> grade-badge">
                                        <?php echo $grade['grade_letter']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-secondary">Sem <?php echo $grade['semester']; ?></span>
                                        <?php if ($grade['academic_year']): ?>
                                            <br><small class="text-muted"><?php echo $grade['academic_year']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $grade['grade_id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $grade['grade_id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $grade['grade_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php echo generate_pagination($pagination, 'index.php'); ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-graph-up" style="font-size: 4rem; color: var(--muted);"></i>
                <h4 class="mt-3">No Grades Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_term) || $course_filter || $subject_filter || $semester_filter || !empty($academic_year_filter)): ?>
                        No grades match your current filters. <a href="index.php">Clear filters</a> to see all grades.
                    <?php else: ?>
                        Start by adding grades for students.
                    <?php endif; ?>
                </p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add First Grade
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grade Statistics -->
<?php if (!empty($grades)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-bar-chart"></i>
            Grade Distribution
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Calculate grade distribution
        $grade_dist_sql = "
            SELECT grade_letter, COUNT(*) as count 
            FROM grades 
            WHERE grade_letter IS NOT NULL 
            GROUP BY grade_letter 
            ORDER BY 
                CASE grade_letter 
                    WHEN 'A+' THEN 1 WHEN 'A' THEN 2 WHEN 'A-' THEN 3
                    WHEN 'B+' THEN 4 WHEN 'B' THEN 5 WHEN 'B-' THEN 6
                    WHEN 'C+' THEN 7 WHEN 'C' THEN 8 WHEN 'C-' THEN 9
                    WHEN 'D' THEN 10 WHEN 'F' THEN 11 
                END
        ";
        $grade_distribution = $pdo->query($grade_dist_sql)->fetchAll();
        
        $total_grades = array_sum(array_column($grade_distribution, 'count'));
        ?>
        
        <div class="row">
            <div class="col-md-8">
                <h6>Grade Distribution</h6>
                <div class="row">
                    <?php foreach ($grade_distribution as $dist): ?>
                        <?php
                        $percentage = $total_grades > 0 ? ($dist['count'] / $total_grades) * 100 : 0;
                        $grade_color = $grade_colors[$dist['grade_letter']] ?? 'secondary';
                        ?>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <div class="grade-circle bg-<?php echo $grade_color; ?> text-white mb-2">
                                    <?php echo $dist['grade_letter']; ?>
                                </div>
                                <div class="fw-bold"><?php echo $dist['count']; ?></div>
                                <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h6>Summary Statistics</h6>
                <?php
                $stats_sql = "
                    SELECT 
                        COUNT(*) as total_grades,
                        AVG(total_marks) as avg_total,
                        MAX(total_marks) as max_total,
                        MIN(total_marks) as min_total
                    FROM grades
                ";
                $stats = $pdo->query($stats_sql)->fetch();
                ?>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total Grades:</span>
                        <strong><?php echo $stats['total_grades']; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Average Score:</span>
                        <strong><?php echo number_format($stats['avg_total'], 1); ?>%</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Highest Score:</span>
                        <strong class="text-success"><?php echo number_format($stats['max_total'], 1); ?>%</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Lowest Score:</span>
                        <strong class="text-danger"><?php echo number_format($stats['min_total'], 1); ?>%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.7rem;
}

.grade-score {
    font-weight: 600;
    color: var(--text);
}

.total-score {
    font-size: 1.1rem;
    color: var(--primary);
}

.grade-badge {
    font-size: 0.9rem;
    font-weight: 700;
    padding: 0.5rem 0.75rem;
}

.grade-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    margin: 0 auto;
}

@media print {
    .btn, .card-header .btn-group, .pagination {
        display: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>