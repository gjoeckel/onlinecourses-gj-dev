<?php
/**
 * Reports Date Range Test
 * Simple test to show what date ranges would actually return data
 * Run with: php tests/integration/reports_date_range_test.php [enterprise]
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

echo "=== Reports Date Range Analysis ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    
    if (!file_exists($registrations_file)) {
        echo "❌ Cache file not found: $registrations_file\n";
        exit(1);
    }
    
    $data = json_decode(file_get_contents($registrations_file), true);
    
    if (!$data) {
        echo "❌ Failed to parse cache data\n";
        exit(1);
    }
    
    echo "📊 Total records in cache: " . count($data) . "\n\n";
    
    // Extract and analyze dates
    $dates = [];
    foreach ($data as $record) {
        if (isset($record[15]) && $record[15] !== '-' && $record[15] !== '') {
            $dates[] = $record[15]; // Date is in column 15
        }
    }
    
    if (empty($dates)) {
        echo "❌ No valid dates found in data\n";
        exit(1);
    }
    
    // Sort dates
    sort($dates);
    
    echo "📅 Date range in data:\n";
    echo "   Earliest: " . $dates[0] . "\n";
    echo "   Latest: " . end($dates) . "\n\n";
    
    // Test the user's date range
    $user_start = '05-06-24';
    $user_end = '06-28-25';
    
    echo "🔍 Testing user's date range: $user_start to $user_end\n";
    
    $data_processor = new DataProcessor();
    $filtered_data = $data_processor->processRegistrantsData($data, $user_start, $user_end);
    
    echo "   Records found: " . count($filtered_data) . "\n";
    
    if (count($filtered_data) === 0) {
        echo "   ❌ No data found for this range!\n\n";
        
        // Suggest better ranges
        echo "💡 Suggested date ranges that would return data:\n";
        
        // Test ranges within the actual data
        $earliest = $dates[0];
        $latest = end($dates);
        
        // Parse dates to understand the format
        $earliest_parts = explode('-', $earliest);
        $latest_parts = explode('-', $latest);
        
        if (count($earliest_parts) === 3 && count($latest_parts) === 3) {
            $earliest_month = $earliest_parts[0];
            $earliest_day = $earliest_parts[1];
            $earliest_year = $earliest_parts[2];
            
            $latest_month = $latest_parts[0];
            $latest_day = $latest_parts[1];
            $latest_year = $latest_parts[2];
            
            echo "   • Full range: $earliest to $latest\n";
            echo "   • Recent data: $latest_month-01-$latest_year to $latest\n";
            echo "   • Early data: $earliest to $earliest_month-31-$earliest_year\n";
            
            // Test a range that should work
            $test_start = "$earliest_month-01-$earliest_year";
            $test_end = $latest;
            
            echo "\n🔍 Testing suggested range: $test_start to $test_end\n";
            $test_filtered = $data_processor->processRegistrantsData($data, $test_start, $test_end);
            echo "   Records found: " . count($test_filtered) . "\n";
            
            if (count($test_filtered) > 0) {
                echo "   ✅ This range returns data!\n";
            }
        }
    } else {
        echo "   ✅ Data found for this range!\n";
    }
    
    echo "\n=== Analysis Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 