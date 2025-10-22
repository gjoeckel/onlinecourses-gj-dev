<?php
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/utils.php';

echo "Testing Environment Migration...\n";
echo "================================\n\n";

// Test 1: Environment detection from dashboards.json
echo "1. Environment Detection Test:\n";
$env = UnifiedEnterpriseConfig::getEnvironment();
echo "   Environment: $env\n";
echo "   Is Local: " . (UnifiedEnterpriseConfig::isLocal() ? 'YES' : 'NO') . "\n";
echo "   Is Production: " . (UnifiedEnterpriseConfig::isProduction() ? 'YES' : 'NO') . "\n\n";

// Test 2: URL Generation Test
echo "2. URL Generation Test:\n";
$dashboard_url = UnifiedEnterpriseConfig::generateUrl('1234', 'dashboard');
$admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
$login_url = UnifiedEnterpriseConfig::generateUrl('', 'login');
echo "   Dashboard URL: $dashboard_url\n";
echo "   Admin URL: $admin_url\n";
echo "   Login URL: $login_url\n\n";

// Test 3: Relative URL Test
echo "3. Relative URL Test:\n";
$css_url = UnifiedEnterpriseConfig::getRelativeUrl('css/admin.css');
echo "   CSS URL: $css_url\n\n";

// Test 4: Utils function test
echo "4. Utils Function Test:\n";
$utils_env = getEnvironment();
echo "   Utils Environment: $utils_env\n";
echo "   Matches UnifiedEnterpriseConfig: " . ($utils_env === $env ? 'YES' : 'NO') . "\n\n";

// Test 5: Configuration validation
echo "5. Configuration Validation:\n";
$dashboardsFile = __DIR__ . '/../../config/dashboards.json';
if (file_exists($dashboardsFile)) {
    $config = json_decode(file_get_contents($dashboardsFile), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✓ dashboards.json is valid JSON\n";
        echo "   ✓ Environment key exists: " . (isset($config['environment']) ? 'YES' : 'NO') . "\n";
        echo "   ✓ Environments section exists: " . (isset($config['environments']) ? 'YES' : 'NO') . "\n";
        echo "   ✓ Local environment configured: " . (isset($config['environments']['local']) ? 'YES' : 'NO') . "\n";
        echo "   ✓ Production environment configured: " . (isset($config['environments']['production']) ? 'YES' : 'NO') . "\n";
        echo "   ✓ Deployment config exists: " . (isset($config['environments']['production']['deployment']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ✗ dashboards.json has JSON errors: " . json_last_error_msg() . "\n";
    }
} else {
    echo "   ✗ dashboards.json not found\n";
}

echo "\nMigration test completed.\n";
?> 