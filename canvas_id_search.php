<?php
require_once 'config.php';
require_once 'db.php';

class CanvasIdSearch {
    private $db;
    private $apiUrl;
    private $accessToken;
    
    public function __construct() {
        $this->db = new Database();
        $this->apiUrl = $GLOBALS['config']['canvas']['api_url'];
        $this->accessToken = $GLOBALS['config']['canvas']['access_token'];
    }
    
    /**
     * Search for user by Canvas ID in local database
     */
    public function searchByCanvasId($canvasId) {
        $sql = "SELECT r.*, c.course_title, c.cohort 
                FROM registrations r 
                LEFT JOIN courses c ON r.course_id = c.id 
                WHERE r.canvas_user_id = ?";
        
        return $this->db->select($sql, [$canvasId]);
    }
    
    /**
     * Search for user by Canvas ID in Canvas API
     */
    public function searchCanvasApi($canvasId) {
        $endpoint = "{$this->apiUrl}/users/{$canvasId}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($result, true);
        }
        
        return null;
    }
    
    /**
     * Get user enrollments from Canvas API
     */
    public function getUserEnrollments($canvasId) {
        $endpoint = "{$this->apiUrl}/users/{$canvasId}/enrollments";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($result, true);
        }
        
        return [];
    }
    
    /**
     * Comprehensive search combining local and Canvas data
     */
    public function comprehensiveSearch($canvasId) {
        $localResults = $this->searchByCanvasId($canvasId);
        $canvasUser = $this->searchCanvasApi($canvasId);
        $enrollments = $this->getUserEnrollments($canvasId);
        
        return [
            'local_data' => $localResults,
            'canvas_user' => $canvasUser,
            'enrollments' => $enrollments,
            'found_locally' => !empty($localResults),
            'found_in_canvas' => !empty($canvasUser)
        ];
    }
}

// Web Interface
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = new CanvasIdSearch();
    $results = null;
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['canvas_id'])) {
        $canvasId = trim($_POST['canvas_id']);
        
        if (empty($canvasId)) {
            $error = "Please enter a Canvas ID";
        } elseif (!is_numeric($canvasId)) {
            $error = "Canvas ID must be a number";
        } else {
            try {
                $results = $search->comprehensiveSearch($canvasId);
            } catch (Exception $e) {
                $error = "Search failed: " . $e->getMessage();
            }
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Canvas ID Search</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <div class="row">
                <div class="col-12">
                    <h1><i class="bi bi-search"></i> Canvas ID Search</h1>
                    <p class="text-muted">Search for users by their Canvas ID in both local database and Canvas API</p>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-md-8">
                                    <label for="canvas_id" class="form-label">Canvas User ID</label>
                                    <input type="number" class="form-control" id="canvas_id" name="canvas_id" 
                                           value="<?php echo isset($_POST['canvas_id']) ? htmlspecialchars($_POST['canvas_id']) : ''; ?>" 
                                           placeholder="Enter Canvas User ID" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($results): ?>
                        <div class="row">
                            <!-- Local Database Results -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="bi bi-database"></i> Local Database
                                            <?php if ($results['found_locally']): ?>
                                                <span class="badge bg-success">Found</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Found</span>
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($results['found_locally']): ?>
                                            <?php foreach ($results['local_data'] as $registration): ?>
                                                <div class="border-bottom pb-3 mb-3">
                                                    <h6>Registration ID: <?php echo htmlspecialchars($registration['id']); ?></h6>
                                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($registration['status']); ?></p>
                                                    <p><strong>Course:</strong> <?php echo htmlspecialchars($registration['course_title'] ?? 'N/A'); ?></p>
                                                    <p><strong>Cohort:</strong> <?php echo htmlspecialchars($registration['cohort'] ?? 'N/A'); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($registration['email'] ?? 'N/A'); ?></p>
                                                    <p><strong>Created:</strong> <?php echo htmlspecialchars($registration['createddate'] ?? 'N/A'); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No local registration found for this Canvas ID.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Canvas API Results -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="bi bi-cloud"></i> Canvas API
                                            <?php if ($results['found_in_canvas']): ?>
                                                <span class="badge bg-success">Found</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Found</span>
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($results['found_in_canvas']): ?>
                                            <div class="border-bottom pb-3 mb-3">
                                                <h6>User Information</h6>
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($results['canvas_user']['name'] ?? 'N/A'); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($results['canvas_user']['email'] ?? 'N/A'); ?></p>
                                                <p><strong>Login ID:</strong> <?php echo htmlspecialchars($results['canvas_user']['login_id'] ?? 'N/A'); ?></p>
                                                <p><strong>Created:</strong> <?php echo htmlspecialchars($results['canvas_user']['created_at'] ?? 'N/A'); ?></p>
                                            </div>
                                            
                                            <?php if (!empty($results['enrollments'])): ?>
                                                <div>
                                                    <h6>Enrollments (<?php echo count($results['enrollments']); ?>)</h6>
                                                    <?php foreach (array_slice($results['enrollments'], 0, 5) as $enrollment): ?>
                                                        <div class="small text-muted mb-1">
                                                            Course ID: <?php echo htmlspecialchars($enrollment['course_id'] ?? 'N/A'); ?> - 
                                                            Status: <?php echo htmlspecialchars($enrollment['enrollment_state'] ?? 'N/A'); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (count($results['enrollments']) > 5): ?>
                                                        <div class="small text-muted">... and <?php echo count($results['enrollments']) - 5; ?> more</div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No user found in Canvas API for this ID.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Search Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Canvas ID:</strong> <?php echo htmlspecialchars($_POST['canvas_id']); ?></p>
                                        <p><strong>Local Database:</strong> 
                                            <?php echo $results['found_locally'] ? 'Found' : 'Not Found'; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Canvas API:</strong> 
                                            <?php echo $results['found_in_canvas'] ? 'Found' : 'Not Found'; ?>
                                        </p>
                                        <p><strong>Total Enrollments:</strong> <?php echo count($results['enrollments']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

// API Endpoint
if (isset($_GET['api']) && $_GET['api'] === '1') {
    header('Content-Type: application/json');
    
    if (!isset($_GET['canvas_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Canvas ID parameter required']);
        exit;
    }
    
    $canvasId = trim($_GET['canvas_id']);
    
    if (empty($canvasId) || !is_numeric($canvasId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Canvas ID must be a valid number']);
        exit;
    }
    
    try {
        $search = new CanvasIdSearch();
        $results = $search->comprehensiveSearch($canvasId);
        echo json_encode($results);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 