<?php
/**
 * Edit Student Form
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

$page_title = 'Edit Student';
$breadcrumbs = [
    ['name' => 'Students', 'url' => 'index.php'],
    ['name' => $student['first_name'] . ' ' . $student['last_name'], 'url' => 'view.php?id=' . $student_id],
    ['name' => 'Edit']
];

$errors = [];
$form_data = $student; // Pre-fill with existing data

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean form data
    $form_data = [
        'student_number' => clean_input($_POST['student_number']),
        'first_name' => clean_input($_POST['first_name']),
        'last_name' => clean_input($_POST['last_name']),
        'email' => clean_input($_POST['email']),
        'phone' => clean_input($_POST['phone']),
        'course_id' => (int)$_POST['course_id'],
        'year_level' => (int)$_POST['year_level'],
        'date_of_birth' => clean_input($_POST['date_of_birth']),
        'address' => clean_input($_POST['address']),
        'status' => clean_input($_POST['status'])
    ];

    // Validation
    if (empty($form_data['student_number'])) {
        $errors['student_number'] = 'Student number is required.';
    }

    if (empty($form_data['first_name'])) {
        $errors['first_name'] = 'First name is required.';
    }

    if (empty($form_data['last_name'])) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (empty($form_data['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!validate_email($form_data['email'])) {
        $errors['email'] = 'Invalid email format.';
    }

    if ($form_data['course_id'] <= 0) {
        $errors['course_id'] = 'Please select a course.';
    }

    if ($form_data['year_level'] < 1 || $form_data['year_level'] > 4) {
        $errors['year_level'] = 'Year level must be between 1 and 4.';
    }

    if (!empty($form_data['date_of_birth']) && strtotime($form_data['date_of_birth']) > time()) {
        $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
    }

    // Check for duplicate student number (excluding current student)
    if (empty($errors['student_number'])) {
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_number = ? AND student_id != ?");
        $stmt->execute([$form_data['student_number'], $student_id]);
        if ($stmt->fetch()) {
            $errors['student_number'] = 'Student number already exists.';
        }
    }

    // Check for duplicate email (excluding current student)
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
        $stmt->execute([$form_data['email'], $student_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email address already exists.';
        }
    }

    // If no errors, update the student
    if (empty($errors)) {
        try {
            $sql = "UPDATE students SET 
                    student_number = ?, first_name = ?, last_name = ?, email = ?, 
                    phone = ?, course_id = ?, year_level = ?, date_of_birth = ?, 
                    address = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE student_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['student_number'],
                $form_data['first_name'],
                $form_data['last_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['course_id'],
                $form_data['year_level'],
                !empty($form_data['date_of_birth']) ? $form_data['date_of_birth'] : null,
                $form_data['address'],
                $form_data['status'],
                $student_id
            ]);

            // Log activity
            log_activity('Student Updated', "Updated student: {$form_data['first_name']} {$form_data['last_name']} ({$form_data['student_number']})");

            // Success message and redirect
            show_alert('Student updated successfully!', 'success');
            header('Location: view.php?id=' . $student_id);
            exit();

        } catch (Exception $e) {
            $errors['general'] = 'Failed to update student. Please try again.';
            error_log("Error updating student: " . $e->getMessage());
        }
    }
}

// Get courses for dropdown
$courses = get_all_courses();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Student</h1>
        <p class="text-muted">Update student information</p>
    </div>
    <div class="btn-group" role="group">
        <a href="view.php?id=<?php echo $student_id; ?>" class="btn btn-outline-info">
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
                        <i class="bi bi-person-fill"></i>
                        Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_number" class="form-label">
                                Student Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['student_number']) ? 'is-invalid' : ''; ?>" 
                                   id="student_number" name="student_number" 
                                   value="<?php echo htmlspecialchars($form_data['student_number'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['student_number'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['student_number']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo ($form_data['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($form_data['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="graduated" <?php echo ($form_data['status'] ?? '') === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="suspended" <?php echo ($form_data['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                   id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                   id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" 
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" 
                                   id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                   placeholder="+60123456789">
                        </div>
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
                            <?php if (isset($errors['course_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['course_id']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="year_level" class="form-label">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($errors['year_level']) ? 'is-invalid' : ''; ?>" 
                                    id="year_level" name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1" <?php echo ($form_data['year_level'] ?? 0) == 1 ? 'selected' : ''; ?>>Year 1</option>
                                <option value="2" <?php echo ($form_data['year_level'] ?? 0) == 2 ? 'selected' : ''; ?>>Year 2</option>
                                <option value="3" <?php echo ($form_data['year_level'] ?? 0) == 3 ? 'selected' : ''; ?>>Year 3</option>
                                <option value="4" <?php echo ($form_data['year_level'] ?? 0) == 4 ? 'selected' : ''; ?>>Year 4</option>
                            </select>
                            <?php if (isset($errors['year_level'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['year_level']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control <?php echo isset($errors['date_of_birth']) ? 'is-invalid' : ''; ?>" 
                                   id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>">
                            <?php if (isset($errors['date_of_birth'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['date_of_birth']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" 
                                  rows="3" placeholder="Enter student's address"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
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
                            <i class="bi bi-save"></i> Update Student
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Student Summary & History -->
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
                    <div class="avatar-large mb-2">
                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                    </div>
                    <h6><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                    <p class="text-muted small"><?php echo $student['student_number']; ?></p>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Current Status</small>
                    <br>
                    <?php
                    $status_colors = [
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'graduated' => 'primary',
                        'suspended' => 'danger'
                    ];
                    $status_color = $status_colors[$student['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $status_color; ?>">
                        <?php echo ucfirst($student['status']); ?>
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Course</small>
                    <br>
                    <strong><?php echo $student['course_code'] ?? 'N/A'; ?></strong>
                    <br>
                    <small><?php echo $student['course_name'] ?? 'No course assigned'; ?></small>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Year Level</small>
                    <br>
                    <span class="badge bg-secondary">Year <?php echo $student['year_level']; ?></span>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Member Since</small>
                    <br>
                    <?php echo format_date($student['created_at']); ?>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Last Updated</small>
                    <br>
                    <?php echo format_datetime($student['updated_at']); ?>
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
                <div class="alert alert-info">
                    <small>
                        <strong>Student Number:</strong> Must be unique in the system.<br>
                        <strong>Email:</strong> Will be used for notifications.<br>
                        <strong>Status Change:</strong> Changing status may affect system access.
                    </small>
                </div>

                <div class="d-grid">
                    <a href="view.php?id=<?php echo $student_id; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Full Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
    margin: 0 auto;
}
</style>

<?php require_once '../includes/footer.php'; ?>