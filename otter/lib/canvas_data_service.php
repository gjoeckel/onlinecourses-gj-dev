<?php
require_once __DIR__ . '/canvas_api.php';

class CanvasDataService {
    private $canvas_api;
    private $cache_dir;
    private $cache_ttl = 10800; // 3 hours
    
    public function __construct() {
        $this->canvas_api = new CanvasAPI();
        $this->cache_dir = __DIR__ . '/../config/cache/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Simple cache implementation
     */
    private function getCache($key) {
        $cache_file = $this->cache_dir . md5($key) . '.json';
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($cache_file), true);
        if (!$data) {
            return false;
        }
        
        // Check if cache is expired
        if (time() - $data['timestamp'] > $this->cache_ttl) {
            unlink($cache_file);
            return false;
        }
        
        return $data['data'];
    }
    
    private function setCache($key, $data) {
        $cache_file = $this->cache_dir . md5($key) . '.json';
        
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        file_put_contents($cache_file, json_encode($cache_data));
    }
    
    private function clearCache($key = null) {
        if ($key) {
            $cache_file = $this->cache_dir . md5($key) . '.json';
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        } else {
            // Clear all cache files
            $files = glob($this->cache_dir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get all enrollment data from Canvas
     */
    public function getAllEnrollments() {
        $cache_key = 'canvas_all_enrollments';
        
        // Check cache first
        $cached_data = $this->getCache($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $all_enrollments = [];
        
        // Get all courses from account
        $courses = $this->canvas_api->getAccountCourses(['per_page' => 100]);
        
        if (isset($courses['error'])) {
            return ['error' => 'Failed to get courses: ' . $courses['error']];
        }
        
        // Get enrollments for each course
        foreach ($courses as $course) {
            $course_id = $course['id'];
            $course_name = $course['name'];
            
            $enrollments = $this->canvas_api->getCourseEnrollments($course_id, ['per_page' => 100]);
            
            if (!isset($enrollments['error'])) {
                foreach ($enrollments as $enrollment) {
                    if (isset($enrollment['user'])) {
                        $user = $enrollment['user'];
                        
                        $all_enrollments[] = [
                            'DaysToClose' => '',
                            'Invited' => $enrollment['created_at'] ?? '',
                            'Enrolled' => $enrollment['enrollment_state'] === 'active' ? 'Yes' : 'No',
                            'Cohort' => $course_name,
                            'Year' => date('Y', strtotime($enrollment['created_at'] ?? 'now')),
                            'First' => $this->extractFirstName($user['name'] ?? ''),
                            'Last' => $this->extractLastName($user['name'] ?? ''),
                            'Email' => $user['email'] ?? '',
                            'Role' => $enrollment['role'] ?? '',
                            'Organization' => $course_name,
                            'Certificate' => ($enrollment['completed_at'] ?? null) ? 'Yes' : '-',
                            'Issued' => $enrollment['completed_at'] ?? '',
                            'ClosingDate' => '',
                            'Completed' => ($enrollment['completed_at'] ?? null) ? 'Yes' : 'No',
                            'ID' => $user['id'] ?? '',
                            'Submitted' => $enrollment['created_at'] ?? '',
                            'Status' => $enrollment['enrollment_state'] ?? '',
                            'CourseID' => $course_id,
                            'CourseName' => $course_name
                        ];
                    }
                }
            }
        }
        
        // Cache the results
        $this->setCache($cache_key, $all_enrollments);
        
        return $all_enrollments;
    }
    
    /**
     * Get enrollments for a specific organization
     */
    public function getOrganizationEnrollments($organization_name) {
        $all_enrollments = $this->getAllEnrollments();
        
        if (isset($all_enrollments['error'])) {
            return $all_enrollments;
        }
        
        // Filter by organization (course name)
        $org_enrollments = [];
        foreach ($all_enrollments as $enrollment) {
            if (stripos($enrollment['Organization'], $organization_name) !== false) {
                $org_enrollments[] = $enrollment;
            }
        }
        
        return $org_enrollments;
    }
    
    /**
     * Get enrollments for CSU organizations
     */
    public function getCSUEnrollments() {
        $csu_keywords = ['CSU', 'California State', 'State University'];
        return $this->getEnrollmentsByKeywords($csu_keywords);
    }
    
    /**
     * Get enrollments for CCC organizations
     */
    public function getCCCEnrollments() {
        $ccc_keywords = ['CCC', 'Community College', 'College'];
        return $this->getEnrollmentsByKeywords($ccc_keywords);
    }
    
    /**
     * Get enrollments for Demo organizations
     */
    public function getDemoEnrollments() {
        $demo_keywords = ['Demo', 'Test', 'Beta'];
        return $this->getEnrollmentsByKeywords($demo_keywords);
    }
    
    /**
     * Get enrollments for ASTHO organizations
     */
    public function getASTHOEnrollments() {
        $astho_keywords = ['ASTHO', 'Association of State and Territorial Health Officials', 'State Health', 'Territorial Health', 'Health Officials'];
        return $this->getEnrollmentsByKeywords($astho_keywords);
    }
    
    /**
     * Get enrollments by keywords
     */
    private function getEnrollmentsByKeywords($keywords) {
        $all_enrollments = $this->getAllEnrollments();
        
        if (isset($all_enrollments['error'])) {
            return $all_enrollments;
        }
        
        $filtered_enrollments = [];
        foreach ($all_enrollments as $enrollment) {
            foreach ($keywords as $keyword) {
                if (stripos($enrollment['Organization'], $keyword) !== false) {
                    $filtered_enrollments[] = $enrollment;
                    break;
                }
            }
        }
        
        return $filtered_enrollments;
    }
    
    /**
     * Get course statistics
     */
    public function getCourseStatistics() {
        $courses = $this->canvas_api->getAccountCourses(['per_page' => 100]);
        
        if (isset($courses['error'])) {
            return $courses;
        }
        
        $stats = [];
        foreach ($courses as $course) {
            $stats[] = [
                'course_name' => $course['name'],
                'course_id' => $course['id'],
                'student_count' => $course['total_students'] ?? 0,
                'status' => $course['workflow_state'],
                'created_at' => $course['created_at'] ?? '',
                'start_at' => $course['start_at'] ?? '',
                'end_at' => $course['end_at'] ?? ''
            ];
        }
        
        return $stats;
    }
    
    /**
     * Extract first name from full name
     */
    private function extractFirstName($full_name) {
        $parts = explode(' ', trim($full_name));
        return $parts[0] ?? '';
    }
    
    /**
     * Extract last name from full name
     */
    private function extractLastName($full_name) {
        $parts = explode(' ', trim($full_name));
        if (count($parts) > 1) {
            return end($parts);
        }
        return '';
    }
    
    /**
     * Clear all Canvas data cache
     */
    public function clearAllCache() {
        $this->clearCache('canvas_all_enrollments');
        return ['success' => 'Canvas cache cleared'];
    }
    
    /**
     * Test the Canvas connection
     */
    public function testConnection() {
        $user_info = $this->canvas_api->getUserInfo();
        if (isset($user_info['error'])) {
            return $user_info;
        }
        
        $courses = $this->canvas_api->getAccountCourses(['per_page' => 5]);
        if (isset($courses['error'])) {
            return $courses;
        }
        
        return [
            'success' => true,
            'user' => $user_info,
            'courses_count' => count($courses)
        ];
    }
}
?> 