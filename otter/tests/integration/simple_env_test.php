<?php
echo "Simple Environment Test\n";
echo "======================\n\n";

// Test 1: Check if files exist
echo "1. File Check:\n";
$dashboardsFile = __DIR__ . '/../../config/dashboards.json';
$envFile = __DIR__ . '/../../config/environment.json';
echo "   dashboards.json exists: " . (file_exists($dashboardsFile) ? 'YES' : 'NO') . "\n";
echo "   environment.json exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n\n";

// Test 2: Check dashboards.json content
echo "2. dashboards.json Content:\n";
if (file_exists($dashboardsFile)) {
    $content = file_get_contents($dashboardsFile);
    $config = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   Environment: " . ($config['environment'] ?? 'NOT SET') . "\n";
        echo "   Has environments section: " . (isset($config['environments']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "   JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "   File not found\n";
}

echo "\nTest completed.\n";
?> 