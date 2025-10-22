<?php
/**
 * Get 5 New/Future Courses from Canvas
 * 
 * This script retrieves the 5 most recent or future courses from Canvas
 * and displays their course IDs for easy reference.
 */

require_once __DIR__ . '/lib/canvas_api.php';

class NewCoursesRetriever {
    private $canvas_api;
    
    public function __construct() {
        $this->canvas_api = new CanvasAPI();
    }
    
    /**
     * Get the 5 newest courses from Canvas
     */
    public function getNewCourses($limit = 5) {
        echo "=== GETTING 5 NEWEST COURSES FROM CANVAS ===\n";
        echo "Limit: $limit courses\n";
        echo str_repeat("=", 60) . "\n";
        
        try {
            // Test connection first
            echo "Testing Canvas API connection...\n";
            $account_info = $this->canvas_api->getAccountInfo();
            
            if (isset($account_info['error'])) {
                echo "❌ Canvas API Error: " . $account_info['error'] . "\n";
                return false;
            }
            
            echo "✅ Connected to Canvas API\n";
            echo "Account: " . ($account_info['name'] ?? 'Unknown') . "\n\n";
            
            // Get all courses from Canvas
            echo "Fetching all courses from Canvas...\n";
            $courses = $this->canvas_api->getAllAccountCourses(['per_page' => 100]);
            
            if (isset($courses['error'])) {
                echo "❌ Error fetching courses: " . $courses['error'] . "\n";
                return false;
            }
            
            echo "Found " . count($courses) . " total courses\n\n";
            
            // Sort courses by creation date (newest first)
            // Canvas courses are typically returned in creation order, but let's sort by created_at if available
            usort($courses, function($a, $b) {
                $date_a = $a['created_at'] ?? $a['id'] ?? 0;
                $date_b = $b['created_at'] ?? $b['id'] ?? 0;
                
                // If we have created_at timestamps, use them
                if (isset($a['created_at']) && isset($b['created_at'])) {
                    return strtotime($date_b) - strtotime($date_a);
                }
                
                // Otherwise, sort by course ID (higher ID = newer course)
                return intval($date_b) - intval($date_a);
            });
            
            // Get the first 5 courses (newest)
            $newest_courses = array_slice($courses, 0, $limit);
            
            echo "=== 5 NEWEST COURSES ===\n";
            echo str_pad("Rank", 6) . str_pad("Course ID", 12) . str_pad("Course Name", 50) . str_pad("Created", 20) . str_pad("Status", 15) . "\n";
            echo str_repeat("-", 110) . "\n";
            
            $course_ids = [];
            foreach ($newest_courses as $index => $course) {
                $rank = $index + 1;
                $course_id = $course['id'] ?? 'N/A';
                $course_name = substr($course['name'] ?? 'Unnamed Course', 0, 47) . (strlen($course['name'] ?? '') > 47 ? '...' : '');
                $created_at = isset($course['created_at']) ? date('Y-m-d H:i', strtotime($course['created_at'])) : 'N/A';
                $status = $course['workflow_state'] ?? 'Unknown';
                
                echo str_pad($rank, 6) . str_pad($course_id, 12) . str_pad($course_name, 50) . str_pad($created_at, 20) . str_pad($status, 15) . "\n";
                
                $course_ids[] = $course_id;
            }
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "COURSE IDs FOR THE 5 NEWEST COURSES:\n";
            echo str_repeat("=", 60) . "\n";
            
            foreach ($course_ids as $index => $course_id) {
                echo ($index + 1) . ". " . $course_id . "\n";
            }
            
            echo "\nComma-separated list: " . implode(', ', $course_ids) . "\n";
            
            return $course_ids;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Get courses with future start dates
     */
    public function getFutureCourses($limit = 5) {
        echo "=== GETTING FUTURE COURSES FROM CANVAS ===\n";
        echo "Limit: $limit courses\n";
        echo str_repeat("=", 60) . "\n";
        
        try {
            // Get all courses
            $courses = $this->canvas_api->getAllAccountCourses(['per_page' => 100]);
            
            if (isset($courses['error'])) {
                echo "❌ Error fetching courses: " . $courses['error'] . "\n";
                return false;
            }
            
            echo "Found " . count($courses) . " total courses\n\n";
            
            // Filter for courses with future start dates
            $future_courses = array_filter($courses, function($course) {
                if (!isset($course['start_at']) || empty($course['start_at'])) {
                    return false;
                }
                
                $start_date = strtotime($course['start_at']);
                $now = time();
                
                return $start_date > $now;
            });
            
            // Sort by start date (soonest first)
            usort($future_courses, function($a, $b) {
                $date_a = strtotime($a['start_at']);
                $date_b = strtotime($b['start_at']);
                return $date_a - $date_b;
            });
            
            // Get the first 5 future courses
            $future_courses = array_slice($future_courses, 0, $limit);
            
            if (empty($future_courses)) {
                echo "No future courses found.\n";
                return [];
            }
            
            echo "=== FUTURE COURSES ===\n";
            echo str_pad("Rank", 6) . str_pad("Course ID", 12) . str_pad("Course Name", 50) . str_pad("Start Date", 20) . str_pad("Status", 15) . "\n";
            echo str_repeat("-", 110) . "\n";
            
            $course_ids = [];
            foreach ($future_courses as $index => $course) {
                $rank = $index + 1;
                $course_id = $course['id'] ?? 'N/A';
                $course_name = substr($course['name'] ?? 'Unnamed Course', 0, 47) . (strlen($course['name'] ?? '') > 47 ? '...' : '');
                $start_date = date('Y-m-d H:i', strtotime($course['start_at']));
                $status = $course['workflow_state'] ?? 'Unknown';
                
                echo str_pad($rank, 6) . str_pad($course_id, 12) . str_pad($course_name, 50) . str_pad($start_date, 20) . str_pad($status, 15) . "\n";
                
                $course_ids[] = $course_id;
            }
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "COURSE IDs FOR FUTURE COURSES:\n";
            echo str_repeat("=", 60) . "\n";
            
            foreach ($course_ids as $index => $course_id) {
                echo ($index + 1) . ". " . $course_id . "\n";
            }
            
            echo "\nComma-separated list: " . implode(', ', $course_ids) . "\n";
            
            return $course_ids;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $retriever = new NewCoursesRetriever();
    
    $command = $argv[1] ?? 'newest';
    $limit = isset($argv[2]) ? (int)$argv[2] : 5;
    
    switch ($command) {
        case 'newest':
            $retriever->getNewCourses($limit);
            break;
            
        case 'future':
            $retriever->getFutureCourses($limit);
            break;
            
        default:
            echo "Usage:\n";
            echo "  php get_new_courses.php newest [limit]  - Get newest courses (default: 5)\n";
            echo "  php get_new_courses.php future [limit]  - Get future courses (default: 5)\n";
            break;
    }
}
?>
