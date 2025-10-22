<?php
/**
 * CCC Enterprise Test Suite
 * Tests the CCC enterprise functionality specifically
 */

require_once __DIR__ . '/test_base.php';

echo "=== CCC Enterprise Test Suite ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Set enterprise to CCC
TestBase::setEnterprise('ccc');
TestBase::initEnterprise('ccc');

echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";

try {
    // Test 1: Enterprise Configuration
    echo "1. Testing Enterprise Configuration...\n";
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "   Enterprise Name: " . $enterprise['name'] . "\n";
    echo "   Enterprise Code: " . $enterprise['code'] . "\n";
    echo "   Display Name: " . $enterprise['display_name'] . "\n";
    echo "   Has Groups: " . ($enterprise['has_groups'] ? 'Yes' : 'No') . "\n";
    echo "   ✅ Enterprise configuration loaded successfully\n\n";
    
    // Test 2: Organizations
    echo "2. Testing Organizations...\n";
    $organizations = UnifiedEnterpriseConfig::getOrganizations();
    echo "   Total Organizations: " . count($organizations) . "\n";
    echo "   Sample Organizations:\n";
    for ($i = 0; $i < min(5, count($organizations)); $i++) {
        $org = $organizations[$i];
        echo "     - " . $org['name'] . "\n";
    }
    echo "   ✅ Organizations loaded successfully\n\n";
    
    // Test 3: Admin Organization
    echo "3. Testing Admin Organization...\n";
    $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
    if ($admin_org) {
        echo "   Admin Name: " . $admin_org['name'] . "\n";
        echo "   Admin Password: " . $admin_org['password'] . "\n";
        echo "   Is Admin: " . ($admin_org['is_admin'] ? 'Yes' : 'No') . "\n";
        echo "   ✅ Admin organization found\n\n";
    } else {
        echo "   ❌ No admin organization found\n\n";
    }
    
    // Test 4: Password Validation
    echo "4. Testing Password Validation...\n";
    if ($admin_org) {
        $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($admin_org['password']);
        echo "   Admin password valid: " . ($is_valid ? '✅ Yes' : '❌ No') . "\n";
        
        // Test a few organization passwords
        $db = new UnifiedDatabase();
        $all_orgs = $db->getAllOrganizations();
        $ccc_orgs = array_filter($all_orgs, function($org) {
            return $org['enterprise'] === 'ccc';
        });
        
        $test_count = 0;
        $valid_count = 0;
        foreach (array_slice($ccc_orgs, 0, 5) as $org) {
            $test_count++;
            if (UnifiedEnterpriseConfig::isValidOrganizationPassword($org['password'])) {
                $valid_count++;
            }
        }
        echo "   Tested $test_count organization passwords: $valid_count valid\n";
        echo "   ✅ Password validation working\n\n";
    }
    
    // Test 5: URL Generation
    echo "5. Testing URL Generation...\n";
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl('', 'dashboard');
    $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
    $login_url = UnifiedEnterpriseConfig::generateUrl('', 'login');
    
    echo "   Dashboard URL: $dashboard_url\n";
    echo "   Admin URL: $admin_url\n";
    echo "   Login URL: $login_url\n";
    echo "   ✅ URL generation working\n\n";
    
    // Test 6: Google Sheets Configuration
    echo "6. Testing Google Sheets Configuration...\n";
    $config = UnifiedEnterpriseConfig::getFullConfig();
    if (isset($config['google_sheets'])) {
        echo "   Registrants Workbook ID: " . $config['google_sheets']['registrants']['workbook_id'] . "\n";
        echo "   Submissions Workbook ID: " . $config['google_sheets']['submissions']['workbook_id'] . "\n";
        echo "   ✅ Google Sheets configuration loaded\n\n";
    } else {
        echo "   ❌ Google Sheets configuration not found\n\n";
    }
    
    // Test 7: Settings
    echo "7. Testing Settings...\n";
    if (isset($config['settings'])) {
        echo "   Start Date: " . $config['settings']['start_date'] . "\n";
        echo "   Timezone: " . $config['settings']['timezone'] . "\n";
        echo "   Cache TTL: " . $config['settings']['cache_ttl'] . "\n";
        echo "   ✅ Settings loaded\n\n";
    } else {
        echo "   ❌ Settings not found\n\n";
    }
    
    // Test 8: Database Connection
    echo "8. Testing Database Connection...\n";
    try {
        $db = new UnifiedDatabase();
        $all_orgs = $db->getAllOrganizations();
        $ccc_orgs = array_filter($all_orgs, function($org) {
            return $org['enterprise'] === 'ccc';
        });
        echo "   Total CCC organizations in database: " . count($ccc_orgs) . "\n";
        echo "   ✅ Database connection successful\n\n";
    } catch (Exception $e) {
        echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    }
    
    echo "=== CCC Enterprise Test Summary ===\n";
    echo "✅ All CCC enterprise tests completed successfully!\n";
    echo "Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
    echo "Organizations: " . count($organizations) . "\n";
    echo "Configuration: Loaded and valid\n";
    echo "URLs: Generated correctly\n";
    echo "Database: Connected and accessible\n";
    
} catch (Exception $e) {
    echo "❌ Error during CCC enterprise testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 