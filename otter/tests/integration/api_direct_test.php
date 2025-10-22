<?php
/**
 * API Direct Test
 * Test the API directly to see what it returns
 * Run with: php tests/integration/api_direct_test.php [enterprise]
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force local environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== API Direct Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Test the same date range that's failing in the browser
    $start_date = '05-06-24';
    $end_date = '06-28-25';
    
    echo "🔍 Testing API directly for date range: $start_date to $end_date\n\n";
    
    // Test summary API call
    echo "📊 Testing summary API call...\n";
    $summary_url = "reports_api.php?start_date=$start_date&end_date=$end_date";
    echo "   URL: $summary_url\n";
    
    // Simulate the API call by setting up the environment
    $_REQUEST['start_date'] = $start_date;
    $_REQUEST['end_date'] = $end_date;
    
    // Capture the output
    ob_start();
    include __DIR__ . '/../../reports/reports_api.php';
    $summary_response = ob_get_clean();
    
    echo "   Response length: " . strlen($summary_response) . " characters\n";
    $summary_data = json_decode($summary_response, true);
    echo "   Decoded successfully: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "   JSON error: " . json_last_error_msg() . "\n";
        echo "   Raw response: " . substr($summary_response, 0, 500) . "...\n";
    } else {
        echo "   Response keys: " . implode(', ', array_keys($summary_data)) . "\n";
        echo "   Registrations: " . count($summary_data['registrations']) . "\n";
        echo "   Enrollments: " . count($summary_data['enrollments']) . "\n";
        echo "   Certificates: " . count($summary_data['certificates']) . "\n";
    }
    
    echo "\n🏢 Testing organization API call...\n";
    $org_url = "reports_api.php?start_date=$start_date&end_date=$end_date&organization_data=1";
    echo "   URL: $org_url\n";
    
    // Reset and test organization API call
    $_REQUEST['start_date'] = $start_date;
    $_REQUEST['end_date'] = $end_date;
    $_REQUEST['organization_data'] = '1';
    
    ob_start();
    include __DIR__ . '/../../reports/reports_api.php';
    $org_response = ob_get_clean();
    
    echo "   Response length: " . strlen($org_response) . " characters\n";
    $org_data = json_decode($org_response, true);
    echo "   Decoded successfully: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "   JSON error: " . json_last_error_msg() . "\n";
        echo "   Raw response: " . substr($org_response, 0, 500) . "...\n";
    } else {
        echo "   Response keys: " . implode(', ', array_keys($org_data)) . "\n";
        echo "   organization_data exists: " . (isset($org_data['organization_data']) ? 'YES' : 'NO') . "\n";
        if (isset($org_data['organization_data'])) {
            echo "   organization_data count: " . count($org_data['organization_data']) . "\n";
            if (count($org_data['organization_data']) > 0) {
                echo "   Sample organization: " . json_encode($org_data['organization_data'][0]) . "\n";
            }
        }
    }
    
    echo "\n📋 Analysis:\n";
    if (isset($org_data['organization_data']) && count($org_data['organization_data']) > 0) {
        echo "   ✅ API is returning organization_data correctly\n";
        echo "   ✅ JavaScript should be able to access organizationData.organization_data\n";
        echo "   💡 The issue might be in the browser environment or JavaScript execution\n";
    } else {
        echo "   ❌ API is not returning organization_data correctly\n";
        echo "   ❌ This explains why JavaScript gets undefined\n";
        echo "   💡 The issue is in the API response generation\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 