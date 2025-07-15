<?php
/**
 * Attendance Report
 * Student Management System
 */

$page_title = 'Attendance Report';
$breadcrumbs = [
    ['name' => 'Reports', 'url' => 'index.php'],
    ['name' => 'Attendance Report']
];

require_once '../includes/header.php';

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$subject_filter = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : '';

// Build filter conditions
$where_conditions = [];
$params = [];

if ($student_filter > 0) {
    $where_conditions[] = "a.student_id = ?";
    $params[] = $student_filter;
}

if ($subject_filter > 0) {
    $where_conditions[] = "a.subject_id = ?";
    $params[] = $subject_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "a.date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "a.date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get attendance data
$attendance_data = [];
$attendance_summary = [];

if ($student_filter > 0 || $subject_filter > 0 || !empty($date_from) || !empty($date_to)) {
    $sql = "
        SELECT a.*, s.first_name, s.last_name, s.student_number,
               sub.subject_code, sub.subject_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN subjects sub ON a.subject_id = sub.subject_id
        $where_clause
        ORDER BY a.date DESC, s.last_name, s.first_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attendance_data = $stmt->fetchAll();

    // Calculate summary statistics
    $summary_sql = "
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance a
        $where_clause
    ";
    
    $stmt = $pdo->prepare($summary_sql);
    $stmt->execute($params);
    $attendance_summary = $stmt->fetch();
}

// Get filter options
$students_sql = "SELECT student_id, student_number, first_name, last_name FROM students WHERE status = 'active' ORDER BY last_name, first_name";
$students = $pdo->query($students_sql)->fetchAll();

$subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_code";
$subjects = $pdo->query($subjects_sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Attendance Report</h1>
        <p class="text-muted">Track and analyze student attendance patterns</p>
    </div>
    <div class="btn-group" role="group">
        <?php if (!empty($attendance_data)): ?>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="attendance-export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
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
                <label for="student_id" class="form-label">Student</label>
                <select class="form-select" id="student_id" name="student_id">
                    <option value="">All Students</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['student_id']; ?>" 
                                <?php echo ($student_filter == $student['student_id']) ? 'selected' : ''; ?>>
                            <?php echo $student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']; ?>
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
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Generate Report
                </button>
            </div>
        </form>
        
        <div class="mt-2">
            <a href="attendance-report.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Reset Filters
            </a>
        </div>
    </div>
</div>

<?php if (!empty($attendance_data)): ?>
<!-- Attendance Summary -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Attendance Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h3><?php echo number_format($attendance_summary['total_records']); ?></h3>
                    <p class="mb-0">Total Records</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h3><?php echo number_format($attendance_summary['present_count']); ?></h3>
                    <p class="mb-0">Present</p>
                    <small><?php echo $attendance_summary['total_records'] > 0 ? number_format(($attendance_summary['present_count'] / $attendance_summary['total_records']) * 100, 1) : 0; ?>%</small>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-danger text-white p-3 rounded">
                    <h3><?php echo number_format($attendance_summary['absent_count']); ?></h3>
                    <p class="mb-0">Absent</p>
                    <small><?php echo $attendance_summary['total_records'] > 0 ? number_format(($attendance_summary['absent_count'] / $attendance_summary['total_records']) * 100, 1) : 0; ?>%</small>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h3><?php echo number_format($attendance_summary['late_count']); ?></h3>
                    <p class="mb-0">Late</p>
                    <small><?php echo $attendance_summary['total_records'] > 0 ? number_format(($attendance_summary['late_count'] / $attendance_summary['total_records']) * 100, 1) : 0; ?>%</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i>
            Attendance Records
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $record): ?>
                        <tr>
                            <td><?php echo format_date($record['date']); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></strong>
                                    <br><small class="text-muted"><?php echo $record['student_number']; ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo $record['subject_code']; ?></strong>
                                <br><small class="text-muted"><?php echo $record['subject_name']; ?></small>
                            </td>
                            <td>
                                <?php
                                $status_colors = [
                                    'present' => 'success',
                                    'absent' => 'danger',
                                    'late' => 'warning'
                                ];
                                $status_color = $status_colors[$record['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_color; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $record['remarks'] ?? '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- No Data Message -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-calendar-check" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">No Attendance Data Found</h4>
        <p class="text-muted">
            Please select filters above to generate an attendance report.
        </p>
    </div>
</div>
<?php endif; ?>

<style>
.stat-card {
    text-align: center;
    border-radius: 8px;
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