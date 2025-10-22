<?php
/**
 * API Response Test
 * Test what the API is actually returning for organization data
 * Run with: php tests/integration/api_response_test.php [enterprise]
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

echo "=== API Response Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Test the same date range that's failing in the browser
    $start_date = '05-06-24';
    $end_date = '06-28-25';
    
    echo "🔍 Testing API response for date range: $start_date to $end_date\n\n";
    
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
    
    // Process data the same way the API does
    echo "🔍 Processing data like the API...\n";
    $processedData = DataProcessor::processRegistrantsData($registrations, $start_date, $end_date);
    $filtered_registrations = $processedData['registrations'];
    $filtered_enrollments = $processedData['enrollments'];
    $filtered_certificates = $processedData['certificates'];
    
    echo "   Filtered registrations: " . count($filtered_registrations) . " records\n";
    echo "   Filtered enrollments: " . count($filtered_enrollments) . " records\n";
    echo "   Filtered certificates: " . count($filtered_certificates) . " records\n\n";
    
    // Process organization data the same way the API does
    echo "🏢 Processing organization data like the API...\n";
    $organizationData = DataProcessor::processOrganizationData($filtered_registrations, $filtered_enrollments, $filtered_certificates);
    
    echo "   Organization data count: " . count($organizationData) . " records\n";
    
    if (count($organizationData) > 0) {
        echo "   Sample organization data:\n";
        for ($i = 0; $i < min(3, count($organizationData)); $i++) {
            $org = $organizationData[$i];
            echo "     " . ($i + 1) . ". " . json_encode($org) . "\n";
        }
    }
    
    // Build the response structure like the API does
    echo "\n📤 Building API response structure...\n";
    $response = [
        'registrations' => $filtered_registrations,
        'enrollments' => $filtered_enrollments,
        'certificates' => $filtered_certificates,
        'organization_data' => $organizationData
    ];
    
    echo "   Response keys: " . implode(', ', array_keys($response)) . "\n";
    echo "   organization_data type: " . gettype($response['organization_data']) . "\n";
    echo "   organization_data count: " . count($response['organization_data']) . "\n";
    
    // Simulate what the JavaScript would receive
    echo "\n🖥️  Simulating JavaScript data access...\n";
    $json_response = json_encode($response);
    $decoded_response = json_decode($json_response, true);
    
    echo "   JSON response length: " . strlen($json_response) . " characters\n";
    echo "   Decoded response keys: " . implode(', ', array_keys($decoded_response)) . "\n";
    echo "   organization_data exists: " . (isset($decoded_response['organization_data']) ? 'YES' : 'NO') . "\n";
    
    if (isset($decoded_response['organization_data'])) {
        echo "   organization_data type: " . gettype($decoded_response['organization_data']) . "\n";
        echo "   organization_data count: " . count($decoded_response['organization_data']) . "\n";
        
        // Test the exact JavaScript access pattern
        $js_access = $decoded_response['organization_data'];
        echo "   JavaScript access (organizationData.organization_data): " . gettype($js_access) . "\n";
        echo "   JavaScript access count: " . count($js_access) . "\n";
        
        if (count($js_access) > 0) {
            echo "   Sample JavaScript access result:\n";
            for ($i = 0; $i < min(2, count($js_access)); $i++) {
                $org = $js_access[$i];
                echo "     " . ($i + 1) . ". " . json_encode($org) . "\n";
            }
        }
    } else {
        echo "   ❌ organization_data is missing from the response!\n";
    }
    
    echo "\n📋 Analysis:\n";
    if (isset($decoded_response['organization_data']) && count($decoded_response['organization_data']) > 0) {
        echo "   ✅ API response structure is correct\n";
        echo "   ✅ organization_data is present and has data\n";
        echo "   ✅ JavaScript should be able to access the data\n";
        echo "   💡 The issue might be in the JavaScript code or browser environment\n";
    } else {
        echo "   ❌ API response structure has issues\n";
        echo "   ❌ organization_data is missing or empty\n";
        echo "   💡 The issue is in the API response generation\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 