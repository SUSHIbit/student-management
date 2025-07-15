<?php
/**
 * Individual Student Report
 * Student Management System - Phase 5
 */

$page_title = 'Student Report';
$breadcrumbs = [
    ['name' => 'Reports', 'url' => 'index.php'],
    ['name' => 'Student Report']
];

require_once '../includes/header.php';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$format = isset($_GET['format']) ? clean_input($_GET['format']) : 'html';

// Get student details
$student = null;
if ($student_id > 0) {
    $student = get_student_by_id($student_id);
}

// If student selected, get comprehensive data
$student_grades = [];
$gpa_data = [];
$semester_performance = [];

if ($student) {
    // Get all grades for the student
    $grades_sql = "
        SELECT g.*, s.subject_code, s.subject_name, s.credits, s.semester,
               c.course_code as subject_course
        FROM grades g
        JOIN subjects s ON g.subject_id = s.subject_id
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE g.student_id = ?
        ORDER BY g.academic_year, s.semester, s.subject_code
    ";
    $stmt = $pdo->prepare($grades_sql);
    $stmt->execute([$student_id]);
    $student_grades = $stmt->fetchAll();

    // Calculate GPA by semester
    $gpa_by_semester = [];
    $cumulative_points = 0;
    $cumulative_credits = 0;

    foreach ($student_grades as $grade) {
        $semester_key = $grade['academic_year'] . '_' . $grade['semester'];
        
        if (!isset($gpa_by_semester[$semester_key])) {
            $gpa_by_semester[$semester_key] = [
                'academic_year' => $grade['academic_year'],
                'semester' => $grade['semester'],
                'total_points' => 0,
                'total_credits' => 0,
                'subjects' => []
            ];
        }

        // Calculate grade points
        $grade_points = 0;
        switch ($grade['grade_letter']) {
            case 'A+': case 'A': $grade_points = 4.0; break;
            case 'A-': $grade_points = 3.7; break;
            case 'B+': $grade_points = 3.3; break;
            case 'B': $grade_points = 3.0; break;
            case 'B-': $grade_points = 2.7; break;
            case 'C+': $grade_points = 2.3; break;
            case 'C': $grade_points = 2.0; break;
            case 'C-': $grade_points = 1.7; break;
            case 'D': $grade_points = 1.0; break;
            case 'F': $grade_points = 0.0; break;
        }

        $gpa_by_semester[$semester_key]['total_points'] += $grade_points * $grade['credits'];
        $gpa_by_semester[$semester_key]['total_credits'] += $grade['credits'];
        $gpa_by_semester[$semester_key]['subjects'][] = $grade;

        $cumulative_points += $grade_points * $grade['credits'];
        $cumulative_credits += $grade['credits'];
    }

    // Calculate semester GPAs and CGPA
    foreach ($gpa_by_semester as $key => $semester_data) {
        $semester_gpa = $semester_data['total_credits'] > 0 ? 
                       $semester_data['total_points'] / $semester_data['total_credits'] : 0;
        
        $gpa_by_semester[$key]['gpa'] = $semester_gpa;
        $semester_performance[] = [
            'semester' => 'Sem ' . $semester_data['semester'] . ' (' . $semester_data['academic_year'] . ')',
            'gpa' => $semester_gpa,
            'credits' => $semester_data['total_credits'],
            'subjects_count' => count($semester_data['subjects'])
        ];
    }

    $cumulative_gpa = $cumulative_credits > 0 ? $cumulative_points / $cumulative_credits : 0;
}

// Get all students for dropdown
$students_sql = "
    SELECT s.student_id, s.student_number, s.first_name, s.last_name, c.course_code
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    ORDER BY s.last_name, s.first_name
";
$all_students = $pdo->query($students_sql)->fetchAll();

// Handle PDF generation
if ($format === 'pdf' && $student) {
    // Simple HTML to PDF conversion (you can enhance this with a proper PDF library)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="student_report_' . $student['student_number'] . '.pdf"');
    
    // For now, we'll create a simple HTML report that can be printed as PDF
    // In a real application, you'd use libraries like TCPDF, FPDF, or DomPDF
}
?>

<?php if ($format !== 'pdf'): ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Individual Student Report</h1>
        <p class="text-muted">Comprehensive academic profile and performance analysis</p>
    </div>
    <div class="btn-group" role="group">
        <?php if ($student): ?>
            <a href="?student_id=<?php echo $student_id; ?>&format=pdf" class="btn btn-danger">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Student Selection -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <label for="student_id" class="form-label">Select Student</label>
                <select class="form-select" id="student_id" name="student_id" required>
                    <option value="">Choose a student...</option>
                    <?php foreach ($all_students as $s): ?>
                        <option value="<?php echo $s['student_id']; ?>" 
                                <?php echo ($student_id == $s['student_id']) ? 'selected' : ''; ?>>
                            <?php echo $s['student_number'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']; ?>
                            (<?php echo $s['course_code'] ?? 'No Course'; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php if ($student): ?>
<!-- Student Information Header -->
<div class="card mb-4" id="reportContent">
    <div class="card-header bg-primary text-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-0"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h4>
                <p class="mb-0">Student Number: <?php echo $student['student_number']; ?></p>
            </div>
            <div class="col-md-4 text-end">
                <p class="mb-0">Report Generated: <?php echo date('F j, Y'); ?></p>
                <?php if ($cumulative_credits > 0): ?>
                    <h3 class="mb-0">CGPA: <?php echo number_format($cumulative_gpa, 2); ?></h3>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Personal Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Full Name:</strong></td><td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td></tr>
                    <tr><td><strong>Student Number:</strong></td><td><?php echo $student['student_number']; ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo $student['email']; ?></td></tr>
                    <tr><td><strong>Phone:</strong></td><td><?php echo $student['phone'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Date of Birth:</strong></td><td><?php echo $student['date_of_birth'] ? format_date($student['date_of_birth']) : 'N/A'; ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Academic Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Course:</strong></td><td><?php echo $student['course_name'] ?? 'No Course Assigned'; ?></td></tr>
                    <tr><td><strong>Course Code:</strong></td><td><?php echo $student['course_code'] ?? 'N/A'; ?></td></tr>
                    <tr><td><strong>Year Level:</strong></td><td>Year <?php echo $student['year_level']; ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($student['status']); ?>
                        </span>
                    </td></tr>
                    <tr><td><strong>Enrollment Date:</strong></td><td><?php echo format_date($student['created_at']); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($student_grades)): ?>
<!-- Academic Performance Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i>
            Academic Performance Summary
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h3><?php echo number_format($cumulative_gpa, 2); ?></h3>
                    <p class="mb-0">Cumulative GPA</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h3><?php echo $cumulative_credits; ?></h3>
                    <p class="mb-0">Total Credits</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h3><?php echo count($student_grades); ?></h3>
                    <p class="mb-0">Subjects Completed</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h3><?php echo count($semester_performance); ?></h3>
                    <p class="mb-0">Semesters</p>
                </div>
            </div>
        </div>

        <!-- Grade Distribution -->
        <div class="row mt-4">
            <div class="col-12">
                <h6>Grade Distribution</h6>
                <?php
                $grade_counts = array_count_values(array_column($student_grades, 'grade_letter'));
                $grade_order = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'];
                $grade_colors = [
                    'A+' => 'success', 'A' => 'success', 'A-' => 'success',
                    'B+' => 'primary', 'B' => 'primary', 'B-' => 'primary',
                    'C+' => 'warning', 'C' => 'warning', 'C-' => 'warning',
                    'D' => 'danger', 'F' => 'danger'
                ];
                ?>
                <div class="row">
                    <?php foreach ($grade_order as $grade): ?>
                        <?php if (isset($grade_counts[$grade])): ?>
                            <div class="col-md-2 col-4 text-center mb-2">
                                <div class="grade-circle bg-<?php echo $grade_colors[$grade]; ?> text-white">
                                    <?php echo $grade; ?>
                                </div>
                                <small><?php echo $grade_counts[$grade]; ?> subjects</small>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Semester Performance -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-calendar-week"></i>
            Semester Performance Trends
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Subjects</th>
                        <th>Credits</th>
                        <th>Semester GPA</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semester_performance as $perf): ?>
                        <tr>
                            <td><?php echo $perf['semester']; ?></td>
                            <td><?php echo $perf['subjects_count']; ?></td>
                            <td><?php echo $perf['credits']; ?></td>
                            <td><strong><?php echo number_format($perf['gpa'], 2); ?></strong></td>
                            <td>
                                <?php
                                $performance_class = 'secondary';
                                $performance_text = 'Satisfactory';
                                if ($perf['gpa'] >= 3.5) {
                                    $performance_class = 'success';
                                    $performance_text = 'Excellent';
                                } elseif ($perf['gpa'] >= 3.0) {
                                    $performance_class = 'primary';
                                    $performance_text = 'Good';
                                } elseif ($perf['gpa'] >= 2.0) {
                                    $performance_class = 'warning';
                                    $performance_text = 'Fair';
                                } elseif ($perf['gpa'] < 2.0) {
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

<!-- Detailed Grade Record -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i>
            Complete Grade Record
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                        <th>Assignment</th>
                        <th>Quiz</th>
                        <th>Midterm</th>
                        <th>Final</th>
                        <th>Total</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_year = '';
                    foreach ($student_grades as $grade): 
                        if ($current_year !== $grade['academic_year']):
                            $current_year = $grade['academic_year'];
                    ?>
                        <tr class="table-secondary">
                            <td colspan="11"><strong>Academic Year: <?php echo $current_year; ?></strong></td>
                        </tr>
                    <?php endif; ?>
                        <tr>
                            <td><?php echo $grade['academic_year']; ?></td>
                            <td>Sem <?php echo $grade['semester']; ?></td>
                            <td><strong><?php echo $grade['subject_code']; ?></strong></td>
                            <td><?php echo $grade['subject_name']; ?></td>
                            <td><?php echo $grade['credits']; ?></td>
                            <td><?php echo number_format($grade['assignment_marks'], 1); ?>%</td>
                            <td><?php echo number_format($grade['quiz_marks'], 1); ?>%</td>
                            <td><?php echo number_format($grade['midterm_marks'], 1); ?>%</td>
                            <td><?php echo number_format($grade['final_marks'], 1); ?>%</td>
                            <td><strong><?php echo number_format($grade['total_marks'], 1); ?>%</strong></td>
                            <td>
                                <span class="badge bg-<?php echo $grade_colors[$grade['grade_letter']]; ?>">
                                    <?php echo $grade['grade_letter']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Academic Standing & Recommendations -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-award"></i>
            Academic Standing & Recommendations
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Current Academic Standing</h6>
                <?php
                $standing_class = 'secondary';
                $standing_text = 'Satisfactory Standing';
                $recommendation = 'Continue current academic progress.';

                if ($cumulative_gpa >= 3.5) {
                    $standing_class = 'success';
                    $standing_text = 'Dean\'s List / Excellent Standing';
                    $recommendation = 'Outstanding academic performance. Consider advanced coursework or research opportunities.';
                } elseif ($cumulative_gpa >= 3.0) {
                    $standing_class = 'primary';
                    $standing_text = 'Good Academic Standing';
                    $recommendation = 'Good progress. Focus on maintaining consistency across all subjects.';
                } elseif ($cumulative_gpa >= 2.0) {
                    $standing_class = 'warning';
                    $standing_text = 'Academic Probation Warning';
                    $recommendation = 'Academic performance needs improvement. Consider tutoring or study groups.';
                } elseif ($cumulative_gpa < 2.0 && $cumulative_gpa > 0) {
                    $standing_class = 'danger';
                    $standing_text = 'Academic Probation';
                    $recommendation = 'Immediate intervention required. Must meet with academic advisor.';
                }
                ?>
                
                <div class="alert alert-<?php echo $standing_class; ?>">
                    <h6 class="alert-heading"><?php echo $standing_text; ?></h6>
                    <p class="mb-0"><?php echo $recommendation; ?></p>
                </div>
                
                <h6>Performance Metrics</h6>
                <ul class="list-unstyled">
                    <li><strong>CGPA:</strong> <?php echo number_format($cumulative_gpa, 2); ?>/4.0</li>
                    <li><strong>Total Credits:</strong> <?php echo $cumulative_credits; ?></li>
                    <li><strong>Subjects Completed:</strong> <?php echo count($student_grades); ?></li>
                    <li><strong>Average per Semester:</strong> <?php echo number_format(array_sum(array_column($semester_performance, 'gpa')) / count($semester_performance), 2); ?></li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h6>Strengths & Areas for Improvement</h6>
                <?php
                // Analyze performance by grade
                $excellent_count = 0;
                $good_count = 0;
                $poor_count = 0;
                
                foreach ($student_grades as $grade) {
                    if (in_array($grade['grade_letter'], ['A+', 'A', 'A-'])) {
                        $excellent_count++;
                    } elseif (in_array($grade['grade_letter'], ['B+', 'B', 'B-'])) {
                        $good_count++;
                    } elseif (in_array($grade['grade_letter'], ['D', 'F'])) {
                        $poor_count++;
                    }
                }
                
                $total_grades = count($student_grades);
                $excellent_rate = $total_grades > 0 ? ($excellent_count / $total_grades) * 100 : 0;
                $poor_rate = $total_grades > 0 ? ($poor_count / $total_grades) * 100 : 0;
                ?>
                
                <div class="mb-3">
                    <strong>Strengths:</strong>
                    <ul>
                        <?php if ($excellent_rate >= 50): ?>
                            <li>Consistently high academic performance</li>
                        <?php endif; ?>
                        <?php if ($cumulative_gpa >= 3.0): ?>
                            <li>Strong overall GPA maintenance</li>
                        <?php endif; ?>
                        <?php if (count($semester_performance) >= 2): ?>
                            <li>Consistent semester completion</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <?php if ($poor_rate > 0 || $cumulative_gpa < 3.0): ?>
                <div class="mb-3">
                    <strong>Areas for Improvement:</strong>
                    <ul>
                        <?php if ($poor_rate > 10): ?>
                            <li>Focus on subjects with poor performance</li>
                        <?php endif; ?>
                        <?php if ($cumulative_gpa < 2.5): ?>
                            <li>Overall academic performance enhancement needed</li>
                        <?php endif; ?>
                        <li>Consider seeking academic support services</li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <h6>Next Steps</h6>
                <ul>
                    <li>Review semester performance trends</li>
                    <li>Set academic goals for next semester</li>
                    <li>Meet with academic advisor if needed</li>
                    <?php if ($cumulative_gpa < 2.0): ?>
                        <li><strong>Mandatory:</strong> Academic intervention meeting</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- No Grades Message -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-graph-up" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">No Academic Records Found</h4>
        <p class="text-muted">This student has no grades recorded in the system yet.</p>
        <a href="../grades/add.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add First Grade
        </a>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- No Student Selected -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-person-lines-fill" style="font-size: 4rem; color: var(--muted);"></i>
        <h4 class="mt-3">Select a Student</h4>
        <p class="text-muted">Choose a student from the dropdown above to generate their academic report.</p>
    </div>
</div>
<?php endif; ?>

<style>
.stat-card {
    text-align: center;
    border-radius: 8px;
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

@media print {
    .btn, .card:first-child {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>