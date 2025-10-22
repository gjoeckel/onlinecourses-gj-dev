<?php
/**
 * Reports API Direct Test
 * Test the reports API directly to see what it returns
 * Run with: php tests/integration/reports_api_test.php [enterprise]
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

echo "=== Reports API Direct Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Test the API directly
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
        
        if (isset($summary_data['systemwide'])) {
            echo "   Systemwide data: " . count($summary_data['systemwide']) . " records\n";
            if (!empty($summary_data['systemwide'])) {
                echo "   Sample: " . json_encode(array_slice($summary_data['systemwide'], 0, 1)) . "\n";
            }
        }
        
        if (isset($summary_data['organizations'])) {
            echo "   Organizations data: " . count($summary_data['organizations']) . " records\n";
            if (!empty($summary_data['organizations'])) {
                echo "   Sample: " . json_encode(array_slice($summary_data['organizations'], 0, 1)) . "\n";
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
        
        if (isset($org_data['organizations'])) {
            echo "   Organizations data: " . count($org_data['organizations']) . " records\n";
            if (!empty($org_data['organizations'])) {
                echo "   Sample: " . json_encode(array_slice($org_data['organizations'], 0, 1)) . "\n";
            }
        }
    }
    
    echo "\n=== API Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 