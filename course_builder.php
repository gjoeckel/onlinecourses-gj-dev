<?php
require_once 'db.php';
require_once 'config.php';

$db = new Database();
$message = '';
$error = '';

$apiUrl = $config['canvas']['api_url'];
$accessToken = $config['canvas']['access_token'];

// Pagination settings
$itemsPerPage = isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Build search and filter conditions
$whereConditions = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $whereConditions[] = "(course_id LIKE ? OR course_title LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
}

if (!empty($_GET['cohort_filter'])) {
    $whereConditions[] = "cohort = ?";
    $params[] = $_GET['cohort_filter'];
}

if (!empty($_GET['status_filter'])) {
    $today = date('Y-m-d');
    if ($_GET['status_filter'] === 'open') {
        $whereConditions[] = "open_date <= ? AND close_date >= ?";
        $params[] = $today;
        $params[] = $today;
    } elseif ($_GET['status_filter'] === 'closed') {
        $whereConditions[] = "close_date < ?";
        $params[] = $today;
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total number of courses for pagination
$countQuery = "SELECT COUNT(*) as count FROM courses {$whereClause}";
$totalCourses = $db->select($countQuery, $params)[0]['count'];
$totalPages = ceil($totalCourses / $itemsPerPage);

// Reset to page 1 if current page exceeds total pages
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = 1;
    $offset = 0;
}

// Get existing courses with pagination, search, and filtering
$query = "SELECT * FROM courses {$whereClause} ORDER BY cohort DESC, id DESC LIMIT {$itemsPerPage} OFFSET {$offset}";
$courses = $db->select($query, $params);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            // Create new course in Canvas first
            $courseName = $_POST['course_title'];
            $cohort = $_POST['cohort'];
            $openDate = $_POST['open_date'];
            $closeDate = $_POST['close_date'];
            
            // Generate course name based on cohort
            // $courseName = "Accessibility Course - Cohort " . $cohort;
            
            // Create course in Canvas
            $canvasCourseData = [
                'course' => [
                    'name' => $courseName,
                    'course_code' => 'ACCESS-' . $cohort,
                    'start_at' => $openDate . 'T00:00:00Z',
                    'end_at' => $closeDate . 'T23:59:59Z',
                    'is_public' => false,
                    'public_syllabus' => false,
                    'public_description' => 'Accessibility course for cohort ' . $cohort,
                    'workflow_state' => 'available',
                    'restrict_enrollments_to_course_dates' => true
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . "/accounts/240/courses");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($canvasCourseData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 201) {
                $canvasCourse = json_decode($result, true);
                $canvasCourseId = $canvasCourse['id'];
                
                // Store course in database with Canvas-generated ID
                $data = [
                    'course_id' => $canvasCourseId,
                    'course_title' => $courseName,
                    'cohort' => $cohort,
                    'open_date' => $openDate,
                    'close_date' => $closeDate
                ];
                
                try {
                    $db->insert('courses', $data);
                    $message = "Course created successfully in Canvas! Canvas Course ID: " . $canvasCourseId;
                } catch (Exception $e) {
                    $error = "Error storing course in database: " . $e->getMessage();
                }
            } else {
                $error = "Failed to create course in Canvas. HTTP Code: " . $httpCode . ". Response: " . $result;
            }
        } elseif ($_POST['action'] === 'publish') {
            $canvasCourseId = $_POST['canvas_course_id'];
            
            // To publish an existing course, we send the 'offer' event via a PUT request.
            $publishData = ['course' => ['event' => 'offer']];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/courses/{$canvasCourseId}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($publishData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $message = "Course successfully published in Canvas!";
            } else {
                $error = "Failed to publish course. HTTP Code: {$httpCode}. Response: {$result}";
            }
        } elseif ($_POST['action'] === 'update_assignments') {
            // Update assignment IDs using existing phpgetassignment.php
            $courseId = $_POST['course_id'];
            $endpoint = "{$apiUrl}/courses/{$courseId}/assignments";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $assignments = json_decode($result, true);
                $updateData = [];
                
                // Map assignment names to their IDs
                foreach ($assignments as $assignment) {
                    $columnName = strtolower(str_replace(' ', '_', $assignment['name'])) . '_id';
                    $updateData[$columnName] = $assignment['id'];
                }
                
                $db->update('courses', $updateData, 'id = ?', [$courseId]);
                $message = "Assignment IDs updated successfully!";
            } else {
                $error = "Failed to fetch assignments from Canvas";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Course Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4 pb-5 px-4">
        <h1>Course Builder</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Create New Course Form - COMMENTED OUT
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Create New Course</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Note:</strong> When you create a course, it will automatically be created in Canvas and the Canvas Course ID will be generated for you.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Title</label>
                        <input type="text" class="form-control" id="course_title" name="course_title" required>
                    </div>

                    <div class="mb-3">
                        <label for="cohort" class="form-label">Cohort</label>
                        <input type="text" class="form-control" id="cohort" name="cohort" 
                               pattern="\d{4}-\d{2}" placeholder="MMMM-YY" required>
                        <div class="form-text">This will be used to generate the course name and code</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="open_date" class="form-label">Open Date</label>
                        <input type="date" class="form-control" id="open_date" name="open_date" required>
                        <div class="form-text">When the course becomes available for registration</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="close_date" class="form-label">Close Date</label>
                        <input type="date" class="form-control" id="close_date" name="close_date" required>
                        <div class="form-text">When the course closes for registration</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Course in Canvas</button>
                </form>
            </div>
        </div>
        -->

        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Search & Filter Courses</h2>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                               placeholder="Course ID or Title">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="cohort_filter" class="form-label">Cohort</label>
                        <select class="form-select" id="cohort_filter" name="cohort_filter">
                            <option value="">All Cohorts</option>
                            <?php
                            $cohorts = $db->select("SELECT DISTINCT cohort FROM courses ORDER BY cohort DESC");
                            foreach ($cohorts as $cohort) {
                                $selected = ($_GET['cohort_filter'] ?? '') === $cohort['cohort'] ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($cohort['cohort']) . "\" {$selected}>" . htmlspecialchars($cohort['cohort']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label">Status</label>
                        <select class="form-select" id="status_filter" name="status_filter">
                            <option value="">All</option>
                            <option value="open" <?php echo ($_GET['status_filter'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="closed" <?php echo ($_GET['status_filter'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="items_per_page" class="form-label">Per Page</label>
                        <select class="form-select" id="items_per_page" name="items_per_page">
                            <option value="10" <?php echo ($_GET['items_per_page'] ?? '10') === '10' ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo ($_GET['items_per_page'] ?? '10') === '25' ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo ($_GET['items_per_page'] ?? '10') === '50' ? 'selected' : ''; ?>>50</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="?" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Courses -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Existing Courses</h2>
                <span class="text-muted">Showing <?php echo count($courses); ?> of <?php echo $totalCourses; ?> courses</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Canvas Course ID</th>
                                <th>Course Title</th>
                                <th>Cohort</th>
                                <th>Open Date</th>
                                <th>Close Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($course['cohort']); ?></td>
                                <td><?php echo htmlspecialchars($course['open_date']); ?></td>
                                <td><?php echo htmlspecialchars($course['close_date']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_assignments">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Update Assignment IDs</button>
                                    </form>
                                    <form method="POST" style="display: inline;" class="ms-1">
                                        <input type="hidden" name="action" value="publish">
                                        <input type="hidden" name="canvas_course_id" value="<?php echo $course['course_id']; ?>">
                                        <!-- <button type="submit" class="btn btn-sm btn-success">Publish</button> -->
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Course pagination">
                    <ul class="pagination justify-content-center">
                        <?php 
                        // Build query string for pagination links
                        $queryParams = $_GET;
                        unset($queryParams['page']); // Remove page from query params
                        $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                        ?>
                        
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $queryString; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo $queryString; ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>"><?php echo $totalPages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $queryString; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="text-center mt-4 mb-4">
                    <a href="https://webaim.org/onlinecourses/registrations" class="btn btn-primary me-2">View Registrations</a>
                    <!-- <a href="/course_access" class="btn btn-secondary">Course Access</a> -->
                </div>
            </div>
        </div>
    </div>

    <?php echo getJsIncludes(); ?>
</body>
</html> 