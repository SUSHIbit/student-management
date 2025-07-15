<?php
/**
 * Edit Subject Form
 * Student Management System - Phase 3
 */

// Start output buffering to prevent header issues
ob_start();

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
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

// Get subject details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    show_alert('Subject not found.', 'danger');
    ob_end_clean();
    header('Location: index.php');
    exit();
}

$page_title = 'Edit Subject';
$breadcrumbs = [
    ['name' => 'Subjects', 'url' => 'index.php'],
    ['name' => $subject['subject_name'], 'url' => 'view.php?id=' . $subject_id],
    ['name' => 'Edit']
];

$errors = [];
$form_data = $subject; // Pre-fill with existing data

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean form data
    $form_data = [
        'subject_code' => strtoupper(clean_input($_POST['subject_code'])),
        'subject_name' => clean_input($_POST['subject_name']),
        'description' => clean_input($_POST['description']),
        'credits' => (int)$_POST['credits'],
        'course_id' => (int)$_POST['course_id'],
        'semester' => (int)$_POST['semester'],
        'status' => clean_input($_POST['status'])
    ];

    // Validation
    if (empty($form_data['subject_code'])) {
        $errors['subject_code'] = 'Subject code is required.';
    } elseif (strlen($form_data['subject_code']) < 2 || strlen($form_data['subject_code']) > 10) {
        $errors['subject_code'] = 'Subject code must be between 2-10 characters.';
    }

    if (empty($form_data['subject_name'])) {
        $errors['subject_name'] = 'Subject name is required.';
    } elseif (strlen($form_data['subject_name']) < 3) {
        $errors['subject_name'] = 'Subject name must be at least 3 characters.';
    }

    if ($form_data['credits'] < 1 || $form_data['credits'] > 6) {
        $errors['credits'] = 'Credits must be between 1 and 6.';
    }

    if ($form_data['course_id'] <= 0) {
        $errors['course_id'] = 'Please select a course.';
    }

    if ($form_data['semester'] < 1 || $form_data['semester'] > 8) {
        $errors['semester'] = 'Semester must be between 1 and 8.';
    }

    // Check for duplicate subject code within the same course (excluding current subject)
    if (empty($errors['subject_code']) && $form_data['course_id'] > 0) {
        $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ? AND subject_id != ?");
        $stmt->execute([$form_data['subject_code'], $form_data['course_id'], $subject_id]);
        if ($stmt->fetch()) {
            $errors['subject_code'] = 'Subject code already exists in this course.';
        }
    }

    // If no errors, update the subject
    if (empty($errors)) {
        try {
            $sql = "UPDATE subjects SET 
                    subject_code = ?, subject_name = ?, description = ?, 
                    credits = ?, course_id = ?, semester = ?, status = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE subject_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['subject_code'],
                $form_data['subject_name'],
                $form_data['description'],
                $form_data['credits'],
                $form_data['course_id'],
                $form_data['semester'],
                $form_data['status'],
                $subject_id
            ]);

            // Get course name for logging
            $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
            $stmt->execute([$form_data['course_id']]);
            $course = $stmt->fetch();

            // Log activity
            log_activity('Subject Updated', "Updated subject: {$form_data['subject_name']} ({$form_data['subject_code']}) for course {$course['course_name']}");

            // Success message and redirect
            show_alert('Subject updated successfully!', 'success');
            ob_end_clean();
            header('Location: view.php?id=' . $subject_id);
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to update subject. Please try again.';
            error_log("Error updating subject: " . $e->getMessage());
        }
    }
}

// Get courses for dropdown
$courses = get_all_courses();

// Get subject statistics for the sidebar
$stmt = $pdo->prepare("SELECT COUNT(*) as grade_count FROM grades WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$grade_count = $stmt->fetch()['grade_count'];

// Include header after all processing
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Subject</h1>
        <p class="text-muted">Update subject information</p>
    </div>
    <div class="btn-group" role="group">
        <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-info">
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
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text"></i>
                        Subject Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_code" class="form-label">
                                Subject Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase <?php echo isset($errors['subject_code']) ? 'is-invalid' : ''; ?>" 
                                   id="subject_code" name="subject_code" 
                                   value="<?php echo htmlspecialchars($form_data['subject_code'] ?? ''); ?>" 
                                   placeholder="e.g., CS101, MATH101" 
                                   maxlength="10" required>
                            <div class="form-text">Unique code to identify the subject (2-10 characters)</div>
                            <?php if (isset($errors['subject_code'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['subject_code']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo ($form_data['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($form_data['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <div class="form-text">Subject availability status</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject_name" class="form-label">
                            Subject Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control <?php echo isset($errors['subject_name']) ? 'is-invalid' : ''; ?>" 
                               id="subject_name" name="subject_name" 
                               value="<?php echo htmlspecialchars($form_data['subject_name'] ?? ''); ?>" 
                               placeholder="e.g., Introduction to Computer Science" 
                               required>
                        <div class="form-text">Full name of the subject</div>
                        <?php if (isset($errors['subject_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['subject_name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_id" class="form-label">
                                Course <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['course_id']) ? 'is-invalid' : ''; ?>" 
                                    id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>" 
                                            <?php echo ($form_data['course_id'] ?? 0) == $course['course_id'] ? 'selected' : ''; ?>>
                                        <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Course this subject belongs to</div>
                            <?php if (isset($errors['course_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['course_id']; ?></div>
                            <?php endif; ?>
                        </div>

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
                            <div class="form-text">When this subject is taught</div>
                            <?php if (isset($errors['semester'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['semester']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="credits" class="form-label">
                            Credit Hours <span class="text-danger">*</span>
                        </label>
                        <select class="form-select <?php echo isset($errors['credits']) ? 'is-invalid' : ''; ?>" 
                                id="credits" name="credits" required>
                            <option value="">Select Credits</option>
                            <option value="1" <?php echo ($form_data['credits'] ?? 0) == 1 ? 'selected' : ''; ?>>1 Credit</option>
                            <option value="2" <?php echo ($form_data['credits'] ?? 0) == 2 ? 'selected' : ''; ?>>2 Credits</option>
                            <option value="3" <?php echo ($form_data['credits'] ?? 0) == 3 ? 'selected' : ''; ?>>3 Credits</option>
                            <option value="4" <?php echo ($form_data['credits'] ?? 0) == 4 ? 'selected' : ''; ?>>4 Credits</option>
                            <option value="5" <?php echo ($form_data['credits'] ?? 0) == 5 ? 'selected' : ''; ?>>5 Credits</option>
                            <option value="6" <?php echo ($form_data['credits'] ?? 0) == 6 ? 'selected' : ''; ?>>6 Credits</option>
                        </select>
                        <div class="form-text">Number of credit hours for this subject</div>
                        <?php if (isset($errors['credits'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['credits']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4" placeholder="Enter subject description, objectives, and details..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                        <div class="form-text">Detailed description of the subject (optional)</div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Subject
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Subject Summary & Impact Analysis -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Current Information
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="subject-icon mb-2">
                        <?php echo strtoupper($subject['subject_code']); ?>
                    </div>
                    <h6><?php echo $subject['subject_name']; ?></h6>
                    <p class="text-muted small"><?php echo $subject['subject_code']; ?></p>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Current Status</small>
                    <br>
                    <?php
                    $status_color = $subject['status'] === 'active' ? 'success' : 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $status_color; ?>">
                        <?php echo ucfirst($subject['status']); ?>
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Credits</small>
                    <br>
                    <strong><?php echo $subject['credits']; ?> Credit Hours</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Semester</small>
                    <br>
                    <span class="badge bg-info">Semester <?php echo $subject['semester']; ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Created</small>
                    <br>
                    <?php echo format_date($subject['created_at']); ?>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Last Updated</small>
                    <br>
                    <?php echo format_datetime($subject['updated_at']); ?>
                </div>
            </div>
        </div>

        <!-- Impact Analysis -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Change Impact
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <small>
                        <strong>Grades Affected:</strong> <?php echo $grade_count; ?><br>
                        <strong>Status Change:</strong> May affect grade calculations<br>
                        <strong>Course Change:</strong> Will move subject to different course
                    </small>
                </div>

                <h6 class="small">Current Statistics:</h6>
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <div class="stat-card bg-primary text-white p-2 rounded">
                            <div class="stat-number"><?php echo $grade_count; ?></div>
                            <div class="stat-label small">Grades</div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="stat-card bg-success text-white p-2 rounded">
                            <div class="stat-number"><?php echo $subject['credits']; ?></div>
                            <div class="stat-label small">Credits</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Full Details
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-gear"></i>
                    Related Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../grades/add.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Add Grade
                    </a>
                    <a href="../grades/index.php?subject=<?php echo $subject_id; ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-graph-up"></i> View Grades
                    </a>
                    <a href="../courses/view.php?id=<?php echo $subject['course_id']; ?>" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-book"></i> View Course
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.subject-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    margin: 0 auto;
}

.stat-card .stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.7rem;
    opacity: 0.9;
}
</style>

<script>
// Auto-uppercase subject code
document.getElementById('subject_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Warning for course change
document.getElementById('course_id').addEventListener('change', function() {
    const currentCourse = '<?php echo $subject['course_id']; ?>';
    const gradeCount = <?php echo $grade_count; ?>;
    
    if (this.value !== currentCourse && gradeCount > 0) {
        if (!confirm(`Warning: Changing the course will affect ${gradeCount} grade record(s). Students may lose access to their grades. Continue?`)) {
            this.value = currentCourse;
        }
    }
});

// Warning for status change
document.getElementById('status').addEventListener('change', function() {
    const currentStatus = '<?php echo $subject['status']; ?>';
    const gradeCount = <?php echo $grade_count; ?>;
    
    if (this.value !== currentStatus && this.value === 'inactive' && gradeCount > 0) {
        if (!confirm(`Warning: Setting this subject as inactive will affect ${gradeCount} grade record(s). Students may not be able to access this subject. Continue?`)) {
            this.value = currentStatus;
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>