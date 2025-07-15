<?php
/**
 * Grade Analysis Report
 * Student Management System
 */

$page_title = 'Grade Analysis Report';
$breadcrumbs = [
    ['name' => 'Reports', 'url' => 'index.php'],
    ['name' => 'Grade Analysis']
];

require_once '../includes/header.php';

// Get filter parameters
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$academic_year_filter = isset($_GET['academic_year']) ? clean_input($_GET['academic_year']) : '';

// Build filter conditions
$where_conditions = [];
$params = [];

if ($course_filter > 0) {
    $where_conditions[] = "s.course_id = ?";
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

// Get grade statistics
$grade_stats = [];
$grade_distribution = [];
$subject_performance = [];

if (!empty($where_conditions)) {
    // Overall grade statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_grades,
            AVG(g.total_marks) as avg_score,
            MIN(g.total_marks) as min_score,
            MAX(g.total_marks) as max_score,
            COUNT(DISTINCT s.student_id) as total_students,
            COUNT(DISTINCT sub.subject_id) as total_subjects
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        $where_clause
    ";
    
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute($params);
    $grade_stats = $stmt->fetch();

    // Grade distribution
    $dist_sql = "
        SELECT 
            g.grade_letter,
            COUNT(*) as count,
            AVG(g.total_marks) as avg_marks
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        $where_clause
        GROUP BY g.grade_letter
        ORDER BY 
            CASE g.grade_letter 
                WHEN 'A+' THEN 1 WHEN 'A' THEN 2 WHEN 'A-' THEN 3
                WHEN 'B+' THEN 4 WHEN 'B' THEN 5 WHEN 'B-' THEN 6
                WHEN 'C+' THEN 7 WHEN 'C' THEN 8 WHEN 'C-' THEN 9
                WHEN 'D' THEN 10 WHEN 'F' THEN 11 
            END
    ";
    
    $stmt = $pdo->prepare($dist_sql);
    $stmt->execute($params);
    $grade_distribution = $stmt->fetchAll();

    // Subject performance
    $perf_sql = "
        SELECT 
            sub.subject_code,
            sub.subject_name,
            COUNT(g.grade_id) as total_students,
            AVG(g.total_marks) as avg_score,
            MIN(g.total_marks) as min_score,
            MAX(g.total_marks) as max_score,
            COUNT(CASE WHEN g.grade_letter IN ('A+', 'A', 'A-') THEN 1 END) as excellent_count,
            COUNT(CASE WHEN g.grade_letter IN ('D', 'F') THEN 1 END) as poor_count
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        $where_clause
        GROUP BY sub.subject_id
        ORDER BY avg_score DESC
    ";
    
    $stmt = $pdo->prepare($perf_sql);
    $stmt->execute($params);
    $subject_performance = $stmt->fetchAll();
}

// Get filter options
$courses = get_all_courses();

$subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_code";
$subjects = $pdo->query($subjects_sql)->fetchAll();

$years_sql = "SELECT DISTINCT academic_year FROM grades WHERE academic_year IS NOT NULL ORDER BY academic_year DESC";
$academic_years = $pdo->query($years_sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Grade Analysis Report</h1>
        <p class="text-muted">Comprehensive analysis of student academic performance</p>
    </div>
    <div class="btn-group" role="group">
        <?php if (!empty($grade_stats)): ?>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="grade-export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-funnel"></i>
            Report Filters
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="course_id" class="form-label">Course</label>
                <select class="form-select" id="course_id" name="course_id">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="subject_id" class="form-label">Subject</label>
                <select class="form-select" id="subject_id" name="subject_id">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" 
                                <?php echo ($subject_filter == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
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
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Generate Report
                </button>
            </div>
        </form>
        
        <div class="mt-2">
            <a href="grade-report.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Reset Filters
            </a>
        </div>
    </div>
</div>

<?php if (!empty($grade_stats)): ?>
<!-- Grade Statistics Summary -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Grade Statistics Overview</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 text-center">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['total_grades']); ?></h3>
                    <p class="mb-0">Total Grades</p>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['avg_score'], 1); ?>%</h3>
                    <p class="mb-0">Average Score</p>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['max_score'], 1); ?>%</h3>
                    <p class="mb-0">Highest Score</p>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <div class="stat-card bg-danger text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['min_score'], 1); ?>%</h3>
                    <p class="mb-0">Lowest Score</p>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <div class="stat-card bg-secondary text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['total_students']); ?></h3>
                    <p class="mb-0">Students</p>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <div class="stat-card bg-dark text-white p-3 rounded">
                    <h3><?php echo number_format($grade_stats['total_subjects']); ?></h3>
                    <p class="mb-0">Subjects</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grade Distribution -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-pie-chart"></i>
            Grade Distribution Analysis
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h6>Grade Distribution</h6>
                <div class="row">
                    <?php
                    $grade_colors = [
                        'A+' => 'success', 'A' => 'success', 'A-' => 'success',
                        'B+' => 'primary', 'B' => 'primary', 'B-' => 'primary',
                        'C+' => 'warning', 'C' => 'warning', 'C-' => 'warning',
                        'D' => 'danger', 'F' => 'danger'
                    ];
                    
                    $total_grades = array_sum(array_column($grade_distribution, 'count'));
                    foreach ($grade_distribution as $grade):
                        $percentage = $total_grades > 0 ? ($grade['count'] / $total_grades) * 100 : 0;
                        $color = $grade_colors[$grade['grade_letter']] ?? 'secondary';
                    ?>
                        <div class="col-lg-2 col-md-3 col-4 mb-3">
                            <div class="text-center">
                                <div class="grade-circle bg-<?php echo $color; ?> text-white mb-2">
                                    <?php echo $grade['grade_letter']; ?>
                                </div>
                                <div class="fw-bold"><?php echo $grade['count']; ?></div>
                                <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                <br><small class="text-muted">Avg: <?php echo number_format($grade['avg_marks'], 1); ?>%</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h6>Performance Categories</h6>
                <?php
                $excellent = 0; $good = 0; $satisfactory = 0; $poor = 0;
                foreach ($grade_distribution as $grade) {
                    if (in_array($grade['grade_letter'], ['A+', 'A', 'A-'])) $excellent += $grade['count'];
                    elseif (in_array($grade['grade_letter'], ['B+', 'B', 'B-'])) $good += $grade['count'];
                    elseif (in_array($grade['grade_letter'], ['C+', 'C', 'C-'])) $satisfactory += $grade['count'];
                    elseif (in_array($grade['grade_letter'], ['D', 'F'])) $poor += $grade['count'];
                }
                ?>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-success"></i> Excellent (A)</span>
                        <strong><?php echo $excellent; ?> (<?php echo number_format($total_grades > 0 ? ($excellent / $total_grades) * 100 : 0, 1); ?>%)</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-primary"></i> Good (B)</span>
                        <strong><?php echo $good; ?> (<?php echo number_format($total_grades > 0 ? ($good / $total_grades) * 100 : 0, 1); ?>%)</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-warning"></i> Satisfactory (C)</span>
                        <strong><?php echo $satisfactory; ?> (<?php echo number_format($total_grades > 0 ? ($satisfactory / $total_grades) * 100 : 0, 1); ?>%)</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-danger"></i> Poor (D/F)</span>
                        <strong><?php echo $poor; ?> (<?php echo number_format($total_grades > 0 ? ($poor / $total_grades) * 100 : 0, 1); ?>%)</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subject Performance -->
<?php if (!empty($subject_performance)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Subject Performance Analysis
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Students</th>
                        <th>Average Score</th>
                        <th>Min Score</th>
                        <th>Max Score</th>
                        <th>Excellent (A)</th>
                        <th>Poor (D/F)</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subject_performance as $subject): ?>
                        <tr>
                            <td>
                                <strong><?php echo $subject['subject_code']; ?></strong>
                                <br><small class="text-muted"><?php echo $subject['subject_name']; ?></small>
                            </td>
                            <td><?php echo $subject['total_students']; ?></td>
                            <td><strong><?php echo number_format($subject['avg_score'], 1); ?>%</strong></td>
                            <td><?php echo number_format($subject['min_score'], 1); ?>%</td>
                            <td><?php echo number_format($subject['max_score'], 1); ?>%</td>
                            <td>
                                <span class="badge bg-success">
                                    <?php echo $subject['excellent_count']; ?> 
                                    (<?php echo number_format($subject['total_students'] > 0 ? ($subject['excellent_count'] / $subject['total_students']) * 100 : 0, 1); ?>%)
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo $subject['poor_count']; ?> 
                                    (<?php echo number_format($subject['total_students'] > 0 ? ($subject['poor_count'] / $subject['total_students']) * 100 : 0, 1); ?>%)
                                </span>
                            </td>
                            <td>
                                <?php
                                $performance_class = 'secondary';
                                $performance_text = 'Average';
                                if ($subject['avg_score'] >= 85) {
                                    $performance_class = 'success';
                                    $performance_text = 'Excellent';
                                } elseif ($subject['avg_score'] >= 75) {
                                    $performance_class = 'primary';
                                    $performance_text = 'Good';
                                } elseif ($subject['avg_score'] >= 60) {
                                    $performance_class = 'warning';
                                    $performance_text = 'Fair';
                                } elseif ($subject['avg_score'] < 50) {
                                    $performance_class = 'danger';
                                    $performance_text = 'Poor';
                                }
                                ?>
                                <span class="badge bg-<?php echo $performance_class; ?>"><?php echo $performance_text; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- No Data Message -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-graph-up" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">No Grade Data Found</h4>
        <p class="text-muted">
            Please select filters above to generate a grade analysis report.
        </p>
    </div>
</div>
<?php endif; ?>

<style>
.stat-card {
    text-align: center;
    border-radius: 8px;
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
    .btn, .card:first-child {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>