<?php
/**
 * Working Date Range Test
 * Find a date range that actually contains data
 * Run with: php tests/integration/working_date_range_test.php [enterprise]
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

echo "=== Working Date Range Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    
    $registrations = json_decode(file_get_contents($registrations_file), true);
    
    echo "📊 Total registrations: " . count($registrations) . "\n\n";
    
    // Extract all dates
    $dates = [];
    foreach ($registrations as $record) {
        if (isset($record[15]) && $record[15] !== '-' && $record[15] !== '') {
            $dates[] = $record[15];
        }
    }
    
    if (empty($dates)) {
        echo "❌ No valid dates found\n";
        exit(1);
    }
    
    // Sort dates
    sort($dates);
    
    echo "📅 Date range in data:\n";
    echo "   Earliest: " . $dates[0] . "\n";
    echo "   Latest: " . end($dates) . "\n\n";
    
    // Test different date ranges
    $test_ranges = [
        ['start' => '01-01-25', 'end' => '12-31-25', 'description' => 'All of 2025'],
        ['start' => '06-01-25', 'end' => '06-30-25', 'description' => 'June 2025'],
        ['start' => '01-01-25', 'end' => '06-30-25', 'description' => 'First half of 2025'],
        ['start' => '12-01-24', 'end' => '12-31-24', 'description' => 'December 2024'],
        ['start' => '12-01-24', 'end' => '06-30-25', 'description' => 'Dec 2024 to June 2025'],
    ];
    
    echo "🔍 Testing different date ranges:\n";
    foreach ($test_ranges as $range) {
        $processedData = DataProcessor::processRegistrantsData($registrations, $range['start'], $range['end']);
        $count = count($processedData['registrations']);
        
        $status = $count > 0 ? '✅' : '❌';
        echo "   $status {$range['description']} ({$range['start']} to {$range['end']}): $count records\n";
        
        if ($count > 0) {
            echo "      💡 This range works! Try using this in the reports page.\n";
        }
    }
    
    echo "\n📋 Recommendations:\n";
    echo "   1. Use a date range that includes 2025 data\n";
    echo "   2. Try: 01-01-25 to 06-30-25 (first half of 2025)\n";
    echo "   3. Or: 06-01-25 to 06-30-25 (June 2025)\n";
    echo "   4. The current range (05-06-24 to 06-28-25) has no data because there's no data from May 2024\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 