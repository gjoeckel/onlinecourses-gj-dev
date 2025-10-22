<?php
/**
 * Import Courses from Canvas Starting from Course ID 2800
 * 
 * This script imports courses from Canvas starting from a specific course ID,
 * skipping duplicates and ensuring course_id is properly stored in the database.
 */

require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/new_database_service.php';
require_once __DIR__ . '/lib/canvas_api.php';

class CanvasCourseImporter {
    private $db;
    private $canvas_api;
    private $import_stats = [
        'total_canvas_courses' => 0,
        'courses_processed' => 0,
        'courses_added' => 0,
        'courses_skipped' => 0,
        'errors' => 0,
        'error_details' => []
    ];

    public function __construct() {
        $this->db = new NewDatabaseService();
        $this->canvas_api = new CanvasAPI();
    }

    /**
     * Check if courses table exists and has the right structure
     */
    private function checkCoursesTable() {
        echo "--- Checking Courses Table ---\n";
        
        $conn = $this->db->getDbConnection();
        if (!$conn) {
            throw new Exception('Database connection failed');
        }

        // Check if table exists
        $conn->query("SHOW TABLES LIKE 'courses'");
        $table_exists = $conn->fetchArray();
        
        if (!$table_exists) {
            echo "❌ Courses table does not exist. Creating it...\n";
            $this->createCoursesTable();
        } else {
            echo "✅ Courses table exists\n";
        }

        // Check table structure
        $conn->query("DESCRIBE courses");
        $columns = $conn->fetchAll();
        
        $has_course_id = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'course_id') {
                $has_course_id = true;
                break;
            }
        }

        if (!$has_course_id) {
            echo "❌ courses table doesn't have course_id column. This is unexpected.\n";
            throw new Exception("courses table structure is not as expected");
        } else {
            echo "✅ course_id column exists (will store Canvas course IDs here)\n";
        }

        return true;
    }

    /**
     * Create courses table if it doesn't exist
     */
    private function createCoursesTable() {
        $conn = $this->db->getDbConnection();
        
        $sql = "
            CREATE TABLE courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_title VARCHAR(255) NOT NULL,
                canvas_course_id VARCHAR(50) NULL,
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_course_title (course_title),
                INDEX idx_canvas_course_id (canvas_course_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->query($sql);
        echo "✅ Courses table created successfully\n";
    }

    /**
     * Get existing course IDs from database
     */
    private function getExistingCourseIds() {
        $conn = $this->db->getDbConnection();
        $conn->query("SELECT course_id FROM courses WHERE course_id IS NOT NULL");
        $existing = $conn->fetchAll();
        
        $existing_ids = [];
        foreach ($existing as $row) {
            $existing_ids[] = $row['course_id'];
        }
        
        echo "Found " . count($existing_ids) . " existing courses in database\n";
        return $existing_ids;
    }

    /**
     * Import courses from Canvas starting from a specific course ID
     */
    public function importCoursesFromCanvas($start_from_course_id = 2800, $dry_run = true) {
        echo "=== IMPORTING COURSES FROM CANVAS ===\n";
        echo "Starting from course ID: $start_from_course_id\n";
        echo "Mode: " . ($dry_run ? "DRY RUN" : "LIVE IMPORT") . "\n";
        echo str_repeat("=", 60) . "\n";

        try {
            // Check courses table
            $this->checkCoursesTable();
            
            // Get existing course IDs
            $existing_course_ids = $this->getExistingCourseIds();
            
            // Test Canvas API connection
            echo "\n--- Testing Canvas API Connection ---\n";
            $account_info = $this->canvas_api->getAccountInfo();
            
            if (isset($account_info['error'])) {
                throw new Exception("Canvas API Error: " . $account_info['error']);
            }
            
            echo "✅ Connected to Canvas API\n";
            echo "Account: " . ($account_info['name'] ?? 'Unknown') . "\n";
            
            // Get all courses from Canvas
            echo "\n--- Fetching Courses from Canvas ---\n";
            $courses = $this->canvas_api->getAllAccountCourses(['per_page' => 100]);
            
            if (isset($courses['error'])) {
                throw new Exception("Failed to get Canvas courses: " . $courses['error']);
            }
            
            $this->import_stats['total_canvas_courses'] = count($courses);
            echo "Found " . count($courses) . " total courses in Canvas\n";
            
            // Filter courses starting from the specified course ID
            $filtered_courses = array_filter($courses, function($course) use ($start_from_course_id) {
                $course_id = intval($course['id'] ?? 0);
                return $course_id >= $start_from_course_id;
            });
            
            // Sort by course ID (ascending)
            usort($filtered_courses, function($a, $b) {
                return intval($a['id']) - intval($b['id']);
            });
            
            echo "Found " . count($filtered_courses) . " courses with ID >= $start_from_course_id\n\n";
            
            if (empty($filtered_courses)) {
                echo "No courses found with ID >= $start_from_course_id\n";
                return;
            }
            
            // Process each course
            echo "--- Processing Courses ---\n";
            echo str_pad("Course ID", 12) . str_pad("Course Name", 50) . str_pad("Action", 15) . str_pad("Reason", 20) . "\n";
            echo str_repeat("-", 100) . "\n";
            
            foreach ($filtered_courses as $course) {
                $this->import_stats['courses_processed']++;
                
                $course_id = $course['id'];
                $course_name = $course['name'] ?? 'Unnamed Course';
                $course_code = $course['course_code'] ?? '';
                $description = $course['public_description'] ?? '';
                
                // Check if course already exists
                if (in_array($course_id, $existing_course_ids)) {
                    $this->import_stats['courses_skipped']++;
                    echo str_pad($course_id, 12) . str_pad(substr($course_name, 0, 47) . (strlen($course_name) > 47 ? '...' : ''), 50) . str_pad("SKIPPED", 15) . str_pad("Already exists", 20) . "\n";
                    continue;
                }
                
                // Add course to database
                if (!$dry_run) {
                    $success = $this->addCourseToDatabase($course_id, $course_name, $course_code, $description);
                    
                    if ($success) {
                        $this->import_stats['courses_added']++;
                        echo str_pad($course_id, 12) . str_pad(substr($course_name, 0, 47) . (strlen($course_name) > 47 ? '...' : ''), 50) . str_pad("ADDED", 15) . str_pad("Success", 20) . "\n";
                    } else {
                        $this->import_stats['errors']++;
                        $this->import_stats['error_details'][] = "Failed to add course $course_id: $course_name";
                        echo str_pad($course_id, 12) . str_pad(substr($course_name, 0, 47) . (strlen($course_name) > 47 ? '...' : ''), 50) . str_pad("ERROR", 15) . str_pad("Database error", 20) . "\n";
                    }
                } else {
                    $this->import_stats['courses_added']++;
                    echo str_pad($course_id, 12) . str_pad(substr($course_name, 0, 47) . (strlen($course_name) > 47 ? '...' : ''), 50) . str_pad("WOULD ADD", 15) . str_pad("Dry run", 20) . "\n";
                }
            }
            
            // Display summary
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "IMPORT SUMMARY\n";
            echo str_repeat("=", 60) . "\n";
            echo "Total Canvas courses: " . $this->import_stats['total_canvas_courses'] . "\n";
            echo "Courses processed: " . $this->import_stats['courses_processed'] . "\n";
            echo "Courses " . ($dry_run ? "would be " : "") . "added: " . $this->import_stats['courses_added'] . "\n";
            echo "Courses skipped: " . $this->import_stats['courses_skipped'] . "\n";
            echo "Errors: " . $this->import_stats['errors'] . "\n";
            
            if (!empty($this->import_stats['error_details'])) {
                echo "\nError Details:\n";
                foreach ($this->import_stats['error_details'] as $error) {
                    echo "  - $error\n";
                }
            }
            
            if ($dry_run) {
                echo "\n⚠️ This was a DRY RUN. No changes were made to the database.\n";
                echo "Run with --live to actually import the courses.\n";
            } else {
                echo "\n✅ Import completed successfully!\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Add a course to the database
     */
    private function addCourseToDatabase($canvas_course_id, $course_title, $course_code = '', $description = '') {
        $conn = $this->db->getDbConnection();
        
        try {
            // Use the existing table structure - course_id stores the Canvas course ID
            // Use the same method as setup_courses_table.php
            $conn->query("INSERT INTO courses (course_id, course_title) VALUES (?, ?)", $canvas_course_id, $course_title);
            return true;
            
        } catch (Exception $e) {
            $this->import_stats['error_details'][] = "Database error for course $canvas_course_id: " . $e->getMessage();
            return false;
        }
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $start_from_id = isset($argv[1]) ? (int)$argv[1] : 2800;
    $dry_run = !in_array('--live', $argv);
    
    $importer = new CanvasCourseImporter();
    $importer->importCoursesFromCanvas($start_from_id, $dry_run);
}
?>
