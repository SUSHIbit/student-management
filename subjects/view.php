<?php
/**
 * View Subject Details
 * Student Management System - Phase 3
 */

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
    header('Location: index.php');
    exit();
}

require_once '../includes/header.php';

// Get subject details with course information
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code, c.duration_years
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

$page_title = $subject['subject_name'];
$breadcrumbs = [
    ['name' => 'Subjects', 'url' => 'index.php'],
    ['name' => 'Subject Details']
];

// Get subject grades statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_grades,
        AVG(total_marks) as avg_marks,
        MAX(total_marks) as highest_marks,
        MIN(total_marks) as lowest_marks,
        COUNT(CASE WHEN grade_letter IN ('A+', 'A', 'A-') THEN 1 END) as excellent_count,
        COUNT(CASE WHEN grade_letter IN ('B+', 'B', 'B-') THEN 1 END) as good_count,
        COUNT(CASE WHEN grade_letter IN ('C+', 'C', 'C-') THEN 1 END) as satisfactory_count,
        COUNT(CASE WHEN grade_letter IN ('D', 'F') THEN 1 END) as poor_count
    FROM grades 
    WHERE subject_id = ?
");
$stmt->execute([$subject_id]);
$grade_stats = $stmt->fetch();

// Get recent grades for this subject
$stmt = $pdo->prepare("
    SELECT g.*, st.student_number, st.first_name, st.last_name, st.year_level
    FROM grades g
    JOIN students st ON g.student_id = st.student_id
    WHERE g.subject_id = ?
    ORDER BY g.created_at DESC
    LIMIT 10
");
$stmt->execute([$subject_id]);
$recent_grades = $stmt->fetchAll();

// Get grade distribution by semester
$stmt = $pdo->prepare("
    SELECT g.semester, g.academic_year, 
           COUNT(*) as student_count,
           AVG(g.total_marks) as avg_marks
    FROM grades g
    WHERE g.subject_id = ?
    GROUP BY g.semester, g.academic_year
    ORDER BY g.academic_year DESC, g.semester DESC
");
$stmt->execute([$subject_id]);
$semester_stats = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><?php echo $subject['subject_name']; ?></h1>
        <p class="text-muted">Subject Code: <?php echo $subject['subject_code']; ?></p>
    </div>
    <div class="btn-group" role="group">
        <a href="edit.php?id=<?php echo $subject['subject_id']; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit Subject
        </a>
        <a href="../grades/add.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Grade
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <!-- Subject Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="subject-icon mb-3">
                    <?php echo strtoupper($subject['subject_code']); ?>
                </div>
                <h5 class="card-title"><?php echo $subject['subject_name']; ?></h5>
                <p class="text-muted"><?php echo $subject['subject_code']; ?></p>
                
                <?php
                $status_color = $subject['status'] === 'active' ? 'success' : 'secondary';
                ?>
                <span class="badge bg-<?php echo $status_color; ?> mb-3">
                    <?php echo ucfirst($subject['status']); ?>
                </span>
                
                <div class="mt-3">
                    <h6>Credit Hours</h6>
                    <h3 class="text-primary"><?php echo $subject['credits']; ?></h3>
                    <small class="text-muted">Semester <?php echo $subject['semester']; ?></small>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Performance Stats
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Students:</span>
                    <strong><?php echo $grade_stats['total_grades']; ?></strong>
                </div>
                <?php if ($grade_stats['total_grades'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Average Score:</span>
                        <strong class="text-primary"><?php echo number_format($grade_stats['avg_marks'], 1); ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Highest Score:</span>
                        <strong class="text-success"><?php echo number_format($grade_stats['highest_marks'], 1); ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Lowest Score:</span>
                        <strong class="text-danger"><?php echo number_format($grade_stats['lowest_marks'], 1); ?>%</strong>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between">
                    <span>Created:</span>
                    <strong><?php echo format_date($subject['created_at']); ?></strong>
                </div>
            </div>
        </div>

        <!-- Grade Distribution -->
        <?php if ($grade_stats['total_grades'] > 0): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-pie-chart"></i>
                    Grade Distribution
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center mb-2">
                        <div class="grade-circle bg-success text-white">
                            <?php echo $grade_stats['excellent_count']; ?>
                        </div>
                        <small class="text-muted">Excellent (A)</small>
                    </div>
                    <div class="col-6 text-center mb-2">
                        <div class="grade-circle bg-primary text-white">
                            <?php echo $grade_stats['good_count']; ?>
                        </div>
                        <small class="text-muted">Good (B)</small>
                    </div>
                    <div class="col-6 text-center">
                        <div class="grade-circle bg-warning text-white">
                            <?php echo $grade_stats['satisfactory_count']; ?>
                        </div>
                        <small class="text-muted">Satisfactory (C)</small>
                    </div>
                    <div class="col-6 text-center">
                        <div class="grade-circle bg-danger text-white">
                            <?php echo $grade_stats['poor_count']; ?>
                        </div>
                        <small class="text-muted">Poor (D/F)</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Detailed Information -->
    <div class="col-lg-8">
        <!-- Subject Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill"></i>
                    Subject Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Subject Code</label>
                        <p class="mb-0"><strong><?php echo $subject['subject_code']; ?></strong></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo $status_color; ?>">
                                <?php echo ucfirst($subject['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Credit Hours</label>
                        <p class="mb-0"><?php echo $subject['credits']; ?> Credits</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Semester</label>
                        <p class="mb-0">
                            <span class="badge bg-info">Semester <?php echo $subject['semester']; ?></span>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Course</label>
                        <p class="mb-0">
                            <?php if ($subject['course_name']): ?>
                                <strong><?php echo $subject['course_code']; ?></strong><br>
                                <small class="text-muted"><?php echo $subject['course_name']; ?></small>
                            <?php else: ?>
                                <span class="text-muted">No course assigned</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Last Updated</label>
                        <p class="mb-0"><?php echo format_datetime($subject['updated_at']); ?></p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted">Description</label>
                        <p class="mb-0">
                            <?php echo $subject['description'] ? nl2br(htmlspecialchars($subject['description'])) : 'No description provided.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance by Semester -->
        <?php if (!empty($semester_stats)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-week"></i>
                    Performance by Semester/Year
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Students</th>
                                <th>Average Score</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semester_stats as $stat): ?>
                                <tr>
                                    <td><?php echo $stat['academic_year']; ?></td>
                                    <td>
                                        <span class="badge bg-secondary">Sem <?php echo $stat['semester']; ?></span>
                                    </td>
                                    <td><?php echo $stat['student_count']; ?></td>
                                    <td>
                                        <strong class="<?php echo $stat['avg_marks'] >= 75 ? 'text-success' : ($stat['avg_marks'] >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                            <?php echo number_format($stat['avg_marks'], 1); ?>%
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $performance_class = 'secondary';
                                        $performance_text = 'Fair';
                                        if ($stat['avg_marks'] >= 85) {
                                            $performance_class = 'success';
                                            $performance_text = 'Excellent';
                                        } elseif ($stat['avg_marks'] >= 75) {
                                            $performance_class = 'primary';
                                            $performance_text = 'Good';
                                        } elseif ($stat['avg_marks'] >= 60) {
                                            $performance_class = 'warning';
                                            $performance_text = 'Satisfactory';
                                        } elseif ($stat['avg_marks'] < 50) {
                                            $performance_class = 'danger';
                                            $performance_text = 'Poor';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $performance_class; ?>"><?php echo $performance_text; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Grades -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Recent Grades
                </h5>
                <div class="btn-group" role="group">
                    <a href="../grades/add.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Grade
                    </a>
                    <a href="../grades/index.php?subject=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                        View All Grades
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_grades)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Year</th>
                                    <th>Assignment</th>
                                    <th>Quiz</th>
                                    <th>Midterm</th>
                                    <th>Final</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_grades as $grade): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <?php echo strtoupper(substr($grade['first_name'], 0, 1) . substr($grade['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div><?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?></div>
                                                    <small class="text-muted"><?php echo $grade['student_number']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Year <?php echo $grade['year_level']; ?></span>
                                        </td>
                                        <td><?php echo number_format($grade['assignment_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['quiz_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['midterm_marks'], 1); ?></td>
                                        <td><?php echo number_format($grade['final_marks'], 1); ?></td>
                                        <td><strong><?php echo number_format($grade['total_marks'], 1); ?></strong></td>
                                        <td>
                                            <?php
                                            $grade_colors = [
                                                'A+' => 'success', 'A' => 'success', 'A-' => 'success',
                                                'B+' => 'primary', 'B' => 'primary', 'B-' => 'primary',
                                                'C+' => 'warning', 'C' => 'warning', 'C-' => 'warning',
                                                'D' => 'danger', 'F' => 'danger'
                                            ];
                                            $grade_color = $grade_colors[$grade['grade_letter']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $grade_color; ?>">
                                                <?php echo $grade['grade_letter']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo format_date($grade['created_at']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-graph-up" style="font-size: 3rem; color: var(--muted);"></i>
                        <h6 class="mt-2">No Grades Available</h6>
                        <p class="text-muted">No grades have been recorded for this subject yet.</p>
                        <a href="../grades/add.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add First Grade
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.subject-icon {
    width: 80px;
    height: 80px;
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

.grade-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    margin: 0 auto 0.5rem;
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

.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php require_once '../includes/footer.php'; ?>