<?php
// Test reports API functionality
echo "Testing reports API functionality...\n";

// Test 1: Check if master_includes file exists
$master_includes_path = '/Users/a00288946/cursor-global/projects/cursor-otter-dev/master_includes/onlinecourses_common.php';
echo "1. Checking master_includes file: ";
if (file_exists($master_includes_path)) {
    echo "✅ EXISTS\n";
} else {
    echo "❌ MISSING\n";
    exit(1);
}

// Test 2: Check if db.php exists
$db_path = __DIR__ . '/includes/db.php';
echo "2. Checking local db.php file: ";
if (file_exists($db_path)) {
    echo "✅ EXISTS\n";
} else {
    echo "❌ MISSING\n";
    exit(1);
}

// Test 3: Test database connection
echo "3. Testing database connection: ";
try {
    require_once $master_includes_path;
    require_once $db_path;

    $db = new db($dbhost, $dbuser, $dbpass, $dbname);
    echo "✅ SUCCESS\n";
} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test reports API directly
echo "4. Testing reports API directly: ";
try {
    // Simulate the reports API call
    $_GET['start_date'] = '01-01-24';
    $_GET['end_date'] = '10-23-25';

    // Capture output
    ob_start();
    include 'otter/reports/reports_api.php';
    $output = ob_get_clean();

    if (strpos($output, 'error') !== false) {
        echo "❌ API ERROR: " . $output . "\n";
    } else {
        echo "✅ SUCCESS\n";
    }
} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

echo "Reports API test completed!\n";
?>
