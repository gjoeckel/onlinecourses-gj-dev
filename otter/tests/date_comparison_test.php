<?php
/**
 * Date Comparison Test
 * Debug the date comparison logic in DataProcessor
 * Run with: php tests/integration/date_comparison_test.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../lib/data_processor.php';

echo "=== Date Comparison Test ===\n\n";

// Test the inRange function directly using reflection
$reflection = new ReflectionClass('DataProcessor');
$inRangeMethod = $reflection->getMethod('inRange');
$inRangeMethod->setAccessible(true);

// Test cases
$test_cases = [
    ['date' => '05-06-24', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => true],
    ['date' => '06-17-25', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => true],
    ['date' => '06-28-25', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => true],
    ['date' => '05-05-24', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => false],
    ['date' => '06-29-25', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => false],
    ['date' => '01-02-25', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => true],
    ['date' => '12-30-24', 'start' => '05-06-24', 'end' => '06-28-25', 'expected' => true],
];

echo "Testing inRange function:\n";
foreach ($test_cases as $i => $test) {
    $result = $inRangeMethod->invoke(null, $test['date'], $test['start'], $test['end']);
    $status = $result === $test['expected'] ? '✅' : '❌';
    echo "  $status Test $i: {$test['date']} in range {$test['start']} to {$test['end']} = " . ($result ? 'true' : 'false') . " (expected " . ($test['expected'] ? 'true' : 'false') . ")\n";
}

echo "\nTesting DateTime parsing:\n";
$dates_to_test = ['05-06-24', '06-17-25', '01-02-25', '12-30-24'];

foreach ($dates_to_test as $date) {
    $dt = DateTime::createFromFormat('m-d-y', $date);
    if ($dt) {
        echo "  ✅ $date -> " . $dt->format('Y-m-d') . "\n";
    } else {
        echo "  ❌ $date -> Failed to parse\n";
    }
}

echo "\nTesting date comparisons:\n";
$start = DateTime::createFromFormat('m-d-y', '05-06-24');
$end = DateTime::createFromFormat('m-d-y', '06-28-25');

echo "  Start date: " . $start->format('Y-m-d') . "\n";
echo "  End date: " . $end->format('Y-m-d') . "\n";

foreach ($dates_to_test as $date) {
    $dt = DateTime::createFromFormat('m-d-y', $date);
    if ($dt) {
        $in_range = $dt >= $start && $dt <= $end;
        echo "  $date (" . $dt->format('Y-m-d') . ") in range: " . ($in_range ? 'true' : 'false') . "\n";
    }
}

echo "\n=== Test Complete ===\n"; 