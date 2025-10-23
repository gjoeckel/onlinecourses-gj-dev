<?php
// Test Canvas configuration loading
require_once 'otter/lib/canvas_config.php';

echo "Testing Canvas configuration loading...\n";

try {
    $config = CanvasConfig::loadConfig();

    if (isset($config['error'])) {
        echo "❌ Canvas configuration error: " . $config['error'] . "\n";
        exit(1);
    }

    echo "✅ Canvas configuration loaded successfully\n";
    echo "Base URL: " . $config['base_url'] . "\n";
    echo "Account ID: " . $config['account_id'] . "\n";
    echo "API Token: " . substr($config['api_token'], 0, 10) . "...\n";
    echo "Full API URL: " . $config['full_api_url'] . "\n";

    // Test if configuration is valid
    if (CanvasConfig::isValid()) {
        echo "✅ Canvas configuration is valid\n";
    } else {
        echo "❌ Canvas configuration is invalid\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Canvas configuration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Canvas configuration test completed successfully!\n";
?>
