<?php
/**
 * Canvas API Integration
 * Handles Canvas/Instructure API calls and data mapping
 */

class CanvasAPI {
    private $base_url;
    private $api_token;
    private $account_id;
    private $course_id;
    
    public function __construct($config = []) {
        // If no config provided, try to load from secure config
        if (empty($config)) {
            require_once __DIR__ . '/canvas_config.php';
            $config = CanvasConfig::getCanvasAPIConfig();
            if (isset($config['error'])) {
                throw new Exception('Canvas configuration error: ' . $config['error']);
            }
        }
        
        $this->base_url = $config['base_url'] ?? '';
        $this->api_token = $config['api_token'] ?? '';
        $this->account_id = $config['account_id'] ?? '17'; // Default to correct account ID
        $this->course_id = $config['course_id'] ?? '';
    }
    
    public function makeRequest($endpoint, $params = []) {
        $url = rtrim($this->base_url, '/') . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->api_token,
            'Content-Type: application/json'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 30
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            return ['error' => 'Failed to connect to Canvas API'];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON response'];
        }
        
        return $data;
    }
    
    public function getAccountInfo() {
        return $this->makeRequest("/api/v1/accounts/{$this->account_id}");
    }
    
    public function getAccountCourses($params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'total_students'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/accounts/{$this->account_id}/courses", $params);
    }
    
    public function getUserInfo() {
        return $this->makeRequest("/api/v1/users/self");
    }
    
    public function getUserCourses($params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'total_students'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/users/self/courses", $params);
    }
    
    public function getCourseEnrollments($course_id, $params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'user'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/courses/{$course_id}/enrollments", $params);
    }
    
    public function getCourseInfo($course_id) {
        return $this->makeRequest("/api/v1/courses/{$course_id}");
    }
    
    public function getAccountUsers($params = []) {
        $default_params = [
            'per_page' => 50
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/accounts/{$this->account_id}/users", $params);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($user_id, $params = []) {
        return $this->makeRequest("/api/v1/users/{$user_id}", $params);
    }
    
    /**
     * Search for users by name or email
     */
    public function searchUsers($search_term, $params = []) {
        $default_params = [
            'search_term' => $search_term,
            'per_page' => 50
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/accounts/{$this->account_id}/users", $params);
    }
    
    /**
     * Get all enrollments for a specific user
     */
    public function getUserEnrollments($user_id, $params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'course'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/users/{$user_id}/enrollments", $params);
    }
    
    /**
     * Get all courses for a specific user
     */
    public function getUserCoursesById($user_id, $params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'total_students'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/users/{$user_id}/courses", $params);
    }
    
    /**
     * Get all users enrolled in a specific course
     */
    public function getCourseUsers($course_id, $params = []) {
        $default_params = [
            'per_page' => 50,
            'include[]' => 'user'
        ];
        $params = array_merge($default_params, $params);
        
        return $this->makeRequest("/api/v1/courses/{$course_id}/users", $params);
    }
    
    /**
     * Get course analytics data
     */
    public function getCourseAnalytics($course_id, $params = []) {
        return $this->makeRequest("/api/v1/courses/{$course_id}/analytics", $params);
    }
    
    /**
     * Get user analytics data
     */
    public function getUserAnalytics($user_id, $params = []) {
        return $this->makeRequest("/api/v1/users/{$user_id}/analytics", $params);
    }
    
    /**
     * Get all pages of results (handles pagination automatically)
     */
    public function getAllPages($endpoint, $params = []) {
        $all_results = [];
        $page = 1;
        $per_page = 100; // Use larger page size for efficiency
        
        do {
            $params['page'] = $page;
            $params['per_page'] = $per_page;
            
            $result = $this->makeRequest($endpoint, $params);
            
            if (isset($result['error'])) {
                return $result; // Return error immediately
            }
            
            if (empty($result)) {
                break; // No more results
            }
            
            $all_results = array_merge($all_results, $result);
            $page++;
            
            // Safety check to prevent infinite loops
            if ($page > 100) {
                break;
            }
            
        } while (count($result) === $per_page);
        
        return $all_results;
    }
    
    /**
     * Get all users from account (with pagination)
     */
    public function getAllAccountUsers($params = []) {
        return $this->getAllPages("/api/v1/accounts/{$this->account_id}/users", $params);
    }
    
    /**
     * Get all courses from account (with pagination)
     */
    public function getAllAccountCourses($params = []) {
        return $this->getAllPages("/api/v1/accounts/{$this->account_id}/courses", $params);
    }
    
    /**
     * Get all enrollments for a course (with pagination)
     */
    public function getAllCourseEnrollments($course_id, $params = []) {
        return $this->getAllPages("/api/v1/courses/{$course_id}/enrollments", $params);
    }
}
?> 