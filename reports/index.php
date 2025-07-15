<?php
/**
 * Reports Dashboard
 * Student Management System - Phase 5
 */

$page_title = 'Reports';
$breadcrumbs = [
    ['name' => 'Reports']
];

require_once '../includes/header.php';

// Get summary statistics for the dashboard
$stats = [];

// Student statistics
$student_stats_sql = "
    SELECT 
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'graduated' THEN 1 END) as graduated_students,
        COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_students
    FROM students
";
$stats['students'] = $pdo->query($student_stats_sql)->fetch();

// Course statistics
$course_stats_sql = "
    SELECT 
        COUNT(*) as total_courses,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_courses
    FROM courses
";
$stats['courses'] = $pdo->query($course_stats_sql)->fetch();

// Subject statistics
$subject_stats_sql = "
    SELECT 
        COUNT(*) as total_subjects,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subjects,
        SUM(credits) as total_credits
    FROM subjects
";
$stats['subjects'] = $pdo->query($subject_stats_sql)->fetch();

// Grade statistics
$grade_stats_sql = "
    SELECT 
        COUNT(*) as total_grades,
        AVG(total_marks) as avg_marks,
        COUNT(CASE WHEN grade_letter IN ('A+', 'A', 'A-') THEN 1 END) as excellent_grades,
        COUNT(CASE WHEN grade_letter IN ('B+', 'B', 'B-') THEN 1 END) as good_grades,
        COUNT(CASE WHEN grade_letter IN ('C+', 'C', 'C-') THEN 1 END) as satisfactory_grades,
        COUNT(CASE WHEN grade_letter IN ('D', 'F') THEN 1 END) as poor_grades
    FROM grades
";
$stats['grades'] = $pdo->query($grade_stats_sql)->fetch();

// Recent activity (last 30 days)
$recent_students_sql = "SELECT COUNT(*) as count FROM students WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$recent_grades_sql = "SELECT COUNT(*) as count FROM grades WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$stats['recent_students'] = $pdo->query($recent_students_sql)->fetch()['count'];
$stats['recent_grades'] = $pdo->query($recent_grades_sql)->fetch()['count'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Reports & Analytics</h1>
        <p class="text-muted">Generate comprehensive reports and view system analytics</p>
    </div>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> Quick Export
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="students-export.php">Export All Students</a></li>
            <li><a class="dropdown-item" href="grades-export.php">Export All Grades</a></li>
            <li><a class="dropdown-item" href="courses-export.php">Export Course Data</a></li>
        </ul>
    </div>
</div>

<!-- System Overview -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['students']['total_students']); ?></div>
                        <div class="small">Total Students</div>
                        <div class="small mt-1">
                            <i class="bi bi-arrow-up"></i> <?php echo $stats['recent_students']; ?> this month
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people-fill" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['courses']['total_courses']); ?></div>
                        <div class="small">Total Courses</div>
                        <div class="small mt-1">
                            <i class="bi bi-check-circle"></i> <?php echo $stats['courses']['active_courses']; ?> active
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-book-fill" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['subjects']['total_subjects']); ?></div>
                        <div class="small">Total Subjects</div>
                        <div class="small mt-1">
                            <i class="bi bi-award"></i> <?php echo number_format($stats['subjects']['total_credits']); ?> credits
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-journal-text" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['grades']['total_grades']); ?></div>
                        <div class="small">Total Grades</div>
                        <div class="small mt-1">
                            <i class="bi bi-graph-up"></i> <?php echo $stats['recent_grades']; ?> this month
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Categories -->
<div class="row">
    <!-- Student Reports -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill"></i>
                    Student Reports
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="student-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Individual Student Report</h6>
                            <p class="mb-1 text-muted">Comprehensive academic profile for a specific student</p>
                        </div>
                        <i class="bi bi-person-lines-fill text-primary"></i>
                    </a>
                    
                    <a href="class-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Class Performance Report</h6>
                            <p class="mb-1 text-muted">Performance analysis by course, year, or subject</p>
                        </div>
                        <i class="bi bi-bar-chart-fill text-success"></i>
                    </a>
                    
                    <a href="student-list.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Student Directory</h6>
                            <p class="mb-1 text-muted">Complete student listing with filters and export</p>
                        </div>
                        <i class="bi bi-list-ul text-info"></i>
                    </a>
                    
                    <a href="enrollment-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Enrollment Statistics</h6>
                            <p class="mb-1 text-muted">Student enrollment trends and demographics</p>
                        </div>
                        <i class="bi bi-graph-up-arrow text-warning"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Reports -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Academic Reports
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="grade-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Grade Analysis Report</h6>
                            <p class="mb-1 text-muted">Detailed grade statistics and distribution</p>
                        </div>
                        <i class="bi bi-clipboard-data text-primary"></i>
                    </a>
                    
                    <a href="subject-performance.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Subject Performance</h6>
                            <p class="mb-1 text-muted">Performance analysis by subject and semester</p>
                        </div>
                        <i class="bi bi-journal-text text-success"></i>
                    </a>
                    
                    <a href="gpa-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">GPA & Academic Standing</h6>
                            <p class="mb-1 text-muted">Student GPA calculations and academic status</p>
                        </div>
                        <i class="bi bi-award text-info"></i>
                    </a>
                    
                    <a href="transcript-generator.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Transcript Generator</h6>
                            <p class="mb-1 text-muted">Official academic transcripts for students</p>
                        </div>
                        <i class="bi bi-file-earmark-text text-warning"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Administrative Reports -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="bi bi-gear-fill"></i>
                    Administrative Reports
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="course-report.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Course Management Report</h6>
                            <p class="mb-1 text-muted">Course utilization and subject mapping</p>
                        </div>
                        <i class="bi bi-book text-primary"></i>
                    </a>
                    
                    <a href="activity-log.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">System Activity Log</h6>
                            <p class="mb-1 text-muted">User activities and system usage tracking</p>
                        </div>
                        <i class="bi bi-clock-history text-success"></i>
                    </a>
                    
                    <a href="data-summary.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Data Summary Report</h6>
                            <p class="mb-1 text-muted">Complete system overview and statistics</p>
                        </div>
                        <i class="bi bi-pie-chart text-info"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Analytics -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2"></i>
                    Quick Analytics
                </h5>
            </div>
            <div class="card-body">
                <!-- Grade Distribution Chart -->
                <h6>Grade Distribution</h6>
                <div class="row mb-3">
                    <div class="col-3 text-center">
                        <div class="stat-circle bg-success text-white">
                            <?php echo $stats['grades']['excellent_grades']; ?>
                        </div>
                        <small class="text-muted">Excellent (A)</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="stat-circle bg-primary text-white">
                            <?php echo $stats['grades']['good_grades']; ?>
                        </div>
                        <small class="text-muted">Good (B)</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="stat-circle bg-warning text-white">
                            <?php echo $stats['grades']['satisfactory_grades']; ?>
                        </div>
                        <small class="text-muted">Satisfactory (C)</small>
                    </div>
                    <div class="col-3 text-center">
                        <div class="stat-circle bg-danger text-white">
                            <?php echo $stats['grades']['poor_grades']; ?>
                        </div>
                        <small class="text-muted">Poor (D/F)</small>
                    </div>
                </div>

                <!-- Student Status Distribution -->
                <h6>Student Status</h6>
                <div class="progress mb-2" style="height: 20px;">
                    <?php 
                    $total = $stats['students']['total_students'];
                    $active_percent = $total > 0 ? ($stats['students']['active_students'] / $total) * 100 : 0;
                    $graduated_percent = $total > 0 ? ($stats['students']['graduated_students'] / $total) * 100 : 0;
                    $suspended_percent = $total > 0 ? ($stats['students']['suspended_students'] / $total) * 100 : 0;
                    ?>
                    <div class="progress-bar bg-success" style="width: <?php echo $active_percent; ?>%">
                        Active: <?php echo $stats['students']['active_students']; ?>
                    </div>
                    <div class="progress-bar bg-primary" style="width: <?php echo $graduated_percent; ?>%">
                        Graduated: <?php echo $stats['students']['graduated_students']; ?>
                    </div>
                    <div class="progress-bar bg-danger" style="width: <?php echo $suspended_percent; ?>%">
                        Suspended: <?php echo $stats['students']['suspended_students']; ?>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary"><?php echo number_format($stats['grades']['avg_marks'], 1); ?>%</h4>
                            <small class="text-muted">Average Grade</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success"><?php echo number_format($stats['subjects']['total_credits'] / max($stats['subjects']['total_subjects'], 1), 1); ?></h4>
                            <small class="text-muted">Avg Credits/Subject</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Report Builder -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-tools"></i>
            Custom Report Builder
        </h5>
    </div>
    <div class="card-body">
        <form action="custom-report.php" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type" required>
                    <option value="">Select Type</option>
                    <option value="students">Student Data</option>
                    <option value="grades">Grade Data</option>
                    <option value="courses">Course Data</option>
                    <option value="subjects">Subject Data</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="filter_course" class="form-label">Course Filter</label>
                <select class="form-select" id="filter_course" name="filter_course">
                    <option value="">All Courses</option>
                    <?php
                    $courses = get_all_courses();
                    foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="filter_year" class="form-label">Year Level</label>
                <select class="form-select" id="filter_year" name="filter_year">
                    <option value="">All Years</option>
                    <option value="1">Year 1</option>
                    <option value="2">Year 2</option>
                    <option value="3">Year 3</option>
                    <option value="4">Year 4</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="format" class="form-label">Export Format</label>
                <select class="form-select" id="format" name="format">
                    <option value="html">HTML View</option>
                    <option value="pdf">PDF Download</option>
                    <option value="csv">CSV Export</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-play-fill"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.stat-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    margin: 0 auto 0.5rem;
}

.list-group-item-action:hover {
    background-color: var(--bs-light);
}

.card-header {
    font-weight: 600;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<?php require_once '../includes/footer.php'; ?>