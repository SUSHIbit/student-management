<?php
/**
 * Edit Grade Form
 * Student Management System - Phase 4
 */

// Start output buffering to prevent header issues
ob_start();

// Get grade ID from URL
$grade_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($grade_id <= 0) {
    ob_end_clean();
    header('Location: index.php');
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get grade details
$stmt = $pdo->prepare("
    SELECT g.*, s.first_name, s.last_name, s.student_number, 
           sub.subject_code, sub.subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.grade_id = ?
");
$stmt->execute([$grade_id]);
$grade = $stmt->fetch();

if (!$grade) {
    show_alert('Grade not found.', 'danger');
    ob_end_clean();
    header('Location: index.php');
    exit();
}

$page_title = 'Edit Grade';
$breadcrumbs = [
    ['name' => 'Grades', 'url' => 'index.php'],
    ['name' => $grade['first_name'] . ' ' . $grade['last_name'], 'url' => 'view.php?id=' . $grade_id],
    ['name' => 'Edit']
];

$errors = [];
$form_data = $grade; // Pre-fill with existing data

// Grade calculation function
function calculate_grade($total_marks) {
    if ($total_marks >= 90) return 'A+';
    elseif ($total_marks >= 85) return 'A';
    elseif ($total_marks >= 80) return 'A-';
    elseif ($total_marks >= 75) return 'B+';
    elseif ($total_marks >= 70) return 'B';
    elseif ($total_marks >= 65) return 'B-';
    elseif ($total_marks >= 60) return 'C+';
    elseif ($total_marks >= 55) return 'C';
    elseif ($total_marks >= 50) return 'C-';
    elseif ($total_marks >= 40) return 'D';
    else return 'F';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean form data
    $form_data = [
        'student_id' => (int)$_POST['student_id'],
        'subject_id' => (int)$_POST['subject_id'],
        'assignment_marks' => (float)$_POST['assignment_marks'],
        'quiz_marks' => (float)$_POST['quiz_marks'],
        'midterm_marks' => (float)$_POST['midterm_marks'],
        'final_marks' => (float)$_POST['final_marks'],
        'semester' => (int)$_POST['semester'],
        'academic_year' => clean_input($_POST['academic_year'])
    ];

    // Calculate total marks (you can adjust weightings as needed)
    $assignment_weight = 0.2; // 20%
    $quiz_weight = 0.2;       // 20%
    $midterm_weight = 0.3;    // 30%
    $final_weight = 0.3;      // 30%

    $form_data['total_marks'] = 
        ($form_data['assignment_marks'] * $assignment_weight) +
        ($form_data['quiz_marks'] * $quiz_weight) +
        ($form_data['midterm_marks'] * $midterm_weight) +
        ($form_data['final_marks'] * $final_weight);

    $form_data['grade_letter'] = calculate_grade($form_data['total_marks']);

    // Validation
    if ($form_data['student_id'] <= 0) {
        $errors['student_id'] = 'Please select a student.';
    }

    if ($form_data['subject_id'] <= 0) {
        $errors['subject_id'] = 'Please select a subject.';
    }

    // Validate marks (0-100)
    $mark_fields = ['assignment_marks', 'quiz_marks', 'midterm_marks', 'final_marks'];
    foreach ($mark_fields as $field) {
        if ($form_data[$field] < 0 || $form_data[$field] > 100) {
            $field_name = ucwords(str_replace('_', ' ', $field));
            $errors[$field] = "$field_name must be between 0 and 100.";
        }
    }

    if ($form_data['semester'] < 1 || $form_data['semester'] > 8) {
        $errors['semester'] = 'Semester must be between 1 and 8.';
    }

    if (empty($form_data['academic_year'])) {
        $errors['academic_year'] = 'Academic year is required.';
    }

    // Check for duplicate grade entry (excluding current grade)
    if ($form_data['student_id'] > 0 && $form_data['subject_id'] > 0 && $form_data['semester'] > 0) {
        $stmt = $pdo->prepare("
            SELECT grade_id FROM grades 
            WHERE student_id = ? AND subject_id = ? AND semester = ? AND academic_year = ? AND grade_id != ?
        ");
        $stmt->execute([$form_data['student_id'], $form_data['subject_id'], $form_data['semester'], $form_data['academic_year'], $grade_id]);
        if ($stmt->fetch()) {
            $errors['general'] = 'Grade already exists for this student, subject, and semester.';
        }
    }

    // If no errors, update the grade
    if (empty($errors)) {
        try {
            $sql = "UPDATE grades SET 
                    student_id = ?, subject_id = ?, assignment_marks = ?, quiz_marks = ?, 
                    midterm_marks = ?, final_marks = ?, total_marks = ?, grade_letter = ?, 
                    semester = ?, academic_year = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE grade_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['student_id'],
                $form_data['subject_id'],
                $form_data['assignment_marks'],
                $form_data['quiz_marks'],
                $form_data['midterm_marks'],
                $form_data['final_marks'],
                $form_data['total_marks'],
                $form_data['grade_letter'],
                $form_data['semester'],
                $form_data['academic_year'],
                $grade_id
            ]);

            // Get student and subject names for logging
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
            $stmt->execute([$form_data['student_id']]);
            $student = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT subject_code FROM subjects WHERE subject_id = ?");
            $stmt->execute([$form_data['subject_id']]);
            $subject = $stmt->fetch();

            // Log activity
            log_activity('Grade Updated', "Updated grade for {$student['first_name']} {$student['last_name']} in {$subject['subject_code']}: {$form_data['grade_letter']}");

            // Success message and redirect
            show_alert('Grade updated successfully!', 'success');
            ob_end_clean();
            header('Location: view.php?id=' . $grade_id);
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to update grade. Please try again.';
            error_log("Error updating grade: " . $e->getMessage());
        }
    }
}

// Get students for dropdown
$students_sql = "
    SELECT s.student_id, s.student_number, s.first_name, s.last_name, c.course_code, s.year_level
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    WHERE s.status = 'active' 
    ORDER BY s.last_name, s.first_name
";
$students = $pdo->query($students_sql)->fetchAll();

// Get subjects for dropdown
$subjects_sql = "
    SELECT sub.subject_id, sub.subject_code, sub.subject_name, sub.credits, sub.semester, c.course_code
    FROM subjects sub 
    LEFT JOIN courses c ON sub.course_id = c.course_id 
    WHERE sub.status = 'active' 
    ORDER BY c.course_code, sub.semester, sub.subject_code
";
$subjects = $pdo->query($subjects_sql)->fetchAll();

// Include header after all processing
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Grade</h1>
        <p class="text-muted">Update grade information</p>
    </div>
    <div class="btn-group" role="group">
        <a href="view.php?id=<?php echo $grade_id; ?>" class="btn btn-outline-info">
            <i class="bi bi-eye"></i> View Details
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo $errors['general']; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="" class="needs-validation" novalidate id="gradeForm">
            <!-- Current Grade Info -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i>
                        Current Grade Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Student:</strong> <?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?><br>
                            <strong>Student Number:</strong> <?php echo $grade['student_number']; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Subject:</strong> <?php echo $grade['subject_code'] . ' - ' . $grade['subject_name']; ?><br>
                            <strong>Current Grade:</strong> <span class="badge bg-primary"><?php echo $grade['grade_letter']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student and Subject Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person-check"></i>
                        Student & Subject Selection
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">
                                Student <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['student_id']) ? 'is-invalid' : ''; ?>" 
                                    id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>" 
                                            <?php echo ($form_data['student_id'] ?? 0) == $student['student_id'] ? 'selected' : ''; ?>
                                            data-course="<?php echo $student['course_code']; ?>"
                                            data-year="<?php echo $student['year_level']; ?>">
                                        <?php echo $student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']; ?>
                                        (<?php echo $student['course_code']; ?> - Year <?php echo $student['year_level']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['student_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['student_id']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">
                                Subject <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['subject_id']) ? 'is-invalid' : ''; ?>" 
                                    id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>" 
                                            <?php echo ($form_data['subject_id'] ?? 0) == $subject['subject_id'] ? 'selected' : ''; ?>
                                            data-credits="<?php echo $subject['credits']; ?>"
                                            data-semester="<?php echo $subject['semester']; ?>"
                                            data-course="<?php echo $subject['course_code']; ?>">
                                        <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                                        (<?php echo $subject['course_code']; ?> - Sem <?php echo $subject['semester']; ?> - <?php echo $subject['credits']; ?> Credits)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['subject_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['subject_id']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="semester" class="form-label">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['semester']) ? 'is-invalid' : ''; ?>" 
                                    id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($form_data['semester'] ?? 0) == $i ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <?php if (isset($errors['semester'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['semester']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="academic_year" class="form-label">
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['academic_year']) ? 'is-invalid' : ''; ?>" 
                                   id="academic_year" name="academic_year" 
                                   value="<?php echo htmlspecialchars($form_data['academic_year'] ?? ''); ?>" 
                                   placeholder="e.g., 2024/2025" required>
                            <?php if (isset($errors['academic_year'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['academic_year']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grade Entry -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calculator"></i>
                        Grade Components
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <small>
                            <strong>Grading Weights:</strong> 
                            Assignment (20%) + Quiz (20%) + Midterm (30%) + Final (30%) = Total Grade
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assignment_marks" class="form-label">
                                Assignment Marks (20%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control <?php echo isset($errors['assignment_marks']) ? 'is-invalid' : ''; ?>" 
                                       id="assignment_marks" name="assignment_marks" 
                                       value="<?php echo htmlspecialchars($form_data['assignment_marks'] ?? ''); ?>" 
                                       min="0" max="100" step="0.1" required>
                                <span class="input-group-text">%</span>
                                <?php if (isset($errors['assignment_marks'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['assignment_marks']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="quiz_marks" class="form-label">
                                Quiz Marks (20%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control <?php echo isset($errors['quiz_marks']) ? 'is-invalid' : ''; ?>" 
                                       id="quiz_marks" name="quiz_marks" 
                                       value="<?php echo htmlspecialchars($form_data['quiz_marks'] ?? ''); ?>" 
                                       min="0" max="100" step="0.1" required>
                                <span class="input-group-text">%</span>
                                <?php if (isset($errors['quiz_marks'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['quiz_marks']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="midterm_marks" class="form-label">
                                Midterm Marks (30%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control <?php echo isset($errors['midterm_marks']) ? 'is-invalid' : ''; ?>" 
                                       id="midterm_marks" name="midterm_marks" 
                                       value="<?php echo htmlspecialchars($form_data['midterm_marks'] ?? ''); ?>" 
                                       min="0" max="100" step="0.1" required>
                                <span class="input-group-text">%</span>
                                <?php if (isset($errors['midterm_marks'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['midterm_marks']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="final_marks" class="form-label">
                                Final Marks (30%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control <?php echo isset($errors['final_marks']) ? 'is-invalid' : ''; ?>" 
                                       id="final_marks" name="final_marks" 
                                       value="<?php echo htmlspecialchars($form_data['final_marks'] ?? ''); ?>" 
                                       min="0" max="100" step="0.1" required>
                                <span class="input-group-text">%</span>
                                <?php if (isset($errors['final_marks'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['final_marks']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Preview -->
                    <div class="grade-preview mt-4 p-3 bg-light rounded" id="gradePreview">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h6>Total Marks</h6>
                                <h3 class="text-primary" id="totalMarks"><?php echo number_format($grade['total_marks'], 1); ?></h3>
                                <small class="text-muted">Weighted Average</small>
                            </div>
                            <div class="col-md-4">
                                <h6>Letter Grade</h6>
                                <h3 class="text-success" id="letterGrade"><?php echo $grade['grade_letter']; ?></h3>
                                <small class="text-muted">Final Grade</small>
                            </div>
                            <div class="col-md-4">
                                <h6>Grade Points</h6>
                                <h3 class="text-info" id="gradePoints">
                                    <?php
                                    $gpa_map = ['A+' => 4.0, 'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 'D' => 1.0, 'F' => 0.0];
                                    echo number_format($gpa_map[$grade['grade_letter']] ?? 0, 1);
                                    ?>
                                </h3>
                                <small class="text-muted">GPA Contribution</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Grade
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Grade Scale & Information -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Grading Scale
                </h6>
            </div>
            <div class="card-body">
                <div class="grade-scale">
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">A+</span>
                        <span>90 - 100%</span>
                        <span class="text-muted">4.0</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">A</span>
                        <span>85 - 89%</span>
                        <span class="text-muted">4.0</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">A-</span>
                        <span>80 - 84%</span>
                        <span class="text-muted">3.7</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary">B+</span>
                        <span>75 - 79%</span>
                        <span class="text-muted">3.3</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary">B</span>
                        <span>70 - 74%</span>
                        <span class="text-muted">3.0</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary">B-</span>
                        <span>65 - 69%</span>
                        <span class="text-muted">2.7</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning">C+</span>
                        <span>60 - 64%</span>
                        <span class="text-muted">2.3</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning">C</span>
                        <span>55 - 59%</span>
                        <span class="text-muted">2.0</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning">C-</span>
                        <span>50 - 54%</span>
                        <span class="text-muted">1.7</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-danger">D</span>
                        <span>40 - 49%</span>
                        <span class="text-muted">1.0</span>
                    </div>
                    <div class="grade-item d-flex justify-content-between align-items-center">
                        <span class="badge bg-danger">F</span>
                        <span>0 - 39%</span>
                        <span class="text-muted">0.0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Important Notes
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <small>
                        <strong>Editing Impact:</strong><br>
                        • Changes will affect student's GPA<br>
                        • Updated grade will appear on transcripts<br>
                        • Academic standing may be affected
                    </small>
                </div>
                
                <div class="d-grid">
                    <a href="view.php?id=<?php echo $grade_id; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Grade Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grade calculation
function calculateGrade() {
    const assignment = parseFloat(document.getElementById('assignment_marks').value) || 0;
    const quiz = parseFloat(document.getElementById('quiz_marks').value) || 0;
    const midterm = parseFloat(document.getElementById('midterm_marks').value) || 0;
    const final = parseFloat(document.getElementById('final_marks').value) || 0;
    
    // Calculate weighted total
    const total = (assignment * 0.2) + (quiz * 0.2) + (midterm * 0.3) + (final * 0.3);
    
    // Determine letter grade
    let letterGrade = 'F';
    let gradePoints = 0.0;
    
    if (total >= 90) { letterGrade = 'A+'; gradePoints = 4.0; }
    else if (total >= 85) { letterGrade = 'A'; gradePoints = 4.0; }
    else if (total >= 80) { letterGrade = 'A-'; gradePoints = 3.7; }
    else if (total >= 75) { letterGrade = 'B+'; gradePoints = 3.3; }
    else if (total >= 70) { letterGrade = 'B'; gradePoints = 3.0; }
    else if (total >= 65) { letterGrade = 'B-'; gradePoints = 2.7; }
    else if (total >= 60) { letterGrade = 'C+'; gradePoints = 2.3; }
    else if (total >= 55) { letterGrade = 'C'; gradePoints = 2.0; }
    else if (total >= 50) { letterGrade = 'C-'; gradePoints = 1.7; }
    else if (total >= 40) { letterGrade = 'D'; gradePoints = 1.0; }
    
    // Update preview
    document.getElementById('totalMarks').textContent = total.toFixed(1);
    document.getElementById('letterGrade').textContent = letterGrade;
    document.getElementById('gradePoints').textContent = gradePoints.toFixed(1);
}

// Auto-fill semester based on subject selection
document.getElementById('subject_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.dataset.semester) {
        document.getElementById('semester').value = selectedOption.dataset.semester;
    }
});

// Add event listeners for real-time calculation
document.getElementById('assignment_marks').addEventListener('input', calculateGrade);
document.getElementById('quiz_marks').addEventListener('input', calculateGrade);
document.getElementById('midterm_marks').addEventListener('input', calculateGrade);
document.getElementById('final_marks').addEventListener('input', calculateGrade);

// Initialize calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateGrade();
});
</script>

<?php require_once '../includes/footer.php'; ?>