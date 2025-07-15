<?php
/**
 * Delete Subject
 * Student Management System - Phase 3
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
    show_alert('Invalid subject ID.', 'danger');
    header('Location: index.php');
    exit();
}

// Get subject details
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code
    FROM subjects s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    WHERE s.subject_id = ?
");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    show_alert('Subject not found.', 'danger');
    header('Location: index.php');
    exit();
}

// Get related data counts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grades WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$grade_count = $stmt->fetch()['count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete related grades first (due to foreign key constraints)
            $pdo->prepare("DELETE FROM grades WHERE subject_id = ?")->execute([$subject_id]);
            
            // Delete the subject
            $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?")->execute([$subject_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log activity
            log_activity('Subject Deleted', "Deleted subject: {$subject['subject_name']} ({$subject['subject_code']})");
            
            // Success message and redirect
            show_alert('Subject deleted successfully.', 'success');
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            
            show_alert('Failed to delete subject. Please try again.', 'danger');
            error_log("Error deleting subject: " . $e->getMessage());
            header('Location: index.php');
            exit();
        }
    } else {
        // User cancelled
        header('Location: view.php?id=' . $subject_id);
        exit();
    }
}

$page_title = 'Delete Subject';
$breadcrumbs = [
    ['name' => 'Subjects', 'url' => 'index.php'],
    ['name' => $subject['subject_name'], 'url' => 'view.php?id=' . $subject_id],
    ['name' => 'Delete']
];

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-danger">Delete Subject</h1>
        <p class="text-muted">This action cannot be undone</p>
    </div>
    <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Subject
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Subject Deletion
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Warning: This action is irreversible!
                    </h6>
                    <p class="mb-0">
                        You are about to permanently delete this subject and all related data. 
                        This includes all grade records and other associated information.
                    </p>
                </div>

                <!-- Subject Information Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <div class="subject-icon mb-3">
                                    <?php echo strtoupper($subject['subject_code']); ?>
                                </div>
                                <h6><?php echo $subject['subject_name']; ?></h6>
                                <p class="text-muted small"><?php echo $subject['subject_code']; ?></p>
                                <?php
                                $status_color = $subject['status'] === 'active' ? 'success' : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_color; ?>">
                                    <?php echo ucfirst($subject['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Subject Details:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Subject Code:</strong> <?php echo $subject['subject_code']; ?></li>
                            <li><strong>Subject Name:</strong> <?php echo $subject['subject_name']; ?></li>
                            <li><strong>Credits:</strong> <?php echo $subject['credits']; ?> Credit Hours</li>
                            <li><strong>Semester:</strong> Semester <?php echo $subject['semester']; ?></li>
                            <li><strong>Course:</strong> <?php echo $subject['course_code'] . ' - ' . $subject['course_name']; ?></li>
                            <li><strong>Status:</strong> <?php echo ucfirst($subject['status']); ?></li>
                            <li><strong>Created:</strong> <?php echo format_date($subject['created_at']); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Data Impact Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-danger">Data that will be permanently deleted:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-journal-text text-primary" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Subject Record</h5>
                                        <p class="small text-muted">Subject information and details</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2"><?php echo $grade_count; ?> Grade Records</h5>
                                        <p class="small text-muted">All student grades for this subject</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Impact Analysis -->
                <?php if ($grade_count > 0): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Critical Impact Warning:</h6>
                        <ul class="mb-0">
                            <li><strong><?php echo $grade_count; ?> grade records</strong> will be permanently lost</li>
                            <li><strong>Student academic records</strong> will be incomplete</li>
                            <li><strong>GPA calculations</strong> may be affected for students who took this subject</li>
                            <li><strong>Transcripts and reports</strong> will no longer show this subject</li>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Confirmation Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="understand" required>
                            <label class="form-check-label" for="understand">
                                I understand that this action cannot be undone and all subject data will be permanently deleted.
                            </label>
                        </div>
                    </div>

                    <?php if ($grade_count > 0): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="impact_understood" required>
                                <label class="form-check-label" for="impact_understood">
                                    I acknowledge that <?php echo $grade_count; ?> grade records will be permanently deleted.
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="confirmation" class="form-label">
                            Type "DELETE SUBJECT" to confirm (case sensitive):
                        </label>
                        <input type="text" class="form-control" id="confirmation" placeholder="Type DELETE SUBJECT here" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='view.php?id=<?php echo $subject_id; ?>'">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="bi bi-trash-fill"></i> Delete Subject Permanently
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
                        <a href="edit.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-warning w-100">
                            <i class="bi bi-pencil"></i> Edit Subject Information
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="setSubjectInactive()">
                            <i class="bi bi-pause-circle"></i> Set as Inactive
                        </button>
                    </div>
                </div>
                <p class="small text-muted mt-2">
                    Setting a subject as "Inactive" preserves all data while removing it from active course lists.
                </p>
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
    font-size: 0.9rem;
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
    
    if (confirmationText === 'DELETE SUBJECT' && allChecked) {
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

function setSubjectInactive() {
    if (confirm('Set this subject as inactive? This will preserve all data while removing it from active subject lists.')) {
        // Create a form to submit status change
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit.php?id=<?php echo $subject_id; ?>';
        
        // Add all current subject data as hidden inputs
        const fields = [
            {name: 'subject_code', value: '<?php echo addslashes($subject['subject_code']); ?>'},
            {name: 'subject_name', value: '<?php echo addslashes($subject['subject_name']); ?>'},
            {name: 'description', value: '<?php echo addslashes($subject['description']); ?>'},
            {name: 'credits', value: '<?php echo $subject['credits']; ?>'},
            {name: 'course_id', value: '<?php echo $subject['course_id']; ?>'},
            {name: 'semester', value: '<?php echo $subject['semester']; ?>'},
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