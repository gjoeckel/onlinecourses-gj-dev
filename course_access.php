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
$sort = trim($_GET['sort'] ?? 'created_at');
$order = trim($_GET['order'] ?? 'DESC');

// Build query
$where = [];
$params = [];

if ($cohort) {
    $where[] = "cohort = ?";
    $params[] = $cohort;
}
if ($college) {
    $where[] = "college LIKE ?";
    $params[] = "%$college%";
}
if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($email) {
    $where[] = "email LIKE ?";
    $params[] = "%$email%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Validate sort field
$allowedSortFields = [
    'email', 'cohort', 'status', 'created_at', 'enrolleddate', 'earnerdate', 'certificatesent', 'reenrolleddate'
];
if (!in_array($sort, $allowedSortFields)) {
    $sort = 'created_at';
}
// Validate order
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Pagination settings
$items_per_page = 25;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count of registrations (with filters)
$countSql = "SELECT COUNT(*) as count FROM registrations $whereClause";
$total_items = $db->select($countSql, $params)[0]['count'];
$total_pages = ceil($total_items / $items_per_page);

// Get paginated registrations (with filters and sorting, exclude deleted users)
$sql = "SELECT * FROM registrations $whereClause AND (deletion_status = 'active' OR deletion_status IS NULL) ORDER BY $sort $order LIMIT $items_per_page OFFSET $offset";
$registrations = $db->select($sql, $params);

// Get course info
$courses = $db->select("SELECT * FROM courses");
$courseMap = array_column($courses, null, 'cohort');

// Get unique values for filters (exclude deleted users)
$colleges = $db->select("SELECT DISTINCT college FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL ORDER BY college");
$roles = $db->select("SELECT DISTINCT role FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL ORDER BY role");
$cohorts = $db->select("SELECT DISTINCT cohort FROM registrations WHERE deletion_status = 'active' OR deletion_status IS NULL ORDER BY cohort DESC");
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
    <?php echo getHtmlHead('Course Access Dashboard'); ?>
    <style>
        .status-badge {
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1>Course Access Dashboard</h1>
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="cohort" class="form-label">Cohort</label>
                        <select class="form-select" id="cohort" name="cohort">
                            <option value="">All Cohorts</option>
                            <?php foreach ($cohorts as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['cohort']); ?>" <?php echo ($cohort === $c['cohort']) ? 'selected' : ''; ?>>
                                    <?php 
                                    $year = substr($c['cohort'], 0, 4);
                                    $month = substr($c['cohort'], 5, 2);
                                    echo date('F Y', strtotime("$year-$month-01")); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="college" class="form-label">College</label>
                        <select class="form-select" id="college" name="college">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['college']); ?>" <?php echo ($college === $c['college']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['college']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role']); ?>" <?php echo ($role === $r['role']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($r['role']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($status === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Search by email">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="course_access.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'email', 'order' => ($sort === 'email' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Email<?php if ($sort === 'email'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'cohort', 'order' => ($sort === 'cohort' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Cohort<?php if ($sort === 'cohort'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => ($sort === 'status' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Status<?php if ($sort === 'status'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => ($sort === 'created_at' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Registration Date<?php if ($sort === 'created_at'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'enrolleddate', 'order' => ($sort === 'enrolleddate' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Enrollment Date<?php if ($sort === 'enrolleddate'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'earnerdate', 'order' => ($sort === 'earnerdate' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Certificate Earned<?php if ($sort === 'earnerdate'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'certificatesent', 'order' => ($sort === 'certificatesent' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Certificate Sent<?php if ($sort === 'certificatesent'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'reenrolleddate', 'order' => ($sort === 'reenrolleddate' && $order === 'ASC') ? 'DESC' : 'ASC', 'page' => 1])); ?>" class="text-decoration-none text-dark">Re-enrollment Date<?php if ($sort === 'reenrolleddate'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><?php echo htmlspecialchars($reg['cohort']); ?></td>
                            <td>
                                <?php
                                $statusClass = match($reg['status']) {
                                    'earner', 'review' => 'success',
                                    'expired' => 'danger',
                                    'reenrolled' => 'warning',
                                    default => 'primary'
                                };
                                ?>
                                <span class="status-badge bg-<?php echo $statusClass; ?> text-white">
                                    <?php echo htmlspecialchars(ucfirst($reg['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $reg['created_at'] ? date('M j, Y', strtotime($reg['created_at'])) : '-'; ?></td>
                            <td><?php echo $reg['enrolleddate'] ? date('M j, Y', strtotime($reg['enrolleddate'])) : '-'; ?></td>
                            <td><?php echo $reg['earnerdate'] ? date('M j, Y', strtotime($reg['earnerdate'])) : '-'; ?></td>
                            <td><?php echo $reg['certificatesent'] ? date('M j, Y', strtotime($reg['certificatesent'])) : '-'; ?></td>
                            <td><?php echo $reg['reenrolleddate'] ? date('M j, Y', strtotime($reg['reenrolleddate'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous button -->
                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" <?php echo $current_page <= 1 ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Previous</a>
                </li>
                
                <!-- Page numbers -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    echo '<li class="page-item ' . ($i === $current_page ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                    echo '</li>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                }
                ?>
                
                <!-- Next button -->
                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" <?php echo $current_page >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="text-center mt-4 mb-4">
            <a href="/registrations" class="btn btn-primary me-2">View Registrations</a>
            <a href="/course_builder" class="btn btn-secondary">Course Builder</a>
        </div>
    </div>
    
    <?php echo getJsIncludes(); ?>
</body>
</html> 