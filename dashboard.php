<?php
/**
 * Admin Dashboard
 * Student Management System
 */

$page_title = 'Dashboard';
$breadcrumbs = [
    ['name' => 'Dashboard']
];

require_once 'includes/header.php';

// Get dashboard statistics
$total_students = get_student_count();
$total_courses = get_course_count();
$total_subjects = get_subject_count();

// Get recent students
$stmt = $pdo->prepare("
    SELECT s.student_id, s.student_number, s.first_name, s.last_name, 
           s.email, c.course_name, s.created_at
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    WHERE s.status = 'active'
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_students = $stmt->fetchAll();

// Get students by year level
$stmt = $pdo->query("
    SELECT year_level, COUNT(*) as count 
    FROM students 
    WHERE status = 'active' 
    GROUP BY year_level 
    ORDER BY year_level
");
$students_by_year = $stmt->fetchAll();

// Get students by course
$stmt = $pdo->query("
    SELECT c.course_name, COUNT(s.student_id) as count 
    FROM courses c 
    LEFT JOIN students s ON c.course_id = s.course_id AND s.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.course_id, c.course_name 
    ORDER BY count DESC
");
$students_by_course = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Welcome back, <?php echo $current_admin['full_name']; ?>!</h1>
        <p class="text-muted">Here's what's happening in your student management system.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stat-number text-primary"><?php echo number_format($total_students); ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="ms-3">
                    <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stat-number text-success"><?php echo number_format($total_courses); ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="ms-3">
                    <i class="bi bi-book-fill text-success" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stat-number text-warning"><?php echo number_format($total_subjects); ?></div>
                    <div class="stat-label">Total Subjects</div>
                </div>
                <div class="ms-3">
                    <i class="bi bi-journal-text text-warning" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="stat-number text-info"><?php echo count($students_by_year); ?></div>
                    <div class="stat-label">Year Levels</div>
                </div>
                <div class="ms-3">
                    <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Recent Data -->
<div class="row">
    <!-- Students by Year Level -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart-fill"></i>
                    Students by Year Level
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($students_by_year)): ?>
                    <?php foreach ($students_by_year as $year_data): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Year <?php echo $year_data['year_level']; ?></span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-3" style="width: 100px; height: 10px;">
                                    <div class="progress-bar bg-primary" 
                                         style="width: <?php echo ($year_data['count'] / $total_students) * 100; ?>%"></div>
                                </div>
                                <span class="badge bg-primary"><?php echo $year_data['count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No student data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Students by Course -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart-fill"></i>
                    Students by Course
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($students_by_course)): ?>
                    <?php foreach ($students_by_course as $course_data): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><?php echo $course_data['course_name']; ?></span>
                            <div class="d-flex align-items-center">
                                <div class="progress me-3" style="width: 100px; height: 10px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: <?php echo $total_students > 0 ? ($course_data['count'] / $total_students) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="badge bg-success"><?php echo $course_data['count']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No course data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i>
                    Recent Students
                </h5>
                <a href="students/index.php" class="btn btn-sm btn-outline-primary">
                    View All Students
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_students)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['student_number']; ?></td>
                                        <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                        <td><?php echo $student['email']; ?></td>
                                        <td><?php echo $student['course_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo format_date($student['created_at']); ?></td>
                                        <td>
                                            <a href="students/view.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="students/edit.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--muted);"></i>
                        <p class="text-muted mt-2">No students found. <a href="students/add.php">Add your first student</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning-fill"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="students/add.php" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus-fill"></i>
                            Add New Student
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="courses/add.php" class="btn btn-success w-100">
                            <i class="bi bi-book-fill"></i>
                            Add New Course
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="subjects/add.php" class="btn btn-warning w-100">
                            <i class="bi bi-journal-plus"></i>
                            Add New Subject
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reports/index.php" class="btn btn-info w-100">
                            <i class="bi bi-file-earmark-text"></i>
                            Generate Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>