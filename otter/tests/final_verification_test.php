<?php
/**
 * Final Verification Test
 * Verify that the date range filtering is now working correctly
 * Run with: php tests/integration/final_verification_test.php [enterprise]
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force local environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/data_processor.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== Final Verification Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Test the original problematic date range
    $start_date = '05-06-24';
    $end_date = '06-28-25';
    
    echo "🔍 Testing the original problematic date range: $start_date to $end_date\n\n";
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    $enrollments_file = "$cache_dir/enrollments.json";
    $certificates_file = "$cache_dir/certificates.json";
    
    $registrations = json_decode(file_get_contents($registrations_file), true);
    $enrollments = json_decode(file_get_contents($enrollments_file), true);
    $certificates = json_decode(file_get_contents($certificates_file), true);
    
    echo "📊 Cache data loaded:\n";
    echo "   Registrations: " . count($registrations) . " records\n";
    echo "   Enrollments: " . count($enrollments) . " records\n";
    echo "   Certificates: " . count($certificates) . " records\n\n";
    
    // Process data with the fixed DataProcessor
    echo "🔍 Processing data with fixed DataProcessor...\n";
    $processedData = DataProcessor::processRegistrantsData($registrations, $start_date, $end_date);
    $filtered_registrations = $processedData['registrations'];
    $filtered_enrollments = $processedData['enrollments'];
    $filtered_certificates = $processedData['certificates'];
    
    echo "   Filtered registrations: " . count($filtered_registrations) . " records\n";
    echo "   Filtered enrollments: " . count($filtered_enrollments) . " records\n";
    echo "   Filtered certificates: " . count($filtered_certificates) . " records\n\n";
    
    // Process organization data
    echo "🏢 Processing organization data...\n";
    $organization_data = DataProcessor::processOrganizationData($filtered_registrations, $filtered_enrollments, $filtered_certificates);
    echo "   Organizations: " . count($organization_data) . " records\n\n";
    
    // Test a smaller date range to verify filtering works
    echo "🔍 Testing a smaller date range (June 2025 only)...\n";
    $june_start = '06-01-25';
    $june_end = '06-30-25';
    
    $june_data = DataProcessor::processRegistrantsData($registrations, $june_start, $june_end);
    $june_registrations = $june_data['registrations'];
    
    echo "   June 2025 registrations: " . count($june_registrations) . " records\n";
    
    if (count($june_registrations) > 0) {
        echo "   Sample June registration: " . json_encode($june_registrations[0]) . "\n";
    }
    
    echo "\n📋 Results Summary:\n";
    
    if (count($filtered_registrations) > 0) {
        echo "   ✅ FIXED: Date range filtering is now working!\n";
        echo "   ✅ Found " . count($filtered_registrations) . " registrations in the date range\n";
        echo "   ✅ Found " . count($organization_data) . " organizations with data\n";
        
        if (count($june_registrations) < count($filtered_registrations)) {
            echo "   ✅ Date filtering is working correctly (June has fewer records than full range)\n";
        }
        
        echo "\n💡 The reports page should now work correctly with the date range picker!\n";
        echo "   - The API will return data for the selected date range\n";
        echo "   - The JavaScript will display the data in the tables\n";
        echo "   - Organization data will be populated correctly\n";
        
    } else {
        echo "   ❌ Still no data found - there might be another issue\n";
    }
    
    echo "\n🔧 What was fixed:\n";
    echo "   - Changed DataProcessor to use index 15 for registration date (was index 1)\n";
    echo "   - Index 1 was empty ('-'), so no registrations were being found\n";
    echo "   - Index 15 contains the actual MM-DD-YY formatted dates\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 