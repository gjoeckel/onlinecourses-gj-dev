<?php
/**
 * Simple Test Runner for Clients-Enterprise
 * Usage: php run_tests.php [enterprise] [test_type]
 *
 * Examples:
 *   php run_tests.php                    # Run all tests for all enterprises
 *   php run_tests.php csu               # Run all tests for CSU only
 *   php run_tests.php csu config        # Run only config tests for CSU
 *   php run_tests.php all               # Run all tests for all enterprises
 */

require_once __DIR__ . '/tests/run_all_tests.php';

// Get command line arguments
$enterprise = $argv[1] ?? 'all';
$test_type = $argv[2] ?? 'all';

echo "=== Clients-Enterprise Test Runner ===\n";
echo "Enterprise: " . strtoupper($enterprise) . "\n";
echo "Test Type: " . strtoupper($test_type) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

if ($enterprise === 'all') {
    // Run tests for all enterprises
    $results = include __DIR__ . '/tests/run_all_tests.php';

    // Summary
    $total_enterprises = count($results);
    $successful_enterprises = 0;

    foreach ($results as $ent => $result) {
        if (!isset($result['error']) && $result['failed'] === 0) {
            $successful_enterprises++;
        }
    }

    echo "\n=== FINAL SUMMARY ===\n";
    echo "Enterprises Tested: $total_enterprises\n";
    echo "Successful: $successful_enterprises\n";
    echo "Failed: " . ($total_enterprises - $successful_enterprises) . "\n";

    if ($successful_enterprises === $total_enterprises) {
        echo "🎉 ALL ENTERPRISES PASSED!\n";
        exit(0);
    } else {
        echo "⚠️  Some enterprises failed.\n";
        exit(1);
    }

} else {
    // Run tests for specific enterprise
    require_once __DIR__ . '/tests/run_enterprise_tests.php';

    try {
        $results = runEnterpriseTests($enterprise);

        if ($results['failed'] === 0) {
            echo "🎉 All tests passed for " . strtoupper($enterprise) . "!\n";
            exit(0);
        } else {
            echo "⚠️  Some tests failed for " . strtoupper($enterprise) . ".\n";
            exit(1);
        }

    } catch (Exception $e) {
        echo "❌ Error testing enterprise $enterprise: " . $e->getMessage() . "\n";
        exit(1);
    }
}