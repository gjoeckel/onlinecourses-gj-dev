<?php
/**
 * Debug Data Structure
 * Examine the actual data structure to understand the issue
 * Run with: php tests/integration/debug_data_structure.php [enterprise]
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

echo "=== Debug Data Structure ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    
    $registrations = json_decode(file_get_contents($registrations_file), true);
    
    echo "📊 Total registrations: " . count($registrations) . "\n\n";
    
    // Examine first few records
    echo "🔍 Examining first 5 records:\n";
    for ($i = 0; $i < min(5, count($registrations)); $i++) {
        $record = $registrations[$i];
        echo "   Record $i:\n";
        echo "     Type: " . gettype($record) . "\n";
        echo "     Length: " . (is_array($record) ? count($record) : 'N/A') . "\n";
        
        if (is_array($record)) {
            echo "     All values: " . json_encode($record) . "\n";
            
            // Check for date in different positions
            for ($j = 0; $j < min(20, count($record)); $j++) {
                if (isset($record[$j]) && $record[$j] !== '' && $record[$j] !== '-') {
                    echo "     Index $j: '{$record[$j]}' (looks like date: " . (preg_match('/^\d{2}-\d{2}-\d{2}$/', $record[$j]) ? 'YES' : 'NO') . ")\n";
                }
            }
        }
        echo "\n";
    }
    
    // Test the inRange function with actual data
    echo "🔍 Testing inRange function with actual data:\n";
    $reflection = new ReflectionClass('DataProcessor');
    $inRangeMethod = $reflection->getMethod('inRange');
    $inRangeMethod->setAccessible(true);
    
    $test_start = '01-01-25';
    $test_end = '12-31-25';
    
    echo "   Testing range: $test_start to $test_end\n";
    
    $found_dates = 0;
    $in_range_count = 0;
    
    for ($i = 0; $i < min(20, count($registrations)); $i++) {
        $record = $registrations[$i];
        
        if (is_array($record)) {
            // Check all positions for dates
            for ($j = 0; $j < count($record); $j++) {
                if (isset($record[$j]) && $record[$j] !== '' && $record[$j] !== '-') {
                    $date_value = $record[$j];
                    
                    if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $date_value)) {
                        $found_dates++;
                        $in_range = $inRangeMethod->invoke(null, $date_value, $test_start, $test_end);
                        
                        echo "     Record $i, Index $j: '$date_value' in range: " . ($in_range ? 'YES' : 'NO') . "\n";
                        
                        if ($in_range) {
                            $in_range_count++;
                        }
                    }
                }
            }
        }
    }
    
    echo "\n📋 Summary:\n";
    echo "   Total date-like values found: $found_dates\n";
    echo "   Values in range $test_start to $test_end: $in_range_count\n";
    
    if ($found_dates === 0) {
        echo "   ❌ No date-like values found in the data!\n";
        echo "   💡 The data structure might be different than expected\n";
    } elseif ($in_range_count === 0) {
        echo "   ❌ No dates found in the specified range!\n";
        echo "   💡 Try a different date range\n";
    } else {
        echo "   ✅ Found $in_range_count dates in the specified range\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 