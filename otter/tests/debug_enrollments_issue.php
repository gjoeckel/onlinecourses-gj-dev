<?php
/**
 * Debug Enrollments Issue Test
 * Tests the enrollments processing logic to identify the issue
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/data_processor.php';

// Initialize enterprise configuration
UnifiedEnterpriseConfig::initializeFromRequest();

echo "=== Debug Enrollments Issue Test ===\n\n";

// Test 1: Check cached enrollments data
echo "Test 1: Cached Enrollments Data\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    echo "  - Cached enrollments count: " . count($enrollmentsCache) . "\n";
    
    if (count($enrollmentsCache) > 0) {
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
    
    // Test with different date ranges
    $test_ranges = [
        ['01-01-25', '12-31-25', 'Full year 2025'],
        ['06-01-25', '06-30-25', 'June 2025'],
        ['05-01-25', '05-31-25', 'May 2025']
    ];
    
    foreach ($test_ranges as $range) {
        $start_date = $range[0];
        $end_date = $range[1];
        $description = $range[2];
        
        $processed = DataProcessor::processEnrollmentsData($enrollmentsCache, $start_date, $end_date);
        
        echo "  - $description:\n";
        echo "    Processed enrollments: " . count($processed) . "\n";
        
        if (count($processed) > 0) {
            echo "    Sample processed row (first 20 columns):\n";
            echo "      " . json_encode(array_slice($processed[0], 0, 20)) . "\n";
        }
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 3: Check the reports API logic
echo "Test 3: Reports API Logic Simulation\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    // Simulate the reports API logic
    $start = '01-01-25';
    $end = '12-31-25';
    
    // This is what the reports API does:
    $enrollments = DataProcessor::processEnrollmentsData($enrollmentsCache, $start, $end);
    
    echo "  - Reports API would return: " . count($enrollments) . " enrollments\n";
    echo "  - JavaScript would count: " . (is_array($enrollments) ? count($enrollments) : 'NOT ARRAY') . "\n";
    
    // Check if the data is being returned correctly
    if (is_array($enrollments)) {
        echo "  PASS: Enrollments is an array\n";
    } else {
        echo "  FAIL: Enrollments is not an array\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 4: Check the JavaScript logic
echo "Test 4: JavaScript Logic Simulation\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    $start = '01-01-25';
    $end = '12-31-25';
    $enrollments = DataProcessor::processEnrollmentsData($enrollmentsCache, $start, $end);
    
    // Simulate the JavaScript logic from reports-data.js
    $enrollmentsCount = is_array($enrollments) ? count($enrollments) : 0;
    
    echo "  - JavaScript enrollmentsCount: $enrollmentsCount\n";
    echo "  - This is what would appear in the Systemwide table\n";
    
    if ($enrollmentsCount > 0) {
        echo "  PASS: Enrollments count is greater than 0\n";
    } else {
        echo "  WARNING: Enrollments count is 0 - this might be the issue\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
echo "The issue might be:\n";
echo "1. DataProcessor::processEnrollmentsData returning empty array\n";
echo "2. Date range filtering removing all enrollments\n";
echo "3. Column index mismatch for 'Submitted' column\n";
echo "4. JavaScript receiving wrong data structure\n\n";
?> 