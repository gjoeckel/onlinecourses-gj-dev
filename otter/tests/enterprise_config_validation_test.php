<?php
/**
 * Enterprise Configuration Validation Test
 * 
 * Tests the process for creating {enterprise}.config with all information.
 * Focuses on validation of enterprise configuration file generation.
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_database.php';

class EnterpriseConfigValidationTest {
    private $test_enterprise_code = 'testenterprise';
    private $test_config_file;
    private $test_passwords_file;
    
    public function __construct() {
        $this->test_config_file = __DIR__ . '/../config/' . $this->test_enterprise_code . '.config';
        $this->test_passwords_file = __DIR__ . '/../config/passwords.json';
    }
    
    public function runAllTests() {
        echo "=== Enterprise Configuration Validation Test ===\n\n";
        
        $tests = [
            'testFormDataValidation',
            'testEnterpriseConfigGeneration',
            'testConfigurationFileStructure',
            'testConfigurationLoading',
            'testDataFormatValidation'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            echo "Running: $test\n";
            try {
                $result = $this->$test();
                if ($result) {
                    echo "✅ PASSED: $test\n";
                    $passed++;
                } else {
                    echo "❌ FAILED: $test\n";
                }
            } catch (Exception $e) {
                echo "❌ ERROR: $test - " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
        
        echo "=== Test Results ===\n";
        echo "Passed: $passed/$total\n";
        echo "Failed: " . ($total - $passed) . "/$total\n";
        
        // Cleanup test files
        $this->cleanup();
        
        return $passed === $total;
    }
    
    /**
     * Test 1: Form Data Validation
     * Validates that all required fields are present and properly formatted
     */
    public function testFormDataValidation() {
        echo "  Testing form data validation...\n";
        
        // Simulate form data from enterprise builder
        $formData = [
            'enterprise_code' => 'testenterprise',
            'enterprise_name' => 'Test Enterprise',
            'display_name' => 'Test Enterprise',
            'admin_password' => '1234',
            'has_groups' => 'false',
            'google_api_key' => 'AIzaSyDd9Dsjeb2KtW7MPV1f1Lx5CE9hWfwTuU8',
            'registrants_workbook_id' => '15BHjtrDXq1k1nLBOxAQcmc_Y3sgXvS6WwZMqaHyARBk',
            'submissions_workbook_id' => '1FqCZWsIohWoeTV7q22o60Y3IGK_lOd1Lc3l4EQ5Ufks',
            'organizations_data' => "Bakersfield\nChico\nFresno\nLong Beach",
            'start_date' => '05-06-24',
            'timezone' => 'America/Los_Angeles'
        ];
        
        // Validate required fields
        $required_fields = [
            'enterprise_code', 'enterprise_name', 'display_name', 'admin_password',
            'has_groups', 'google_api_key', 'registrants_workbook_id', 
            'submissions_workbook_id', 'organizations_data', 'start_date', 'timezone'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($formData[$field]) || empty($formData[$field])) {
                echo "    ❌ Missing required field: $field\n";
                return false;
            }
        }
        
        // Validate enterprise code format
        if (!preg_match('/^[a-z0-9]{2,10}$/', $formData['enterprise_code'])) {
            echo "    ❌ Invalid enterprise code format\n";
            return false;
        }
        
        // Validate admin password format
        if (!preg_match('/^[0-9]{4}$/', $formData['admin_password'])) {
            echo "    ❌ Invalid admin password format\n";
            return false;
        }
        
        // Validate start date format
        if (!preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/', $formData['start_date'])) {
            echo "    ❌ Invalid start date format\n";
            return false;
        }
        
        // Validate organizations data
        $organizations = array_filter(array_map('trim', explode("\n", $formData['organizations_data'])));
        if (empty($organizations)) {
            echo "    ❌ No organizations provided\n";
            return false;
        }
        
        echo "    ✅ All form data validation passed\n";
        return true;
    }
    
    /**
     * Test 2: Enterprise Config Generation
     * Tests the generation of enterprise configuration file
     */
    public function testEnterpriseConfigGeneration() {
        echo "  Testing enterprise config generation...\n";
        
        // Simulate form data
        $formData = [
            'enterprise_code' => $this->test_enterprise_code,
            'enterprise_name' => 'Test Enterprise',
            'display_name' => 'Test Enterprise',
            'admin_password' => '1234',
            'has_groups' => 'false',
            'google_api_key' => 'AIzaSyDd9Dsjeb2KtW7MPV1f1Lx5CE9hWfwTuU8',
            'registrants_workbook_id' => '15BHjtrDXq1k1nLBOxAQcmc_Y3sgXvS6WwZMqaHyARBk',
            'submissions_workbook_id' => '1FqCZWsIohWoeTV7q22o60Y3IGK_lOd1Lc3l4EQ5Ufks',
            'organizations_data' => "Bakersfield\nChico\nFresno\nLong Beach",
            'start_date' => '05-06-24',
            'timezone' => 'America/Los_Angeles'
        ];
        
        // Generate enterprise configuration
        $config = $this->generateEnterpriseConfig($formData);
        
        if (!$config) {
            echo "    ❌ Failed to generate enterprise config\n";
            return false;
        }
        
        // Validate config structure
        $required_sections = ['enterprise', 'google_sheets', 'settings', 'api', 'metadata'];
        foreach ($required_sections as $section) {
            if (!isset($config[$section])) {
                echo "    ❌ Missing required section: $section\n";
                return false;
            }
        }
        
        // Validate enterprise section
        $enterprise_fields = ['name', 'code', 'display_name', 'has_groups'];
        foreach ($enterprise_fields as $field) {
            if (!isset($config['enterprise'][$field])) {
                echo "    ❌ Missing enterprise field: $field\n";
                return false;
            }
        }
        
        // Validate Google Sheets section
        if (!isset($config['google_sheets']['registrants']['workbook_id']) ||
            !isset($config['google_sheets']['submissions']['workbook_id'])) {
            echo "    ❌ Missing Google Sheets workbook IDs\n";
            return false;
        }
        
        // Validate settings section
        if (!isset($config['settings']['start_date']) ||
            !isset($config['settings']['timezone'])) {
            echo "    ❌ Missing settings fields\n";
            return false;
        }
        
        // Validate API section
        if (!isset($config['api']['google_api_key'])) {
            echo "    ❌ Missing API key\n";
            return false;
        }
        
        echo "    ✅ Enterprise config generation passed\n";
        return true;
    }
    
    /**
     * Test 3: Configuration File Structure
     * Tests that generated configuration files have proper structure and can be written
     */
    public function testConfigurationFileStructure() {
        echo "  Testing configuration file structure...\n";
        
        // Generate test configuration
        $formData = [
            'enterprise_code' => $this->test_enterprise_code,
            'enterprise_name' => 'Test Enterprise',
            'display_name' => 'Test Enterprise',
            'admin_password' => '1234',
            'has_groups' => 'false',
            'google_api_key' => 'AIzaSyDd9Dsjeb2KtW7MPV1f1Lx5CE9hWfwTuU8',
            'registrants_workbook_id' => '15BHjtrDXq1k1nLBOxAQcmc_Y3sgXvS6WwZMqaHyARBk',
            'submissions_workbook_id' => '1FqCZWsIohWoeTV7q22o60Y3IGK_lOd1Lc3l4EQ5Ufks',
            'organizations_data' => "Bakersfield\nChico\nFresno\nLong Beach",
            'start_date' => '05-06-24',
            'timezone' => 'America/Los_Angeles'
        ];
        
        $config = $this->generateEnterpriseConfig($formData);
        
        // Test file writing
        $json_data = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json_data === false) {
            echo "    ❌ Failed to encode JSON\n";
            return false;
        }
        
        // Write test file
        $result = file_put_contents($this->test_config_file, $json_data);
        if ($result === false) {
            echo "    ❌ Failed to write config file\n";
            return false;
        }
        
        // Verify file exists and is readable
        if (!file_exists($this->test_config_file)) {
            echo "    ❌ Config file not created\n";
            return false;
        }
        
        // Verify file content is valid JSON
        $read_config = json_decode(file_get_contents($this->test_config_file), true);
        if ($read_config === null) {
            echo "    ❌ Invalid JSON in config file\n";
            return false;
        }
        
        echo "    ✅ Configuration file structure passed\n";
        return true;
    }
    
    /**
     * Test 4: Configuration Loading
     * Tests that generated configuration can be loaded by the system
     */
    public function testConfigurationLoading() {
        echo "  Testing configuration loading...\n";
        
        // Ensure test config file exists
        if (!file_exists($this->test_config_file)) {
            echo "    ❌ Test config file not found\n";
            return false;
        }
        
        try {
            // Initialize the configuration for the test enterprise
            UnifiedEnterpriseConfig::init($this->test_enterprise_code);
            
            // Test basic configuration access
            $enterprise_name = UnifiedEnterpriseConfig::getEnterpriseName();
            $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
            $display_name = UnifiedEnterpriseConfig::getDisplayName();
            $has_groups = UnifiedEnterpriseConfig::getHasGroups();
            
            if ($enterprise_name !== 'Test Enterprise' ||
                $enterprise_code !== $this->test_enterprise_code ||
                $display_name !== 'Test Enterprise' ||
                $has_groups !== false) {
                echo "    ❌ Configuration values don't match expected\n";
                echo "      Expected: Test Enterprise, {$this->test_enterprise_code}, Test Enterprise, false\n";
                echo "      Got: $enterprise_name, $enterprise_code, $display_name, " . ($has_groups ? 'true' : 'false') . "\n";
                return false;
            }
            
            // Test Google Sheets configuration
            $registrants_workbook = UnifiedEnterpriseConfig::getRegistrantsWorkbookId();
            $submissions_workbook = UnifiedEnterpriseConfig::getSubmissionsWorkbookId();
            
            if ($registrants_workbook !== '15BHjtrDXq1k1nLBOxAQcmc_Y3sgXvS6WwZMqaHyARBk' ||
                $submissions_workbook !== '1FqCZWsIohWoeTV7q22o60Y3IGK_lOd1Lc3l4EQ5Ufks') {
                echo "    ❌ Google Sheets configuration doesn't match expected\n";
                echo "      Expected: 15BHjtrDXq1k1nLBOxAQcmc_Y3sgXvS6WwZMqaHyARBk, 1FqCZWsIohWoeTV7q22o60Y3IGK_lOd1Lc3l4EQ5Ufks\n";
                echo "      Got: $registrants_workbook, $submissions_workbook\n";
                return false;
            }
            
            // Test settings configuration
            $start_date = UnifiedEnterpriseConfig::getStartDate();
            $timezone = UnifiedEnterpriseConfig::getTimezone();
            
            if ($start_date !== '05-06-24' || $timezone !== 'America/Los_Angeles') {
                echo "    ❌ Settings configuration doesn't match expected\n";
                echo "      Expected: 05-06-24, America/Los_Angeles\n";
                echo "      Got: $start_date, $timezone\n";
                return false;
            }
            
            echo "    ✅ Configuration loading passed\n";
            return true;
            
        } catch (Exception $e) {
            echo "    ❌ Configuration loading failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test 5: Data Format Validation
     * Tests that all data formats are correct
     */
    public function testDataFormatValidation() {
        echo "  Testing data format validation...\n";
        
        // Test date format validation
        $valid_dates = ['05-06-24', '12-31-25', '01-01-26'];
        $invalid_dates = ['2024-05-06', '05/06/24', '5-6-24', '05-06-2024'];
        
        foreach ($valid_dates as $date) {
            if (!preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/', $date)) {
                echo "    ❌ Valid date rejected: $date\n";
                return false;
            }
        }
        
        foreach ($invalid_dates as $date) {
            if (preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{2}$/', $date)) {
                echo "    ❌ Invalid date accepted: $date\n";
                return false;
            }
        }
        
        // Test enterprise code format validation
        $valid_codes = ['csu', 'ccc', 'test123'];
        $invalid_codes = ['CSU', 'test-enterprise', 'a', 'toolongenterprisecode'];
        
        foreach ($valid_codes as $code) {
            if (!preg_match('/^[a-z0-9]{2,10}$/', $code)) {
                echo "    ❌ Valid enterprise code rejected: $code\n";
                return false;
            }
        }
        
        foreach ($invalid_codes as $code) {
            if (preg_match('/^[a-z0-9]{2,10}$/', $code)) {
                echo "    ❌ Invalid enterprise code accepted: $code\n";
                return false;
            }
        }
        
        // Test admin password format validation
        $valid_passwords = ['1234', '0000', '9999', '5678'];
        $invalid_passwords = ['123', '12345', 'abcd', '12ab'];
        
        foreach ($valid_passwords as $password) {
            if (!preg_match('/^[0-9]{4}$/', $password)) {
                echo "    ❌ Valid password rejected: $password\n";
                return false;
            }
        }
        
        foreach ($invalid_passwords as $password) {
            if (preg_match('/^[0-9]{4}$/', $password)) {
                echo "    ❌ Invalid password accepted: $password\n";
                return false;
            }
        }
        
        echo "    ✅ Data format validation passed\n";
        return true;
    }
    
    /**
     * Generate enterprise configuration from form data
     */
    private function generateEnterpriseConfig($formData) {
        // Parse organizations
        $organizations = array_filter(array_map('trim', explode("\n", $formData['organizations_data'])));
        
        // Generate auto-generated passwords for organizations
        $existing_passwords = ['1234']; // Include admin password
        $organization_passwords = [];
        
        foreach ($organizations as $org) {
            $password = $this->generateUniquePassword($existing_passwords);
            $organization_passwords[$org] = $password;
            $existing_passwords[] = $password;
        }
        
        // Build configuration structure
        $config = [
            'enterprise' => [
                'name' => $formData['enterprise_name'],
                'code' => $formData['enterprise_code'],
                'display_name' => $formData['display_name'],
                'has_groups' => $formData['has_groups'] === 'true'
            ],
            'organizations' => $organizations,
            'google_sheets' => [
                '_comment' => 'Google Sheets uses 1-based column indexing (A=1, B=2, etc.). Array indices are 0-based.',
                '_column_mapping' => 'Google Sheets Column A=1 → Array Index 0, Column B=2 → Array Index 1, etc.',
                'registrants' => [
                    'workbook_id' => $formData['registrants_workbook_id'],
                    'sheet_name' => 'Registrants',
                    'start_row' => 2,
                    '_comment' => 'Column mapping for registrants data. Key dates: Invited (B/1), Enrolled (C/2), Certificate (K/10), Issued (L/11), Submitted (P/15)',
                    'columns' => [
                        'DaysToClose' => ['index' => 0, 'type' => 'string', '_sheets_column' => 'A'],
                        'Invited' => ['index' => 1, 'type' => 'string', '_sheets_column' => 'B', '_description' => 'Registration date (MM-DD-YY format)'],
                        'Enrolled' => ['index' => 2, 'type' => 'string', '_sheets_column' => 'C', '_description' => 'Enrollment status (\'Yes\' or \'-\')'],
                        'Cohort' => ['index' => 3, 'type' => 'string', '_sheets_column' => 'D'],
                        'Year' => ['index' => 4, 'type' => 'string', '_sheets_column' => 'E'],
                        'First' => ['index' => 5, 'type' => 'string', '_sheets_column' => 'F'],
                        'Last' => ['index' => 6, 'type' => 'string', '_sheets_column' => 'G'],
                        'Email' => ['index' => 7, 'type' => 'string', '_sheets_column' => 'H'],
                        'Role' => ['index' => 8, 'type' => 'string', '_sheets_column' => 'I'],
                        'Organization' => ['index' => 9, 'type' => 'string', '_sheets_column' => 'J', '_description' => 'Organization name'],
                        'Certificate' => ['index' => 10, 'type' => 'string', '_sheets_column' => 'K', '_description' => 'Certificate status (\'Yes\' or \'-\')'],
                        'Issued' => ['index' => 11, 'type' => 'string', '_sheets_column' => 'L', '_description' => 'Certificate issued date (MM-DD-YY format)'],
                        'ClosingDate' => ['index' => 12, 'type' => 'string', '_sheets_column' => 'M'],
                        'Completed' => ['index' => 13, 'type' => 'string', '_sheets_column' => 'N'],
                        'ID' => ['index' => 14, 'type' => 'string', '_sheets_column' => 'O'],
                        'Submitted' => ['index' => 15, 'type' => 'string', '_sheets_column' => 'P', '_description' => 'Submission date (MM-DD-YY format)'],
                        'Status' => ['index' => 16, 'type' => 'string', '_sheets_column' => 'Q']
                    ]
                ],
                'submissions' => [
                    'workbook_id' => $formData['submissions_workbook_id'],
                    'sheet_name' => 'Sheet1',
                    'start_row' => 2,
                    '_comment' => 'Column mapping for submissions data. Key dates: Token (B/1), Submitted (P/15)',
                    'columns' => [
                        'DaysToClose' => ['index' => 0, 'type' => 'string', '_sheets_column' => 'A'],
                        'Token' => ['index' => 1, 'type' => 'string', '_sheets_column' => 'B', '_description' => 'Submission date (MM-DD-YY format)'],
                        'Enrolled' => ['index' => 2, 'type' => 'string', '_sheets_column' => 'C'],
                        'Cohort' => ['index' => 3, 'type' => 'string', '_sheets_column' => 'D'],
                        'Year' => ['index' => 4, 'type' => 'string', '_sheets_column' => 'E'],
                        'First' => ['index' => 5, 'type' => 'string', '_sheets_column' => 'F'],
                        'Last' => ['index' => 6, 'type' => 'string', '_sheets_column' => 'G'],
                        'Email' => ['index' => 7, 'type' => 'string', '_sheets_column' => 'H'],
                        'Role' => ['index' => 8, 'type' => 'string', '_sheets_column' => 'I'],
                        'Organization' => ['index' => 9, 'type' => 'string', '_sheets_column' => 'J'],
                        'Certificate' => ['index' => 10, 'type' => 'string', '_sheets_column' => 'K'],
                        'Issued' => ['index' => 11, 'type' => 'string', '_sheets_column' => 'L'],
                        'ClosingDate' => ['index' => 12, 'type' => 'string', '_sheets_column' => 'M'],
                        'Completed' => ['index' => 13, 'type' => 'string', '_sheets_column' => 'N'],
                        'ID' => ['index' => 14, 'type' => 'string', '_sheets_column' => 'O'],
                        'Submitted' => ['index' => 15, 'type' => 'string', '_sheets_column' => 'P'],
                        'Status' => ['index' => 16, 'type' => 'string', '_sheets_column' => 'Q']
                    ]
                ]
            ],
            'settings' => [
                'start_date' => $formData['start_date'],
                'cache_ttl' => 21600,
                'timezone' => $formData['timezone'],
                'date_format' => 'm-d-y',
                'time_format' => 'g:i A'
            ],
            'api' => [
                'google_api_key' => $formData['google_api_key']
            ],
            'metadata' => [
                'version' => '1.0',
                'last_updated' => date('Y-m-d'),
                'generated_by' => 'unified_system',
                'enterprise' => $formData['enterprise_code'],
                'total_organizations' => count($organizations)
            ]
        ];
        
        return $config;
    }
    
    /**
     * Generate unique password for organizations
     */
    private function generateUniquePassword($existingPasswords) {
        do {
            $password = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (in_array($password, $existingPasswords));
        return $password;
    }
    
    /**
     * Cleanup test files
     */
    private function cleanup() {
        if (file_exists($this->test_config_file)) {
            unlink($this->test_config_file);
        }
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $test = new EnterpriseConfigValidationTest();
    $success = $test->runAllTests();
    exit($success ? 0 : 1);
}
?> 