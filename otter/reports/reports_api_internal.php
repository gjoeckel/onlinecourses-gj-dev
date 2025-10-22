<?php
/**
 * reports_api_internal.php - Internal API for PHP-to-PHP Data Processing
 * 
 * PURPOSE: This file serves as an INTERNAL API that returns data arrays to
 * calling PHP code without any HTTP headers or output buffering. It is designed
 * to be included via require_once in other PHP files.
 * 
 * KEY CHARACTERISTICS:
 * - NO HTTP headers (prevents "headers already sent" errors)
 * - NO output buffering (prevents JSON corruption of HTML pages)
 * - Returns data arrays instead of outputting JSON
 * - Called by: lib/unified_refresh_service.php for cache refresh operations
 * 
 * ARCHITECTURAL NOTE: This file is intentionally duplicated from reports_api.php
 * to prevent output buffering race conditions. The external version sets headers
 * and outputs JSON for browser consumption, while this version returns data for
 * PHP consumption.
 * 
 * RACE CONDITION PREVENTION: When reports/index.php included the original
 * reports_api.php, it would output JSON instead of HTML, causing the entire
 * page to return JSON data to the browser. This internal version prevents
 * that by avoiding all output and header operations.
 * 
 * See reports_api.php for the external version used by JavaScript AJAX calls.
 * See changelog.md for detailed explanation of the race condition and solution.
 */

// reports_api_internal.php - Internal API for data refresh without headers
// This version is designed to be included in other pages without sending JSON headers

// Start session first
require_once __DIR__ . '/../lib/session.php';
initializeSession();

// Load enterprise configuration and cache manager
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/cache_utils.php';
require_once __DIR__ . '/../lib/data_processor.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$cacheManager = EnterpriseCacheManager::getInstance();

// --- Config paths ---
define('CACHE_DIR', $cacheManager->getEnterpriseCacheDir());
define('GLOBAL_CACHE_DIR', $cacheManager->getEnterpriseCacheDir());
define('REGISTRANTS_CACHE_FILE', $cacheManager->getRegistrantsCachePath());
define('SUBMISSIONS_CACHE_FILE', $cacheManager->getSubmissionsCachePath());
define('REGISTRATIONS_FILE', $cacheManager->getRegistrationsCachePath());
define('ENROLLMENTS_FILE', $cacheManager->getEnrollmentsCachePath());
define('CERTIFICATES_FILE', $cacheManager->getCertificatesCachePath());
define('CACHE_TTL', 6 * 60 * 60); // 6 hours

// Get enterprise code for dynamic config paths
$enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();

$registrantsFile = "../../config/$enterprise_code/registrants.json";
$submissionsFile = "../../config/$enterprise_code/submissions.json";
$googleApiKeyFile = "../../config/$enterprise_code/google_api_key.txt";

// Validate enterprise configuration
if (!in_array($enterprise_code, ['csu', 'ccc', 'demo', 'astho'])) {
    require_once __DIR__ . '/../lib/error_messages.php';
    return ['error' => ErrorMessages::getTechnicalDifficulties()];


    // --- Helpers ---




    if ($yearNum == $startYYNum && $cohortNum < $startMMNum) {
        return false;
    }

    if ($yearNum == $endYYNum && $cohortNum > $endMMNum) {
        return false;
    }

    return true;
}

// --- Helper functions ---
function trim_row($row) {
    if (is_array($row)) {
        return array_map('trim', $row);
    }
    return trim($row);
}

// --- Google Sheets API ---
if (!function_exists('fetch_sheet_data')) {
    function fetch_sheet_data($workbook_id, $sheet_name, $start_row) {
    $api_key = UnifiedEnterpriseConfig::getGoogleApiKey();

    if (empty($api_key)) {
        require_once __DIR__ . '/../lib/error_messages.php';
        return ['error' => ErrorMessages::getTechnicalDifficulties()];
    }

    $url = "https://sheets.googleapis.com/v4/spreadsheets/$workbook_id/values/$sheet_name!A$start_row:Z";
    $url .= "?key=$api_key";

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; Enterprise API)'
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        // Get the actual PHP error that was suppressed
        $error = error_get_last();
        $errorMessage = $error ? $error['message'] : 'Unknown error';
        
        // Check if it's a Google service issue (503, 500, connection timeout, etc.)
        if (strpos($errorMessage, '503') !== false || 
            strpos($errorMessage, '500') !== false || 
            strpos($errorMessage, 'Service Unavailable') !== false ||
            strpos($errorMessage, 'HTTP request failed') !== false) {
            require_once __DIR__ . '/../lib/error_messages.php';
            return ['error' => ErrorMessages::getGoogleServicesIssue()];
        }
        
        require_once __DIR__ . '/../lib/error_messages.php';
        return ['error' => ErrorMessages::getTechnicalDifficulties()];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        require_once __DIR__ . '/../lib/error_messages.php';
        return ['error' => ErrorMessages::getTechnicalDifficulties()];
    }

    if (!isset($data['values'])) {
        require_once __DIR__ . '/../lib/error_messages.php';
        return ['error' => ErrorMessages::getTechnicalDifficulties()];
    }

    return $data['values'];
    }
}

// --- Load config files ---
$registrantsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('registrants');
$submissionsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('submissions');

if (!$registrantsSheetConfig || !$submissionsSheetConfig) {
    require_once __DIR__ . '/../lib/error_messages.php';
    return ['error' => ErrorMessages::getTechnicalDifficulties()];
}

// --- Extract config values ---
$regCols = $registrantsSheetConfig['columns'];
$regStartRow = $registrantsSheetConfig['start_row'];
$regWbId = $registrantsSheetConfig['workbook_id'];
$regSheet = $registrantsSheetConfig['sheet_name'];
$subCols = $submissionsSheetConfig['columns'];
$subStartRow = $submissionsSheetConfig['start_row'];
$subWbId = $submissionsSheetConfig['workbook_id'];
$subSheet = $submissionsSheetConfig['sheet_name'];

// --- Get date range ---
$start = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
$end = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';

if (!CacheUtils::isValidMMDDYY($start) || !CacheUtils::isValidMMDDYY($end)) {
    require_once __DIR__ . '/../lib/error_messages.php';
    return ['error' => ErrorMessages::getTechnicalDifficulties()];
}

// --- Registrants data (cache or fetch) ---
$useRegCache = false;
$forceRefresh = isset($_REQUEST['force_refresh']) && $_REQUEST['force_refresh'] === '1';

if (!$forceRefresh && CacheUtils::isCacheFresh($cacheManager, 'all-registrants-data.json')) {
    $json = $cacheManager->readCacheFile('all-registrants-data.json');
    $registrantsData = isset($json['data']) ? $json['data'] : [];
    $useRegCache = true;
}

if (!$useRegCache) {
    $registrantsData = fetch_sheet_data($regWbId, $regSheet, $regStartRow);

    if (isset($registrantsData['error'])) {
        return ['error' => $registrantsData['error']];
    }

    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }

    // Ensure all data is trimmed and stringified
    $registrantsData = array_map('trim_row', $registrantsData);

    // Add global_timestamp using utility
    $registrantsDataWithTimestamp = CacheUtils::createTimestampedData($registrantsData);

    $cacheManager->writeCacheFile('all-registrants-data.json', $registrantsDataWithTimestamp);
}

// --- Submissions data (cache or fetch) ---
$useSubCache = false;

if (!$forceRefresh && CacheUtils::isCacheFresh($cacheManager, 'all-submissions-data.json')) {
    $json = $cacheManager->readCacheFile('all-submissions-data.json');
    $submissionsData = isset($json['data']) ? $json['data'] : [];
    $useSubCache = true;
}

if (!$useSubCache) {
    $submissionsData = fetch_sheet_data($subWbId, $subSheet, $subStartRow);
    if (isset($submissionsData['error'])) {
        return ['error' => $submissionsData['error']];
    }

    // Ensure all data is trimmed and stringified
    $submissionsData = array_map('trim_row', $submissionsData);

    // Add global_timestamp using utility
    $submissionsDataWithTimestamp = CacheUtils::createTimestampedData($submissionsData);

    $cacheManager->writeCacheFile('all-submissions-data.json', $submissionsDataWithTimestamp);
}

// --- Process data ---
$invitationsProcessed = DataProcessor::processInvitationsData($registrantsData, $start, $end);
$registrationsProcessed = DataProcessor::processRegistrationsData($submissionsData, $start, $end);
// Load cached enrollments data and process for date range
$enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
$enrollmentsData = $enrollmentsCache ?? [];

$enrollmentsProcessed = DataProcessor::processEnrollmentsData($enrollmentsData, $start, $end);
$submissionsProcessed = DataProcessor::processSubmissionsData($submissionsData, $start, $end);
$organizationData = DataProcessor::processOrganizationData(
    $registrationsProcessed, // Use new registrations data for organization processing
    $enrollmentsProcessed,
    $invitationsProcessed['certificates']
);

// --- Generate derived cache files (registrations, enrollments, certificates) ---
// This step was missing and is critical for admin/dashboard functionality
// ALWAYS generate derived cache files when this function is called
// Use hardcoded Google Sheets column indices for reliable data processing
$idxRegEnrolled = 2;      // Google Sheets Column C (Enrolled)
$idxRegCertificate = 10;  // Google Sheets Column K (Certificate)
$idxRegIssued = 11;       // Google Sheets Column L (Issued)

// Generate registrations data (ALL submissions data, no date filtering for cache)
$registrations = [];
foreach ($submissionsData as $row) {
    $registrations[] = array_map('strval', $row);
}
file_put_contents($cacheManager->getRegistrationsCachePath(), json_encode($registrations));

// Generate enrollments data
// Track ALL registrations that are also enrolled (no date range filtering for cache)
$enrollments = [];
foreach ($registrantsData as $row) {
    $enrolled = isset($row[$idxRegEnrolled]) ? $row[$idxRegEnrolled] : '';
    if ($enrolled === 'Yes') {
        $enrollments[] = array_map('strval', $row);
    }
}
file_put_contents($cacheManager->getEnrollmentsCachePath(), json_encode($enrollments));

// Generate certificates data (ALL certificates, no date filtering for cache)
$certificates = [];
foreach ($registrantsData as $row) {
    $certificate = isset($row[$idxRegCertificate]) ? $row[$idxRegCertificate] : '';
    if ($certificate === 'Yes') {
        $certificates[] = array_map('strval', $row);
    }
}
file_put_contents($cacheManager->getCertificatesCachePath(), json_encode($certificates));

// Return success (no output, just return)
return ['success' => true, 'data' => [
    'invitations' => $invitationsProcessed,
    'registrations' => $registrationsProcessed,
    'submissions' => $submissionsProcessed,
    'organizations' => $organizationData
]];