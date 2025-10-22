<?php
/**
 * Setup Courses Table
 * 
 * This script creates the courses table if it doesn't exist,
 * or shows you the current structure if it does.
 */

require_once __DIR__ . '/lib/new_database_service.php';

class CoursesTableSetup {
    private $db;
    
    public function __construct() {
        $this->db = new NewDatabaseService();
    }
    
    /**
     * Check if courses table exists
     */
    public function checkCoursesTable() {
        echo "=== CHECKING COURSES TABLE ===\n";
        
        $conn = $this->db->getDbConnection();
        if (!$conn) {
            echo "❌ Database connection failed\n";
            return false;
        }
        
        // Check if table exists
        $conn->query("SHOW TABLES LIKE 'courses'");
        $table_exists = $conn->fetchArray();
        
        if ($table_exists) {
            echo "✅ Courses table exists\n";
            $this->showTableStructure();
            return true;
        } else {
            echo "❌ Courses table does not exist\n";
            return false;
        }
    }
    
    /**
     * Show current table structure
     */
    public function showTableStructure() {
        echo "\n--- Current Table Structure ---\n";
        
        $conn = $this->db->getDbConnection();
        if (!$conn) {
            return;
        }
        
        $conn->query("DESCRIBE courses");
        $columns = $conn->fetchAll();
        
        echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . str_pad("Default", 15) . "\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $column) {
            echo str_pad($column['Field'], 20) . 
                 str_pad($column['Type'], 20) . 
                 str_pad($column['Null'], 10) . 
                 str_pad($column['Key'], 10) . 
                 str_pad($column['Default'] ?? 'NULL', 15) . "\n";
        }
        
        // Show sample data
        $conn->query("SELECT * FROM courses LIMIT 5");
        $sample_data = $conn->fetchAll();
        
        if (!empty($sample_data)) {
            echo "\n--- Sample Data (first 5 rows) ---\n";
            foreach ($sample_data as $row) {
                echo "ID: " . ($row['id'] ?? 'N/A') . ", Title: " . ($row['course_title'] ?? 'N/A') . "\n";
            }
        } else {
            echo "\n⚠️ No data found in courses table\n";
        }
    }
    
    /**
     * Create courses table
     */
    public function createCoursesTable() {
        echo "\n=== CREATING COURSES TABLE ===\n";
        
        $conn = $this->db->getDbConnection();
        if (!$conn) {
            echo "❌ Database connection failed\n";
            return false;
        }
        
        try {
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
            echo "ℹ️ Table is ready for your course data\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error creating table: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    
    /**
     * Add a specific course
     */
    public function addCourse($course_title, $canvas_course_id = null) {
        echo "\n--- Adding Course ---\n";
        echo "Title: $course_title\n";
        if ($canvas_course_id) {
            echo "Canvas ID: $canvas_course_id\n";
        }
        
        $conn = $this->db->getDbConnection();
        if (!$conn) {
            echo "❌ Database connection failed\n";
            return false;
        }
        
        try {
            $conn->query("INSERT INTO courses (course_title, canvas_course_id) VALUES (?, ?)", $course_title, $canvas_course_id);
            echo "✅ Course added successfully\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error adding course: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Command line usage
if (php_sapi_name() === 'cli') {
    $setup = new CoursesTableSetup();
    
    $command = $argv[1] ?? 'check';
    $param1 = $argv[2] ?? null;
    $param2 = $argv[3] ?? null;
    
    switch ($command) {
        case 'check':
            $setup->checkCoursesTable();
            break;
            
        case 'create':
            $setup->createCoursesTable();
            break;
            
        case 'add':
            if (!$param1) {
                echo "Usage: php setup_courses_table.php add \"Course Title\" [canvas_course_id]\n";
                exit(1);
            }
            $setup->addCourse($param1, $param2);
            break;
            
        default:
            echo "Usage:\n";
            echo "  php setup_courses_table.php check                    - Check if table exists\n";
            echo "  php setup_courses_table.php create                   - Create table structure\n";
            echo "  php setup_courses_table.php add \"Title\" [canvas_id]  - Add a specific course\n";
            break;
    }
}
?>
