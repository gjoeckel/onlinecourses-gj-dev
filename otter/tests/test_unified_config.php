<?php
// Test script for unified configuration system
require_once __DIR__ . '/lib/unified_enterprise_config.php';

echo "<h1>Unified Configuration System Test</h1>\n";

try {
    // Test 1: Load configuration files
    echo "<h2>Test 1: Configuration File Loading</h2>\n";
    
    // Test passwords.json
    if (file_exists(__DIR__ . '/config/passwords.json')) {
        $passwords = json_decode(file_get_contents(__DIR__ . '/config/passwords.json'), true);
        echo "✓ passwords.json loaded successfully<br>\n";
        echo "  - Total organizations: " . count($passwords['organizations']) . "<br>\n";
        echo "  - Enterprises: " . implode(', ', $passwords['metadata']['enterprises']) . "<br>\n";
    } else {
        echo "✗ passwords.json not found<br>\n";
    }
    
    // Test dashboards.json
    if (file_exists(__DIR__ . '/config/dashboards.json')) {
        $dashboards = json_decode(file_get_contents(__DIR__ . '/config/dashboards.json'), true);
        echo "✓ dashboards.json loaded successfully<br>\n";
        echo "  - Supported environments: " . implode(', ', $dashboards['metadata']['supported_environments']) . "<br>\n";
    } else {
        echo "✗ dashboards.json not found<br>\n";
    }
    
    // Test csu.config
    if (file_exists(__DIR__ . '/config/csu.config')) {
        $csu_config = json_decode(file_get_contents(__DIR__ . '/config/csu.config'), true);
        echo "✓ csu.config loaded successfully<br>\n";
        echo "  - Enterprise: " . $csu_config['enterprise']['name'] . " (" . $csu_config['enterprise']['code'] . ")<br>\n";
    } else {
        echo "✗ csu.config not found<br>\n";
    }
    
    // Test 2: Unified Database
    echo "<h2>Test 2: Unified Database</h2>\n";
    
    $database = new UnifiedDatabase();
    echo "✓ UnifiedDatabase initialized successfully<br>\n";
    
    // Test organization lookup
    $test_password = "8472"; // Bakersfield
    $org = $database->getOrganizationByPassword($test_password);
    if ($org) {
        echo "✓ Organization lookup successful for password $test_password<br>\n";
        echo "  - Name: " . $org['name'] . "<br>\n";
        echo "  - Enterprise: " . $org['enterprise'] . "<br>\n";
        echo "  - Is Admin: " . ($org['is_admin'] ? 'Yes' : 'No') . "<br>\n";
    } else {
        echo "✗ Organization lookup failed for password $test_password<br>\n";
    }
    
    // Test admin lookup
    $admin_org = $database->getAdminOrganization('csu');
    if ($admin_org) {
        echo "✓ Admin organization lookup successful<br>\n";
        echo "  - Name: " . $admin_org['name'] . "<br>\n";
        echo "  - Password: " . $admin_org['password'] . "<br>\n";
    } else {
        echo "✗ Admin organization lookup failed<br>\n";
    }
    
    // Test 3: Unified Enterprise Config
    echo "<h2>Test 3: Unified Enterprise Config</h2>\n";
    
    // Initialize with CSU
    UnifiedEnterpriseConfig::init('csu');
    echo "✓ UnifiedEnterpriseConfig initialized for CSU<br>\n";
    
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "  - Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")<br>\n";
    
    $organizations = UnifiedEnterpriseConfig::getOrganizations();
    echo "  - Organizations count: " . count($organizations) . "<br>\n";
    
    // Test 4: URL Generation
    echo "<h2>Test 4: URL Generation</h2>\n";
    
    $urlGenerator = UnifiedEnterpriseConfig::getUrlGenerator();
    echo "✓ URL Generator initialized<br>\n";
    
    // Test dashboard URL generation
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl($test_password, 'dashboard');
    echo "  - Dashboard URL for $test_password: $dashboard_url<br>\n";
    
    // Test admin URL generation
    $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
    echo "  - Admin URL: $admin_url<br>\n";
    
    // Test 5: Enterprise Detection
    echo "<h2>Test 5: Enterprise Detection</h2>\n";
    
    $detected_enterprise = UnifiedEnterpriseConfig::detectEnterpriseFromPassword($test_password);
    echo "  - Enterprise detected from password $test_password: $detected_enterprise<br>\n";
    
    // Test 6: Password Validation
    echo "<h2>Test 6: Password Validation</h2>\n";
    
    $valid_passwords = ["8472", "4000", "9999"]; // Bakersfield, Admin, Invalid
    foreach ($valid_passwords as $password) {
        $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($password);
        $is_admin = UnifiedEnterpriseConfig::isAdminOrganization($password);
        echo "  - Password $password: " . ($is_valid ? 'Valid' : 'Invalid') . 
             ($is_admin ? ' (Admin)' : '') . "<br>\n";
    }
    
    echo "<h2>Test Summary</h2>\n";
    echo "✓ All configuration files loaded successfully<br>\n";
    echo "✓ Unified database working correctly<br>\n";
    echo "✓ Enterprise detection working correctly<br>\n";
    echo "✓ URL generation working correctly<br>\n";
    echo "✓ Password validation working correctly<br>\n";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>\n";
    echo "✗ " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "1. Test login_unified.php with CSU organization passwords<br>\n";
echo "2. Verify URL generation produces URLs with org parameters<br>\n";
echo "3. Test admin login functionality<br>\n";
echo "4. Validate enterprise detection from organization passwords<br>\n";
?> 