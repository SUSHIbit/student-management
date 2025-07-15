<?php
/**
 * Add Subject Form
 * Student Management System - Phase 3
 */

// Start output buffering to prevent header issues
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

$page_title = 'Add Subject';
$breadcrumbs = [
    ['name' => 'Subjects', 'url' => 'index.php'],
    ['name' => 'Add Subject']
];

$errors = [];
$form_data = [];

// Get course_id from URL if provided (when adding from course page)
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

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

    // Check for duplicate subject code within the same course
    if (empty($errors['subject_code']) && $form_data['course_id'] > 0) {
        $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ?");
        $stmt->execute([$form_data['subject_code'], $form_data['course_id']]);
        if ($stmt->fetch()) {
            $errors['subject_code'] = 'Subject code already exists in this course.';
        }
    }

    // If no errors, insert the subject
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO subjects (subject_code, subject_name, description, credits, course_id, semester, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['subject_code'],
                $form_data['subject_name'],
                $form_data['description'],
                $form_data['credits'],
                $form_data['course_id'],
                $form_data['semester'],
                $form_data['status']
            ]);

            // Get course name for logging
            $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
            $stmt->execute([$form_data['course_id']]);
            $course = $stmt->fetch();

            // Log activity
            log_activity('Subject Added', "Added subject: {$form_data['subject_name']} ({$form_data['subject_code']}) for course {$course['course_name']}");

            // Success message and redirect
            show_alert('Subject added successfully!', 'success');
            ob_end_clean();
            header('Location: index.php');
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to add subject. Please try again.';
            error_log("Error adding subject: " . $e->getMessage());
        }
    }
}

// Get courses for dropdown
$courses = get_all_courses();

// Pre-select course if provided in URL
if ($course_id > 0) {
    $form_data['course_id'] = $course_id;
}

// Include header after all processing
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add New Subject</h1>
        <p class="text-muted">Create a new subject for a course</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Subjects
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
                                <option value="active" <?php echo ($form_data['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
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
                            <option value="3" <?php echo ($form_data['credits'] ?? 3) == 3 ? 'selected' : ''; ?>>3 Credits</option>
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
                            <i class="bi bi-save"></i> Add Subject
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
                    Subject Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Subject Code</h6>
                <p class="small text-muted">
                    Use a clear abbreviation (2-10 characters). 
                    Examples: CS101, MATH201, ENG102
                </p>

                <h6>Subject Name</h6>
                <p class="small text-muted">
                    Full descriptive name of the subject. 
                    Examples: "Introduction to Programming", "Calculus I"
                </p>

                <h6>Credit Hours</h6>
                <ul class="small text-muted">
                    <li><strong>1-2 Credits:</strong> Lab/Workshop subjects</li>
                    <li><strong>3 Credits:</strong> Standard lecture subjects</li>
                    <li><strong>4-6 Credits:</strong> Major/Core subjects</li>
                </ul>

                <h6>Semester Guidelines</h6>
                <p class="small text-muted">
                    Organize subjects by when they should be taken in the program. 
                    Consider prerequisites and course progression.
                </p>

                <div class="alert alert-info mt-3">
                    <small>
                        <i class="bi bi-lightbulb"></i>
                        <strong>Tip:</strong> Subject codes are automatically converted to uppercase for consistency.
                    </small>
                </div>
            </div>
        </div>

        <!-- Subject Examples -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-clipboard-check"></i>
                    Example Subjects
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="border-bottom pb-2 mb-2">
                        <strong>CS101</strong> - Intro to Programming<br>
                        <span class="text-muted">3 credits, Semester 1</span>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <strong>MATH201</strong> - Calculus I<br>
                        <span class="text-muted">4 credits, Semester 2</span>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <strong>ENG102</strong> - Technical Writing<br>
                        <span class="text-muted">2 credits, Semester 1</span>
                    </div>
                    <div class="pb-2">
                        <strong>CS301</strong> - Database Systems<br>
                        <span class="text-muted">3 credits, Semester 5</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-uppercase subject code
document.getElementById('subject_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Generate subject code suggestion based on course and subject name
document.getElementById('subject_name').addEventListener('input', function() {
    const subjectName = this.value;
    const courseSelect = document.getElementById('course_id');
    const subjectCodeField = document.getElementById('subject_code');
    
    if (!subjectCodeField.value && subjectName.length > 3 && courseSelect.value) {
        // Get course code from selected option
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const courseCode = selectedOption.text.split(' - ')[0];
        
        // Extract first letters of subject name words
        const words = subjectName.split(' ');
        let suggestion = courseCode;
        
        // Add a number (you could make this smarter)
        suggestion += '101'; // Default to 101, could be made dynamic
        
        if (suggestion.length >= 4 && suggestion.length <= 10) {
            subjectCodeField.value = suggestion;
        }
    }
});

// Update subject code suggestion when course changes
document.getElementById('course_id').addEventListener('change', function() {
    const subjectName = document.getElementById('subject_name').value;
    if (subjectName) {
        // Trigger the subject name event to regenerate code
        document.getElementById('subject_name').dispatchEvent(new Event('input'));
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>