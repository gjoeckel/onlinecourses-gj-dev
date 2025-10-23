<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/new_database_service.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

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
// Check local development first, then production fallback
$master_includes_path = '/Users/a00288946/cursor-global/projects/cursor-otter-dev/master_includes/onlinecourses_common.php';
$db_file_path = __DIR__ . '/../../includes/db.php';

// Try local development paths first
if (!file_exists($master_includes_path)) {
    // Fallback to production paths
    $master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
    $db_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/db.php';
}

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

// Load registrations data from database
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
        $submissionsData = $db->fetchAll();

        // If no data found by organization name, try by enterprise_id as fallback
        if (empty($submissionsData)) {
            $db->query("
                SELECT r.*, o.name as org_name
                FROM registrations r
                LEFT JOIN organizations o ON r.organization_id = o.id
                WHERE r.enterprise_id = ? AND r.deletion_status = 'active'
                ORDER BY r.created_at DESC
            ", $enterpriseId);
            $submissionsData = $db->fetchAll();
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
        $submissionsData = $db->fetchAll();

        // If no data found by enterprise_id, try by organization name
        if (empty($submissionsData)) {
            $orgName = strtoupper($enterprise_code);
            $db->query("
                SELECT r.*, r.organization as org_name
                FROM registrations r
                WHERE r.organization = ? AND r.deletion_status = 'active'
                ORDER BY r.created_at DESC
            ", $orgName);
            $submissionsData = $db->fetchAll();
        }
    }
} else {
    $submissionsData = [];
}

// Debug: Log what we found
error_log("Registrations Data Debug - Enterprise: {$enterprise_code}, Found " . count($submissionsData) . " registrations");
if (count($submissionsData) > 0) {
    $sample = $submissionsData[0];
    error_log("Sample registration: " . json_encode($sample));
} else {
    error_log("No registrations found for enterprise: {$enterprise_code}");

    // Try a direct query to see what's in the database
    $db->query("SELECT COUNT(*) as count FROM registrations WHERE organization = 'ASTHO' AND deletion_status = 'active'");
    $direct_count = $db->fetchArray();
    error_log("Direct ASTHO count: " . ($direct_count['count'] ?? 0));

    $db->query("SELECT COUNT(*) as count FROM registrations WHERE organization = 'CCC' AND deletion_status = 'active'");
    $ccc_count = $db->fetchArray();
    error_log("Direct CCC count: " . ($ccc_count['count'] ?? 0));
}

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();



$filtered = [];

// TEMPORARY: Show all data regardless of date filtering for debugging
if (count($submissionsData) > 0) {
    // Convert database rows to the format expected by the display
    $filtered = [];
    foreach ($submissionsData as $row) {
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
            $row['earnerdate'] ?? '',            // 14
            date('m-d-y', strtotime($row['created_at'])) // 15 - Submitted date
        ];
    }
    error_log("DEBUGGING: Showing ALL data without date filtering - count: " . count($filtered));
} else {
    error_log("DEBUGGING: No submissions data to filter");
}

// Original date filtering logic (commented out for debugging)
/*
if ($validRange) {
    $isAllRange = ($start === $minStartDate && $end === date('m-d-y'));

    // Database field mappings
    $createdDateField = 'created_at';
    $nameField = 'name';
    $emailField = 'email';
    $orgField = 'org_name';

    error_log("Date Range Debug - Start: {$start}, End: {$end}, MinStart: {$minStartDate}, IsAllRange: " . ($isAllRange ? 'true' : 'false'));

    if ($isAllRange) {
        // For 'All', include all registrations
        $filtered = $submissionsData;
        error_log("Using all range - filtered count: " . count($filtered));
    } else {
        // For other ranges, filter by created date in range
        $filtered = array_filter($submissionsData, function($row) use ($start, $end, $createdDateField) {
            if (empty($row[$createdDateField])) return false;
            $createdDate = date('m-d-y', strtotime($row[$createdDateField]));
            return in_range($createdDate, $start, $end);
        });
        error_log("Date filtered - filtered count: " . count($filtered));
    }
} else {
    error_log("Invalid date range - Start: {$start}, End: {$end}");
}
*/
