<?php
require_once 'db.php';
require_once 'config.php';

$db = new Database();

// Get filter parameters
$cohort = trim($_GET['cohort'] ?? '');
$college = trim($_GET['college'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$email = trim($_GET['email'] ?? '');

// Get sort parameters
$sort = trim($_GET['sort'] ?? 'id');
$order = trim($_GET['order'] ?? 'ASC');

// Build query
$where = [];
$params = [];

if ($cohort) {
    // Parse cohort filter (format: "07_25" for July 2025)
    $cohortParts = explode('_', $cohort);
    if (count($cohortParts) === 2) {
        $where[] = "r.cohort = ? AND r.year = ?";
        $params[] = $cohortParts[0]; // month
        $params[] = $cohortParts[1]; // year
    }
}

if ($college) {
    $where[] = "r.college LIKE ?";
    $params[] = "%$college%";
}

if ($role) {
    $where[] = "r.role = ?";
    $params[] = $role;
}

if ($status) {
    $where[] = "r.status = ?";
    $params[] = $status;
}

if ($email) {
    $where[] = "r.email LIKE ?";
    $params[] = "%$email%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Validate sort field
$allowedSortFields = [
    'id', 'canvas_user_id', 'name', 'email', 'cohort', 'cohort_display', 'organization', 'college', 
    'role', 'status', 'invitation_date', 'enrolleddate', 'earnerdate', 
    'certificatesent', 'reenrolleddate', 'created_at', 'updated_at'
];
if (!in_array($sort, $allowedSortFields)) {
    $sort = 'created_at';
}

// Validate order
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Handle cohort sorting - sort by year first, then month
if ($sort === 'cohort') {
    $sort = 'r.year ' . $order . ', r.cohort ' . $order;
} else {
    $sort = $sort . ' ' . $order;
}

// Pagination setup
$perPage = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as cnt FROM registrations r $whereClause";
$countResult = $db->select($countSql, $params);
$totalRows = $countResult[0]['cnt'] ?? 0;
$totalPages = max(1, ceil($totalRows / $perPage));

// Get registrations with limit/offset (exclude deleted users)
$sql = "SELECT r.*, c.course_id as canvas_course_id,
        CONCAT(
            CASE r.cohort
                WHEN '01' THEN 'January'
                WHEN '02' THEN 'February'
                WHEN '03' THEN 'March'
                WHEN '04' THEN 'April'
                WHEN '05' THEN 'May'
                WHEN '06' THEN 'June'
                WHEN '07' THEN 'July'
                WHEN '08' THEN 'August'
                WHEN '09' THEN 'September'
                WHEN '10' THEN 'October'
                WHEN '11' THEN 'November'
                WHEN '12' THEN 'December'
                ELSE r.cohort
            END,
            ' 20', r.year
        ) as cohort_display
        FROM registrations r 
        LEFT JOIN courses c ON r.course_id = c.id 
        $whereClause 
        AND (r.deletion_status = 'active' OR r.deletion_status IS NULL)
        ORDER BY $sort
        LIMIT $perPage OFFSET $offset";
$registrations = $db->select($sql, $params);

// Get unique values for filters (exclude deleted users)
$colleges = $db->select("SELECT DISTINCT college FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL ORDER BY college");
$roles = $db->select("SELECT DISTINCT role FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL ORDER BY role");
$cohorts = $db->select("SELECT DISTINCT cohort, year, 
        CONCAT(
            CASE cohort
                WHEN '01' THEN 'January'
                WHEN '02' THEN 'February'
                WHEN '03' THEN 'March'
                WHEN '04' THEN 'April'
                WHEN '05' THEN 'May'
                WHEN '06' THEN 'June'
                WHEN '07' THEN 'July'
                WHEN '08' THEN 'August'
                WHEN '09' THEN 'September'
                WHEN '10' THEN 'October'
                WHEN '11' THEN 'November'
                WHEN '12' THEN 'December'
                ELSE cohort
            END,
            ' 20', year
        ) as cohort_display
        FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL 
        ORDER BY year DESC, cohort DESC");
$statuses = [
    'submitter' => 'Submitter',
    'active' => 'Active',
    'enrollee' => 'Enrolled',
    'completer' => 'Completed',
    'earner' => 'Certificate Earner',
    'expired' => 'Expired',
    'reenrolled' => 'Re-enrolled',
    'review' => 'In Review'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4 pb-5 px-4">
        <h1>Registrations</h1>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="cohort" class="form-label">Cohort</label>
                        <select class="form-select" id="cohort" name="cohort">
                            <option value="">All Cohorts</option>
                            <?php foreach ($cohorts as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['cohort'] . '_' . $c['year']); ?>"
                                        <?php echo ($cohort === $c['cohort'] . '_' . $c['year']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['cohort_display']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="college" class="form-label">College</label>
                        <select class="form-select" id="college" name="college">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['college']); ?>"
                                        <?php echo ($college === $c['college']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['college']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <?php foreach ($config['roles'] as $r): ?>
                                <option value="<?php echo $r; ?>"
                                        <?php echo ($role === $r) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($r); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>"
                                        <?php echo ($status === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="Search by email">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="registrations.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="?sort=id&order=<?php echo $sort === 'id' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        ID
                                        <?php if ($sort === 'id'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=name&order=<?php echo $sort === 'name' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Name
                                        <?php if ($sort === 'name'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=email&order=<?php echo $sort === 'email' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Email
                                        <?php if ($sort === 'email'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=cohort&order=<?php echo $sort === 'cohort' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Cohort
                                        <?php if ($sort === 'cohort'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=organization&order=<?php echo $sort === 'organization' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Enterprise
                                        <?php if ($sort === 'organization'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=college&order=<?php echo $sort === 'college' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Organization
                                        <?php if ($sort === 'college'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=role&order=<?php echo $sort === 'role' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Role
                                        <?php if ($sort === 'role'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=status&order=<?php echo $sort === 'status' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Status
                                        <?php if ($sort === 'status'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=enrolleddate&order=<?php echo $sort === 'enrolleddate' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none text-dark">
                                        Dates
                                        <?php if ($sort === 'enrolleddate'): ?>
                                            <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['id']); ?></td>
                                <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($reg['cohort_display']); ?></td>
                                <td><?php echo htmlspecialchars($reg['organization']); ?></td>
                                <td><?php echo htmlspecialchars($reg['college']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($reg['role'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($reg['status']) {
                                            'submitter' => 'secondary',
                                            'active' => 'primary',
                                            'enrollee' => 'info',
                                            'completer' => 'warning',
                                            'earner' => 'success',
                                            'expired' => 'danger',
                                            'reenrolled' => 'info',
                                            'review' => 'success',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo $statuses[$reg['status']] ?? ucfirst($reg['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reg['enrolleddate']): ?>
                                        <div>Enrolled: <?php echo date('Y-m-d', strtotime($reg['enrolleddate'])); ?></div>
                                    <?php endif; ?>
                                    <?php if ($reg['earnerdate']): ?>
                                        <div>Earned: <?php echo date('Y-m-d', strtotime($reg['earnerdate'])); ?></div>
                                    <?php endif; ?>
                                    <?php if ($reg['certificatesent']): ?>
                                        <div>Certified: <?php echo date('Y-m-d', strtotime($reg['certificatesent'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $reg['id']; ?>" 
                                       class="btn btn-sm btn-primary">Edit</a>
                                    <a href="delete.php?id=<?php echo $reg['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this registration?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <!-- Page Info -->
                    <div class="text-center text-muted mb-3">
                        Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                        (<?php echo number_format($totalRows); ?> total records)
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <?php
                            // Build query string for pagination links
                            $queryString = '&sort=' . urlencode($sort) . '&order=' . urlencode($order) . 
                                         '&cohort=' . urlencode($cohort) . '&college=' . urlencode($college) . 
                                         '&role=' . urlencode($role) . '&status=' . urlencode($status) . 
                                         '&email=' . urlencode($email);
                            
                            // First and Previous buttons
                            if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo $queryString; ?>" title="First page">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" title="Previous page">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" title="First page">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link" title="Previous page">
                                        <span aria-hidden="true">&laquo;</span>
                                    </span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Smart page range calculation
                            $range = 3; // Show 3 pages on each side of current page
                            $startPage = max(1, $page - $range);
                            $endPage = min($totalPages, $page + $range);
                            
                            // Always show first page if not in range
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo $queryString; ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php
                            // Show page range around current page
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php
                            // Always show last page if not in range
                            if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Next and Last buttons
                            if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" title="Next page">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" title="Last page">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" title="Next page">
                                        <span aria-hidden="true">&raquo;</span>
                                    </span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link" title="Last page">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>
                <?php endif; ?>
                <!-- Add Registration Button -->
                <div class="mt-3 d-flex gap-2">
                    <a href="register.php" class="btn btn-success">Add Registration</a>
                    <a href="course_builder.php" class="btn btn-secondary">Manage Courses</a>
                    <a href="email_test.php" class="btn btn-info">Test Emails</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 