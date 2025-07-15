<?php
/**
 * Attendance Management - Index Page
 * Student Management System
 */

$page_title = 'Attendance';
$breadcrumbs = [
    ['name' => 'Attendance']
];

require_once '../includes/header.php';

// Pagination settings
$records_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$subject_filter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$date_filter = isset($_GET['date']) ? clean_input($_GET['date']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(st.first_name LIKE ? OR st.last_name LIKE ? OR st.student_number LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($course_filter > 0) {
    $where_conditions[] = "st.course_id = ?";
    $params[] = $course_filter;
}

if ($subject_filter > 0) {
    $where_conditions[] = "a.subject_id = ?";
    $params[] = $subject_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(a.date) = ?";
    $params[] = $date_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    LEFT JOIN subjects sub ON a.subject_id = sub.subject_id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// Calculate pagination
$pagination = paginate($total_records, $records_per_page, $current_page);
$offset = $pagination['offset'];

// Get attendance records
$sql = "
    SELECT a.*, st.student_number, st.first_name, st.last_name, st.year_level,
           sub.subject_code, sub.subject_name, c.course_code
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    LEFT JOIN subjects sub ON a.subject_id = sub.subject_id
    LEFT JOIN courses c ON st.course_id = c.course_id
    $where_clause
    ORDER BY a.date DESC, st.last_name, st.first_name
    LIMIT $records_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get filter options
$courses = get_all_courses();
$subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_code";
$subjects = $pdo->query($subjects_sql)->fetchAll();

// Get attendance statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_records,
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count
    FROM attendance a
    JOIN students st ON a.student_id = st.student_id
    $where_clause
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Attendance Management</h1>
        <p class="text-muted">Track and manage student attendance</p>
    </div>
    <div class="btn-group" role="group">
        <a href="mark.php" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Mark Attendance
        </a>
        <a href="view.php" class="btn btn-success">
            <i class="bi bi-graph-up"></i> View Reports
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?php echo number_format($stats['total_records']); ?></div>
                        <div class="small">Total Records</div>
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
                        <div class="h4 mb-0"><?php echo number_format($stats['present_count']); ?></div>
                        <div class="small">Present</div>
                        <div class="small">
                            <?php echo $stats['total_records'] > 0 ? number_format(($stats['present_count'] / $stats['total_records']) * 100, 1) : 0; ?>%
                        </div>
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
                        <div class="h4 mb-0"><?php echo number_format($stats['absent_count']); ?></div>
                        <div class="small">Absent</div>
                        <div class="small">
                            <?php echo $stats['total_records'] > 0 ? number_format(($stats['absent_count'] / $stats['total_records']) * 100, 1) : 0; ?>%
                        </div>
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
                        <div class="h4 mb-0"><?php echo number_format($stats['late_count']); ?></div>
                        <div class="small">Late</div>
                        <div class="small">
                            <?php echo $stats['total_records'] > 0 ? number_format(($stats['late_count'] / $stats['total_records']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Student name or number..." 
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
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="present" <?php echo ($status_filter == 'present') ? 'selected' : ''; ?>>Present</option>
                    <option value="absent" <?php echo ($status_filter == 'absent') ? 'selected' : ''; ?>>Absent</option>
                    <option value="late" <?php echo ($status_filter == 'late') ? 'selected' : ''; ?>>Late</option>
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

<!-- Attendance Records -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-calendar-check"></i>
            Attendance Records (<?php echo number_format($total_records); ?> total)
        </h5>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="../reports/attendance-export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($attendance_records)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo format_date($record['date']); ?></strong>
                                    <br><small class="text-muted"><?php echo date('l', strtotime($record['date'])); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <?php echo strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></div>
                                            <small class="text-muted"><?php echo $record['student_number']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $record['course_code']; ?></span>
                                    <br><small class="text-muted">Year <?php echo $record['year_level']; ?></small>
                                </td>
                                <td>
                                    <?php if ($record['subject_code']): ?>
                                        <strong><?php echo $record['subject_code']; ?></strong>
                                        <br><small class="text-muted"><?php echo $record['subject_name']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">General</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'present' => 'success',
                                        'absent' => 'danger',
                                        'late' => 'warning'
                                    ];
                                    $status_color = $status_colors[$record['status']] ?? 'secondary';
                                    $status_icons = [
                                        'present' => 'check-circle',
                                        'absent' => 'x-circle',
                                        'late' => 'clock'
                                    ];
                                    $status_icon = $status_icons[$record['status']] ?? 'circle';
                                    ?>
                                    <span class="badge bg-<?php echo $status_color; ?>">
                                        <i class="bi bi-<?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $record['remarks'] ? htmlspecialchars($record['remarks']) : '-'; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                onclick="editAttendance(<?php echo $record['attendance_id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteAttendance(<?php echo $record['attendance_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
                <i class="bi bi-calendar-x" style="font-size: 4rem; color: var(--muted);"></i>
                <h4 class="mt-3">No Attendance Records Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_term) || $course_filter || $subject_filter || !empty($date_filter) || !empty($status_filter)): ?>
                        No attendance records match your current filters. <a href="index.php">Clear filters</a> to see all records.
                    <?php else: ?>
                        Start by marking attendance for students.
                    <?php endif; ?>
                </p>
                <a href="mark.php" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Mark First Attendance
                </a>
            </div>
        <?php endif; ?>
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
</style>

<script>
function editAttendance(attendanceId) {
    // Implement edit attendance functionality
    window.location.href = 'mark.php?edit=' + attendanceId;
}

function deleteAttendance(attendanceId) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        // Create form to delete attendance
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete-attendance.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'attendance_id';
        input.value = attendanceId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>