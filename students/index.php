<?php
/**
 * Student Listing Page
 * Student Management System - Phase 2
 */

$page_title = 'Students';
$breadcrumbs = [
    ['name' => 'Students']
];

require_once '../includes/header.php';

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build search query
$where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ? OR s.email LIKE ?)";
    $search_param = "%$search_term%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($course_filter > 0) {
    $where_conditions[] = "s.course_id = ?";
    $params[] = $course_filter;
}

if ($year_filter > 0) {
    $where_conditions[] = "s.year_level = ?";
    $params[] = $year_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM students s $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// Calculate pagination
$pagination = paginate($total_records, $records_per_page, $current_page);
$offset = $pagination['offset'];

// Get students with pagination
$sql = "
    SELECT s.*, c.course_name, c.course_code 
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    $where_clause
    ORDER BY s.created_at DESC 
    LIMIT $records_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get courses for filter dropdown
$courses = get_all_courses();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Students Management</h1>
        <p class="text-muted">Manage all student records</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill"></i> Add New Student
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Name, Student Number, Email..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="col-md-2">
                <label for="course" class="form-label">Course</label>
                <select class="form-select" id="course" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                            <?php echo $course['course_code']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label">Year Level</label>
                <select class="form-select" id="year" name="year">
                    <option value="">All Years</option>
                    <option value="1" <?php echo ($year_filter == 1) ? 'selected' : ''; ?>>Year 1</option>
                    <option value="2" <?php echo ($year_filter == 2) ? 'selected' : ''; ?>>Year 2</option>
                    <option value="3" <?php echo ($year_filter == 3) ? 'selected' : ''; ?>>Year 3</option>
                    <option value="4" <?php echo ($year_filter == 4) ? 'selected' : ''; ?>>Year 4</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="graduated" <?php echo ($status_filter == 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                    <option value="suspended" <?php echo ($status_filter == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-people-fill"></i>
            Students List (<?php echo number_format($total_records); ?> total)
        </h5>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="../reports/students-export.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th class="sortable">Student Number</th>
                            <th class="sortable">Name</th>
                            <th class="sortable">Email</th>
                            <th class="sortable">Course</th>
                            <th class="sortable">Year</th>
                            <th class="sortable">Status</th>
                            <th class="sortable">Date Added</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $student['student_number']; ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                            <small class="text-muted"><?php echo $student['phone'] ?? 'No phone'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['email']; ?></td>
                                <td>
                                    <?php if ($student['course_name']): ?>
                                        <span class="badge bg-info"><?php echo $student['course_code']; ?></span>
                                        <br><small class="text-muted"><?php echo $student['course_name']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">No Course</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">Year <?php echo $student['year_level']; ?></span>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <small><?php echo format_date($student['created_at']); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php echo generate_pagination($pagination, 'index.php'); ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: var(--muted);"></i>
                <h4 class="mt-3">No Students Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_term) || $course_filter || $year_filter || !empty($status_filter)): ?>
                        No students match your current filters. <a href="index.php">Clear filters</a> to see all students.
                    <?php else: ?>
                        Start by adding your first student to the system.
                    <?php endif; ?>
                </p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill"></i> Add First Student
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.8rem;
}

.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: var(--background);
}

@media print {
    .btn, .card-header .btn-group, .pagination {
        display: none !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>