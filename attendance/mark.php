<?php
/**
 * Mark Attendance
 * Student Management System
 */

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

$page_title = 'Mark Attendance';
$breadcrumbs = [
    ['name' => 'Attendance', 'url' => 'index.php'],
    ['name' => 'Mark Attendance']
];

$errors = [];
$success_message = '';
$selected_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
$selected_subject = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
$selected_course = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

// Handle bulk attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $attendance_date = clean_input($_POST['attendance_date']);
    $subject_id = (int)$_POST['subject_id'];
    $course_id = (int)$_POST['course_id'];
    $attendance_data = $_POST['attendance'] ?? [];
    
    if (empty($attendance_date)) {
        $errors[] = 'Please select an attendance date.';
    }
    
    if (empty($attendance_data)) {
        $errors[] = 'Please mark attendance for at least one student.';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $success_count = 0;
            $updated_count = 0;
            
            foreach ($attendance_data as $student_id => $data) {
                $student_id = (int)$student_id;
                $status = clean_input($data['status']);
                $remarks = clean_input($data['remarks'] ?? '');
                
                if (empty($status)) continue;
                
                // Check if attendance already exists
                $check_sql = "SELECT attendance_id FROM attendance 
                             WHERE student_id = ? AND DATE(date) = ? AND subject_id = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$student_id, $attendance_date, $subject_id]);
                $existing = $check_stmt->fetch();
                
                if ($existing) {
                    // Update existing record
                    $update_sql = "UPDATE attendance SET status = ?, remarks = ?, updated_at = CURRENT_TIMESTAMP 
                                  WHERE attendance_id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$status, $remarks, $existing['attendance_id']]);
                    $updated_count++;
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO attendance (student_id, subject_id, date, status, remarks) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $insert_stmt->execute([$student_id, $subject_id, $attendance_date, $status, $remarks]);
                    $success_count++;
                }
            }
            
            $pdo->commit();
            
            // Log activity
            log_activity('Attendance Marked', "Marked attendance for date: $attendance_date, Subject ID: $subject_id");
            
            $success_message = "Attendance marked successfully! ($success_count new records, $updated_count updated)";
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Failed to save attendance. Please try again.';
            error_log("Error marking attendance: " . $e->getMessage());
        }
    }
}

// Get courses for dropdown
$courses = get_all_courses();

// Get subjects based on selected course
$subjects = [];
if ($selected_course > 0) {
    $subjects_sql = "SELECT subject_id, subject_code, subject_name FROM subjects 
                     WHERE course_id = ? AND status = 'active' ORDER BY subject_code";
    $subjects_stmt = $pdo->prepare($subjects_sql);
    $subjects_stmt->execute([$selected_course]);
    $subjects = $subjects_stmt->fetchAll();
}

// Get students for attendance marking
$students = [];
if ($selected_course > 0) {
    $students_sql = "SELECT student_id, student_number, first_name, last_name, year_level
                     FROM students 
                     WHERE course_id = ? AND status = 'active' 
                     ORDER BY year_level, last_name, first_name";
    $students_stmt = $pdo->prepare($students_sql);
    $students_stmt->execute([$selected_course]);
    $students = $students_stmt->fetchAll();
}

// Get existing attendance for the selected date and subject
$existing_attendance = [];
if ($selected_subject > 0 && !empty($selected_date)) {
    $existing_sql = "SELECT student_id, status, remarks FROM attendance 
                     WHERE subject_id = ? AND DATE(date) = ?";
    $existing_stmt = $pdo->prepare($existing_sql);
    $existing_stmt->execute([$selected_subject, $selected_date]);
    $existing_records = $existing_stmt->fetchAll();
    
    foreach ($existing_records as $record) {
        $existing_attendance[$record['student_id']] = [
            'status' => $record['status'],
            'remarks' => $record['remarks']
        ];
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Mark Attendance</h1>
        <p class="text-muted">Record student attendance for classes</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Attendance
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<!-- Selection Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-funnel"></i>
            Attendance Parameters
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="selectionForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="attendance_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                           value="<?php echo $selected_date; ?>" required max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                    <select class="form-select" id="course_id" name="course_id" required onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo ($selected_course == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                    <select class="form-select" id="subject_id" name="subject_id" required onchange="this.form.submit()">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>" 
                                    <?php echo ($selected_subject == $subject['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Load Students
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Marking Form -->
<?php if (!empty($students) && $selected_subject > 0): ?>
<form method="POST" action="">
    <!-- Hidden fields to preserve selection -->
    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
    <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
    <input type="hidden" name="subject_id" value="<?php echo $selected_subject; ?>">
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-people"></i>
                Mark Attendance - <?php echo count($students); ?> Students
            </h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                    <i class="bi bi-check-all"></i> Mark All Present
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="markAllAbsent()">
                    <i class="bi bi-x-circle"></i> Mark All Absent
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Student</th>
                            <th width="15%">Student Number</th>
                            <th width="10%">Year</th>
                            <th width="20%">Status</th>
                            <th width="25%">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <?php
                            $existing = $existing_attendance[$student['student_id']] ?? null;
                            $current_status = $existing ? $existing['status'] : '';
                            $current_remarks = $existing ? $existing['remarks'] : '';
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['student_number']; ?></td>
                                <td>
                                    <span class="badge bg-secondary">Year <?php echo $student['year_level']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" data-bs-toggle="buttons">
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['student_id']; ?>][status]" 
                                               value="present" id="present_<?php echo $student['student_id']; ?>" 
                                               <?php echo ($current_status === 'present') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success btn-sm" for="present_<?php echo $student['student_id']; ?>">
                                            <i class="bi bi-check-circle"></i> Present
                                        </label>

                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['student_id']; ?>][status]" 
                                               value="absent" id="absent_<?php echo $student['student_id']; ?>" 
                                               <?php echo ($current_status === 'absent') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-danger btn-sm" for="absent_<?php echo $student['student_id']; ?>">
                                            <i class="bi bi-x-circle"></i> Absent
                                        </label>

                                        <input type="radio" class="btn-check" name="attendance[<?php echo $student['student_id']; ?>][status]" 
                                               value="late" id="late_<?php echo $student['student_id']; ?>" 
                                               <?php echo ($current_status === 'late') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning btn-sm" for="late_<?php echo $student['student_id']; ?>">
                                            <i class="bi bi-clock"></i> Late
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="attendance[<?php echo $student['student_id']; ?>][remarks]" 
                                           placeholder="Optional remarks..." 
                                           value="<?php echo htmlspecialchars($current_remarks); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="submit_attendance" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Save Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php elseif ($selected_course > 0 && empty($students)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-people" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">No Students Found</h4>
        <p class="text-muted">No active students found in the selected course.</p>
        <a href="../students/add.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add Students
        </a>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-calendar-check" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">Select Parameters</h4>
        <p class="text-muted">Please select date, course, and subject to load students for attendance marking.</p>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-lightning"></i>
            Quick Actions
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2">
                <a href="index.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-list"></i> View All Attendance
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="view.php" class="btn btn-outline-success w-100">
                    <i class="bi bi-graph-up"></i> Attendance Reports
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <button type="button" class="btn btn-outline-info w-100" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print This Page
                </button>
            </div>
            <div class="col-md-3 mb-2">
                <a href="../dashboard.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-house"></i> Dashboard
                </a>
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

.btn-check:checked + .btn {
    background-color: var(--bs-btn-color);
    border-color: var(--bs-btn-color);
    color: white;
}

@media print {
    .btn, .card-header .btn-group {
        display: none !important;
    }
}
</style>

<script>
function markAllPresent() {
    document.querySelectorAll('input[value="present"]').forEach(function(radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
}

function markAllAbsent() {
    document.querySelectorAll('input[value="absent"]').forEach(function(radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
}

// Auto-submit form when course or subject changes
document.getElementById('course_id').addEventListener('change', function() {
    // Reset subject when course changes
    document.getElementById('subject_id').value = '';
});

// Confirm before leaving if attendance is marked
let attendanceMarked = false;
document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        attendanceMarked = true;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (attendanceMarked) {
        e.preventDefault();
        e.returnValue = 'You have unsaved attendance data. Are you sure you want to leave?';
    }
});

// Remove warning when form is submitted
document.querySelector('form').addEventListener('submit', function() {
    attendanceMarked = false;
});
</script>

<?php require_once '../includes/footer.php'; ?>