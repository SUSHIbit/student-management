<?php
/**
 * Class Performance Report
 * Student Management System - Phase 5
 */

$page_title = 'Class Performance Report';
$breadcrumbs = [
    ['name' => 'Reports', 'url' => 'index.php'],
    ['name' => 'Class Performance']
];

require_once '../includes/header.php';

// Get filter parameters
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$year_filter = isset($_GET['year_level']) ? (int)$_GET['year_level'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$academic_year_filter = isset($_GET['academic_year']) ? clean_input($_GET['academic_year']) : '';

// Build filter conditions
$where_conditions = ["s.status = 'active'"];
$params = [];

if ($course_filter > 0) {
    $where_conditions[] = "s.course_id = ?";
    $params[] = $course_filter;
}

if ($year_filter > 0) {
    $where_conditions[] = "s.year_level = ?";
    $params[] = $year_filter;
}

$grade_where_conditions = [];
$grade_params = [];

if ($subject_filter > 0) {
    $grade_where_conditions[] = "g.subject_id = ?";
    $grade_params[] = $subject_filter;
}

if ($semester_filter > 0) {
    $grade_where_conditions[] = "g.semester = ?";
    $grade_params[] = $semester_filter;
}

if (!empty($academic_year_filter)) {
    $grade_where_conditions[] = "g.academic_year = ?";
    $grade_params[] = $academic_year_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
$grade_where_clause = !empty($grade_where_conditions) ? 'AND ' . implode(' AND ', $grade_where_conditions) : '';

// Get class statistics
$class_stats = null;
$performance_data = [];
$grade_distribution = [];

if ($course_filter > 0 || $year_filter > 0 || $subject_filter > 0) {
    // Get basic class information
    $class_info_sql = "
        SELECT 
            COUNT(DISTINCT s.student_id) as total_students,
            c.course_name,
            c.course_code
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.course_id
        $where_clause
    ";
    
    $stmt = $pdo->prepare($class_info_sql);
    $stmt->execute($params);
    $class_stats = $stmt->fetch();

    // Get performance statistics
    $performance_sql = "
        SELECT 
            s.student_id,
            s.student_number,
            s.first_name,
            s.last_name,
            s.year_level,
            c.course_code,
            AVG(g.total_marks) as avg_marks,
            COUNT(g.grade_id) as total_subjects,
            SUM(CASE WHEN g.grade_letter IN ('A+', 'A', 'A-') THEN 1 ELSE 0 END) as excellent_grades,
            SUM(CASE WHEN g.grade_letter IN ('B+', 'B', 'B-') THEN 1 ELSE 0 END) as good_grades,
            SUM(CASE WHEN g.grade_letter IN ('C+', 'C', 'C-') THEN 1 ELSE 0 END) as satisfactory_grades,
            SUM(CASE WHEN g.grade_letter IN ('D', 'F') THEN 1 ELSE 0 END) as poor_grades
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.course_id
        LEFT JOIN grades g ON s.student_id = g.student_id $grade_where_clause
        $where_clause
        GROUP BY s.student_id
        HAVING total_subjects > 0
        ORDER BY avg_marks DESC
    ";
    
    $stmt = $pdo->prepare($performance_sql);
    $stmt->execute(array_merge($params, $grade_params));
    $performance_data = $stmt->fetchAll();

    // Get grade distribution
    $grade_dist_sql = "
        SELECT 
            g.grade_letter,
            COUNT(*) as count,
            AVG(g.total_marks) as avg_marks
        FROM students s
        LEFT JOIN grades g ON s.student_id = g.student_id
        $where_clause $grade_where_clause
        GROUP BY g.grade_letter
        ORDER BY 
            CASE g.grade_letter 
                WHEN 'A+' THEN 1 WHEN 'A' THEN 2 WHEN 'A-' THEN 3
                WHEN 'B+' THEN 4 WHEN 'B' THEN 5 WHEN 'B-' THEN 6
                WHEN 'C+' THEN 7 WHEN 'C' THEN 8 WHEN 'C-' THEN 9
                WHEN 'D' THEN 10 WHEN 'F' THEN 11 
            END
    ";
    
    $stmt = $pdo->prepare($grade_dist_sql);
    $stmt->execute(array_merge($params, $grade_params));
    $grade_distribution = $stmt->fetchAll();
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
        <h1 class="h3 mb-0">Class Performance Report</h1>
        <p class="text-muted">Analyze academic performance by class, course, or subject</p>
    </div>
    <div class="btn-group" role="group">
        <?php if (!empty($performance_data)): ?>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="class-export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
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
            
            <div class="col-md-2">
                <label for="year_level" class="form-label">Year Level</label>
                <select class="form-select" id="year_level" name="year_level">
                    <option value="">All Years</option>
                    <option value="1" <?php echo ($year_filter == 1) ? 'selected' : ''; ?>>Year 1</option>
                    <option value="2" <?php echo ($year_filter == 2) ? 'selected' : ''; ?>>Year 2</option>
                    <option value="3" <?php echo ($year_filter == 3) ? 'selected' : ''; ?>>Year 3</option>
                    <option value="4" <?php echo ($year_filter == 4) ? 'selected' : ''; ?>>Year 4</option>
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
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Generate Report
                </button>
                <a href="class-report.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($performance_data)): ?>
<!-- Class Summary -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Class Performance Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Class Information</h6>
                <ul class="list-unstyled">
                    <li><strong>Course:</strong> <?php echo $class_stats['course_name'] ?? 'Multiple Courses'; ?></li>
                    <li><strong>Course Code:</strong> <?php echo $class_stats['course_code'] ?? 'Various'; ?></li>
                    <li><strong>Year Level:</strong> <?php echo $year_filter > 0 ? "Year $year_filter" : 'All Years'; ?></li>
                    <li><strong>Total Students:</strong> <?php echo count($performance_data); ?></li>
                    <li><strong>Report Generated:</strong> <?php echo date('F j, Y'); ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <?php
                $total_grades = array_sum(array_column($grade_distribution, 'count'));
                $class_avg = !empty($performance_data) ? array_sum(array_column($performance_data, 'avg_marks')) / count($performance_data) : 0;
                $top_performer = !empty($performance_data) ? $performance_data[0] : null;
                ?>
                <h6>Performance Metrics</h6>
                <ul class="list-unstyled">
                    <li><strong>Class Average:</strong> <?php echo number_format($class_avg, 1); ?>%</li>
                    <li><strong>Total Grades:</strong> <?php echo $total_grades; ?></li>
                    <?php if ($top_performer): ?>
                        <li><strong>Top Performer:</strong> <?php echo $top_performer['first_name'] . ' ' . $top_performer['last_name']; ?> (<?php echo number_format($top_performer['avg_marks'], 1); ?>%)</li>
                    <?php endif; ?>
                    <li><strong>Students with Data:</strong> <?php echo count($performance_data); ?></li>
                </ul>
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
                    
                    foreach ($grade_distribution as $grade):
                        $percentage = $total_grades > 0 ? ($grade['count'] / $total_grades) * 100 : 0;
                        $color = $grade_colors[$grade['grade_letter']] ?? 'secondary';
                    ?>
                        <div class="col-lg-3 col-md-4 col-6 mb-3">
                            <div class="text-center">
                                <div class="grade-circle bg-<?php echo $color; ?> text-white mb-2">
                                    <?php echo $grade['grade_letter']; ?>
                                </div>
                                <div class="fw-bold"><?php echo $grade['count']; ?> students</div>
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
                <div class="progress-stacked mb-3">
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $total_grades > 0 ? ($excellent / $total_grades) * 100 : 0; ?>%">
                            Excellent: <?php echo $excellent; ?>
                        </div>
                        <div class="progress-bar bg-primary" style="width: <?php echo $total_grades > 0 ? ($good / $total_grades) * 100 : 0; ?>%">
                            Good: <?php echo $good; ?>
                        </div>
                        <div class="progress-bar bg-warning" style="width: <?php echo $total_grades > 0 ? ($satisfactory / $total_grades) * 100 : 0; ?>%">
                            Satisfactory: <?php echo $satisfactory; ?>
                        </div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $total_grades > 0 ? ($poor / $total_grades) * 100 : 0; ?>%">
                            Poor: <?php echo $poor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-success"></i> Excellent (A)</span>
                        <strong><?php echo number_format($total_grades > 0 ? ($excellent / $total_grades) * 100 : 0, 1); ?>%</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-primary"></i> Good (B)</span>
                        <strong><?php echo number_format($total_grades > 0 ? ($good / $total_grades) * 100 : 0, 1); ?>%</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-warning"></i> Satisfactory (C)</span>
                        <strong><?php echo number_format($total_grades > 0 ? ($satisfactory / $total_grades) * 100 : 0, 1); ?>%</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-circle-fill text-danger"></i> Poor (D/F)</span>
                        <strong><?php echo number_format($total_grades > 0 ? ($poor / $total_grades) * 100 : 0, 1); ?>%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Student Performance -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i>
            Individual Student Performance
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Average Score</th>
                        <th>Subjects</th>
                        <th>Performance Distribution</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performance_data as $index => $student): ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?>">
                                    #<?php echo $index + 1; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                        <small class="text-muted"><?php echo $student['student_number']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $student['course_code']; ?></td>
                            <td>Year <?php echo $student['year_level']; ?></td>
                            <td>
                                <strong class="<?php echo $student['avg_marks'] >= 80 ? 'text-success' : ($student['avg_marks'] >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo number_format($student['avg_marks'], 1); ?>%
                                </strong>
                            </td>
                            <td><?php echo $student['total_subjects']; ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($student['excellent_grades'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $student['excellent_grades']; ?>A</span>
                                    <?php endif; ?>
                                    <?php if ($student['good_grades'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $student['good_grades']; ?>B</span>
                                    <?php endif; ?>
                                    <?php if ($student['satisfactory_grades'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $student['satisfactory_grades']; ?>C</span>
                                    <?php endif; ?>
                                    <?php if ($student['poor_grades'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $student['poor_grades']; ?>D/F</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $performance_class = 'secondary';
                                $performance_text = 'Fair';
                                if ($student['avg_marks'] >= 85) {
                                    $performance_class = 'success';
                                    $performance_text = 'Excellent';
                                } elseif ($student['avg_marks'] >= 75) {
                                    $performance_class = 'primary';
                                    $performance_text = 'Good';
                                } elseif ($student['avg_marks'] >= 60) {
                                    $performance_class = 'warning';
                                    $performance_text = 'Satisfactory';
                                } elseif ($student['avg_marks'] < 50) {
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

<!-- Class Analysis & Recommendations -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-lightbulb"></i>
            Class Analysis & Recommendations
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Performance Analysis</h6>
                <?php
                $excellent_rate = $total_grades > 0 ? ($excellent / $total_grades) * 100 : 0;
                $poor_rate = $total_grades > 0 ? ($poor / $total_grades) * 100 : 0;
                ?>
                
                <div class="alert alert-<?php echo $class_avg >= 75 ? 'success' : ($class_avg >= 60 ? 'warning' : 'danger'); ?>">
                    <h6 class="alert-heading">Overall Class Performance: 
                        <?php 
                        if ($class_avg >= 75) echo 'Excellent';
                        elseif ($class_avg >= 60) echo 'Good';
                        elseif ($class_avg >= 50) echo 'Satisfactory';
                        else echo 'Needs Improvement';
                        ?>
                    </h6>
                    <p class="mb-0">Class average: <?php echo number_format($class_avg, 1); ?>%</p>
                </div>
                
                <ul class="list-unstyled">
                    <li><strong>High Performers (≥85%):</strong> <?php echo count(array_filter($performance_data, function($s) { return $s['avg_marks'] >= 85; })); ?> students</li>
                    <li><strong>At Risk (<50%):</strong> <?php echo count(array_filter($performance_data, function($s) { return $s['avg_marks'] < 50; })); ?> students</li>
                    <li><strong>Excellence Rate:</strong> <?php echo number_format($excellent_rate, 1); ?>%</li>
                    <li><strong>Failure Rate:</strong> <?php echo number_format($poor_rate, 1); ?>%</li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h6>Recommendations</h6>
                <ul>
                    <?php if ($excellent_rate >= 50): ?>
                        <li class="text-success">Excellent class performance - consider advanced coursework</li>
                    <?php endif; ?>
                    
                    <?php if ($poor_rate > 20): ?>
                        <li class="text-danger">High failure rate - implement intervention programs</li>
                    <?php endif; ?>
                    
                    <?php if ($class_avg < 60): ?>
                        <li class="text-warning">Class average below target - review teaching methods</li>
                    <?php endif; ?>
                    
                    <?php if (count($performance_data) < 10): ?>
                        <li class="text-info">Small class size - consider personalized attention</li>
                    <?php endif; ?>
                    
                    <li>Schedule regular performance review meetings</li>
                    <li>Identify students needing additional support</li>
                    <li>Recognize and reward top performers</li>
                </ul>
                
                <h6>Action Items</h6>
                <div class="list-group list-group-flush">
                    <?php
                    $at_risk_students = array_filter($performance_data, function($s) { return $s['avg_marks'] < 50; });
                    if (!empty($at_risk_students)):
                    ?>
                        <div class="list-group-item">
                            <strong class="text-danger">Priority:</strong> <?php echo count($at_risk_students); ?> students need immediate intervention
                        </div>
                    <?php endif; ?>
                    
                    <div class="list-group-item">
                        <strong>Follow-up:</strong> Monitor progress weekly
                    </div>
                    <div class="list-group-item">
                        <strong>Support:</strong> Arrange tutoring for struggling students
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- No Data Message -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-bar-chart" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">No Performance Data Found</h4>
        <p class="text-muted">
            Please select filters above to generate a class performance report. 
            Make sure there are students with grades matching your criteria.
        </p>
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
    
    .table {
        font-size: 0.8rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>