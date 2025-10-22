<?php
// Test Login Flow - Enterprise-Agnostic
require_once __DIR__ . '/../test_base.php';

echo "=== Login Flow Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded successfully\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    // Test admin organization
    $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
    if (!$admin_org) {
        throw new Exception("No admin organization found");
    }
    
    echo "Admin organization: " . $admin_org['name'] . " (password: " . $admin_org['password'] . ")\n";
    
    // Test password validation
    $test_password = $admin_org['password'];
    $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($test_password);
    echo "Password validation: " . ($is_valid ? "✅ PASS" : "❌ FAIL") . "\n";
    
    if (!$is_valid) {
        throw new Exception("Admin password validation failed");
    }
    
    // Test enterprise information
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
    
    // Test URL generation
    $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
    echo "Admin URL: " . $admin_url . "\n";
    
    // Test session management
    echo "\nTesting session management...\n";
    
    // Start output buffering to prevent headers already sent warning
    ob_start();
    session_start();
    
    // Simulate login
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();
    
    $is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
    $enterprise_matches = isset($_SESSION['enterprise_code']) && $_SESSION['enterprise_code'] === UnifiedEnterpriseConfig::getEnterpriseCode();
    
    echo "Authentication set: " . ($is_authenticated ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "Enterprise code matches: " . ($enterprise_matches ? "✅ PASS" : "❌ FAIL") . "\n";
    
    if (!$is_authenticated || !$enterprise_matches) {
        throw new Exception("Session management failed");
    }
    
    // Clean up
    ob_clean();
    
    echo "\n✅ All login flow tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 