<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';
// Date utility functions (inline to remove dependency on date_utils.php)
function is_valid_mmddyy($date) {
    return preg_match('/^\d{2}-\d{2}-\d{2}$/', $date);
}

function in_range($date, $start, $end) {
    $d = DateTime::createFromFormat('m-d-y', $date);
    $s = DateTime::createFromFormat('m-d-y', $start);
    $e = DateTime::createFromFormat('m-d-y', $end);
    if (!$d || !$s || !$e) return false;
    return $d >= $s && $d <= $e;
}

// Abbreviate organization names using prioritized, single-abbreviation logic
function abbreviateLinkText($name) {
    return abbreviateOrganizationName($name);
}



// Get date range from GET only
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
$validRange = is_valid_mmddyy($start) && is_valid_mmddyy($end);

// Initialize database connection using the same method as reports_api.php
$master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
$db_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/db.php';

if (file_exists($master_includes_path) && file_exists($db_file_path)) {
    require_once $master_includes_path;
    require_once $db_file_path;
    
    try {
        $db = new db($dbhost, $dbuser, $dbpass, $dbname);
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $db = null;
    }
} else {
    error_log("Database files not found");
    $db = null;
}

// Get enterprise configuration
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$enterprise_code = $context['enterprise_code'] ?? false;

// Fallback: if enterprise detection failed, try URL parameter
if (!$enterprise_code && isset($_GET['ent'])) {
    $enterprise_code = $_GET['ent'];
    error_log("Using fallback enterprise from URL parameter: {$enterprise_code}");
}

// Additional fallback: if still no enterprise, try to detect from URL
if (!$enterprise_code) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, 'ent=astho') !== false) {
        $enterprise_code = 'astho';
        error_log("Detected ASTHO from URL: {$requestUri}");
    } elseif (strpos($requestUri, 'ent=ccc') !== false) {
        $enterprise_code = 'ccc';
        error_log("Detected CCC from URL: {$requestUri}");
    } elseif (strpos($requestUri, 'ent=csu') !== false) {
        $enterprise_code = 'csu';
        error_log("Detected CSU from URL: {$requestUri}");
    }
}

error_log("Final enterprise_code: {$enterprise_code}");

// Get enterprise ID from database
$db->query("SELECT id FROM enterprises WHERE name = ?", $enterprise_code);
$enterpriseResult = $db->fetchArray();
$enterpriseId = $enterpriseResult ? $enterpriseResult['id'] : null;

// Load enrollments data from database
if ($enterpriseId) {
    // For CCC, prefer organization name query since most data is there
    if ($enterprise_code === 'CCC') {
        // For CCC, try organization name first since most data is there
        $orgName = strtoupper($enterprise_code);
        $db->query("
            SELECT r.*, r.organization as org_name 
            FROM registrations r 
            WHERE r.organization = ? AND r.deletion_status = 'active'
            ORDER BY r.created_at DESC
        ", $orgName);
        $allRegistrations = $db->fetchAll();
        
        // Filter for enrolled participants (enrolled = 1 OR status = 'enrollee')
        $enrollmentsData = array_filter($allRegistrations, function($row) {
            return ($row['enrolled'] == 1) || ($row['status'] === 'enrollee');
        });
        
        // If no data found by organization name, try by enterprise_id as fallback
        if (empty($enrollmentsData)) {
            $db->query("
                SELECT r.*, o.name as org_name 
                FROM registrations r 
                LEFT JOIN organizations o ON r.organization_id = o.id 
                WHERE r.enterprise_id = ? AND r.deletion_status = 'active'
                ORDER BY r.created_at DESC
            ", $enterpriseId);
            $allRegistrations = $db->fetchAll();
            
            // Filter for enrolled participants
            $enrollmentsData = array_filter($allRegistrations, function($row) {
                return ($row['enrolled'] == 1) || ($row['status'] === 'enrollee');
            });
        }
    } else {
        // For other enterprises, try enterprise_id first
        $db->query("
            SELECT r.*, o.name as org_name 
            FROM registrations r 
            LEFT JOIN organizations o ON r.organization_id = o.id 
            WHERE r.enterprise_id = ? AND r.deletion_status = 'active'
            ORDER BY r.created_at DESC
        ", $enterpriseId);
        $allRegistrations = $db->fetchAll();
        
        // Filter for enrolled participants (enrolled = 1 OR status = 'enrollee')
        $enrollmentsData = array_filter($allRegistrations, function($row) {
            return ($row['enrolled'] == 1) || ($row['status'] === 'enrollee');
        });
        
        // If no data found by enterprise_id, try by organization name
        if (empty($enrollmentsData)) {
            $orgName = strtoupper($enterprise_code);
            $db->query("
                SELECT r.*, r.organization as org_name 
                FROM registrations r 
                WHERE r.organization = ? AND r.deletion_status = 'active'
                ORDER BY r.created_at DESC
            ", $orgName);
            $allRegistrations = $db->fetchAll();
            
            // Filter for enrolled participants
            $enrollmentsData = array_filter($allRegistrations, function($row) {
                return ($row['enrolled'] == 1) || ($row['status'] === 'enrollee');
            });
        }
    }
} else {
    $enrollmentsData = [];
}

// Debug: Log what we found
error_log("Enrollments Data Debug - Enterprise: {$enterprise_code}, Found " . count($enrollmentsData) . " enrollments");
if (count($enrollmentsData) > 0) {
    $sample = reset($enrollmentsData);
    error_log("Sample enrollment: " . json_encode($sample));
}

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();



$filtered = [];

// TEMPORARY: Show all data regardless of date filtering for debugging
if (count($enrollmentsData) > 0) {
    // Convert database rows to the format expected by the display
    $filtered = [];
    foreach ($enrollmentsData as $row) {
        // Split name into first and last
        $name_parts = explode(' ', $row['name'], 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        
        $filtered[] = [
            $row['id'] ?? '',                    // 0
            $row['status'] ?? '',                // 1
            $row['certificate'] ?? '',           // 2
            $row['cohort'] ?? '',                // 3 - Cohort
            $row['year'] ?? '',                  // 4 - Year
            $first_name,                         // 5 - First name
            $last_name,                          // 6 - Last name
            $row['email'] ?? '',                 // 7 - Email
            $row['organization'] ?? '',          // 8 - Organization
            $row['college'] ?? '',               // 9
            $row['role'] ?? '',                  // 10
            $row['invited'] ?? '',               // 11
            $row['enrolled'] ?? '',              // 12
            $row['submitted_date'] ?? '',        // 13
            $row['enrolleddate'] ?? '',          // 14
            date('m-d-y', strtotime($row['created_at'])) // 15 - Submitted date
        ];
    }
    error_log("DEBUGGING: Showing ALL enrollments without date filtering - count: " . count($filtered));
} else {
    error_log("DEBUGGING: No enrollments data to filter");
}