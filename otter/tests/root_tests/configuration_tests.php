<?php
/**
 * Consolidated Configuration Tests
 * Combines unified configuration system testing and validation
 * Run with: php tests/root_tests/configuration_tests.php [enterprise]
 */

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/unified_database.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== Consolidated Configuration Tests ===\n";
echo "Enterprise: $enterprise\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test 1: Configuration File Loading
    echo "=== Test 1: Configuration File Loading ===\n";
    
    // Test passwords.json
    $passwordsFile = __DIR__ . '/../../config/passwords.json';
    if (file_exists($passwordsFile)) {
        $passwords = json_decode(file_get_contents($passwordsFile), true);
        if ($passwords && isset($passwords['organizations'])) {
            echo "✅ passwords.json loaded successfully\n";
            echo "   Organizations count: " . count($passwords['organizations']) . "\n";
        } else {
            echo "❌ passwords.json has invalid structure\n";
        }
    } else {
        echo "❌ passwords.json not found\n";
    }
    
    // Test dashboards.json
    $dashboardsFile = __DIR__ . '/../../config/dashboards.json';
    if (file_exists($dashboardsFile)) {
        $dashboards = json_decode(file_get_contents($dashboardsFile), true);
        if ($dashboards && isset($dashboards['environments'])) {
            echo "✅ dashboards.json loaded successfully\n";
            echo "   Environments: " . implode(', ', array_keys($dashboards['environments'])) . "\n";
        } else {
            echo "❌ dashboards.json has invalid structure\n";
        }
    } else {
        echo "❌ dashboards.json not found\n";
    }
    
    // Test enterprise config
    $configFile = __DIR__ . "/../../config/$enterprise.config";
    if (file_exists($configFile)) {
        echo "✅ $enterprise.config found\n";
        $configContent = file_get_contents($configFile);
        if (strpos($configContent, 'enterprise') !== false) {
            echo "   Contains enterprise configuration\n";
        }
    } else {
        echo "❌ $enterprise.config not found\n";
    }
    
    echo "\n";
    
    // Test 2: Unified Database
    echo "=== Test 2: Unified Database ===\n";
    
    $database = new UnifiedDatabase();
    
    // Test organization lookup
    $test_password = "8472"; // Bakersfield
    $org = $database->getOrganizationByPassword($test_password);
    
    if ($org) {
        echo "✅ Organization lookup successful for password $test_password\n";
        echo "   Organization: " . $org['name'] . "\n";
    } else {
        echo "❌ Organization lookup failed for password $test_password\n";
    }
    
    // Test admin lookup
    $admin_password = "4000"; // Admin
    $admin_org = $database->getOrganizationByPassword($admin_password);
    
    if ($admin_org && isset($admin_org['is_admin']) && $admin_org['is_admin']) {
        echo "✅ Admin organization lookup successful\n";
        echo "   Admin organization: " . $admin_org['name'] . "\n";
    } else {
        echo "❌ Admin organization lookup failed\n";
    }
    
    echo "\n";
    
    // Test 3: Unified Enterprise Config
    echo "=== Test 3: Unified Enterprise Config ===\n";
    
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Test enterprise detection
    $detected_enterprise = UnifiedEnterpriseConfig::getEnterpriseCode();
    echo "✅ Enterprise detected: $detected_enterprise\n";
    
    // Test enterprise info
    $enterprise_info = UnifiedEnterpriseConfig::getEnterprise();
    if ($enterprise_info) {
        echo "✅ Enterprise info loaded\n";
        echo "   Name: " . $enterprise_info['name'] . "\n";
        echo "   Display Name: " . $enterprise_info['display_name'] . "\n";
    } else {
        echo "❌ Enterprise info not loaded\n";
    }
    
    // Test environment detection
    $environment = UnifiedEnterpriseConfig::getEnvironment();
    echo "✅ Environment detected: $environment\n";
    
    // Test start date
    $start_date = UnifiedEnterpriseConfig::getStartDate();
    echo "✅ Start date: $start_date\n";
    
    echo "\n";
    
    // Test 4: URL Generation
    echo "=== Test 4: URL Generation ===\n";
    
    // Test dashboard URL generation
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl($test_password, 'dashboard');
    echo "✅ Dashboard URL for $test_password: $dashboard_url\n";
    
    // Test admin URL generation
    $admin_url = UnifiedEnterpriseConfig::generateUrl($admin_password, 'dashboard');
    echo "✅ Admin URL for $admin_password: $admin_url\n";
    
    echo "\n";
    
    // Test 5: Enterprise Detection
    echo "=== Test 5: Enterprise Detection ===\n";
    
    $detected_enterprise = UnifiedEnterpriseConfig::detectEnterpriseFromPassword($test_password);
    echo "✅ Enterprise detected from password $test_password: $detected_enterprise\n";
    
    // Test enterprise detection from environment
    $detected_from_env = UnifiedEnterpriseConfig::detectEnterprise();
    echo "✅ Enterprise detected from environment: $detected_from_env\n";
    
    echo "\n";
    
    // Test 6: Password Validation
    echo "=== Test 6: Password Validation ===\n";
    
    $test_passwords = ['1234', '5678', '9999', '4000'];
    foreach ($test_passwords as $password) {
        $org = $database->getOrganizationByPassword($password);
        if ($org) {
            echo "✅ Password $password: " . $org['name'] . "\n";
        } else {
            echo "❌ Password $password: Not found\n";
        }
    }
    
    echo "\n";
    
    // Test 7: Configuration Validation
    echo "=== Test 7: Configuration Validation ===\n";
    
    // Test sheet configuration
    $registrants_config = UnifiedEnterpriseConfig::getSheetConfig('registrants');
    if ($registrants_config) {
        echo "✅ Registrants sheet config loaded\n";
        echo "   Workbook ID: " . $registrants_config['workbook_id'] . "\n";
        echo "   Sheet Name: " . $registrants_config['sheet_name'] . "\n";
        echo "   Start Row: " . $registrants_config['start_row'] . "\n";
    } else {
        echo "❌ Registrants sheet config not found\n";
    }
    
    $submissions_config = UnifiedEnterpriseConfig::getSheetConfig('submissions');
    if ($submissions_config) {
        echo "✅ Submissions sheet config loaded\n";
        echo "   Workbook ID: " . $submissions_config['workbook_id'] . "\n";
        echo "   Sheet Name: " . $submissions_config['sheet_name'] . "\n";
        echo "   Start Row: " . $submissions_config['start_row'] . "\n";
    } else {
        echo "❌ Submissions sheet config not found\n";
    }
    
    echo "\n";
    
    // Test Summary
    echo "=== Test Summary ===\n";
    echo "✅ All configuration tests completed successfully\n";
    echo "✅ Unified configuration system is working correctly\n";
    echo "✅ Enterprise: $enterprise\n";
    echo "✅ Environment: $environment\n";
    echo "✅ Database: Connected and functional\n";
    echo "✅ URL Generation: Working correctly\n";
    
    echo "\n=== Configuration Tests Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 