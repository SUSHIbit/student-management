<?php
/**
 * Add Course Form
 * Student Management System - Phase 3
 */

$page_title = 'Add Course';
$breadcrumbs = [
    ['name' => 'Courses', 'url' => 'index.php'],
    ['name' => 'Add Course']
];

require_once '../includes/header.php';

$errors = [];
$form_data = [];

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

    // Check for duplicate course code
    if (empty($errors['course_code'])) {
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
        $stmt->execute([$form_data['course_code']]);
        if ($stmt->fetch()) {
            $errors['course_code'] = 'Course code already exists.';
        }
    }

    // Check for duplicate course name
    if (empty($errors['course_name'])) {
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_name = ?");
        $stmt->execute([$form_data['course_name']]);
        if ($stmt->fetch()) {
            $errors['course_name'] = 'Course name already exists.';
        }
    }

    // If no errors, insert the course
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO courses (course_code, course_name, description, duration_years, status) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['course_code'],
                $form_data['course_name'],
                $form_data['description'],
                $form_data['duration_years'],
                $form_data['status']
            ]);

            // Log activity
            log_activity('Course Added', "Added course: {$form_data['course_name']} ({$form_data['course_code']})");

            // Success message and redirect
            show_alert('Course added successfully!', 'success');
            header('Location: index.php');
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to add course. Please try again.';
            error_log("Error adding course: " . $e->getMessage());
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add New Course</h1>
        <p class="text-muted">Create a new academic course program</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Courses
    </a>
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
                                <option value="active" <?php echo ($form_data['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
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
                            <option value="4" <?php echo ($form_data['duration_years'] ?? 4) == 4 ? 'selected' : ''; ?>>4 Years (Bachelor)</option>
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
                            <i class="bi bi-save"></i> Add Course
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Help sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Course Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Course Code</h6>
                <p class="small text-muted">
                    Use a short, memorable abbreviation (2-10 characters). 
                    Examples: CS, IT, SE, BBA, MBA
                </p>

                <h6>Course Name</h6>
                <p class="small text-muted">
                    Full official name of the degree program. 
                    Examples: "Bachelor of Computer Science", "Master of Business Administration"
                </p>

                <h6>Duration Guidelines</h6>
                <ul class="small text-muted">
                    <li><strong>1 Year:</strong> Certificate programs</li>
                    <li><strong>2 Years:</strong> Diploma programs</li>
                    <li><strong>3 Years:</strong> Bachelor's (some regions)</li>
                    <li><strong>4 Years:</strong> Standard Bachelor's</li>
                    <li><strong>5-6 Years:</strong> Professional programs</li>
                </ul>

                <h6>Status Options</h6>
                <ul class="small text-muted">
                    <li><strong>Active:</strong> Currently offered course</li>
                    <li><strong>Inactive:</strong> Not accepting new students</li>
                </ul>

                <div class="alert alert-info mt-3">
                    <small>
                        <i class="bi bi-lightbulb"></i>
                        <strong>Tip:</strong> Course codes are automatically converted to uppercase for consistency.
                    </small>
                </div>
            </div>
        </div>

        <!-- Course Examples -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-clipboard-check"></i>
                    Example Courses
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="border-bottom pb-2 mb-2">
                        <strong>CS</strong> - Computer Science<br>
                        <span class="text-muted">4 years, Bachelor's program</span>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <strong>IT</strong> - Information Technology<br>
                        <span class="text-muted">4 years, Bachelor's program</span>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <strong>SE</strong> - Software Engineering<br>
                        <span class="text-muted">4 years, Bachelor's program</span>
                    </div>
                    <div class="pb-2">
                        <strong>MBA</strong> - Master of Business Admin<br>
                        <span class="text-muted">2 years, Master's program</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-uppercase course code
document.getElementById('course_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Generate course code suggestion based on course name
document.getElementById('course_name').addEventListener('input', function() {
    const courseName = this.value;
    const courseCodeField = document.getElementById('course_code');
    
    if (!courseCodeField.value && courseName.length > 3) {
        // Extract first letters of each word
        const words = courseName.split(' ');
        let suggestion = '';
        
        words.forEach(word => {
            if (word.length > 2) { // Only take letters from significant words
                suggestion += word.charAt(0).toUpperCase();
            }
        });
        
        if (suggestion.length >= 2 && suggestion.length <= 4) {
            courseCodeField.value = suggestion;
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>