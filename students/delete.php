<?php
/**
 * Delete Student
 * Student Management System - Phase 2
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    show_alert('Invalid student ID.', 'danger');
    header('Location: index.php');
    exit();
}

// Get student details
$student = get_student_by_id($student_id);

if (!$student) {
    show_alert('Student not found.', 'danger');
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete related grades first (due to foreign key constraints)
            $stmt = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Delete the student
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log activity
            log_activity('Student Deleted', "Deleted student: {$student['first_name']} {$student['last_name']} ({$student['student_number']})");
            
            // Success message and redirect
            show_alert('Student deleted successfully.', 'success');
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            
            show_alert('Failed to delete student. Please try again.', 'danger');
            error_log("Error deleting student: " . $e->getMessage());
            header('Location: index.php');
            exit();
        }
    } else {
        // User cancelled
        header('Location: view.php?id=' . $student_id);
        exit();
    }
}

$page_title = 'Delete Student';
$breadcrumbs = [
    ['name' => 'Students', 'url' => 'index.php'],
    ['name' => $student['first_name'] . ' ' . $student['last_name'], 'url' => 'view.php?id=' . $student_id],
    ['name' => 'Delete']
];

require_once '../includes/header.php';

// Check if student has grades
$stmt = $pdo->prepare("SELECT COUNT(*) as grade_count FROM grades WHERE student_id = ?");
$stmt->execute([$student_id]);
$grade_count = $stmt->fetch()['grade_count'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-danger">Delete Student</h1>
        <p class="text-muted">This action cannot be undone</p>
    </div>
    <a href="view.php?id=<?php echo $student_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Student
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Student Deletion
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Warning: This action is irreversible!
                    </h6>
                    <p class="mb-0">
                        You are about to permanently delete this student and all related data. 
                        This includes grades, enrollment records, and other associated information.
                    </p>
                </div>

                <!-- Student Information Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="avatar-large mb-3">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                </div>
                                <h6><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h6>
                                <p class="text-muted small"><?php echo $student['student_number']; ?></p>
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
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Student Details:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Student Number:</strong> <?php echo $student['student_number']; ?></li>
                            <li><strong>Email:</strong> <?php echo $student['email']; ?></li>
                            <li><strong>Phone:</strong> <?php echo $student['phone'] ?? 'N/A'; ?></li>
                            <li><strong>Course:</strong> <?php echo $student['course_name'] ?? 'N/A'; ?></li>
                            <li><strong>Year Level:</strong> Year <?php echo $student['year_level']; ?></li>
                            <li><strong>Status:</strong> <?php echo ucfirst($student['status']); ?></li>
                            <li><strong>Member Since:</strong> <?php echo format_date($student['created_at']); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Data Impact Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-danger">Data that will be permanently deleted:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-person-fill text-primary" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Student Record</h5>
                                        <p class="small text-muted">Personal and academic information</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2"><?php echo $grade_count; ?> Grade Records</h5>
                                        <p class="small text-muted">All academic performance data</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-clock-history text-info" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">History</h5>
                                        <p class="small text-muted">All activity and enrollment history</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="understand" required>
                            <label class="form-check-label" for="understand">
                                I understand that this action cannot be undone and all student data will be permanently deleted.
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmation" class="form-label">
                            Type "DELETE" to confirm (case sensitive):
                        </label>
                        <input type="text" class="form-control" id="confirmation" placeholder="Type DELETE here" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='view.php?id=<?php echo $student_id; ?>'">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="bi bi-trash-fill"></i> Delete Student Permanently
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
                        <a href="edit.php?id=<?php echo $student_id; ?>" class="btn btn-outline-warning w-100">
                            <i class="bi bi-pencil"></i> Edit Student Information
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <form method="POST" action="edit.php?id=<?php echo $student_id; ?>" class="d-inline w-100">
                            <input type="hidden" name="status" value="inactive">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="setStatusInactive()">
                                <i class="bi bi-pause-circle"></i> Set as Inactive
                            </button>
                        </form>
                    </div>
                </div>
                <p class="small text-muted mt-2">
                    Setting a student as "Inactive" preserves all data while removing them from active lists.
                </p>
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

<script>
// Enable delete button only when confirmation is typed correctly
document.getElementById('confirmation').addEventListener('input', function() {
    const confirmationText = this.value;
    const deleteBtn = document.getElementById('deleteBtn');
    const understandCheck = document.getElementById('understand');
    
    if (confirmationText === 'DELETE' && understandCheck.checked) {
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('btn-secondary');
        deleteBtn.classList.add('btn-danger');
    } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove('btn-danger');
        deleteBtn.classList.add('btn-secondary');
    }
});

document.getElementById('understand').addEventListener('change', function() {
    const confirmationText = document.getElementById('confirmation').value;
    const deleteBtn = document.getElementById('deleteBtn');
    
    if (confirmationText === 'DELETE' && this.checked) {
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('btn-secondary');
        deleteBtn.classList.add('btn-danger');
    } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove('btn-danger');
        deleteBtn.classList.add('btn-secondary');
    }
});

function setStatusInactive() {
    if (confirm('Set this student as inactive? This will preserve all data while removing them from active student lists.')) {
        // Create a form to submit status change
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit.php?id=<?php echo $student_id; ?>';
        
        // Add all current student data as hidden inputs
        const fields = [
            {name: 'student_number', value: '<?php echo addslashes($student['student_number']); ?>'},
            {name: 'first_name', value: '<?php echo addslashes($student['first_name']); ?>'},
            {name: 'last_name', value: '<?php echo addslashes($student['last_name']); ?>'},
            {name: 'email', value: '<?php echo addslashes($student['email']); ?>'},
            {name: 'phone', value: '<?php echo addslashes($student['phone']); ?>'},
            {name: 'course_id', value: '<?php echo $student['course_id']; ?>'},
            {name: 'year_level', value: '<?php echo $student['year_level']; ?>'},
            {name: 'date_of_birth', value: '<?php echo $student['date_of_birth']; ?>'},
            {name: 'address', value: '<?php echo addslashes($student['address']); ?>'},
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