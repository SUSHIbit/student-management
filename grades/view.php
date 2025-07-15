<?php
/**
 * View Grade Details
 * Student Management System - Phase 4
 */

// Get grade ID from URL
$grade_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($grade_id <= 0) {
    header('Location: index.php');
    exit();
}

require_once '../includes/header.php';

// Get grade details with student and subject information
$stmt = $pdo->prepare("
    SELECT g.*, 
           s.student_number, s.first_name, s.last_name, s.year_level, s.email,
           sub.subject_code, sub.subject_name, sub.credits, sub.semester,
           c.course_code, c.course_name
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    JOIN subjects sub ON g.subject_id = sub.subject_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE g.grade_id = ?
");
$stmt->execute([$grade_id]);
$grade = $stmt->fetch();

if (!$grade) {
    show_alert('Grade not found.', 'danger');
    header('Location: index.php');
    exit();
}

$page_title = 'Grade Details';
$breadcrumbs = [
    ['name' => 'Grades', 'url' => 'index.php'],
    ['name' => $grade['first_name'] . ' ' . $grade['last_name']],
    ['name' => 'Grade Details']
];

// Calculate grade point value
function get_grade_points($grade_letter) {
    $grade_points = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D' => 1.0, 'F' => 0.0
    ];
    return $grade_points[$grade_letter] ?? 0.0;
}

// Get student's other grades for comparison
$stmt = $pdo->prepare("
    SELECT g.grade_letter, g.total_marks, sub.subject_code, g.academic_year, g.semester
    FROM grades g
    JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.student_id = ? AND g.grade_id != ?
    ORDER BY g.academic_year DESC, g.semester DESC
");
$stmt->execute([$grade['student_id'], $grade_id]);
$other_grades = $stmt->fetchAll();

// Get subject performance statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_students,
        AVG(total_marks) as avg_marks,
        MAX(total_marks) as highest_marks,
        MIN(total_marks) as lowest_marks,
        COUNT(CASE WHEN grade_letter IN ('A+', 'A', 'A-') THEN 1 END) as excellent_count,
        COUNT(CASE WHEN grade_letter IN ('B+', 'B', 'B-') THEN 1 END) as good_count,
        COUNT(CASE WHEN grade_letter IN ('C+', 'C', 'C-') THEN 1 END) as satisfactory_count,
        COUNT(CASE WHEN grade_letter IN ('D', 'F') THEN 1 END) as poor_count
    FROM grades
    WHERE subject_id = ? AND academic_year = ? AND semester = ?
");
$stmt->execute([$grade['subject_id'], $grade['academic_year'], $grade['semester']]);
$subject_stats = $stmt->fetch();

// Calculate percentile ranking
$stmt = $pdo->prepare("
    SELECT COUNT(*) as lower_count
    FROM grades
    WHERE subject_id = ? AND academic_year = ? AND semester = ? AND total_marks < ?
");
$stmt->execute([$grade['subject_id'], $grade['academic_year'], $grade['semester'], $grade['total_marks']]);
$lower_count = $stmt->fetch()['lower_count'];
$percentile = $subject_stats['total_students'] > 0 ? ($lower_count / $subject_stats['total_students']) * 100 : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Grade Details</h1>
        <p class="text-muted"><?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?> - <?php echo $grade['subject_name']; ?></p>
    </div>
    <div class="btn-group" role="group">
        <a href="edit.php?id=<?php echo $grade_id; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit Grade
        </a>
        <a href="../students/view.php?id=<?php echo $grade['student_id']; ?>" class="btn btn-info">
            <i class="bi bi-person"></i> View Student
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Grades
        </a>
    </div>
</div>

<div class="row">
    <!-- Grade Overview -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="grade-icon mb-3">
                    <?php echo $grade['grade_letter']; ?>
                </div>
                <h5 class="card-title">Final Grade</h5>
                <h2 class="text-primary mb-3"><?php echo number_format($grade['total_marks'], 1); ?>%</h2>
                
                <?php
                $grade_colors = [
                    'A+' => 'success', 'A' => 'success', 'A-' => 'success',
                    'B+' => 'primary', 'B' => 'primary', 'B-' => 'primary',
                    'C+' => 'warning', 'C' => 'warning', 'C-' => 'warning',
                    'D' => 'danger', 'F' => 'danger'
                ];
                $grade_color = $grade_colors[$grade['grade_letter']] ?? 'secondary';
                ?>
                
                <div class="mb-3">
                    <span class="badge bg-<?php echo $grade_color; ?> fs-6 px-3 py-2">
                        Grade: <?php echo $grade['grade_letter']; ?>
                    </span>
                </div>
                
                <div class="mt-3">
                    <h6>Grade Points</h6>
                    <h4 class="text-info"><?php echo number_format(get_grade_points($grade['grade_letter']), 1); ?></h4>
                    <small class="text-muted">GPA Contribution</small>
                </div>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up"></i>
                    Performance Analysis
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Class Ranking</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percentile; ?>%"></div>
                        </div>
                        <strong><?php echo number_format($percentile, 0); ?>th percentile</strong>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Class Average:</span>
                    <strong><?php echo number_format($subject_stats['avg_marks'], 1); ?>%</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Highest Score:</span>
                    <strong class="text-success"><?php echo number_format($subject_stats['highest_marks'], 1); ?>%</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Lowest Score:</span>
                    <strong class="text-danger"><?php echo number_format($subject_stats['lowest_marks'], 1); ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Students:</span>
                    <strong><?php echo $subject_stats['total_students']; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="col-lg-8">
        <!-- Student & Subject Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill"></i>
                    Student & Subject Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Student Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Name:</strong></td><td><?php echo $grade['first_name'] . ' ' . $grade['last_name']; ?></td></tr>
                            <tr><td><strong>Student Number:</strong></td><td><?php echo $grade['student_number']; ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?php echo $grade['email']; ?></td></tr>
                            <tr><td><strong>Course:</strong></td><td><?php echo $grade['course_code'] . ' - ' . $grade['course_name']; ?></td></tr>
                            <tr><td><strong>Year Level:</strong></td><td>Year <?php echo $grade['year_level']; ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Subject Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Subject:</strong></td><td><?php echo $grade['subject_name']; ?></td></tr>
                            <tr><td><strong>Subject Code:</strong></td><td><?php echo $grade['subject_code']; ?></td></tr>
                            <tr><td><strong>Credits:</strong></td><td><?php echo $grade['credits']; ?> Credit Hours</td></tr>
                            <tr><td><strong>Semester:</strong></td><td>Semester <?php echo $grade['semester']; ?></td></tr>
                            <tr><td><strong>Academic Year:</strong></td><td><?php echo $grade['academic_year']; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Breakdown -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calculator"></i>
                    Grade Breakdown & Analysis
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <small>
                        <strong>Grading Weights:</strong> 
                        Assignment (20%) + Quiz (20%) + Midterm (30%) + Final (30%) = Total Grade
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6>Component Scores</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Score</th>
                                        <th>Weight</th>
                                        <th>Contribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Assignment</td>
                                        <td><?php echo number_format($grade['assignment_marks'], 1); ?>%</td>
                                        <td>20%</td>
                                        <td><?php echo number_format($grade['assignment_marks'] * 0.2, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Quiz</td>
                                        <td><?php echo number_format($grade['quiz_marks'], 1); ?>%</td>
                                        <td>20%</td>
                                        <td><?php echo number_format($grade['quiz_marks'] * 0.2, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Midterm</td>
                                        <td><?php echo number_format($grade['midterm_marks'], 1); ?>%</td>
                                        <td>30%</td>
                                        <td><?php echo number_format($grade['midterm_marks'] * 0.3, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Final</td>
                                        <td><?php echo number_format($grade['final_marks'], 1); ?>%</td>
                                        <td>30%</td>
                                        <td><?php echo number_format($grade['final_marks'] * 0.3, 1); ?>%</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Total</strong></td>
                                        <td><strong><?php echo number_format($grade['total_marks'], 1); ?>%</strong></td>
                                        <td><strong>100%</strong></td>
                                        <td><strong><?php echo number_format($grade['total_marks'], 1); ?>%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6>Performance Visualization</h6>
                        <canvas id="gradeChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Comparison -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-people"></i>
                    Class Performance Comparison
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6>Grade Distribution in Class</h6>
                        <div class="row">
                            <?php
                            $distribution = [
                                ['label' => 'Excellent (A)', 'count' => $subject_stats['excellent_count'], 'color' => 'success'],
                                ['label' => 'Good (B)', 'count' => $subject_stats['good_count'], 'color' => 'primary'],
                                ['label' => 'Satisfactory (C)', 'count' => $subject_stats['satisfactory_count'], 'color' => 'warning'],
                                ['label' => 'Poor (D/F)', 'count' => $subject_stats['poor_count'], 'color' => 'danger']
                            ];
                            
                            foreach ($distribution as $dist):
                                $percentage = $subject_stats['total_students'] > 0 ? ($dist['count'] / $subject_stats['total_students']) * 100 : 0;
                            ?>
                                <div class="col-3 text-center mb-3">
                                    <div class="grade-circle bg-<?php echo $dist['color']; ?> text-white mb-2">
                                        <?php echo $dist['count']; ?>
                                    </div>
                                    <small class="text-muted"><?php echo $dist['label']; ?></small>
                                    <br><small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <h6>Student Position</h6>
                        <div class="alert alert-<?php echo $grade['total_marks'] >= $subject_stats['avg_marks'] ? 'success' : 'warning'; ?>">
                            <strong>Performance:</strong> 
                            <?php echo $grade['total_marks'] >= $subject_stats['avg_marks'] ? 'Above' : 'Below'; ?> Class Average
                            <br>
                            <strong>Difference:</strong> 
                            <?php echo $grade['total_marks'] >= $subject_stats['avg_marks'] ? '+' : ''; ?>
                            <?php echo number_format($grade['total_marks'] - $subject_stats['avg_marks'], 1); ?>%
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Class Ranking</small>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: <?php echo $percentile; ?>%">
                                    <?php echo number_format($percentile, 0); ?>th percentile
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student's Other Grades -->
        <?php if (!empty($other_grades)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-check"></i>
                    Student's Academic History
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Subject</th>
                                <th>Score</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($other_grades as $other_grade): ?>
                                <tr>
                                    <td><?php echo $other_grade['academic_year']; ?></td>
                                    <td>Sem <?php echo $other_grade['semester']; ?></td>
                                    <td><?php echo $other_grade['subject_code']; ?></td>
                                    <td><?php echo number_format($other_grade['total_marks'], 1); ?>%</td>
                                    <td>
                                        <span class="badge bg-<?php echo $grade_colors[$other_grade['grade_letter']] ?? 'secondary'; ?>">
                                            <?php echo $other_grade['grade_letter']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Record Information -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-clock-history"></i>
            Record Information
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">Created:</small>
                <p class="mb-0"><?php echo format_datetime($grade['created_at']); ?></p>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Last Updated:</small>
                <p class="mb-0"><?php echo format_datetime($grade['updated_at']); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.grade-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0 auto;
}

.grade-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    margin: 0 auto;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Grade breakdown chart
const ctx = document.getElementById('gradeChart').getContext('2d');
const gradeChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Assignment', 'Quiz', 'Midterm', 'Final'],
        datasets: [{
            label: 'Score (%)',
            data: [
                <?php echo $grade['assignment_marks']; ?>,
                <?php echo $grade['quiz_marks']; ?>,
                <?php echo $grade['midterm_marks']; ?>,
                <?php echo $grade['final_marks']; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(255, 99, 132, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Score (%)'
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>