<?php
// check_error_logs.php - Check web server error logs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Error Log Check</h1>";

// Common error log locations
$error_logs = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    '/var/log/php_errors.log',
    '/var/log/php8.1-fpm.log',
    '/var/log/syslog',
    '/var/websites/webaim/logs/error.log',
    '/var/websites/webaim/htdocs/onlinecourses/otter/error.log',
    ini_get('error_log')
];

echo "<h2>Checking Error Logs</h2>";

foreach ($error_logs as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo "<h3>$log_file</h3>";
        
        // Get last 20 lines
        $lines = file($log_file);
        if ($lines) {
            $last_lines = array_slice($lines, -20);
            echo "<pre>";
            foreach ($last_lines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        } else {
            echo "Could not read log file<br>";
        }
    } else {
        echo "❌ $log_file not found or not readable<br>";
    }
}

echo "<h2>PHP Error Log</h2>";
echo "Error log setting: " . ini_get('error_log') . "<br>";

// Check if we can write to error log
if (function_exists('error_log')) {
    error_log("Test error message from check_error_logs.php");
    echo "✅ Test error message written to log<br>";
} else {
    echo "❌ error_log function not available<br>";
}

echo "<h2>Test Complete</h2>";
?>
