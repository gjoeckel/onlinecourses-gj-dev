<?php
function get_credentials_from_config_file() {
    $config_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
    $credentials = [
        'dbhost' => null, 'dbuser' => null, 'dbpass' => null, 'dbname' => null,
        'token' => null, 'url' => null
    ];

    if (!is_readable($config_path)) {
        return $credentials;
    }

    $config_content = file_get_contents($config_path);

    $patterns = [
        'dbhost' => '/\$dbhost\s*=\s*"([^"]+)"/',
        'dbuser' => '/\$dbuser\s*=\s*"([^"]+)"/',
        'dbpass' => '/\$dbpass\s*=\s*"([^"]+)"/',
        'dbname' => '/\$dbname\s*=\s*"([^"]+)"/',
        'token'  => '/\$CANVAS_API_TOKEN\s*=\s*"([^"]+)"/',
        'url'    => '/\$CANVAS_API_URL\s*=\s*"([^"]+)"/'
    ];

    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $config_content, $matches)) {
            $credentials[$key] = $matches[1];
        }
    }

    return $credentials;
}

$creds = get_credentials_from_config_file();

$dbhost = $creds['dbhost'];
$dbuser = $creds['dbuser'];
$dbpass = $creds['dbpass'];
$dbname = $creds['dbname'];
$accessToken = $creds['token'];
$apiUrl = $creds['url'];

// Sanity Check
if (empty($dbhost) || empty($accessToken) || empty($apiUrl)) {
    die("CRITICAL ERROR: Failed to parse credentials from the master config file. Halting cron job.\n");
}

class CronDatabase {
    private $conn;

    public function __construct() {
        global $dbhost, $dbuser, $dbpass, $dbname;
        try {
            $this->conn = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            die("DB Query failed: " . $e->getMessage());
        }
    }

    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $set = array_map(function($field) { return "{$field} = ?"; }, $fields);
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params);
    }
}

// --- Main Cron Logic ---

$db = new CronDatabase();

// Log start
error_log("Daily cron started at " . date('Y-m-d H:i:s'));

// Get ONLY users who have Canvas IDs AND course IDs (real users, not test data)
$recentUsers = $db->select(
    "SELECT
        r.id, r.canvas_user_id, r.status, r.email,
        c.course_id as canvas_course_id, c.exam_4_id,
        c.overview_of_document_accessibility_id, c.images_id, c.hyperlinks_id,
        c.contrast_color_reliance_id, c.optimizing_writing_id, c.exam_1_id,
        c.headings_in_word_id, c.optimizing_powerpoint_presentations_id,
        c.lists_columns_id, c.tables_id, c.exam_2_id, c.evaluating_accessibility_id,
        c.practicing_evaluation_repair_id, c.creating_pdfs_id, c.exam_3_id,
        c.introduction_to_optimizing_pdfs_id, c.checking_accessibility_id,
        c.reading_order_tool_id, c.content_order_and_tags_order_id
     FROM registrations r
     LEFT JOIN courses c ON r.course_id = c.id
     WHERE (r.status IN ('submitter', 'active', 'enrollee', 'completer', 'earner'))
     AND r.canvas_user_id IS NOT NULL
     AND r.course_id IS NOT NULL
     AND c.course_id IS NOT NULL"
);

error_log("Found " . count($recentUsers) . " real users to process");

$updatedCount = 0;
$errorCount = 0;

foreach ($recentUsers as $user) {
    // Use the correctly aliased canvas_course_id for the API call
    $courseId = $user['canvas_course_id'];
    
    error_log("Processing user: {$user['email']} (Status: {$user['status']})");
    
    // 1. Check submitter status
    if ($user['status'] === 'submitter') {
        // Use the enrollments API to check the user's status in the course
        $endpoint = "{$apiUrl}/courses/{$courseId}/enrollments?user_id={$user['canvas_user_id']}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $enrollments = json_decode($result, true);
            
            if (!empty($enrollments)) {
                $enrollment = $enrollments[0];
                if (isset($enrollment['enrollment_state']) && ($enrollment['enrollment_state'] === 'active' || $enrollment['enrollment_state'] === 'completed')) {
                    $db->update('registrations', 
                        [
                            'status' => 'active',
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 
                        'id = ?', 
                        [$user['id']]
                    );
                    error_log("Updated user {$user['email']} from submitter to active");
                    $updatedCount++;
                } else {
                    error_log("User {$user['email']} has not accepted invite yet (enrollment_state: {$enrollment['enrollment_state']})");
                }
            } else {
                error_log("No enrollment found for user {$user['email']}");
            }
        } else {
            error_log("API call failed for user {$user['email']} with HTTP code: $httpCode");
            $errorCount++;
        }
    }
    
    // 2. Check active status for ToU assignment completion
    if ($user['status'] === 'active' && !empty($user['tou_quiz_id'])) {
        $endpoint = "{$apiUrl}/courses/{$courseId}/assignments/{$user['tou_quiz_id']}/submissions/{$user['canvas_user_id']}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $submission = json_decode($result, true);
            if (isset($submission['score']) && $submission['score'] >= 1) {
                $db->update('registrations', 
                    [
                        'status' => 'enrollee',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 
                    'id = ?', 
                    [$user['id']]
                );
                error_log("Updated user {$user['email']} from active to enrollee (ToU completed)");
                $updatedCount++;
            } else {
                error_log("ToU assignment not completed or score < 1 for user {$user['email']}");
            }
        } else {
            error_log("ToU assignment API call failed for user {$user['email']} with HTTP code: $httpCode");
            $errorCount++;
        }
    } elseif ($user['status'] === 'active') {
        error_log("ToU assignment check skipped for user {$user['email']} (tou_quiz_id not configured)");
    }
    
    // 3. Check enrollee status for Exam 4 completion
    if ($user['status'] === 'enrollee' && !empty($user['exam_4_id'])) {
        $endpoint = "{$apiUrl}/courses/{$courseId}/assignments/{$user['exam_4_id']}/submissions/{$user['canvas_user_id']}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $submission = json_decode($result, true);
            if (isset($submission['score']) && $submission['score'] >= 1) {
                $db->update('registrations', 
                    [
                        'status' => 'completer',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 
                    'id = ?', 
                    [$user['id']]
                );
                error_log("Updated user {$user['email']} from enrollee to completer");
                $updatedCount++;
            } else {
                error_log("Exam 4 not completed or score < 1 for user {$user['email']}");
            }
        } else {
            error_log("Exam 4 API call failed for user {$user['email']} with HTTP code: $httpCode");
            $errorCount++;
        }
    }
    
    // 4. Check completer status for certificate eligibility
    if ($user['status'] === 'completer') {
        $totalScore = 0;
        $examScores = [];
        $allSubmitted = true;
        
        // Check all quizzes and exams
        $assignments = [
            'overview_of_document_accessibility_id', 'images_id', 'hyperlinks_id',
            'contrast_color_reliance_id', 'optimizing_writing_id', 'exam_1_id',
            'headings_in_word_id', 'optimizing_powerpoint_presentations_id',
            'lists_columns_id', 'tables_id', 'exam_2_id', 'evaluating_accessibility_id',
            'practicing_evaluation_repair_id', 'creating_pdfs_id', 'exam_3_id',
            'introduction_to_optimizing_pdfs_id', 'checking_accessibility_id',
            'reading_order_tool_id', 'content_order_and_tags_order_id', 'exam_4_id'
        ];
        
        foreach ($assignments as $assignmentId) {
            if (empty($user[$assignmentId])) continue;
            
            $endpoint = "{$apiUrl}/courses/{$courseId}/assignments/{$user[$assignmentId]}/submissions/{$user['canvas_user_id']}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $submission = json_decode($result, true);
                if (isset($submission['score'])) {
                    $totalScore += $submission['score'];
                    if (strpos($assignmentId, 'exam_') !== false) {
                        $examScores[] = $submission['score'];
                    }
                } else {
                    $allSubmitted = false;
                    break;
                }
            } else {
                $allSubmitted = false;
                break;
            }
        }
        
        if ($allSubmitted && $totalScore >= 93 && count($examScores) === 4 && min($examScores) >= 8) {
            $db->update('registrations', 
                [
                    'status' => 'earner', 
                    'earnerdate' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                'id = ?', 
                [$user['id']]
            );
            error_log("Updated user {$user['email']} from completer to earner");
            $updatedCount++;
        }
    }
    
    // 5. Check earner status for review cohort acceptance
    if ($user['status'] === 'earner') {
        $endpoint = "{$apiUrl}/courses/{$courseId}/enrollments?user_id={$user['canvas_user_id']}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $enrollments = json_decode($result, true);
            
            if (!empty($enrollments)) {
                $enrollment = $enrollments[0];
                if (isset($enrollment['enrollment_state']) && ($enrollment['enrollment_state'] === 'active' || $enrollment['enrollment_state'] === 'completed')) {
                    $db->update('registrations', 
                        [
                            'status' => 'review',
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 
                        'id = ?', 
                        [$user['id']]
                    );
                    error_log("Updated user {$user['email']} from earner to review");
                    $updatedCount++;
                }
            }
        } else {
            error_log("Review cohort API call failed for user {$user['email']} with HTTP code: $httpCode");
            $errorCount++;
        }
    }
}

// Log summary
error_log("Daily cron completed: $updatedCount updates, $errorCount errors");

// 5. Update quiz completion status for all users (KEEP THIS - it's fast and important)
if (file_exists('quiz_score_tracker.php')) {
    require_once 'quiz_score_tracker.php';
    $tracker = new QuizScoreTracker();
    $quizResults = $tracker->processAllQuizUpdates();
    
    // Log quiz tracking results
    error_log("Daily cron: Quiz tracking completed - {$quizResults['processed']} processed, {$quizResults['errors']} errors");
}

// REMOVED: Course import and points update (moved to weekly cron)
error_log("Daily cron: Course import skipped (moved to weekly cron for performance)");
?> 