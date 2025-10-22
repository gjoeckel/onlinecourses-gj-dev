<?php
require_once 'config.php';
require_once 'db.php';

class QuizScoreTracker {
    private $db;
    private $apiUrl;
    private $accessToken;
    
    // Hard-coded exclusion: total quiz points - 49 (for non-graded quizzes/exams)
    private $excludedPoints = 49;
    
    public function __construct() {
        $this->db = new Database();
        $this->apiUrl = $GLOBALS['config']['canvas']['api_url'];
        $this->accessToken = $GLOBALS['config']['canvas']['access_token'];
    }
    
    /**
     * Fetch all assignments for a course and calculate total points using omit_from_final_grade
     */
    public function calculateTotalQuizPoints($courseId) {
        $endpoint = "{$this->apiUrl}/courses/{$courseId}/assignments";
        
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
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch assignments from Canvas. HTTP Code: {$httpCode}");
        }
        
        $assignments = json_decode($result, true);
        if (!is_array($assignments)) {
            throw new Exception("Invalid response format for course {$courseId}");
        }
        
        $totalPoints = 0;
        $includedAssignments = [];
        $excludedAssignments = [];
        
        foreach ($assignments as $assignment) {
            $pointsPossible = isset($assignment['points_possible']) ? (int)$assignment['points_possible'] : 0;
            $omitFromFinalGrade = isset($assignment['omit_from_final_grade']) ? (bool)$assignment['omit_from_final_grade'] : false;
            
            if ($pointsPossible > 0) {
                if (!$omitFromFinalGrade) {
                    $totalPoints += $pointsPossible;
                    $includedAssignments[] = [
                        'id' => $assignment['id'],
                        'name' => $assignment['name'],
                        'points' => $pointsPossible
                    ];
                } else {
                    $excludedAssignments[] = [
                        'id' => $assignment['id'],
                        'name' => $assignment['name'],
                        'points' => $pointsPossible,
                        'reason' => 'omit_from_final_grade = true'
                    ];
                }
            }
        }
        
        return [
            'total_points' => $totalPoints,
            'included_assignments' => $includedAssignments,
            'excluded_assignments' => $excludedAssignments,
            'total_assignments' => count($assignments)
        ];
    }
    
    /**
     * Check if a quiz should be excluded based on title
     */
    private function shouldExcludeQuiz($quizTitle) {
        $excludedTitles = [
            'terms of use',
            'terms of service',
            'syllabus quiz',
            'course introduction',
            'welcome quiz'
        ];
        
        $title = strtolower($quizTitle);
        foreach ($excludedTitles as $excluded) {
            if (strpos($title, $excluded) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch quiz scores for a specific user in a course
     */
    public function fetchUserQuizScores($courseId, $userId) {
        // Use assignments endpoint to get all assignments including quizzes
        $endpoint = "{$this->apiUrl}/users/{$userId}/courses/{$courseId}/assignments?per_page=100";
        
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
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch assignments for user scores. HTTP Code: {$httpCode}");
        }
        
        $assignments = json_decode($result, true);
        $userScores = [];
        $missingQuizzes = [];
        
        foreach ($assignments as $assignment) {
            // Only process quiz assignments
            if (!isset($assignment['is_quiz_assignment']) || !$assignment['is_quiz_assignment']) {
                continue;
            }
            
            // Skip excluded quizzes
            if ($this->shouldExcludeQuiz($assignment['name'])) {
                continue;
            }
            
            // Check if user has submitted this assignment
            if ($assignment['has_submitted_submissions']) {
                $submission = $this->fetchAssignmentSubmission($courseId, $assignment['id'], $userId);
                
                if ($submission && isset($submission['score'])) {
                    $userScores[] = [
                        'quiz_id' => $assignment['quiz_id'] ?? $assignment['id'],
                        'quiz_title' => $assignment['name'],
                        'score' => $submission['score'],
                        'points_possible' => $assignment['points_possible'] ?? 0,
                        'submitted_at' => $submission['submitted_at'] ?? null,
                        'workflow_state' => $submission['workflow_state'] ?? 'submitted'
                    ];
                } else {
                    // Has submissions but no score (might be pending)
                    $missingQuizzes[] = [
                        'quiz_id' => $assignment['quiz_id'] ?? $assignment['id'],
                        'quiz_title' => $assignment['name'],
                        'points_possible' => $assignment['points_possible'] ?? 0,
                        'due_at' => $assignment['due_at'] ?? null,
                        'status' => 'submitted_no_score'
                    ];
                }
            } else {
                // No submissions
                $missingQuizzes[] = [
                    'quiz_id' => $assignment['quiz_id'] ?? $assignment['id'],
                    'quiz_title' => $assignment['name'],
                    'points_possible' => $assignment['points_possible'] ?? 0,
                    'due_at' => $assignment['due_at'] ?? null,
                    'status' => 'not_submitted'
                ];
            }
        }
        
        return [
            'scores' => $userScores,
            'missing_quizzes' => $missingQuizzes,
            'total_completed' => count($userScores),
            'total_missing' => count($missingQuizzes)
        ];
    }
    
    /**
     * Fetch a specific assignment submission for a user
     */
    private function fetchAssignmentSubmission($courseId, $assignmentId, $userId) {
        $endpoint = "{$this->apiUrl}/courses/{$courseId}/assignments/{$assignmentId}/submissions/{$userId}";
        
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
     * Update course total quiz points in database
     */
    public function updateCourseQuizPoints($courseId, $totalPoints) {
        try {
            $this->db->update('courses', 
                ['total_quiz_points' => $totalPoints], 
                'course_id = ?', 
                [$courseId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Failed to update course quiz points: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate and update total quiz points for a course
     */
    public function updateCoursePoints($courseId) {
        try {
            $pointsData = $this->calculateTotalQuizPoints($courseId);
            
            $data = [
                'total_quiz_points' => $pointsData['total_points'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('courses', $data, 'course_id = ?', [$courseId]);
            
            return [
                'success' => true,
                'course_id' => $courseId,
                'total_points' => $pointsData['total_points'],
                'included_count' => count($pointsData['included_assignments']),
                'excluded_count' => count($pointsData['excluded_assignments'])
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update total quiz points for all courses in the database
     */
    public function updateAllCoursePoints() {
        $courses = $this->db->select("SELECT course_id FROM courses WHERE course_id IS NOT NULL");
        
        $results = [
            'total_courses' => count($courses),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'details' => []
        ];
        
        foreach ($courses as $course) {
            $courseId = $course['course_id'];
            $result = $this->updateCoursePoints($courseId);
            
            $results['details'][] = $result;
            
            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Course {$courseId}: " . $result['error'];
            }
        }
        
        return $results;
    }
    
    /**
     * Update user quiz completion status in database
     */
    public function updateUserQuizStatus($registrationId, $quizScores, $missingQuizzes) {
        try {
            $data = [
                'quiz_completion_status' => json_encode($quizScores),
                'missing_quizzes' => json_encode($missingQuizzes)
            ];
            
            $this->db->update('registrations', 
                $data, 
                'id = ?', 
                [$registrationId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Failed to update user quiz status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users who need quiz status updates
     */
    public function getUsersNeedingQuizUpdates() {
        return $this->db->select(
            "SELECT r.id, r.canvas_user_id, c.course_id as canvas_course_id 
             FROM registrations r 
             LEFT JOIN courses c ON r.course_id = c.id 
             WHERE r.status IN ('active', 'enrollee', 'completer') 
             AND r.canvas_user_id IS NOT NULL 
             AND c.course_id IS NOT NULL"
        );
    }
    
    /**
     * Process quiz updates for all users
     */
    public function processAllQuizUpdates() {
        $users = $this->getUsersNeedingQuizUpdates();
        $results = [
            'processed' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        foreach ($users as $user) {
            try {
                $quizData = $this->fetchUserQuizScores($user['canvas_course_id'], $user['canvas_user_id']);
                $this->updateUserQuizStatus($user['id'], $quizData['scores'], $quizData['missing_quizzes']);
                
                $results['processed']++;
                $results['details'][] = [
                    'user_id' => $user['canvas_user_id'],
                    'completed_quizzes' => $quizData['total_completed'],
                    'missing_quizzes' => $quizData['total_missing']
                ];
            } catch (Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'user_id' => $user['canvas_user_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    $tracker = new QuizScoreTracker();
    
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'calculate-points':
                if (!isset($argv[2])) {
                    echo "Usage: php quiz_score_tracker.php calculate-points <course_id>\n";
                    exit(1);
                }
                $courseId = $argv[2];
                $points = $tracker->calculateTotalQuizPoints($courseId);
                echo "Total Quiz Points: {$points['total_points']}\n";
                echo "Included assignments: " . count($points['included_assignments']) . "\n";
                echo "Excluded assignments: " . count($points['excluded_assignments']) . "\n";
                echo "Total assignments: {$points['total_assignments']}\n";
                break;
                
            case 'update-course-points':
                if (!isset($argv[2])) {
                    echo "Usage: php quiz_score_tracker.php update-course-points <course_id>\n";
                    exit(1);
                }
                $courseId = $argv[2];
                $result = $tracker->updateCoursePoints($courseId);
                
                if ($result['success']) {
                    echo "Successfully updated course {$result['course_id']} with {$result['total_points']} total points\n";
                    echo "Included assignments: {$result['included_count']}\n";
                    echo "Excluded assignments: {$result['excluded_count']}\n";
                } else {
                    echo "Failed to update course {$result['course_id']}: {$result['error']}\n";
                }
                break;
                
            case 'update-all-points':
                echo "Updating total quiz points for all courses...\n";
                $results = $tracker->updateAllCoursePoints();
                
                echo "Results:\n";
                echo "Total courses: {$results['total_courses']}\n";
                echo "Successful: {$results['successful']}\n";
                echo "Failed: {$results['failed']}\n";
                
                if (!empty($results['errors'])) {
                    echo "\nErrors:\n";
                    foreach (array_slice($results['errors'], 0, 10) as $error) {
                        echo "- {$error}\n";
                    }
                    if (count($results['errors']) > 10) {
                        echo "... and " . (count($results['errors']) - 10) . " more errors\n";
                    }
                }
                break;
                
            case 'update-all':
                $results = $tracker->processAllQuizUpdates();
                echo "Processed: {$results['processed']}\n";
                echo "Errors: {$results['errors']}\n";
                
                // Show detailed results
                if (!empty($results['details'])) {
                    echo "\nDetailed Results:\n";
                    foreach ($results['details'] as $detail) {
                        if (isset($detail['error'])) {
                            echo "ERROR - User {$detail['user_id']}: {$detail['error']}\n";
                        } else {
                            echo "SUCCESS - User {$detail['user_id']}: {$detail['completed_quizzes']} completed, {$detail['missing_quizzes']} missing\n";
                        }
                    }
                }
                break;
                
            case 'test-user':
                if (!isset($argv[2]) || !isset($argv[3])) {
                    echo "Usage: php quiz_score_tracker.php test-user <canvas_user_id> <canvas_course_id>\n";
                    exit(1);
                }
                $userId = $argv[2];
                $courseId = $argv[3];
                try {
                    $quizData = $tracker->fetchUserQuizScores($courseId, $userId);
                    echo "Quiz Data for User {$userId} in Course {$courseId}:\n";
                    echo "Completed Quizzes: {$quizData['total_completed']}\n";
                    echo "Missing Quizzes: {$quizData['total_missing']}\n";
                    
                    if (!empty($quizData['scores'])) {
                        echo "\nCompleted Quizzes:\n";
                        foreach ($quizData['scores'] as $score) {
                            echo "- {$score['quiz_title']}: {$score['score']}/{$score['points_possible']}\n";
                        }
                    }
                    
                    if (!empty($quizData['missing_quizzes'])) {
                        echo "\nMissing Quizzes:\n";
                        foreach ($quizData['missing_quizzes'] as $missing) {
                            echo "- {$missing['quiz_title']} (Status: {$missing['status']})\n";
                        }
                    }
                } catch (Exception $e) {
                    echo "ERROR: " . $e->getMessage() . "\n";
                }
                break;
                
            default:
                echo "Unknown command. Available commands: calculate-points, update-course-points, update-all-points, update-all, test-user\n";
                exit(1);
        }
    } else {
        echo "Usage: php quiz_score_tracker.php <command> [args]\n";
        echo "Commands:\n";
        echo "  calculate-points <course_id> - Calculate total quiz points for a course\n";
        echo "  update-course-points <course_id> - Update total quiz points for a course in database\n";
        echo "  update-all-points - Update total quiz points for all courses\n";
        echo "  update-all - Update quiz status for all users\n";
        echo "  test-user <canvas_user_id> <canvas_course_id> - Test quiz fetching for specific user\n";
    }
}
?> 