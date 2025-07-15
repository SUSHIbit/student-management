<?php
/**
 * View Attendance Reports
 * Student Management System
 */

$page_title = 'Attendance Reports';
$breadcrumbs = [
    ['name' => 'Attendance', 'url' => 'index.php'],
    ['name' => 'Reports']
];

require_once '../includes/header.php';

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : date('Y-m-d');

// Build filter conditions
$where_conditions = ["a.date BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if ($student_filter > 0) {
    $where_conditions[] = "a.student_id = ?";
    $params[] = $student_filter;
}

if ($course_filter > 0) {
    $where_conditions[] = "st.course_id = ?";
    $params[] = $course_filter;
}

if ($subject_filter > 0) {
    $where_conditions[] = "a.subject_id = ?";
    $params[] = $subject_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get attendance statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT a.student_id) as total_students,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    $where_clause
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Calculate percentages
$present_percentage = $stats['total_records'] > 0 ? ($stats['present_count'] / $stats['total_records']) * 100 : 0;
$absent_percentage = $stats['total_records'] > 0 ? ($stats['absent_count'] / $stats['total_records']) * 100 : 0;
$late_percentage = $stats['total_records'] > 0 ? ($stats['late_count'] / $stats['total_records']) * 100 : 0;

// Get individual student attendance summary
$student_summary_sql = "
    SELECT 
        st.student_id,
        st.student_number,
        st.first_name,
        st.last_name,
        st.year_level,
        c.course_code,
        COUNT(*) as total_days,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
        ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(*)) * 100, 1) as attendance_percentage
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    LEFT JOIN courses c ON st.course_id = c.course_id
    $where_clause
    GROUP BY st.student_id
    ORDER BY attendance_percentage DESC, st.last_name, st.first_name
";
$student_summary_stmt = $pdo->prepare($student_summary_sql);
$student_summary_stmt->execute($params);
$student_summary = $student_summary_stmt->fetchAll();

// Get daily attendance summary
$daily_summary_sql = "
    SELECT 
        DATE(a.date) as attendance_date,
        COUNT(*) as total_records,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
        ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(*)) * 100, 1) as daily_percentage
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    $where_clause
    GROUP BY DATE(a.date)
    ORDER BY attendance_date DESC
    LIMIT 10
";
$daily_summary_stmt = $pdo->prepare($daily_summary_sql);
$daily_summary_stmt->execute($params);
$daily_summary = $daily_summary_stmt->fetchAll();

// Get filter options
$courses = get_all_courses();
$subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_code";
$subjects = $pdo->query($subjects_sql)->fetchAll();

$students_sql = "SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name, first_name";
$students = $pdo->query($students_sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Attendance Reports</h1>
        <p class="text-muted">Analyze attendance patterns and statistics</p>
    </div>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Attendance
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
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2">
                <label for="course_id" class="form-label">Course</label>
                <select class="form-select" id="course_id" name="course_id">
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
                <label for="subject_id" class="form-label">Subject</label>
                <select class="form-select" id="subject_id" name="subject_id">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" 
                                <?php echo ($subject_filter == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo $subject['subject_code']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="student_id" class="form-label">Student</label>
                <select class="form-select" id="student_id" name="student_id">
                    <option value="">All Students</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['student_id']; ?>" 
                                <?php echo ($student_filter == $student['student_id']) ? 'selected' : ''; ?>>
                            <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'; ?>
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
                <a href="view.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Overall Statistics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['total_records']); ?></div>
                        <div class="small">Total Records</div>
                        <div class="small"><?php echo number_format($stats['total_students']); ?> students</div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($present_percentage, 1); ?>%</div>
                        <div class="small">Present Rate</div>
                        <div class="small"><?php echo number_format($stats['present_count']); ?> records</div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($absent_percentage, 1); ?>%</div>
                        <div class="small">Absent Rate</div>
                        <div class="small"><?php echo number_format($stats['absent_count']); ?> records</div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-x-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($late_percentage, 1); ?>%</div>
                        <div class="small">Late Rate</div>
                        <div class="small"><?php echo number_format($stats['late_count']); ?> records</div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Attendance Summary -->
<?php if (!empty($student_summary)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-people"></i>
            Student Attendance Summary
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_summary as $summary): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($summary['first_name'], 0, 1) . substr($summary['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div><?php echo $summary['first_name'] . ' ' . $summary['last_name']; ?></div>
                                        <small class="text-muted"><?php echo $summary['student_number']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $summary['course_code']; ?></span>
                                <br><small class="text-muted">Year <?php echo $summary['year_level']; ?></small>
                            </td>
                            <td><strong><?php echo $summary['total_days']; ?></strong></td>
                            <td>
                                <span class="badge bg-success"><?php echo $summary['present_days']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?php echo $summary['absent_days']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning"><?php echo $summary['late_days']; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px; height: 8px;">
                                        <div class="progress-bar bg-<?php echo $summary['attendance_percentage'] >= 75 ? 'success' : ($summary['attendance_percentage'] >= 60 ? 'warning' : 'danger'); ?>" 
                                             style="width: <?php echo $summary['attendance_percentage']; ?>%"></div>
                                    </div>
                                    <strong><?php echo $summary['attendance_percentage']; ?>%</strong>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_class = 'secondary';
                                $status_text = 'Poor';
                                if ($summary['attendance_percentage'] >= 90) {
                                    $status_class = 'success';
                                    $status_text = 'Excellent';
                                } elseif ($summary['attendance_percentage'] >= 75) {
                                    $status_class = 'primary';
                                    $status_text = 'Good';
                                } elseif ($summary['attendance_percentage'] >= 60) {
                                    $status_class = 'warning';
                                    $status_text = 'Average';
                                } else {
                                    $status_class = 'danger';
                                    $status_text = 'Poor';
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Daily Attendance Trends -->
<?php if (!empty($daily_summary)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Recent Daily Attendance Trends
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Daily Percentage</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_summary as $day): ?>
                        <tr>
                            <td>
                                <strong><?php echo format_date($day['attendance_date']); ?></strong>
                                <br><small class="text-muted"><?php echo date('l', strtotime($day['attendance_date'])); ?></small>
                            </td>
                            <td><?php echo $day['total_records']; ?></td>
                            <td>
                                <span class="badge bg-success"><?php echo $day['present_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?php echo $day['absent_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning"><?php echo $day['late_count']; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 80px; height: 10px;">
                                        <div class="progress-bar bg-<?php echo $day['daily_percentage'] >= 75 ? 'success' : ($day['daily_percentage'] >= 60 ? 'warning' : 'danger'); ?>" 
                                             style="width: <?php echo $day['daily_percentage']; ?>%"></div>
                                    </div>
                                    <strong><?php echo $day['daily_percentage']; ?>%</strong>
                                </div>
                            </td>
                            <td>
                                <?php if ($day['daily_percentage'] >= 90): ?>
                                    <i class="bi bi-arrow-up-circle text-success" title="Excellent"></i>
                                <?php elseif ($day['daily_percentage'] >= 75): ?>
                                    <i class="bi bi-arrow-right-circle text-primary" title="Good"></i>
                                <?php elseif ($day['daily_percentage'] >= 60): ?>
                                    <i class="bi bi-arrow-down-circle text-warning" title="Average"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-down-circle text-danger" title="Poor"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Low Attendance Alerts -->
<?php
$low_attendance_sql = "
    SELECT 
        st.student_id,
        st.student_number,
        st.first_name,
        st.last_name,
        c.course_code,
        COUNT(*) as total_days,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
        ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) / COUNT(*)) * 100, 1) as attendance_percentage
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    LEFT JOIN courses c ON st.course_id = c.course_id
    $where_clause
    GROUP BY st.student_id
    HAVING attendance_percentage < 75 AND total_days >= 5
    ORDER BY attendance_percentage ASC
    LIMIT 10
";
$low_attendance_stmt = $pdo->prepare($low_attendance_sql);
$low_attendance_stmt->execute($params);
$low_attendance = $low_attendance_stmt->fetchAll();
?>

<?php if (!empty($low_attendance)): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            Low Attendance Alerts (Below 75%)
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>Action Required:</strong> The following students have attendance below 75% and may need intervention.
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Attendance %</th>
                        <th>Days Present/Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_attendance as $alert): ?>
                        <tr>
                            <td>
                                <strong><?php echo $alert['first_name'] . ' ' . $alert['last_name']; ?></strong>
                                <br><small class="text-muted"><?php echo $alert['student_number']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $alert['course_code']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $alert['attendance_percentage'] < 50 ? 'danger' : 'warning'; ?>">
                                    <?php echo $alert['attendance_percentage']; ?>%
                                </span>
                            </td>
                            <td>
                                <?php echo $alert['present_days']; ?> / <?php echo $alert['total_days']; ?>
                            </td>
                            <td>
                                <a href="../students/view.php?id=<?php echo $alert['student_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-person"></i> View Student
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Analysis and Recommendations -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-lightbulb"></i>
            Analysis & Recommendations
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Attendance Analysis</h6>
                <?php
                $analysis_class = 'secondary';
                $analysis_text = 'Fair';
                $recommendation = 'Monitor attendance patterns and implement improvement strategies.';

                if ($present_percentage >= 90) {
                    $analysis_class = 'success';
                    $analysis_text = 'Excellent';
                    $recommendation = 'Outstanding attendance rate. Continue current practices and recognize student commitment.';
                } elseif ($present_percentage >= 80) {
                    $analysis_class = 'primary';
                    $analysis_text = 'Good';
                    $recommendation = 'Good attendance rate. Focus on reducing absenteeism further.';
                } elseif ($present_percentage >= 70) {
                    $analysis_class = 'warning';
                    $analysis_text = 'Needs Improvement';
                    $recommendation = 'Attendance needs improvement. Consider incentive programs and follow-up with absent students.';
                } elseif ($present_percentage < 70) {
                    $analysis_class = 'danger';
                    $analysis_text = 'Critical';
                    $recommendation = 'Critical attendance rate. Immediate intervention required. Review policies and implement support programs.';
                }
                ?>
                
                <div class="alert alert-<?php echo $analysis_class; ?>">
                    <h6 class="alert-heading">Overall Status: <?php echo $analysis_text; ?></h6>
                    <p class="mb-0"><?php echo $recommendation; ?></p>
                </div>
                
                <h6>Key Metrics</h6>
                <ul class="list-unstyled">
                    <li><strong>Overall Attendance Rate:</strong> <?php echo number_format($present_percentage, 1); ?>%</li>
                    <li><strong>Total Students Tracked:</strong> <?php echo $stats['total_students']; ?></li>
                    <li><strong>Average Daily Records:</strong> <?php echo !empty($daily_summary) ? number_format(array_sum(array_column($daily_summary, 'total_records')) / count($daily_summary), 1) : 0; ?></li>
                    <li><strong>Students Below 75%:</strong> <?php echo count($low_attendance); ?></li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h6>Recommendations</h6>
                <ul>
                    <?php if ($present_percentage >= 90): ?>
                        <li class="text-success">Excellent attendance - maintain current strategies</li>
                        <li class="text-info">Consider recognition programs for consistent attendees</li>
                    <?php endif; ?>
                    
                    <?php if ($absent_percentage > 20): ?>
                        <li class="text-danger">High absenteeism - implement intervention programs</li>
                    <?php endif; ?>
                    
                    <?php if ($late_percentage > 15): ?>
                        <li class="text-warning">High tardiness rate - review start times and policies</li>
                    <?php endif; ?>
                    
                    <?php if (count($low_attendance) > 0): ?>
                        <li class="text-warning"><?php echo count($low_attendance); ?> students need immediate attention</li>
                    <?php endif; ?>
                    
                    <li>Implement regular attendance monitoring and reporting</li>
                    <li>Provide early warning systems for at-risk students</li>
                    <li>Consider attendance improvement incentives</li>
                </ul>
                
                <h6>Action Items</h6>
                <div class="list-group list-group-flush">
                    <?php if (count($low_attendance) > 0): ?>
                        <div class="list-group-item">
                            <strong class="text-danger">Priority:</strong> Contact <?php echo count($low_attendance); ?> students with low attendance
                        </div>
                    <?php endif; ?>
                    
                    <div class="list-group-item">
                        <strong>Follow-up:</strong> Weekly attendance review meetings
                    </div>
                    <div class="list-group-item">
                        <strong>Support:</strong> Provide resources for struggling students
                    </div>
                    <?php if ($present_percentage < 80): ?>
                        <div class="list-group-item">
                            <strong>Policy:</strong> Review attendance policies and enforcement
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

@media print {
    .btn, .card:first-child {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>