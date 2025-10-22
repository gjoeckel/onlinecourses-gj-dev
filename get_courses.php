<?php
/**
 * Get Courses API Endpoint
 * 
 * This endpoint provides courses for use in the searchable dropdown
 * on the registration form.
 * 
 * Usage: GET get_courses.php
 * Response: JSON with courses array
 */

require_once 'config.php';
require_once 'db.php';

// Set JSON content type
header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get all active courses
    $currentDate = date('Y-m-d');
    $courses = $db->select("
        SELECT id, course_id, course_title, cohort, open_date, close_date, 
               CASE 
                   WHEN ? BETWEEN open_date AND close_date THEN 'active'
                   WHEN ? < open_date THEN 'upcoming'
                   ELSE 'closed'
               END as status
        FROM courses 
        WHERE close_date >= ? 
        ORDER BY cohort DESC, course_title
    ", [$currentDate, $currentDate, $currentDate]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'count' => count($courses),
        'current_date' => $currentDate,
        'courses' => $courses
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 