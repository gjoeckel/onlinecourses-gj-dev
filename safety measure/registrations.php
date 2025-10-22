<?php
require_once 'db.php';
require_once 'config.php';

$db = new Database();

// Get filter parameters
$cohort = filter_input(INPUT_GET, 'cohort_month', FILTER_VALIDATE_INT);
$year = filter_input(INPUT_GET, 'cohort_year', FILTER_VALIDATE_INT);
$college = trim($_GET['college'] ?? '');
$role = trim($_GET['role'] ?? '');
$alumni = trim($_GET['alumni'] ?? '');
$certificate = trim($_GET['certificate'] ?? '');
$enrolled = trim($_GET['enrolled'] ?? '');
$email = trim($_GET['email'] ?? '');

// Get sort parameters
$sort = trim($_GET['sort'] ?? 'id');
$order = trim($_GET['order'] ?? 'ASC');

// Build query
$where = [];
$params = [];

if ($cohort) {
    $where[] = "cohort = ?";
    $params[] = $cohort;
}

if ($year) {
    $where[] = "year = ?";
    $params[] = $year;
}

if ($college) {
    $where[] = "college LIKE ?";
    $params[] = "%$college%";
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($alumni !== '') {
    $where[] = "alumni = ?";
    $params[] = $alumni;
}

if ($certificate !== '') {
    $where[] = "certificate = ?";
    $params[] = $certificate;
}

if ($enrolled !== '') {
    $where[] = "enrolled = ?";
    $params[] = $enrolled;
}

if ($email) {
    $where[] = "email LIKE ?";
    $params[] = "%$email%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Validate sort field
$allowedSortFields = [
    'id', 'canvas_user_id', 'name', 'email', 'year', 'cohort', 'organization', 'college', 'role', 'invitation_date', 'enrolled', 'certificate', 'alumni', 'created_at', 'updated_at'
];
if (!in_array($sort, $allowedSortFields)) {
    $sort = 'created_at';
}

// Validate order
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Special handling for cohort sorting
if ($sort === 'cohort_month') {
    $orderBy = "year $order, cohort $order";
} else {
    $orderBy = "$sort $order";
}

// Pagination setup
$perPage = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as cnt FROM registrations $whereClause";
$countResult = $db->select($countSql, $params);
$totalRows = $countResult[0]['cnt'] ?? 0;
$totalPages = max(1, ceil($totalRows / $perPage));

// Get registrations with limit/offset
$sql = "SELECT * FROM registrations $whereClause ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$registrations = $db->select($sql, $params);

// Get unique values for filters
$colleges = $db->select("SELECT DISTINCT college FROM registrations ORDER BY college");
$roles = $db->select("SELECT DISTINCT role FROM registrations ORDER BY role");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo getHtmlHead('Registrations'); ?>
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
                        <select class="form-select" id="cohort" name="cohort_month">
                            <option value="">All Months</option>
                            <?php
                            foreach ($config['months'] as $num => $name) {
                                $selected = ($cohort == $num) ? 'selected' : '';
                                echo "<option value='{$num}' {$selected}>{$name}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="cohort_year">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = (int)date('y');
                            for ($y = $currentYear; $y <= $currentYear + 2; $y++) {
                                $selected = ($year == $y) ? 'selected' : '';
                                echo "<option value='{$y}' {$selected}>20{$y}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
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
                        <label for="alumni" class="form-label">Alumni</label>
                        <select class="form-select" id="alumni" name="alumni">
                            <option value="">All</option>
                            <option value="1" <?php echo ($alumni === '1') ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo ($alumni === '0') ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="certificate" class="form-label">Certificate</label>
                        <select class="form-select" id="certificate" name="certificate">
                            <option value="">All</option>
                            <option value="1" <?php echo ($certificate === '1') ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo ($certificate === '0') ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="enrolled" class="form-label">Enrolled</label>
                        <select class="form-select" id="enrolled" name="enrolled">
                            <option value="">All</option>
                            <option value="1" <?php echo ($enrolled === '1') ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo ($enrolled === '0') ? 'selected' : ''; ?>>No</option>
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
                        <a href="registrations.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Registrations Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'id', 'order' => $sort === 'id' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">ID<?php if ($sort === 'id'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'canvas_user_id', 'order' => $sort === 'canvas_user_id' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Canvas User ID<?php if ($sort === 'canvas_user_id'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => $sort === 'name' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Name<?php if ($sort === 'name'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'email', 'order' => $sort === 'email' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Email<?php if ($sort === 'email'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'year', 'order' => $sort === 'year' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Year<?php if ($sort === 'year'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'cohort', 'order' => $sort === 'cohort' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Cohort<?php if ($sort === 'cohort'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'organization', 'order' => $sort === 'organization' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Organization<?php if ($sort === 'organization'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'college', 'order' => $sort === 'college' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">College<?php if ($sort === 'college'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'role', 'order' => $sort === 'role' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Role<?php if ($sort === 'role'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'invitation_date', 'order' => $sort === 'invitation_date' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Invitation Date<?php if ($sort === 'invitation_date'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'enrolled', 'order' => $sort === 'enrolled' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Enrolled<?php if ($sort === 'enrolled'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'certificate', 'order' => $sort === 'certificate' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Certificate<?php if ($sort === 'certificate'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'alumni', 'order' => $sort === 'alumni' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Alumni<?php if ($sort === 'alumni'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Created At<?php if ($sort === 'created_at'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'updated_at', 'order' => $sort === 'updated_at' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none text-dark">Updated At<?php if ($sort === 'updated_at'): ?><i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i><?php endif; ?></a></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['id']); ?></td>
                            <td><?php echo htmlspecialchars($reg['canvas_user_id']); ?></td>
                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><?php echo htmlspecialchars($reg['year']); ?></td>
                            <td><?php echo htmlspecialchars($reg['cohort']); ?></td>
                            <td><?php echo htmlspecialchars($reg['organization']); ?></td>
                            <td><?php echo htmlspecialchars($reg['college']); ?></td>
                            <td><?php echo htmlspecialchars($reg['role']); ?></td>
                            <td><?php echo htmlspecialchars($reg['invitation_date']); ?></td>
                            <td><?php echo htmlspecialchars($reg['enrolled']); ?></td>
                            <td><?php echo htmlspecialchars($reg['certificate']); ?></td>
                            <td><?php echo htmlspecialchars($reg['alumni']); ?></td>
                            <td><?php echo htmlspecialchars($reg['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($reg['updated_at']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this registration?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item<?php echo $p == $page ? ' active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a></li>
            <?php endif; ?>
          </ul>
        </nav>
        
        <div class="mt-3">
            <a href="register.php" class="btn btn-success">New Registration</a>
        </div>
    </div>
    
    <?php echo getJsIncludes(); ?>
</body>
</html> 