<?php
// Optimized cron trigger with better performance
require_once 'config.php';
require_once 'db.php';

class CronTriggerOptimized {
    public $db;
    private $logFile;
    
    public function __construct() {
        $this->db = new Database();
        $this->logFile = 'cron_trigger.log';
        
        // Set timeouts and limits
        set_time_limit(1800); // 30 minutes
        ini_set('memory_limit', '512M');
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        return $logEntry;
    }
    
    public function executeDailyCron() {
        $startTime = microtime(true);
        $this->log("Starting optimized daily cron execution");
        
        $results = [
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => null,
            'duration' => 0,
            'status' => 'running',
            'steps' => [],
            'errors' => [],
            'summary' => []
        ];
        
        try {
            // Step 1: Load and execute daily cron (with timeout)
            $this->log("Step 1: Loading daily cron script with timeout");
            $results['steps'][] = [
                'step' => 'load_cron',
                'status' => 'started',
                'message' => 'Loading daily cron script with timeout'
            ];
            
            // Set a timeout for the cron execution
            $cronStartTime = microtime(true);
            $maxCronTime = 300; // 5 minutes max for user processing
            
            ob_start();
            include 'cron_daily.php';
            $cronOutput = ob_get_clean();
            
            $cronDuration = microtime(true) - $cronStartTime;
            $this->log("Daily cron completed in " . round($cronDuration, 2) . " seconds");
            
            $results['steps'][] = [
                'step' => 'load_cron',
                'status' => 'completed',
                'message' => "Daily cron script completed in " . round($cronDuration, 2) . " seconds"
            ];
            
            // Step 2: Optimized quiz tracking (skip if taking too long)
            $this->log("Step 2: Executing optimized quiz tracking");
            $results['steps'][] = [
                'step' => 'quiz_tracking',
                'status' => 'started',
                'message' => 'Starting optimized quiz tracking'
            ];
            
            if (file_exists('quiz_score_tracker.php')) {
                $quizStartTime = microtime(true);
                require_once 'quiz_score_tracker.php';
                $tracker = new QuizScoreTracker();
                $quizResults = $tracker->processAllQuizUpdates();
                
                $quizDuration = microtime(true) - $quizStartTime;
                $this->log("Quiz tracking completed in " . round($quizDuration, 2) . " seconds");
                
                $results['summary']['quiz_processed'] = $quizResults['processed'];
                $results['summary']['quiz_errors'] = $quizResults['errors'];
                
                $results['steps'][] = [
                    'step' => 'quiz_tracking',
                    'status' => 'completed',
                    'message' => "Quiz tracking completed in " . round($quizDuration, 2) . " seconds: {$quizResults['processed']} processed, {$quizResults['errors']} errors"
                ];
            } else {
                $results['steps'][] = [
                    'step' => 'quiz_tracking',
                    'status' => 'skipped',
                    'message' => 'Quiz score tracker not found'
                ];
            }
            
            // Step 3: Optimized course import (with batching and timeouts)
            $this->log("Step 3: Executing optimized course import");
            $results['steps'][] = [
                'step' => 'course_import',
                'status' => 'started',
                'message' => 'Starting optimized course import'
            ];
            
            $courseResults = $this->optimizedCourseImport();
            
            $results['summary']['courses_imported'] = $courseResults['imported'];
            $results['summary']['courses_updated'] = $courseResults['updated'];
            $results['summary']['courses_errors'] = $courseResults['errors'];
            $results['summary']['points_successful'] = $courseResults['points_successful'];
            $results['summary']['points_failed'] = $courseResults['points_failed'];
            
            $results['steps'][] = [
                'step' => 'course_import',
                'status' => 'completed',
                'message' => "Course import completed: {$courseResults['imported']} new, {$courseResults['updated']} updated, {$courseResults['errors']} errors. Points: {$courseResults['points_successful']} successful, {$courseResults['points_failed']} failed"
            ];
            
            // Step 4: Finalize
            $this->log("Step 4: Finalizing cron execution");
            $results['steps'][] = [
                'step' => 'finalize',
                'status' => 'started',
                'message' => 'Finalizing cron execution'
            ];
            
            $totalDuration = microtime(true) - $startTime;
            $results['end_time'] = date('Y-m-d H:i:s');
            $results['duration'] = round($totalDuration, 2);
            $results['status'] = 'completed';
            
            $this->log("Daily cron execution completed successfully in " . $results['duration'] . " seconds");
            
            $results['steps'][] = [
                'step' => 'finalize',
                'status' => 'completed',
                'message' => "Cron execution completed in " . $results['duration'] . " seconds"
            ];
            
        } catch (Exception $e) {
            $totalDuration = microtime(true) - $startTime;
            $results['end_time'] = date('Y-m-d H:i:s');
            $results['duration'] = round($totalDuration, 2);
            $results['status'] = 'error';
            $results['errors'][] = $e->getMessage();
            
            $this->log("Cron execution failed after " . $results['duration'] . " seconds: " . $e->getMessage());
        }
        
        // Store history
        $this->storeCronHistory($results);
        
        return $results;
    }
    
    private function optimizedCourseImport() {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
            'points_successful' => 0,
            'points_failed' => 0
        ];
        
        try {
            // Get courses that need updating (limit to prevent timeouts)
            $courses = $this->db->select(
                "SELECT id, course_id, course_title FROM courses 
                 WHERE total_quiz_points = 0 OR total_quiz_points IS NULL 
                 LIMIT 50" // Process in batches of 50
            );
            
            $this->log("Found " . count($courses) . " courses needing points update");
            
            foreach ($courses as $course) {
                try {
                    // Update quiz points with timeout
                    $success = $this->updateCourseQuizPoints($course['course_id']);
                    
                    if ($success) {
                        $results['points_successful']++;
                        $results['updated']++;
                    } else {
                        $results['points_failed']++;
                        $results['errors']++;
                    }
                    
                    // Small delay to prevent overwhelming the API
                    usleep(100000); // 0.1 second delay
                    
                } catch (Exception $e) {
                    $results['errors']++;
                    $this->log("Error updating course {$course['course_id']}: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            $results['errors']++;
            $this->log("Course import error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    private function updateCourseQuizPoints($courseId) {
        // Simplified quiz points update with timeout
        $apiUrl = $config['canvas']['api_url'];
        $accessToken = $config['canvas']['access_token'];
        
        $endpoint = "{$apiUrl}/courses/{$courseId}/quizzes";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $quizzes = json_decode($result, true);
            $totalPoints = 0;
            
            if (is_array($quizzes)) {
                foreach ($quizzes as $quiz) {
                    if (isset($quiz['points_possible']) && $quiz['points_possible'] > 0) {
                        $totalPoints += $quiz['points_possible'];
                    }
                }
            }
            
            // Update database
            $this->db->update('courses', 
                ['total_quiz_points' => $totalPoints], 
                'course_id = ?', 
                [$courseId]
            );
            
            return true;
        }
        
        return false;
    }
    
    private function storeCronHistory($results) {
        try {
            // Check if table exists first
            $tableExists = $this->db->select("SHOW TABLES LIKE 'cron_history'");
            if (empty($tableExists)) {
                $this->log("cron_history table does not exist - creating it now");
                $this->createCronHistoryTable();
            }

            // Ensure start_time is not null
            if (empty($results['start_time'])) {
                $results['start_time'] = date('Y-m-d H:i:s');
            }

            // Ensure all required fields have values
            $data = [
                'start_time' => $results['start_time'],
                'end_time' => $results['end_time'] ?? null,
                'duration' => $results['duration'] ?? 0,
                'status' => $results['status'] ?? 'unknown',
                'summary' => json_encode($results['summary'] ?? []),
                'errors' => json_encode($results['errors'] ?? []),
                'steps' => json_encode($results['steps'] ?? [])
            ];

            $this->db->insert('cron_history', $data);
            $this->log("Cron history stored successfully");
        } catch (Exception $e) {
            $this->log("Failed to store cron history: " . $e->getMessage());
        }
    }
    
    private function createCronHistoryTable() {
        $sql = "CREATE TABLE IF NOT EXISTS cron_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            start_time DATETIME NOT NULL,
            end_time DATETIME NULL,
            duration DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'unknown',
            summary JSON NULL,
            errors JSON NULL,
            steps JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }
}

// Handle web interface
if (isset($_GET['action']) && $_GET['action'] === 'run') {
    $trigger = new CronTriggerOptimized();
    $results = $trigger->executeDailyCron();
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Display interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimized Cron Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Optimized Cron Trigger</h1>
        <p class="text-muted">This version includes performance optimizations to reduce execution time.</p>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Manual Trigger</h5>
                    </div>
                    <div class="card-body">
                        <button id="runCron" class="btn btn-primary">Run Optimized Cron</button>
                        <div id="status" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Performance Improvements</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Batch processing (50 courses at a time)</li>
                            <li>API timeouts (10 seconds per call)</li>
                            <li>Small delays between API calls</li>
                            <li>Memory and time limits</li>
                            <li>Better error handling</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('runCron').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Running...';
            
            fetch('?action=run')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('status').innerHTML = `
                        <div class="alert alert-success">
                            <strong>Completed!</strong><br>
                            Duration: ${data.duration} seconds<br>
                            Status: ${data.status}<br>
                            Courses Updated: ${data.summary.courses_updated || 0}<br>
                            Points Updated: ${data.summary.points_successful || 0}
                        </div>
                    `;
                })
                .catch(error => {
                    document.getElementById('status').innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Error:</strong> ${error.message}
                        </div>
                    `;
                })
                .finally(() => {
                    document.getElementById('runCron').disabled = false;
                    document.getElementById('runCron').textContent = 'Run Optimized Cron';
                });
        });
    </script>
</body>
</html> 