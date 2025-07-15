<?php
/**
 * View Course Details
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

$page_title = $course['course_name'];
$breadcrumbs = [
    ['name' => 'Courses', 'url' => 'index.php'],
    ['name' => 'Course Details']
];

// Get course statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_students,
           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
           COUNT(CASE WHEN year_level = 1 THEN 1 END) as year1_students,
           COUNT(CASE WHEN year_level = 2 THEN 1 END) as year2_students,
           COUNT(CASE WHEN year_level = 3 THEN 1 END) as year3_students,
           COUNT(CASE WHEN year_level = 4 THEN 1 END) as year4_students
    FROM students 
    WHERE course_id = ?
");
$stmt->execute([$course_id]);
$student_stats = $stmt->fetch();

// Get course subjects
$stmt = $pdo->prepare("
    SELECT * 
    FROM subjects 
    WHERE course_id = ? AND status = 'active'
    ORDER BY semester, subject_code
");
$stmt->execute([$course_id]);
$subjects = $stmt->fetchAll();

// Group subjects by semester
$subjects_by_semester = [];
foreach ($subjects as $subject) {
    $subjects_by_semester[$subject['semester']][] = $subject;
}

// Get recent students in this course
$stmt = $pdo->prepare("
    SELECT student_id, student_number, first_name, last_name, year_level, status, created_at
    FROM students 
    WHERE course_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$course_id]);
$recent_students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><?php echo $course['course_name']; ?></h1>
        <p class="text-muted">Course Code: <?php echo $course['course_code']; ?></p>
    </div>
    <div class="btn-group" role="group">
        <a href="edit.php?id=<?php echo $course['course_id']; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit Course
        </a>
        <a href="../subjects/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
            <i class="bi bi-journal-plus"></i> Add Subject
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <!-- Course Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="course-icon mb-3">
                    <?php echo strtoupper($course['course_code']); ?>
                </div>
                <h5 class="card-title"><?php echo $course['course_name']; ?></h5>
                <p class="text-muted"><?php echo $course['course_code']; ?></p>
                
                <?php
                $status_color = $course['status'] === 'active' ? 'success' : 'secondary';
                ?>
                <span class="badge bg-<?php echo $status_color; ?> mb-3">
                    <?php echo ucfirst($course['status']); ?>
                </span>
                
                <div class="mt-3">
                    <h6>Duration</h6>
                    <h3 class="text-primary"><?php echo $course['duration_years']; ?> Years</h3>
                </div>
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
                    <span>Total Students:</span>
                    <strong><?php echo $student_stats['total_students']; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Active Students:</span>
                    <strong class="text-success"><?php echo $student_stats['active_students']; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Subjects:</span>
                    <strong><?php echo count($subjects); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Created:</span>
                    <strong><?php echo format_date($course['created_at']); ?></strong>
                </div>
            </div>
        </div>

        <!-- Student Distribution -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-people-fill"></i>
                    Students by Year
                </h6>
            </div>
            <div class="card-body">
                <?php if ($student_stats['total_students'] > 0): ?>
                    <?php for ($year = 1; $year <= 4; $year++): ?>
                        <?php $year_count = $student_stats["year{$year}_students"]; ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Year <?php echo $year; ?></span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                    <div class="progress-bar bg-primary" 
                                         style="width: <?php echo $student_stats['total_students'] > 0 ? ($year_count / $student_stats['total_students']) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="badge bg-primary"><?php echo $year_count; ?></span>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <p class="text-muted small">No students enrolled yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="col-lg-8">
        <!-- Course Description -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill"></i>
                    Course Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Course Code</label>
                        <p class="mb-0"><strong><?php echo $course['course_code']; ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo $status_color; ?>">
                                <?php echo ucfirst($course['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Duration</label>
                        <p class="mb-0"><?php echo $course['duration_years']; ?> Years</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Last Updated</label>
                        <p class="mb-0"><?php echo format_datetime($course['updated_at']); ?></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted">Description</label>
                        <p class="mb-0">
                            <?php echo $course['description'] ? nl2br(htmlspecialchars($course['description'])) : 'No description provided.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Subjects -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-journal-text"></i>
                    Course Curriculum (<?php echo count($subjects); ?> subjects)
                </h5>
                <a href="../subjects/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Add Subject
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($subjects_by_semester)): ?>
                    <?php foreach ($subjects_by_semester as $semester => $semester_subjects): ?>
                        <div class="mb-4">
                            <h6 class="text-primary border-bottom pb-2">
                                <i class="bi bi-calendar3"></i>
                                Semester <?php echo $semester; ?>
                            </h6>
                            <div class="row">
                                <?php foreach ($semester_subjects as $subject): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo $subject['subject_code']; ?></h6>
                                                        <p class="mb-1 small"><?php echo $subject['subject_name']; ?></p>
                                                        <span class="badge bg-info"><?php echo $subject['credits']; ?> Credits</span>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="../subjects/view.php?id=<?php echo $subject['subject_id']; ?>">
                                                                <i class="bi bi-eye"></i> View
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="../subjects/edit.php?id=<?php echo $subject['subject_id']; ?>">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Curriculum Summary -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h6>Total Subjects</h6>
                                <h4 class="text-primary"><?php echo count($subjects); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Credits</h6>
                                <h4 class="text-success"><?php echo array_sum(array_column($subjects, 'credits')); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Semesters</h6>
                                <h4 class="text-info"><?php echo count($subjects_by_semester); ?></h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Avg Credits/Semester</h6>
                                <h4 class="text-warning">
                                    <?php echo count($subjects_by_semester) > 0 ? round(array_sum(array_column($subjects, 'credits')) / count($subjects_by_semester), 1) : 0; ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-journal-text" style="font-size: 3rem; color: var(--muted);"></i>
                        <h6 class="mt-2">No Subjects Available</h6>
                        <p class="text-muted">No subjects have been added to this course yet.</p>
                        <a href="../subjects/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add First Subject
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill"></i>
                    Recent Students
                </h5>
                <a href="../students/index.php?course=<?php echo $course_id; ?>" class="btn btn-sm btn-outline-primary">
                    View All Students
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Enrolled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                                    <small class="text-muted"><?php echo $student['student_number']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Year <?php echo $student['year_level']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $student_status_colors = [
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'graduated' => 'primary',
                                                'suspended' => 'danger'
                                            ];
                                            $student_status_color = $student_status_colors[$student['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $student_status_color; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo format_date($student['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="../students/view.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people" style="font-size: 3rem; color: var(--muted);"></i>
                        <h6 class="mt-2">No Students Enrolled</h6>
                        <p class="text-muted">No students have enrolled in this course yet.</p>
                        <a href="../students/add.php" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Add Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.course-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    margin: 0 auto;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.7rem;
}

.border-left-primary {
    border-left: 4px solid var(--accent) !important;
}

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php require_once '../includes/footer.php'; ?>