<?php
// Test Enterprise Configuration System - Enterprise-Agnostic
require_once __DIR__ . '/../test_base.php';

echo "=== Enterprise Configuration Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded successfully\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    // Test enterprise information
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
    echo "Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";
    
    // Test organizations
    $organizations = UnifiedEnterpriseConfig::getOrganizations();
    echo "Organizations count: " . count($organizations) . "\n";
    
    // Test admin organization
    $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
    if ($admin_org) {
        echo "Admin organization: " . $admin_org['name'] . " (password: " . $admin_org['password'] . ")\n";
    } else {
        echo "❌ No admin organization found\n";
    }
    
    // Test Google Sheets configuration
    $sheets = UnifiedEnterpriseConfig::getGoogleSheets();
    echo "\nGoogle Sheets configured:\n";
    foreach (array_keys($sheets) as $sheet_type) {
        echo "- $sheet_type\n";
    }
    
    // Test settings
    $settings = UnifiedEnterpriseConfig::getSettings();
    echo "\nSettings:\n";
    echo "- Start date: " . $settings['start_date'] . "\n";
    echo "- Cache TTL: " . $settings['cache_ttl'] . " seconds\n";
    echo "- Timezone: " . $settings['timezone'] . "\n";
    
    // Test URL generation
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl('', 'dashboard');
    echo "- Dashboard URL: " . $dashboard_url . "\n";
    
    echo "\n✅ All configuration tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 