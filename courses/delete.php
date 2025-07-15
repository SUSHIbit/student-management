<?php
/**
 * Delete Course
 * Student Management System - Phase 3
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get course ID from URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    show_alert('Invalid course ID.', 'danger');
    header('Location: index.php');
    exit();
}

// Get course details
$course = get_course_by_id($course_id);

if (!$course) {
    show_alert('Course not found.', 'danger');
    header('Location: index.php');
    exit();
}

// Get related data counts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE course_id = ?");
$stmt->execute([$course_id]);
$student_count = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subjects WHERE course_id = ?");
$stmt->execute([$course_id]);
$subject_count = $stmt->fetch()['count'];

// Count grades related to this course
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM grades g 
    JOIN students s ON g.student_id = s.student_id 
    WHERE s.course_id = ?
");
$stmt->execute([$course_id]);
$grade_count = $stmt->fetch()['count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete grades for students in this course
            $pdo->prepare("
                DELETE g FROM grades g 
                JOIN students s ON g.student_id = s.student_id 
                WHERE s.course_id = ?
            ")->execute([$course_id]);
            
            // Delete subjects for this course
            $pdo->prepare("DELETE FROM subjects WHERE course_id = ?")->execute([$course_id]);
            
            // Update students to remove course reference
            $pdo->prepare("UPDATE students SET course_id = NULL WHERE course_id = ?")->execute([$course_id]);
            
            // Delete the course
            $pdo->prepare("DELETE FROM courses WHERE course_id = ?")->execute([$course_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log activity
            log_activity('Course Deleted', "Deleted course: {$course['course_name']} ({$course['course_code']})");
            
            // Success message and redirect
            show_alert('Course deleted successfully.', 'success');
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            
            show_alert('Failed to delete course. Please try again.', 'danger');
            error_log("Error deleting course: " . $e->getMessage());
            header('Location: index.php');
            exit();
        }
    } else {
        // User cancelled
        header('Location: view.php?id=' . $course_id);
        exit();
    }
}

$page_title = 'Delete Course';
$breadcrumbs = [
    ['name' => 'Courses', 'url' => 'index.php'],
    ['name' => $course['course_name'], 'url' => 'view.php?id=' . $course_id],
    ['name' => 'Delete']
];

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-danger">Delete Course</h1>
        <p class="text-muted">This action cannot be undone</p>
    </div>
    <a href="view.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Course
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Course Deletion
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Warning: This action is irreversible!
                    </h6>
                    <p class="mb-0">
                        You are about to permanently delete this course and all related data. 
                        This includes subjects, student enrollments, grades, and other associated information.
                    </p>
                </div>

                <!-- Course Information Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="course-icon mb-3">
                                    <?php echo strtoupper($course['course_code']); ?>
                                </div>
                                <h6><?php echo $course['course_name']; ?></h6>
                                <p class="text-muted small"><?php echo $course['course_code']; ?></p>
                                <?php
                                $status_color = $course['status'] === 'active' ? 'success' : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_color; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Course Details:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Course Code:</strong> <?php echo $course['course_code']; ?></li>
                            <li><strong>Course Name:</strong> <?php echo $course['course_name']; ?></li>
                            <li><strong>Duration:</strong> <?php echo $course['duration_years']; ?> Years</li>
                            <li><strong>Status:</strong> <?php echo ucfirst($course['status']); ?></li>
                            <li><strong>Created:</strong> <?php echo format_date($course['created_at']); ?></li>
                            <li><strong>Description:</strong> <?php echo $course['description'] ? substr($course['description'], 0, 100) . '...' : 'No description'; ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Data Impact Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-danger">Data that will be permanently deleted:</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-book-fill text-primary" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Course Record</h5>
                                        <p class="small text-muted">Course information and details</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-people-fill text-warning" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2"><?php echo $student_count; ?> Students</h5>
                                        <p class="small text-muted">Will be unassigned from course</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-journal-text text-success" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2"><?php echo $subject_count; ?> Subjects</h5>
                                        <p class="small text-muted">All course subjects</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-graph-up text-danger" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2"><?php echo $grade_count; ?> Grades</h5>
                                        <p class="small text-muted">All student grades</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Impact Analysis -->
                <?php if ($student_count > 0 || $subject_count > 0): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Critical Impact Warning:</h6>
                        <ul class="mb-0">
                            <?php if ($student_count > 0): ?>
                                <li><strong><?php echo $student_count; ?> students</strong> will lose their course assignment</li>
                            <?php endif; ?>
                            <?php if ($subject_count > 0): ?>
                                <li><strong><?php echo $subject_count; ?> subjects</strong> will be permanently deleted</li>
                            <?php endif; ?>
                            <?php if ($grade_count > 0): ?>
                                <li><strong><?php echo $grade_count; ?> grade records</strong> will be permanently lost</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Confirmation Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="understand" required>
                            <label class="form-check-label" for="understand">
                                I understand that this action cannot be undone and all course data will be permanently deleted.
                            </label>
                        </div>
                    </div>

                    <?php if ($student_count > 0 || $subject_count > 0): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="impact_understood" required>
                                <label class="form-check-label" for="impact_understood">
                                    I acknowledge that <?php echo $student_count; ?> students and <?php echo $subject_count; ?> subjects will be affected.
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="confirmation" class="form-label">
                            Type "DELETE COURSE" to confirm (case sensitive):
                        </label>
                        <input type="text" class="form-control" id="confirmation" placeholder="Type DELETE COURSE here" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='view.php?id=<?php echo $course_id; ?>'">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="bi bi-trash-fill"></i> Delete Course Permanently
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alternative Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-lightbulb"></i>
                    Alternative Actions
                </h6>
            </div>
            <div class="card-body">
                <p>Instead of deleting, you might want to consider:</p>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="edit.php?id=<?php echo $course_id; ?>" class="btn btn-outline-warning w-100">
                            <i class="bi bi-pencil"></i> Edit Course Information
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="setCourseInactive()">
                            <i class="bi bi-pause-circle"></i> Set as Inactive
                        </button>
                    </div>
                </div>
                <p class="small text-muted mt-2">
                    Setting a course as "Inactive" preserves all data while preventing new enrollments.
                </p>
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
</style>

<script>
// Enable delete button only when confirmation is typed correctly
function checkConfirmation() {
    const confirmationText = document.getElementById('confirmation').value;
    const deleteBtn = document.getElementById('deleteBtn');
    const understandCheck = document.getElementById('understand');
    const impactCheck = document.getElementById('impact_understood');
    
    let allChecked = understandCheck.checked;
    if (impactCheck) {
        allChecked = allChecked && impactCheck.checked;
    }
    
    if (confirmationText === 'DELETE COURSE' && allChecked) {
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('btn-secondary');
        deleteBtn.classList.add('btn-danger');
    } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove('btn-danger');
        deleteBtn.classList.add('btn-secondary');
    }
}

document.getElementById('confirmation').addEventListener('input', checkConfirmation);
document.getElementById('understand').addEventListener('change', checkConfirmation);

const impactCheck = document.getElementById('impact_understood');
if (impactCheck) {
    impactCheck.addEventListener('change', checkConfirmation);
}

function setCourseInactive() {
    if (confirm('Set this course as inactive? This will preserve all data while preventing new enrollments.')) {
        // Create a form to submit status change
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit.php?id=<?php echo $course_id; ?>';
        
        // Add all current course data as hidden inputs
        const fields = [
            {name: 'course_code', value: '<?php echo addslashes($course['course_code']); ?>'},
            {name: 'course_name', value: '<?php echo addslashes($course['course_name']); ?>'},
            {name: 'description', value: '<?php echo addslashes($course['description']); ?>'},
            {name: 'duration_years', value: '<?php echo $course['duration_years']; ?>'},
            {name: 'status', value: 'inactive'}
        ];
        
        fields.forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>