<?php
/**
 * Edit Course Form
 * Student Management System - Phase 3
 */

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    header('Location: index.php');
    exit();
}

require_once '../includes/header.php';

// Get course details
$course = get_course_by_id($course_id);

if (!$course) {
    show_alert('Course not found.', 'danger');
    header('Location: index.php');
    exit();
}

$page_title = 'Edit Course';
$breadcrumbs = [
    ['name' => 'Courses', 'url' => 'index.php'],
    ['name' => $course['course_name'], 'url' => 'view.php?id=' . $course_id],
    ['name' => 'Edit']
];

$errors = [];
$form_data = $course; // Pre-fill with existing data

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean form data
    $form_data = [
        'course_code' => strtoupper(clean_input($_POST['course_code'])),
        'course_name' => clean_input($_POST['course_name']),
        'description' => clean_input($_POST['description']),
        'duration_years' => (int)$_POST['duration_years'],
        'status' => clean_input($_POST['status'])
    ];

    // Validation
    if (empty($form_data['course_code'])) {
        $errors['course_code'] = 'Course code is required.';
    } elseif (strlen($form_data['course_code']) < 2 || strlen($form_data['course_code']) > 10) {
        $errors['course_code'] = 'Course code must be between 2-10 characters.';
    }

    if (empty($form_data['course_name'])) {
        $errors['course_name'] = 'Course name is required.';
    } elseif (strlen($form_data['course_name']) < 3) {
        $errors['course_name'] = 'Course name must be at least 3 characters.';
    }

    if ($form_data['duration_years'] < 1 || $form_data['duration_years'] > 6) {
        $errors['duration_years'] = 'Duration must be between 1 and 6 years.';
    }

    // Check for duplicate course code (excluding current course)
    if (empty($errors['course_code'])) {
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?");
        $stmt->execute([$form_data['course_code'], $course_id]);
        if ($stmt->fetch()) {
            $errors['course_code'] = 'Course code already exists.';
        }
    }

    // Check for duplicate course name (excluding current course)
    if (empty($errors['course_name'])) {
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_name = ? AND course_id != ?");
        $stmt->execute([$form_data['course_name'], $course_id]);
        if ($stmt->fetch()) {
            $errors['course_name'] = 'Course name already exists.';
        }
    }

    // If no errors, update the course
    if (empty($errors)) {
        try {
            $sql = "UPDATE courses SET 
                    course_code = ?, course_name = ?, description = ?, 
                    duration_years = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE course_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['course_code'],
                $form_data['course_name'],
                $form_data['description'],
                $form_data['duration_years'],
                $form_data['status'],
                $course_id
            ]);

            // Log activity
            log_activity('Course Updated', "Updated course: {$form_data['course_name']} ({$form_data['course_code']})");

            // Success message and redirect
            show_alert('Course updated successfully!', 'success');
            header('Location: view.php?id=' . $course_id);
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to update course. Please try again.';
            error_log("Error updating course: " . $e->getMessage());
        }
    }
}

// Get course statistics for the sidebar
$stmt = $pdo->prepare("SELECT COUNT(*) as student_count FROM students WHERE course_id = ?");
$stmt->execute([$course_id]);
$student_count = $stmt->fetch()['student_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as subject_count FROM subjects WHERE course_id = ?");
$stmt->execute([$course_id]);
$subject_count = $stmt->fetch()['subject_count'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Course</h1>
        <p class="text-muted">Update course information</p>
    </div>
    <div class="btn-group" role="group">
        <a href="view.php?id=<?php echo $course_id; ?>" class="btn btn-outline-info">
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
                        <i class="bi bi-book-fill"></i>
                        Course Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_code" class="form-label">
                                Course Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase <?php echo isset($errors['course_code']) ? 'is-invalid' : ''; ?>" 
                                   id="course_code" name="course_code" 
                                   value="<?php echo htmlspecialchars($form_data['course_code'] ?? ''); ?>" 
                                   placeholder="e.g., CS, IT, SE" 
                                   maxlength="10" required>
                            <div class="form-text">Short code to identify the course (2-10 characters)</div>
                            <?php if (isset($errors['course_code'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['course_code']; ?></div>
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
                            <div class="form-text">Course availability status</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="course_name" class="form-label">
                            Course Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control <?php echo isset($errors['course_name']) ? 'is-invalid' : ''; ?>" 
                               id="course_name" name="course_name" 
                               value="<?php echo htmlspecialchars($form_data['course_name'] ?? ''); ?>" 
                               placeholder="e.g., Bachelor of Computer Science" 
                               required>
                        <div class="form-text">Full name of the course program</div>
                        <?php if (isset($errors['course_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['course_name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="duration_years" class="form-label">
                            Duration (Years) <span class="text-danger">*</span>
                        </label>
                        <select class="form-select <?php echo isset($errors['duration_years']) ? 'is-invalid' : ''; ?>" 
                                id="duration_years" name="duration_years" required>
                            <option value="">Select Duration</option>
                            <option value="1" <?php echo ($form_data['duration_years'] ?? 0) == 1 ? 'selected' : ''; ?>>1 Year (Certificate)</option>
                            <option value="2" <?php echo ($form_data['duration_years'] ?? 0) == 2 ? 'selected' : ''; ?>>2 Years (Diploma)</option>
                            <option value="3" <?php echo ($form_data['duration_years'] ?? 0) == 3 ? 'selected' : ''; ?>>3 Years (Degree)</option>
                            <option value="4" <?php echo ($form_data['duration_years'] ?? 0) == 4 ? 'selected' : ''; ?>>4 Years (Bachelor)</option>
                            <option value="5" <?php echo ($form_data['duration_years'] ?? 0) == 5 ? 'selected' : ''; ?>>5 Years (Extended)</option>
                            <option value="6" <?php echo ($form_data['duration_years'] ?? 0) == 6 ? 'selected' : ''; ?>>6 Years (Professional)</option>
                        </select>
                        <div class="form-text">Length of the course program</div>
                        <?php if (isset($errors['duration_years'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['duration_years']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4" placeholder="Enter course description and details..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                        <div class="form-text">Detailed description of the course program (optional)</div>
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
                            <i class="bi bi-save"></i> Update Course
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Course Summary & Impact Analysis -->
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
                    <div class="course-icon mb-2">
                        <?php echo strtoupper($course['course_code']); ?>
                    </div>
                    <h6><?php echo $course['course_name']; ?></h6>
                    <p class="text-muted small"><?php echo $course['course_code']; ?></p>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Current Status</small>
                    <br>
                    <?php
                    $status_color = $course['status'] === 'active' ? 'success' : 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $status_color; ?>">
                        <?php echo ucfirst($course['status']); ?>
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Duration</small>
                    <br>
                    <strong><?php echo $course['duration_years']; ?> Years</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Created</small>
                    <br>
                    <?php echo format_date($course['created_at']); ?>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Last Updated</small>
                    <br>
                    <?php echo format_datetime($course['updated_at']); ?>
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
                        <strong>Students Affected:</strong> <?php echo $student_count; ?><br>
                        <strong>Subjects:</strong> <?php echo $subject_count; ?><br>
                        <strong>Status Change:</strong> May affect student enrollment
                    </small>
                </div>

                <h6 class="small">Current Statistics:</h6>
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <div class="stat-card bg-primary text-white p-2 rounded">
                            <div class="stat-number"><?php echo $student_count; ?></div>
                            <div class="stat-label small">Students</div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="stat-card bg-success text-white p-2 rounded">
                            <div class="stat-number"><?php echo $subject_count; ?></div>
                            <div class="stat-label small">Subjects</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <a href="view.php?id=<?php echo $course_id; ?>" class="btn btn-outline-primary btn-sm">
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
                    <a href="../subjects/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-journal-plus"></i> Add Subject
                    </a>
                    <a href="../students/index.php?course=<?php echo $course_id; ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-people"></i> View Students
                    </a>
                    <a href="../subjects/index.php?course=<?php echo $course_id; ?>" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-journal-text"></i> Manage Subjects
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.course-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
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
// Auto-uppercase course code
document.getElementById('course_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Warning for status change
document.getElementById('status').addEventListener('change', function() {
    const currentStatus = '<?php echo $course['status']; ?>';
    const studentCount = <?php echo $student_count; ?>;
    
    if (this.value !== currentStatus && studentCount > 0) {
        if (this.value === 'inactive') {
            if (!confirm(`Warning: Setting this course as inactive will affect ${studentCount} student(s). They may not be able to access course materials. Continue?`)) {
                this.value = currentStatus;
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>