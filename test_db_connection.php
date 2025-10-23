<?php
// Test database connection
require_once '/Users/a00288946/Desktop/cursor-otter-dev/onlinecourses_common.php';
require_once 'includes/db.php';

try {
    echo "Testing database connection...\n";
    echo "Host: $dbhost\n";
    echo "User: $dbuser\n";
    echo "Database: $dbname\n";

    $db = new db($dbhost, $dbuser, $dbpass, $dbname);
    echo "Database connection successful!\n";

    // Test a simple query
    $result = $db->query("SELECT COUNT(*) as count FROM enterprises");
    $row = $db->fetchArray();
    echo "Enterprises count: " . $row['count'] . "\n";

    $db->close();

} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
