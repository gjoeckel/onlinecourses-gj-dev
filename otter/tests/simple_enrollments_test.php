<?php
/**
 * Simple Enrollments Test
 * Tests enrollments data without session complications
 */

// Set enterprise code directly
$_GET['enterprise'] = 'csu';

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/data_processor.php';

echo "=== Simple Enrollments Test ===\n\n";

// Test 1: Check cached enrollments data
echo "Test 1: Cached Enrollments Data\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    echo "  - Cached enrollments type: " . gettype($enrollmentsCache) . "\n";
    echo "  - Cached enrollments count: " . (is_array($enrollmentsCache) ? count($enrollmentsCache) : 'NOT ARRAY') . "\n";
    
    if (is_array($enrollmentsCache) && count($enrollmentsCache) > 0) {
        echo "  - Sample enrollment row (first 20 columns):\n";
        echo "    " . json_encode(array_slice($enrollmentsCache[0], 0, 20)) . "\n";
        
        // Check Submitted column (index 15)
        $submittedIdx = 15;
        echo "  - Submitted column (index $submittedIdx): " . (isset($enrollmentsCache[0][$submittedIdx]) ? $enrollmentsCache[0][$submittedIdx] : 'NOT SET') . "\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 2: Test DataProcessor::processEnrollmentsData
echo "Test 2: DataProcessor::processEnrollmentsData\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    if (!is_array($enrollmentsCache)) {
        echo "  FAIL: Enrollments cache is not an array\n\n";
    } else {
        // Test with a specific date range
        $start_date = '01-01-25';
        $end_date = '12-31-25';
        
        $processed = DataProcessor::processEnrollmentsData($enrollmentsCache, $start_date, $end_date);
        
        echo "  - Processed enrollments: " . count($processed) . "\n";
        
        if (count($processed) > 0) {
            echo "  - Sample processed row (first 20 columns):\n";
            echo "    " . json_encode(array_slice($processed[0], 0, 20)) . "\n";
        }
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
?> 