<?php
/**
 * View Student Details
 * Student Management System - Phase 2
 */

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header('Location: index.php');
    exit();
}

require_once '../includes/header.php';

// Get student details
$student = get_student_by_id($student_id);

if (!$student) {
    show_alert('Student not found.', 'danger');
    header('Location: index.php');
    exit();
}

$page_title = $student['first_name'] . ' ' . $student['last_name'];
$breadcrumbs = [
    ['name' => 'Students', 'url' => 'index.php'],
    ['name' => 'Student Details']
];

// Get student's grades (if any)
$stmt = $pdo->prepare("
    SELECT g.*, s.subject_name, s.subject_code, s.credits
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.student_id = ?
    ORDER BY g.semester, s.subject_code
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll();

// Calculate GPA if grades exist
$total_points = 0;
$total_credits = 0;
$gpa = 0;

if (!empty($grades)) {
    foreach ($grades as $grade) {
        $grade_points = 0;
        switch ($grade['grade_letter']) {
            case 'A+': case 'A': $grade_points = 4.0; break;
            case 'A-': $grade_points = 3.7; break;
            case 'B+': $grade_points = 3.3; break;
            case 'B': $grade_points = 3.0; break;
            case 'B-': $grade_points = 2.7; break;
            case 'C+': $grade_points = 2.3; break;
            case 'C': $grade_points = 2.0; break;
            case 'C-': $grade_points = 1.7; break;
            case 'D': $grade_points = 1.0; break;
            case 'F': $grade_points = 0.0; break;
        }
        $total_points += $grade_points * $grade['credits'];
        $total_credits += $grade['credits'];
    }
    
    if ($total_credits > 0) {
        $gpa = $total_points / $total_credits;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h1>
        <p class="text-muted">Student ID: <?php echo $student['student_number']; ?></p>
    </div>
    <div class="btn-group" role="group">
        <a href="edit.php?id=<?php echo $student['student_id']; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit Student
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <!-- Student Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-large mb-3">
                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                </div>
                <h5 class="card-title"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h5>
                <p class="text-muted"><?php echo $student['student_number']; ?></p>
                
                <?php
                $status_colors = [
                    'active' => 'success',
                    'inactive' => 'secondary', 
                    'graduated' => 'primary',
                    'suspended' => 'danger'
                ];
                $status_color = $status_colors[$student['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $status_color; ?> mb-3">
                    <?php echo ucfirst($student['status']); ?>
                </span>
                
                <?php if ($gpa > 0): ?>
                    <div class="mt-3">
                        <h6>Current GPA</h6>
                        <h3 class="text-primary"><?php echo number_format($gpa, 2); ?></h3>
                        <small class="text-muted">Based on <?php echo count($grades); ?> subjects</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Quick Stats
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Subjects:</span>
                    <strong><?php echo count($grades); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Credits:</span>
                    <strong><?php echo $total_credits; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Year Level:</span>
                    <strong>Year <?php echo $student['year_level']; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Member Since:</span>
                    <strong><?php echo format_date($student['created_at']); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="col-lg-8">
        <!-- Personal Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-fill"></i>
                    Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">First Name</label>
                        <p class="mb-0"><?php echo $student['first_name']; ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Last Name</label>
                        <p class="mb-0"><?php echo $student['last_name']; ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Email Address</label>
                        <p class="mb-0">
                            <a href="mailto:<?php echo $student['email']; ?>" class="text-decoration-none">
                                <?php echo $student['email']; ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Phone Number</label>
                        <p class="mb-0">
                            <?php if ($student['phone']): ?>
                                <a href="tel:<?php echo $student['phone']; ?>" class="text-decoration-none">
                                    <?php echo $student['phone']; ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Not provided</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Date of Birth</label>
                        <p class="mb-0">
                            <?php echo $student['date_of_birth'] ? format_date($student['date_of_birth']) : 'Not provided'; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Address</label>
                        <p class="mb-0">
                            <?php echo $student['address'] ? nl2br(htmlspecialchars($student['address'])) : 'Not provided'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-book-fill"></i>
                    Academic Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Student Number</label>
                        <p class="mb-0"><strong><?php echo $student['student_number']; ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Current Status</label>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo $status_color; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Course</label>
                        <p class="mb-0">
                            <?php if ($student['course_name']): ?>
                                <strong><?php echo $student['course_code']; ?></strong><br>
                                <small class="text-muted"><?php echo $student['course_name']; ?></small>
                            <?php else: ?>
                                <span class="text-muted">No course assigned</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Year Level</label>
                        <p class="mb-0">
                            <span class="badge bg-secondary">Year <?php echo $student['year_level']; ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Grades History
                </h5>
                <a href="../grades/add.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Add Grade
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($grades)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Semester</th>
                                    <th>Credits</th>
                                    <th>Assignment</th>
                                    <th>Quiz</th>
                                    <th>Midterm</th>
                                    <th>Final</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $grade['subject_code']; ?></strong><br>
                                            <small class="text-muted"><?php echo $grade['subject_name']; ?></small>
                                        </td>
                                        <td><?php echo $grade['semester']; ?></td>
                                        <td><?php echo $grade['credits']; ?></td>
                                        <td><?php echo number_format($grade['assignment_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['quiz_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['midterm_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['final_marks'], 1); ?></td>
                                        <td><strong><?php echo number_format($grade['total_marks'], 1); ?></strong></td>
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
                                            <span class="badge bg-<?php echo $grade_color; ?>">
                                                <?php echo $grade['grade_letter']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($gpa > 0): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h6>Current GPA</h6>
                                    <h4 class="text-primary"><?php echo number_format($gpa, 2); ?></h4>
                                </div>
                                <div class="col-md-3">
                                    <h6>Total Credits</h6>
                                    <h4 class="text-info"><?php echo $total_credits; ?></h4>
                                </div>
                                <div class="col-md-3">
                                    <h6>Subjects Taken</h6>
                                    <h4 class="text-success"><?php echo count($grades); ?></h4>
                                </div>
                                <div class="col-md-3">
                                    <h6>Academic Standing</h6>
                                    <h4 class="<?php echo $gpa >= 3.0 ? 'text-success' : ($gpa >= 2.0 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php 
                                        if ($gpa >= 3.5) echo 'Excellent';
                                        elseif ($gpa >= 3.0) echo 'Good';
                                        elseif ($gpa >= 2.0) echo 'Satisfactory';
                                        else echo 'Needs Improvement';
                                        ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-graph-up" style="font-size: 3rem; color: var(--muted);"></i>
                        <h6 class="mt-2">No Grades Available</h6>
                        <p class="text-muted">No grades have been recorded for this student yet.</p>
                        <a href="../grades/add.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add First Grade
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.5rem;
    margin: 0 auto;
}

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php require_once '../includes/footer.php'; ?>