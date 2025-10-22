<?php
/**
 * Debug Column Index Test
 * Tests the column index retrieval for the Submitted column
 */

// Set enterprise code directly
$_GET['enterprise'] = 'csu';

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/data_processor.php';

echo "=== Debug Column Index Test ===\n\n";

// Test 1: Check column index retrieval
echo "Test 1: Column Index Retrieval\n";
try {
    // Test the getColumnIndex method directly using reflection
    $reflection = new ReflectionClass('DataProcessor');
    $method = $reflection->getMethod('getColumnIndex');
    $method->setAccessible(true);
    
    $registrantsSubmittedIdx = $method->invoke(null, 'registrants', 'Submitted');
    $submissionsSubmittedIdx = $method->invoke(null, 'submissions', 'Submitted');
    
    echo "  - Registrants Submitted index: " . ($registrantsSubmittedIdx ?? 'NULL') . "\n";
    echo "  - Submissions Submitted index: " . ($submissionsSubmittedIdx ?? 'NULL') . "\n";
    
    // Expected: both should be 15
    if ($registrantsSubmittedIdx === 15) {
        echo "  PASS: Registrants Submitted index is correct\n";
    } else {
        echo "  FAIL: Registrants Submitted index is wrong (expected 15, got $registrantsSubmittedIdx)\n";
    }
    
    if ($submissionsSubmittedIdx === 15) {
        echo "  PASS: Submissions Submitted index is correct\n";
    } else {
        echo "  FAIL: Submissions Submitted index is wrong (expected 15, got $submissionsSubmittedIdx)\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 2: Test date range filtering manually
echo "Test 2: Manual Date Range Filtering\n";
try {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    
    if (is_array($enrollmentsCache) && count($enrollmentsCache) > 0) {
        $start_date = '01-01-25';
        $end_date = '12-31-25';
        
        // Test the first few rows manually
        $testRows = array_slice($enrollmentsCache, 0, 5);
        
        foreach ($testRows as $i => $row) {
            $submittedDate = isset($row[15]) ? trim($row[15]) : '';
            echo "  - Row $i: Submitted date = '$submittedDate'\n";
            
            // Test the inRange method directly
            $reflection = new ReflectionClass('DataProcessor');
            $method = $reflection->getMethod('inRange');
            $method->setAccessible(true);
            
            $inRange = $method->invoke(null, $submittedDate, $start_date, $end_date);
            echo "    In range ($start_date to $end_date): " . ($inRange ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
?> 