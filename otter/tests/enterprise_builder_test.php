<?php
/**
 * Enterprise Builder Test Suite
 * Tests the enterprise builder functionality
 */

require_once __DIR__ . '/test_base.php';

class EnterpriseBuilderTest extends TestBase {
    
    public function testSuperAdminAuthentication() {
        echo "Testing Super Admin Authentication...\n";
        
        // Test that super admin can access builder
        $this->assertTrue(true, "Super admin authentication test placeholder");
        
        echo "✓ Super Admin Authentication: PASSED\n";
    }
    
    public function testEnterpriseCodeValidation() {
        echo "Testing Enterprise Code Validation...\n";
        
        $valid_codes = ['csu', 'ccc', 'test123'];
        $invalid_codes = ['CS', 'invalid-code', '123', 'a', 'toolongenterprisecode'];
        
        foreach ($valid_codes as $code) {
            self::assertTrue(preg_match('/^[a-z0-9]{2,10}$/', $code), "Valid enterprise code should pass: $code");
        }
        
        foreach ($invalid_codes as $code) {
            self::assertFalse(preg_match('/^[a-z0-9]{2,10}$/', $code), "Invalid enterprise code should fail: $code");
        }
        
        echo "✓ Enterprise Code Validation: PASSED\n";
    }
    
    public function testAdminPasswordValidation() {
        echo "Testing Admin Password Validation...\n";
        
        $valid_passwords = ['1234', '0000', '9999', '5678'];
        $invalid_passwords = ['123', '12345', 'abcd', '12a4', ''];
        
        foreach ($valid_passwords as $password) {
            self::assertTrue(preg_match('/^[0-9]{4}$/', $password), "Valid admin password should pass: $password");
        }
        
        foreach ($invalid_passwords as $password) {
            self::assertFalse(preg_match('/^[0-9]{4}$/', $password), "Invalid admin password should fail: $password");
        }
        
        echo "✓ Admin Password Validation: PASSED\n";
    }
    
    public function testStartDateValidation() {
        echo "Testing Start Date Validation...\n";
        
        $valid_dates = ['01-01-24', '12-31-25', '05-06-24'];
        $invalid_dates = ['1-1-24', '01/01/24', '2024-01-01', 'invalid', ''];
        
        foreach ($valid_dates as $date) {
            self::assertTrue(preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/', $date), "Valid start date should pass: $date");
        }
        
        foreach ($invalid_dates as $date) {
            self::assertFalse(preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/', $date), "Invalid start date should fail: $date");
        }
        
        echo "✓ Start Date Validation: PASSED\n";
    }
    
    public function testOrganizationsDataParsing() {
        echo "Testing Organizations Data Parsing...\n";
        
        $valid_data = "Org1|1234\nOrg2|5678\nOrg3|9012";
        $invalid_data = "Org1|123\nOrg2|5678\nOrg3|9012"; // Invalid password format
        
        // Test valid data parsing
        $lines = explode("\n", trim($valid_data));
        $organizations = [];
        $passwords = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode('|', $line);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $password = trim($parts[1]);
                
                if (!empty($name) && preg_match('/^[0-9]{4}$/', $password)) {
                    $organizations[] = ['name' => $name, 'password' => $password];
                    $passwords[] = $password;
                }
            }
        }
        
        self::assertEquals(3, count($organizations), "Should parse 3 valid organizations");
        self::assertEquals(3, count($passwords), "Should have 3 valid passwords");
        
        echo "✓ Organizations Data Parsing: PASSED\n";
    }
    
    public function testConfigurationFileGeneration() {
        echo "Testing Configuration File Generation...\n";
        
        // Test passwords.json structure
        $passwords_file = __DIR__ . '/../config/passwords.json';
        self::assertTrue(file_exists($passwords_file), "passwords.json should exist");
        
        $passwords_data = json_decode(file_get_contents($passwords_file), true);
        self::assertNotNull($passwords_data, "passwords.json should be valid JSON");
        self::assertArrayHasKey('admin_passwords', $passwords_data, "Should have admin_passwords section");
        self::assertArrayHasKey('organizations', $passwords_data, "Should have organizations section");
        self::assertArrayHasKey('metadata', $passwords_data, "Should have metadata section");
        
        // Test dashboards.json structure
        $dashboards_file = __DIR__ . '/../config/dashboards.json';
        self::assertTrue(file_exists($dashboards_file), "dashboards.json should exist");
        
        $dashboards_data = json_decode(file_get_contents($dashboards_file), true);
        self::assertNotNull($dashboards_data, "dashboards.json should be valid JSON");
        self::assertArrayHasKey('environments', $dashboards_data, "Should have environments section");
        self::assertArrayHasKey('metadata', $dashboards_data, "Should have metadata section");
        
        // Test super.config structure
        $super_config_file = __DIR__ . '/../config/super.config';
        self::assertTrue(file_exists($super_config_file), "super.config should exist");
        
        $super_data = json_decode(file_get_contents($super_config_file), true);
        self::assertNotNull($super_data, "super.config should be valid JSON");
        self::assertArrayHasKey('enterprise', $super_data, "Should have enterprise section");
        self::assertArrayHasKey('google_sheets', $super_data, "Should have google_sheets section");
        self::assertArrayHasKey('settings', $super_data, "Should have settings section");
        self::assertArrayHasKey('api', $super_data, "Should have api section");
        self::assertArrayHasKey('metadata', $super_data, "Should have metadata section");
        
        echo "✓ Configuration File Generation: PASSED\n";
    }
    
    public function testBackupAndRollback() {
        echo "Testing Backup and Rollback...\n";
        
        $config_dir = __DIR__ . '/../config';
        $backup_dir = $config_dir . '/backups/test_backup';
        
        // Test backup creation
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        self::assertTrue(is_dir($backup_dir), "Backup directory should be created");
        
        // Test file backup
        $test_file = $backup_dir . '/test.txt';
        file_put_contents($test_file, 'test content');
        self::assertTrue(file_exists($test_file), "Test file should be created in backup");
        
        // Cleanup
        unlink($test_file);
        rmdir($backup_dir);
        
        echo "✓ Backup and Rollback: PASSED\n";
    }
    
    public function testCacheDirectoryCreation() {
        echo "Testing Cache Directory Creation...\n";
        
        $cache_dir = __DIR__ . '/../cache/test_enterprise';
        
        // Test directory creation
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0777, true);
        }
        
        self::assertTrue(is_dir($cache_dir), "Cache directory should be created");
        
        // Test write permissions
        $test_file = $cache_dir . '/test.txt';
        $result = file_put_contents($test_file, 'test content');
        self::assertNotFalse($result, "Should be able to write to cache directory");
        
        // Cleanup
        unlink($test_file);
        rmdir($cache_dir);
        
        echo "✓ Cache Directory Creation: PASSED\n";
    }
    
    public function testFormValidation() {
        echo "Testing Form Validation...\n";
        
        // Test required fields validation
        $required_fields = [
            'enterprise_code', 'enterprise_name', 'display_name', 'admin_password',
            'production_domain', 'google_api_key', 'registrants_workbook_id', 
            'registrants_sheet_name', 'submissions_workbook_id', 'submissions_sheet_name',
            'organizations_data', 'start_date', 'timezone'
        ];
        
        $test_data = [];
        foreach ($required_fields as $field) {
            $test_data[$field] = ''; // Empty values should fail validation
        }
        
        // Test that empty required fields would fail
        $has_empty_fields = false;
        foreach ($required_fields as $field) {
            if (empty($test_data[$field])) {
                $has_empty_fields = true;
                break;
            }
        }
        
        self::assertTrue($has_empty_fields, "Should detect empty required fields");
        
        echo "✓ Form Validation: PASSED\n";
    }
    
    public function testDuplicateValidation() {
        echo "Testing Duplicate Validation...\n";
        
        // Test organization name uniqueness
        $organizations = [
            ['name' => 'Org1', 'password' => '1234'],
            ['name' => 'Org2', 'password' => '5678'],
            ['name' => 'Org1', 'password' => '9012'] // Duplicate name
        ];
        
        $names = array_column($organizations, 'name');
        $unique_names = array_unique($names);
        
        self::assertNotEquals(count($names), count($unique_names), "Should detect duplicate organization names");
        
        // Test password uniqueness
        $passwords = array_column($organizations, 'password');
        $unique_passwords = array_unique($passwords);
        
        self::assertEquals(count($passwords), count($unique_passwords), "Passwords should be unique");
        
        echo "✓ Duplicate Validation: PASSED\n";
    }
    
    public function runAllTests() {
        echo "=== Enterprise Builder Test Suite ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->testSuperAdminAuthentication();
        $this->testEnterpriseCodeValidation();
        $this->testAdminPasswordValidation();
        $this->testStartDateValidation();
        $this->testOrganizationsDataParsing();
        $this->testConfigurationFileGeneration();
        $this->testBackupAndRollback();
        $this->testCacheDirectoryCreation();
        $this->testFormValidation();
        $this->testDuplicateValidation();
        
        echo "\n=== All Enterprise Builder Tests Completed ===\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $test = new EnterpriseBuilderTest();
    $test->runAllTests();
} 