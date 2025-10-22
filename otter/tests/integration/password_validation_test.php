<?php
require_once __DIR__ . '/../test_base.php';
require_once __DIR__ . '/../../lib/unified_database.php';
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

class PasswordValidationTest extends TestBase {
    private $db;
    private $testOrgName = 'Test Organization';
    private $originalPassword = '1234';
    private $existingPassword = '5678';
    private $validNewPassword = '9999';

    public function __construct() {
        // Initialize enterprise configuration
        UnifiedEnterpriseConfig::init();
        
        // Create database instance
        $this->db = new UnifiedDatabase();
        
        // Set up test data - ensure test organization exists with known password
        $this->setupTestData();
    }

    private function setupTestData() {
        // This test assumes the test organization exists in enterprise.json
        // If it doesn't exist, we'll need to create it or use an existing one
        $organizations = $this->db->getAllOrganizations();
        
        // Find a suitable test organization (not ADMIN)
        foreach ($organizations as $org) {
            if ($org['name'] !== 'ADMIN' && $org['name'] !== 'Test Organization') {
                $this->testOrgName = $org['name'];
                $this->originalPassword = $org['password'];
                break;
            }
        }
        
        // Ensure we have a different existing password for testing
        foreach ($organizations as $org) {
            if ($org['name'] !== $this->testOrgName && $org['name'] !== 'ADMIN') {
                $this->existingPassword = $org['password'];
                break;
            }
        }
        
        // Ensure valid new password is unique
        $this->validNewPassword = '9999';
        foreach ($organizations as $org) {
            if ($org['password'] === $this->validNewPassword) {
                $this->validNewPassword = '8888';
                break;
            }
        }
    }

    public function testPasswordValidationScenarios() {
        echo "Testing password validation scenarios for organization: {$this->testOrgName}\n";
        echo "Original password: {$this->originalPassword}\n";
        echo "Existing password: {$this->existingPassword}\n";
        echo "Valid new password: {$this->validNewPassword}\n\n";

        // Test 1: User enters New Password that matches Current Password
        $this->testMatchingCurrentPassword();
        
        // Test 2: User enters New Password that is already used
        $this->testAlreadyUsedPassword();
        
        // Test 3: User enters valid New Password value
        $this->testValidNewPassword();
        
        // Cleanup: Restore original password
        $this->restoreOriginalPassword();
    }

    private function testMatchingCurrentPassword() {
        echo "Test 1: Matching Current Password\n";
        echo "  Attempting to change password to same value: {$this->originalPassword}\n";
        
        $result = $this->db->updatePassword($this->testOrgName, $this->originalPassword);
        
        if ($result === false) {
            echo "  ✅ PASS: Correctly rejected matching current password\n";
        } else {
            echo "  ❌ FAIL: Should have rejected matching current password\n";
            self::assertFalse($result, "Password validation should reject matching current password");
        }
        
        // Verify password is still the original
        $organizations = $this->db->getAllOrganizations();
        $currentPassword = null;
        foreach ($organizations as $org) {
            if ($org['name'] === $this->testOrgName) {
                $currentPassword = $org['password'];
                break;
            }
        }
        
        if ($currentPassword === $this->originalPassword) {
            echo "  ✅ PASS: Password unchanged after rejection\n\n";
        } else {
            echo "  ❌ FAIL: Password was changed when it should have been rejected\n\n";
            self::assertEquals($this->originalPassword, $currentPassword, "Password should remain unchanged after rejection");
        }
    }

    private function testAlreadyUsedPassword() {
        echo "Test 2: Already Used Password\n";
        echo "  Attempting to change password to existing value: {$this->existingPassword}\n";
        
        $result = $this->db->updatePassword($this->testOrgName, $this->existingPassword);
        
        if ($result === false) {
            echo "  ✅ PASS: Correctly rejected already used password\n";
        } else {
            echo "  ❌ FAIL: Should have rejected already used password\n";
            self::assertFalse($result, "Password validation should reject already used password");
        }
        
        // Verify password is still the original
        $organizations = $this->db->getAllOrganizations();
        $currentPassword = null;
        foreach ($organizations as $org) {
            if ($org['name'] === $this->testOrgName) {
                $currentPassword = $org['password'];
                break;
            }
        }
        
        if ($currentPassword === $this->originalPassword) {
            echo "  ✅ PASS: Password unchanged after rejection\n\n";
        } else {
            echo "  ❌ FAIL: Password was changed when it should have been rejected\n\n";
            self::assertEquals($this->originalPassword, $currentPassword, "Password should remain unchanged after rejection");
        }
    }

    private function testValidNewPassword() {
        echo "Test 3: Valid New Password\n";
        echo "  Attempting to change password to valid value: {$this->validNewPassword}\n";
        
        $result = $this->db->updatePassword($this->testOrgName, $this->validNewPassword);
        
        if ($result === true) {
            echo "  ✅ PASS: Successfully updated to valid new password\n";
        } else {
            echo "  ❌ FAIL: Should have accepted valid new password\n";
            self::assertTrue($result, "Password validation should accept valid new password");
        }
        
        // Verify password was actually changed
        $organizations = $this->db->getAllOrganizations();
        $currentPassword = null;
        foreach ($organizations as $org) {
            if ($org['name'] === $this->testOrgName) {
                $currentPassword = $org['password'];
                break;
            }
        }
        
        if ($currentPassword === $this->validNewPassword) {
            echo "  ✅ PASS: Password successfully updated in database\n\n";
        } else {
            echo "  ❌ FAIL: Password was not updated in database\n\n";
            self::assertEquals($this->validNewPassword, $currentPassword, "Password should be updated in database");
        }
    }

    private function restoreOriginalPassword() {
        echo "Cleanup: Restoring original password\n";
        $result = $this->db->updatePassword($this->testOrgName, $this->originalPassword);
        
        if ($result === true) {
            echo "  ✅ PASS: Successfully restored original password\n\n";
        } else {
            echo "  ❌ FAIL: Could not restore original password\n\n";
            self::assertTrue($result, "Should be able to restore original password");
        }
    }

    public function testAJAXPasswordValidation() {
        echo "Testing AJAX password validation endpoints\n";
        
        // Test AJAX endpoint for password change
        $this->testAJAXPasswordChange();
        
        // Test AJAX endpoint for getting organizations
        $this->testAJAXGetOrganizations();
    }

    private function testAJAXPasswordChange() {
        echo "Test: AJAX Password Change Endpoint\n";
        
        // Simulate AJAX request for matching current password
        $_POST = [
            'action' => 'change_password',
            'org_name' => $this->testOrgName,
            'new_password' => $this->originalPassword
        ];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Capture output
        ob_start();
        include __DIR__ . '/../../settings/index.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && isset($response['success']) && $response['success'] === false) {
            echo "  ✅ PASS: AJAX endpoint correctly rejects matching password\n";
        } else {
            echo "  ❌ FAIL: AJAX endpoint should reject matching password\n";
            self::assertFalse($response['success'] ?? true, "AJAX endpoint validation failed");
        }
        
        // Test valid password change
        $_POST['new_password'] = $this->validNewPassword;
        
        ob_start();
        include __DIR__ . '/../../settings/index.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && isset($response['success']) && $response['success'] === true) {
            echo "  ✅ PASS: AJAX endpoint correctly accepts valid password\n";
        } else {
            echo "  ❌ FAIL: AJAX endpoint should accept valid password\n";
            self::assertTrue($response['success'] ?? false, "AJAX endpoint validation failed");
        }
        
        // Restore original password
        $this->db->updatePassword($this->testOrgName, $this->originalPassword);
    }

    private function testAJAXGetOrganizations() {
        echo "Test: AJAX Get Organizations Endpoint\n";
        
        // Simulate AJAX request for getting organizations
        $_GET = ['action' => 'get_organizations'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Capture output
        ob_start();
        include __DIR__ . '/../../settings/index.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && isset($response['organizations']) && is_array($response['organizations'])) {
            echo "  ✅ PASS: AJAX endpoint returns organizations data\n";
            
            // Verify test organization is in the response
            $found = false;
            foreach ($response['organizations'] as $org) {
                if ($org['name'] === $this->testOrgName) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo "  ✅ PASS: Test organization found in response\n";
            } else {
                echo "  ❌ FAIL: Test organization not found in response\n";
                self::assertTrue($found, "Test organization should be in response");
            }
        } else {
            echo "  ❌ FAIL: AJAX endpoint should return organizations data\n";
            self::assertNotEmpty($response['organizations'] ?? [], "AJAX get organizations endpoint failed");
        }
    }
}

// Run the test if this file is executed directly
if (php_sapi_name() === 'cli' || isset($argv)) {
    $test = new PasswordValidationTest();
    $test->testPasswordValidationScenarios();
    $test->testAJAXPasswordValidation();
    echo "\nAll tests completed!\n";
}
?> 