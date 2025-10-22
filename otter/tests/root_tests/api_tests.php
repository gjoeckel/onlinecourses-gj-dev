<?php
/**
 * Consolidated API Tests
 * Combines API response testing and validation
 * Run with: php tests/root_tests/api_tests.php [enterprise]
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== Consolidated API Tests ===\n";
echo "Enterprise: $enterprise\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Simulate web environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    echo "✅ Enterprise configuration initialized: $enterprise\n\n";
    
    // Test 1: API Response Structure
    echo "=== Test 1: API Response Structure ===\n";
    
    // Set up the request parameters
    $_REQUEST['start_date'] = '05-06-24';
    $_REQUEST['end_date'] = '06-28-25';
    $_REQUEST['organization_data'] = '1';
    
    // Capture the API output
    ob_start();
    include __DIR__ . '/../../reports/reports_api.php';
    $response = ob_get_clean();
    
    echo "Response length: " . strlen($response) . " characters\n";
    echo "Response starts with: " . substr($response, 0, 100) . "...\n\n";
    
    // Try to decode the response
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON decode successful\n";
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
    } else {
        echo "❌ JSON decode failed: " . json_last_error_msg() . "\n";
        echo "Raw response: " . $response . "\n";
    }
    
    echo "\n";
    
    // Test 2: API Direct Testing
    echo "=== Test 2: API Direct Testing ===\n";
    
    $start_date = '05-06-24';
    $end_date = '06-28-25';
    
    echo "🔍 Testing API with date range: $start_date to $end_date\n\n";
    
    // Test summary data
    $summary_url = "reports/reports_api.php?start_date=$start_date&end_date=$end_date";
    echo "📊 Testing summary data...\n";
    echo "   URL: $summary_url\n";
    
    $summary_response = file_get_contents($summary_url);
    $summary_data = json_decode($summary_response, true);
    
    if ($summary_data === null) {
        echo "   ❌ Failed to parse JSON response\n";
        echo "   Raw response: " . substr($summary_response, 0, 500) . "...\n";
    } else {
        echo "   ✅ JSON parsed successfully\n";
        echo "   Response keys: " . implode(', ', array_keys($summary_data)) . "\n";
        
        if (isset($summary_data['registrations'])) {
            echo "   Registrations data: " . count($summary_data['registrations']) . " records\n";
            if (!empty($summary_data['registrations'])) {
                echo "   Sample: " . json_encode(array_slice($summary_data['registrations'], 0, 1)) . "\n";
            }
        }
        
        if (isset($summary_data['enrollments'])) {
            echo "   Enrollments data: " . count($summary_data['enrollments']) . " records\n";
            if (!empty($summary_data['enrollments'])) {
                echo "   Sample: " . json_encode(array_slice($summary_data['enrollments'], 0, 1)) . "\n";
            }
        }
        
        if (isset($summary_data['certificates'])) {
            echo "   Certificates data: " . count($summary_data['certificates']) . " records\n";
            if (!empty($summary_data['certificates'])) {
                echo "   Sample: " . json_encode(array_slice($summary_data['certificates'], 0, 1)) . "\n";
            }
        }
    }
    
    echo "\n";
    
    // Test organization data
    $org_url = "reports/reports_api.php?start_date=$start_date&end_date=$end_date&organization_data=1";
    echo "🏢 Testing organization data...\n";
    echo "   URL: $org_url\n";
    
    $org_response = file_get_contents($org_url);
    $org_data = json_decode($org_response, true);
    
    if ($org_data === null) {
        echo "   ❌ Failed to parse JSON response\n";
        echo "   Raw response: " . substr($org_response, 0, 500) . "...\n";
    } else {
        echo "   ✅ JSON parsed successfully\n";
        echo "   Response keys: " . implode(', ', array_keys($org_data)) . "\n";
        
        if (isset($org_data['organization_data'])) {
            echo "   Organizations data: " . count($org_data['organization_data']) . " records\n";
            if (!empty($org_data['organization_data'])) {
                echo "   Sample: " . json_encode(array_slice($org_data['organization_data'], 0, 1)) . "\n";
            }
        }
    }
    
    echo "\n=== API Tests Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 