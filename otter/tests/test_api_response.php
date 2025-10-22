<?php
// Test script to check API response structure
require_once 'lib/unified_enterprise_config.php';

// Simulate web environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Initialize enterprise configuration
UnifiedEnterpriseConfig::init('csu');

// Set up the request parameters
$_REQUEST['start_date'] = '05-06-24';
$_REQUEST['end_date'] = '06-28-25';
$_REQUEST['organization_data'] = '1';

// Capture the API output
ob_start();
include 'reports/reports_api.php';
$response = ob_get_clean();

echo "API Response Analysis:\n";
echo "Response length: " . strlen($response) . " characters\n";
echo "Response starts with: " . substr($response, 0, 100) . "...\n\n";

// Try to decode the response
$data = json_decode($response, true);
echo "JSON decode successful: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON error: " . json_last_error_msg() . "\n";
    echo "Raw response: " . $response . "\n";
    exit;
}

echo "Response keys: " . implode(', ', array_keys($data)) . "\n\n";

// Check for organization_data
if (isset($data['organization_data'])) {
    echo "✅ organization_data exists in response\n";
    echo "organization_data type: " . gettype($data['organization_data']) . "\n";
    echo "organization_data count: " . count($data['organization_data']) . "\n";
    
    if (count($data['organization_data']) > 0) {
        echo "Sample organization: " . json_encode($data['organization_data'][0]) . "\n";
    }
} else {
    echo "❌ organization_data missing from response\n";
    echo "Available keys: " . implode(', ', array_keys($data)) . "\n";
}

// Check other response data
if (isset($data['registrations'])) {
    echo "\nRegistrations count: " . count($data['registrations']) . "\n";
}
if (isset($data['enrollments'])) {
    echo "Enrollments count: " . count($data['enrollments']) . "\n";
}
if (isset($data['certificates'])) {
    echo "Certificates count: " . count($data['certificates']) . "\n";
}
?> 