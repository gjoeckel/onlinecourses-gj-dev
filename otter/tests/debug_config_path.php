<?php
echo "Testing path resolution...\n";

$libDir = __DIR__ . '/lib';
$configPath = $libDir . '/../config/csu.config';
$absolutePath = realpath($configPath);

echo "Lib directory: $libDir\n";
echo "Config path: $configPath\n";
echo "Absolute path: $absolutePath\n";
echo "File exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n";

if (file_exists($configPath)) {
    $content = file_get_contents($configPath);
    $decoded = json_decode($content, true);
    echo "Config content length: " . strlen($content) . "\n";
    echo "Config decoded successfully: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
    if ($decoded && isset($decoded['settings'])) {
        echo "Settings found: " . json_encode($decoded['settings']) . "\n";
    }
}

echo "\nTesting UnifiedEnterpriseConfig...\n";
require_once __DIR__ . '/lib/unified_enterprise_config.php';

try {
    UnifiedEnterpriseConfig::init('csu');
    $settings = UnifiedEnterpriseConfig::getSettings();
    $startDate = UnifiedEnterpriseConfig::getStartDate();
    $fullConfig = UnifiedEnterpriseConfig::getFullConfig();
    
    echo "Config loaded successfully!\n";
    echo "Settings: " . json_encode($settings) . "\n";
    echo "Start date: '$startDate'\n";
    echo "Full config keys: " . json_encode(array_keys($fullConfig)) . "\n";
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} 