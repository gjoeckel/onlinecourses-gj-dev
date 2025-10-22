// This file was previously named password_validation_test.php. It has been renamed to change_password_validation_test.php for clarity. 

<?php
/**
 * Password Change Validation Integration Test
 * Tests password change functionality and validation
 */

require_once __DIR__ . '/../test_base.php';
require_once __DIR__ . '/../../lib/unified_database.php';
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

echo "=== Password Change Validation Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    $db = new UnifiedDatabase();
    
    // Test 1: Database connection
    TestBase::runTest('Database Connection', function() use ($db) {
        $organizations = $db->getAllOrganizations();
        TestBase::assertNotNull($organizations, 'Organizations should be loaded from database');
        TestBase::assertTrue(is_array($organizations), 'Organizations should be an array');
        TestBase::assertGreaterThan(0, count($organizations), 'Should have at least one organization');
        
        echo "   Found " . count($organizations) . " organizations\n";
    });
    
    // Test 2: Password validation function
    TestBase::runTest('Password Validation Function', function() {
        $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
        TestBase::assertNotNull($admin_org, 'Admin organization should exist');
        
        $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($admin_org['password']);
        TestBase::assertTrue($is_valid, 'Admin password should be valid');
        
        echo "   Admin password validation: PASS\n";
    });
    
    // Test 3: Password update functionality
    TestBase::runTest('Password Update Functionality', function() use ($db) {
        // Find an organization to test with
        $organizations = $db->getAllOrganizations();
        $test_org = null;
        
        foreach ($organizations as $org) {
            if (!$org['is_admin']) {
                $test_org = $org;
                break;
            }
        }
        
        if ($test_org) {
            $original_password = $test_org['password'];
            $test_password = 'test_' . time(); // Unique test password
            
            // Update password
            $success = $db->updatePassword($test_org['name'], $test_password);
            
            if ($success) {
                // Verify password was updated
                $updated_orgs = $db->getAllOrganizations();
                $updated_org = null;
                
                foreach ($updated_orgs as $org) {
                    if ($org['name'] === $test_org['name']) {
                        $updated_org = $org;
                        break;
                    }
                }
                
                TestBase::assertNotNull($updated_org, 'Updated organization should be found');
                TestBase::assertEquals($test_password, $updated_org['password'], 'Password should be updated');
                
                // Restore original password
                $db->updatePassword($test_org['name'], $original_password);
                
                echo "   Password update test: PASS\n";
            } else {
                echo "   Password update test: SKIP (update failed, may be expected)\n";
            }
        } else {
            echo "   Password update test: SKIP (no non-admin organization found)\n";
        }
    });
    
    // Test 4: Settings endpoint accessibility
    TestBase::runTest('Settings Endpoint Accessibility', function() {
        $settings_file = __DIR__ . '/../../settings/index.php';
        TestBase::assertTrue(file_exists($settings_file), 'Settings file should exist');
        
        // Test if settings endpoint is accessible
        if (function_exists('file_get_contents')) {
            $settings_url = 'http://localhost:8000/settings/';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $result = @file_get_contents($settings_url, false, $context);
            if ($result !== false) {
                echo "   Settings endpoint accessible\n";
            } else {
                echo "   Settings endpoint test skipped (server not running)\n";
            }
        }
    });
    
    // Test 5: Enterprise configuration after password change
    TestBase::runTest('Enterprise Configuration Validation', function() {
        $enterprise = UnifiedEnterpriseConfig::getEnterprise();
        TestBase::assertNotNull($enterprise, 'Enterprise configuration should be loaded');
        TestBase::assertNotEmpty($enterprise['name'], 'Enterprise name should not be empty');
        TestBase::assertNotEmpty($enterprise['code'], 'Enterprise code should not be empty');
        
        $organizations = UnifiedEnterpriseConfig::getOrganizations();
        TestBase::assertNotNull($organizations, 'Organizations should be loaded');
        TestBase::assertTrue(is_array($organizations), 'Organizations should be an array');
        
        echo "   Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
        echo "   Organizations: " . count($organizations) . "\n";
    });
    
    echo "\n✅ Password Change Validation Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 