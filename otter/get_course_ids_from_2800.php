<?php
/**
 * Get Course IDs from Canvas Starting from 2800
 * 
 * Simple script to get just the course IDs from Canvas starting from course ID 2800
 */

require_once __DIR__ . '/lib/canvas_api.php';

class CourseIdRetriever {
    private $canvas_api;
    
    public function __construct() {
        $this->canvas_api = new CanvasAPI();
    }
    
    /**
     * Get course IDs starting from a specific ID
     */
    public function getCourseIdsFrom($start_from_id = 2800) {
        echo "=== GETTING COURSE IDs FROM $start_from_id ONWARDS ===\n";
        echo str_repeat("=", 60) . "\n";
        
        try {
            // Test connection
            $account_info = $this->canvas_api->getAccountInfo();
            if (isset($account_info['error'])) {
                echo "❌ Canvas API Error: " . $account_info['error'] . "\n";
                return false;
            }
            
            echo "✅ Connected to Canvas API\n";
            echo "Account: " . ($account_info['name'] ?? 'Unknown') . "\n\n";
            
            // Get all courses
            $courses = $this->canvas_api->getAllAccountCourses(['per_page' => 100]);
            
            if (isset($courses['error'])) {
                echo "❌ Error fetching courses: " . $courses['error'] . "\n";
                return false;
            }
            
            echo "Found " . count($courses) . " total courses in Canvas\n\n";
            
            // Filter courses starting from the specified ID
            $filtered_courses = array_filter($courses, function($course) use ($start_from_id) {
                $course_id = intval($course['id'] ?? 0);
                return $course_id >= $start_from_id;
            });
            
            // Sort by course ID
            usort($filtered_courses, function($a, $b) {
                return intval($a['id']) - intval($b['id']);
            });
            
            echo "Found " . count($filtered_courses) . " courses with ID >= $start_from_id\n\n";
            
            if (empty($filtered_courses)) {
                echo "No courses found with ID >= $start_from_id\n";
                return [];
            }
            
            // Display course information
            echo "=== COURSES FROM ID $start_from_id ONWARDS ===\n";
            echo str_pad("Course ID", 12) . str_pad("Course Name", 60) . str_pad("Status", 15) . "\n";
            echo str_repeat("-", 90) . "\n";
            
            $course_ids = [];
            foreach ($filtered_courses as $course) {
                $course_id = $course['id'];
                $course_name = $course['name'] ?? 'Unnamed Course';
                $status = $course['workflow_state'] ?? 'Unknown';
                
                echo str_pad($course_id, 12) . str_pad(substr($course_name, 0, 57) . (strlen($course_name) > 57 ? '...' : ''), 60) . str_pad($status, 15) . "\n";
                
                $course_ids[] = $course_id;
            }
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "COURSE IDs (comma-separated):\n";
            echo str_repeat("=", 60) . "\n";
            echo implode(', ', $course_ids) . "\n\n";
            
            echo "COURSE IDs (one per line):\n";
            echo str_repeat("=", 60) . "\n";
            foreach ($course_ids as $course_id) {
                echo $course_id . "\n";
            }
            
            return $course_ids;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $start_from_id = isset($argv[1]) ? (int)$argv[1] : 2800;
    
    $retriever = new CourseIdRetriever();
    $retriever->getCourseIdsFrom($start_from_id);
}
?>
