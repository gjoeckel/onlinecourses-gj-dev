<?php
/**
 * Master Test Runner for Clients-Enterprise
 * Runs tests for all enterprises and provides comprehensive summary
 */

require_once __DIR__ . '/run_enterprise_tests.php';

echo "=== Clients-Enterprise Master Test Suite ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Available enterprises to test
$enterprises = ['csu', 'ccc', 'demo'];

$allResults = [];
$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($enterprises as $enterprise) {
    echo "Testing Enterprise: " . strtoupper($enterprise) . "\n";
    echo str_repeat('=', 50) . "\n";
    
    try {
        $results = runEnterpriseTests($enterprise);
        $allResults[$enterprise] = $results;
        
        $totalTests += $results['total'];
        $totalPassed += $results['passed'];
        $totalFailed += $results['failed'];
        
    } catch (Exception $e) {
        echo "❌ Error testing enterprise $enterprise: " . $e->getMessage() . "\n";
        $allResults[$enterprise] = [
            'enterprise' => $enterprise,
            'error' => $e->getMessage(),
            'total' => 0,
            'passed' => 0,
            'failed' => 0
        ];
    }
    
    echo "\n";
}

// Overall summary
echo "=== OVERALL TEST SUMMARY ===\n";
echo "Total Enterprises Tested: " . count($enterprises) . "\n";
echo "Total Tests: $totalTests\n";
echo "Total Passed: $totalPassed\n";
echo "Total Failed: $totalFailed\n";
echo "Overall Success Rate: " . ($totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 0) . "%\n\n";

// Enterprise breakdown
echo "=== ENTERPRISE BREAKDOWN ===\n";
foreach ($allResults as $enterprise => $results) {
    if (isset($results['error'])) {
        echo "❌ " . strtoupper($enterprise) . ": ERROR - " . $results['error'] . "\n";
    } else {
        $status = $results['failed'] === 0 ? '✅' : '❌';
        $success_rate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;
        echo "$status " . strtoupper($enterprise) . ": {$results['passed']}/{$results['total']} ({$success_rate}%)\n";
    }
}

echo "\n";

// Detailed test breakdown
echo "=== DETAILED TEST BREAKDOWN ===\n";
foreach ($allResults as $enterprise => $results) {
    if (isset($results['error'])) {
        continue;
    }
    
    echo strtoupper($enterprise) . ":\n";
    foreach ($results['tests'] as $test_category => $category_results) {
        $status = $category_results['failed'] === 0 ? '✅' : '❌';
        $success_rate = $category_results['total'] > 0 ? round(($category_results['passed'] / $category_results['total']) * 100, 1) : 0;
        echo "  $status $test_category: {$category_results['passed']}/{$category_results['total']} ({$success_rate}%)\n";
    }
    echo "\n";
}

// Failed tests summary
$failedTests = [];
foreach ($allResults as $enterprise => $results) {
    if (isset($results['error'])) {
        $failedTests[] = [
            'enterprise' => $enterprise,
            'category' => 'SYSTEM',
            'test' => 'Enterprise Loading',
            'message' => $results['error']
        ];
        continue;
    }
    
    foreach ($results['tests'] as $test_category => $category_results) {
        // Note: Individual test failures would be captured in the detailed output
        // This is a simplified version focusing on category-level failures
        if ($category_results['failed'] > 0) {
            $failedTests[] = [
                'enterprise' => $enterprise,
                'category' => $test_category,
                'test' => 'Multiple Tests',
                'message' => "{$category_results['failed']} out of {$category_results['total']} tests failed"
            ];
        }
    }
}

if (!empty($failedTests)) {
    echo "=== FAILED TESTS SUMMARY ===\n";
    foreach ($failedTests as $failedTest) {
        echo "❌ {$failedTest['enterprise']} - {$failedTest['category']}: {$failedTest['message']}\n";
    }
    echo "\n";
}

// Final status
if ($totalFailed === 0 && $totalTests > 0) {
    echo "🎉 ALL TESTS PASSED! All enterprises are ready for production.\n";
} else {
    echo "⚠️  Some tests failed. Please review the failed tests above.\n";
}

echo "\n=== Test Suite Complete ===\n";

// Return results for programmatic use
return $allResults;
?> 