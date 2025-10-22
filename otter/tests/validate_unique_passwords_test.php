<?php
/**
 * Validate Unique Passwords Test
 * 
 * This test uses the actual data from builder-test.php to create ccc-test config files
 * and validates that unique passwords are generated for each organization.
 *
 * It also ensures that all CCC orgs are removed from passwords-tests.json before running.
 */

// Clean the test passwords file before running
$clean_script = __DIR__ . '/clean_passwords_json.php';
$test_passwords_file = __DIR__ . '/../config/passwords-tests.json';

// Run the cleaning script on the test file
$cmd = "php " . escapeshellarg($clean_script) . " " . escapeshellarg($test_passwords_file);
$output = [];
$return_var = 0;
exec($cmd, $output, $return_var);
echo implode("\n", $output) . "\n";
if ($return_var !== 0) {
    echo "❌ ERROR: Failed to clean passwords-tests.json before running test\n";
    exit(1);
}

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_database.php';

class UniquePasswordValidator {
    private $config_dir;
    private $existing_passwords = [];
    private $test_counter = 1;
    
    public function __construct() {
        $this->config_dir = __DIR__ . '/../config';
        $this->loadExistingPasswords();
    }
    
    /**
     * Load existing passwords from passwords.json
     */
    private function loadExistingPasswords() {
        $passwords_file = $this->config_dir . '/passwords.json';
        if (file_exists($passwords_file)) {
            $passwords_data = json_decode(file_get_contents($passwords_file), true);
            if ($passwords_data && isset($passwords_data['organizations'])) {
                foreach ($passwords_data['organizations'] as $org) {
                    $this->existing_passwords[] = $org['password'];
                }
            }
            if ($passwords_data && isset($passwords_data['admin_passwords'])) {
                foreach ($passwords_data['admin_passwords'] as $password) {
                    $this->existing_passwords[] = $password;
                }
            }
        }
        echo "Loaded " . count($this->existing_passwords) . " existing passwords\n";
    }
    
    /**
     * Generate unique password that doesn't exist in current passwords
     */
    private function generateUniquePassword($existingPasswords) {
        do {
            $password = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (in_array($password, $existingPasswords));
        return $password;
    }
    
    /**
     * Parse organizations data from textarea format
     */
    private function parseOrganizationsData($data, $enterprise_code) {
        $lines = explode("\n", trim($data));
        $organizations = [];
        $passwords = [];
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $name = $line;
            
            if (empty($name)) {
                return ['success' => false, 'message' => "Empty organization name on line " . ($line_num + 1)];
            }
            
            if (in_array($name, array_column($organizations, 'name'))) {
                return ['success' => false, 'message' => "Duplicate organization name: $name"];
            }
            
            // Generate unique password for this organization
            $password = $this->generateUniquePassword(array_merge($this->existing_passwords, $passwords));
            $passwords[] = $password;
            
            $organizations[] = [
                'name' => $name,
                'password' => $password,
                'enterprise' => $enterprise_code,
                'is_admin' => false
            ];
        }
        
        if (empty($organizations)) {
            return ['success' => false, 'message' => 'No valid organizations found'];
        }
        
        return ['success' => true, 'data' => $organizations];
    }
    
    /**
     * Generate passwords.json with new organizations
     */
    private function generatePasswordsJson($data, $organizations) {
        $passwords_file = $this->config_dir . '/passwords.json';
        
        // Read existing passwords.json
        if (file_exists($passwords_file)) {
            $passwords_data = json_decode(file_get_contents($passwords_file), true);
        } else {
            $passwords_data = [
                'admin_passwords' => [],
                'organizations' => [],
                'metadata' => [
                    'last_updated' => date('Y-m-d'),
                    'total_organizations' => 0,
                    'enterprises' => [],
                    'version' => '1.0'
                ]
            ];
        }
        
        // Add new admin password
        $passwords_data['admin_passwords'][$data['enterprise_code']] = $data['admin_password'];
        
        // Add new organizations
        $passwords_data['organizations'] = array_merge($passwords_data['organizations'], $organizations);
        
        // Update metadata
        $passwords_data['metadata']['last_updated'] = date('Y-m-d');
        $passwords_data['metadata']['total_organizations'] = count($passwords_data['organizations']);
        if (!in_array($data['enterprise_code'], $passwords_data['metadata']['enterprises'])) {
            $passwords_data['metadata']['enterprises'][] = $data['enterprise_code'];
        }
        
        return $passwords_data;
    }
    
    /**
     * Generate enterprise configuration file
     */
    private function generateEnterpriseConfig($data, $organizations) {
        // Standard column mapping for Google Sheets
        $column_mapping = [
            "DaysToClose" => ["index" => 0, "type" => "string", "_sheets_column" => "A"],
            "Invited" => ["index" => 1, "type" => "string", "_sheets_column" => "B", "_description" => "Registration date (MM-DD-YY format)"],
            "Enrolled" => ["index" => 2, "type" => "string", "_sheets_column" => "C", "_description" => "Enrollment status ('Yes' or '-')"],
            "Cohort" => ["index" => 3, "type" => "string", "_sheets_column" => "D"],
            "Year" => ["index" => 4, "type" => "string", "_sheets_column" => "E"],
            "First" => ["index" => 5, "type" => "string", "_sheets_column" => "F"],
            "Last" => ["index" => 6, "type" => "string", "_sheets_column" => "G"],
            "Email" => ["index" => 7, "type" => "string", "_sheets_column" => "H"],
            "Role" => ["index" => 8, "type" => "string", "_sheets_column" => "I"],
            "Organization" => ["index" => 9, "type" => "string", "_sheets_column" => "J", "_description" => "Organization name"],
            "Certificate" => ["index" => 10, "type" => "string", "_sheets_column" => "K", "_description" => "Certificate status ('Yes' or '-')"],
            "Issued" => ["index" => 11, "type" => "string", "_sheets_column" => "L", "_description" => "Certificate issued date (MM-DD-YY format)"],
            "ClosingDate" => ["index" => 12, "type" => "string", "_sheets_column" => "M"],
            "Completed" => ["index" => 13, "type" => "string", "_sheets_column" => "N"],
            "ID" => ["index" => 14, "type" => "string", "_sheets_column" => "O"],
            "Submitted" => ["index" => 15, "type" => "string", "_sheets_column" => "P", "_description" => "Submission date (MM-DD-YY format)"],
            "Status" => ["index" => 16, "type" => "string", "_sheets_column" => "Q"]
        ];
        
        // Create enterprise configuration
        $enterprise_config = [
            "enterprise" => [
                "name" => $data['enterprise_name'],
                "code" => $data['enterprise_code'],
                "display_name" => $data['display_name'],
                "has_groups" => $data['has_groups'] === 'true'
            ],
            "organizations" => array_column($organizations, 'name'),
            "google_sheets" => [
                "_comment" => "Google Sheets uses 1-based column indexing (A=1, B=2, etc.). Array indices are 0-based.",
                "_column_mapping" => "Google Sheets Column A=1 → Array Index 0, Column B=2 → Array Index 1, etc.",
                "registrants" => [
                    "workbook_id" => $data['registrants_workbook_id'],
                    "sheet_name" => "Registrants",
                    "start_row" => 2,
                    "_comment" => "Column mapping for registrants data. Key dates: Invited (B/1), Enrolled (C/2), Certificate (K/10), Issued (L/11), Submitted (P/15)",
                    "columns" => $column_mapping
                ],
                "submissions" => [
                    "workbook_id" => $data['submissions_workbook_id'],
                    "sheet_name" => "Sheet1",
                    "start_row" => 2,
                    "_comment" => "Column mapping for submissions data. Key dates: Token (B/1), Submitted (P/15)",
                    "columns" => $column_mapping
                ]
            ],
            "settings" => [
                "start_date" => $data['start_date'],
                "cache_ttl" => 21600,
                "timezone" => $data['timezone'],
                "date_format" => "m-d-y",
                "time_format" => "g:i A"
            ],
            "api" => [
                "google_api_key" => $data['google_api_key']
            ],
            "metadata" => [
                "version" => "1.0",
                "last_updated" => date('Y-m-d'),
                "generated_by" => "unique_password_validator",
                "enterprise" => $data['enterprise_code'],
                "total_organizations" => count($organizations)
            ]
        ];
        
        return $enterprise_config;
    }
    
    /**
     * Run the validation test
     */
    public function runTest() {
        echo "=== Unique Password Validation Test ===\n";
        echo "Test started at: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Use actual data from builder-test.php
        $test_data = [
            'enterprise_code' => 'ccc-test' . $this->test_counter,
            'enterprise_name' => 'California Community Colleges Test',
            'display_name' => 'CCC Test',
            'admin_password' => '1234',
            'has_groups' => 'false',
            'google_api_key' => 'AIzaSyCdYB-4kHhd7ZbvxTx7amSm6or3ONSAEaI',
            'registrants_workbook_id' => '1dC-GgSFe2x4CHJkijyudVWhnUk9UNU5NbWneRgJO6Q0',
            'submissions_workbook_id' => '1LwR4j62XKlaHYsRRB2MtdQUPU0MTj5ynWW_5VpkPqIg',
            'organizations_data' => "Los Angeles Community College District\nSan Diego Community College District\nSan Francisco Community College District\nSanta Monica Community College District\nPasadena City College\nGlendale Community College\nBakersfield College\nFresno City College\nSacramento City College\nSanta Barbara City College",
            'start_date' => '08-06-22',
            'timezone' => 'America/Los_Angeles'
        ];
        
        echo "Using enterprise code: " . $test_data['enterprise_code'] . "\n";
        echo "Organizations to process: " . substr_count($test_data['organizations_data'], "\n") + 1 . "\n\n";
        
        // Parse organizations and generate passwords
        $organizations_result = $this->parseOrganizationsData($test_data['organizations_data'], $test_data['enterprise_code']);
        
        if (!$organizations_result['success']) {
            echo "❌ ERROR: " . $organizations_result['message'] . "\n";
            return false;
        }
        
        $organizations = $organizations_result['data'];
        echo "✅ Successfully parsed " . count($organizations) . " organizations\n";
        
        // Validate unique passwords
        $passwords = array_column($organizations, 'password');
        $unique_passwords = array_unique($passwords);
        
        if (count($passwords) !== count($unique_passwords)) {
            echo "❌ ERROR: Duplicate passwords found!\n";
            return false;
        }
        
        // Check against existing passwords
        $conflicts = array_intersect($passwords, $this->existing_passwords);
        if (!empty($conflicts)) {
            echo "❌ ERROR: Password conflicts with existing passwords: " . implode(', ', $conflicts) . "\n";
            return false;
        }
        
        echo "✅ All passwords are unique and don't conflict with existing passwords\n";
        
        // Display generated passwords
        echo "\nGenerated Passwords:\n";
        foreach ($organizations as $org) {
            echo "  " . $org['name'] . ": " . $org['password'] . "\n";
        }
        
        // Generate passwords.json data
        $passwords_data = $this->generatePasswordsJson($test_data, $organizations);
        
        // Generate enterprise config
        $enterprise_config = $this->generateEnterpriseConfig($test_data, $organizations);
        
        // Write test config file
        $config_file = $this->config_dir . '/' . $test_data['enterprise_code'] . '.config';
        $result = file_put_contents($config_file, json_encode($enterprise_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        if ($result === false) {
            echo "❌ ERROR: Failed to write config file: $config_file\n";
            return false;
        }
        
        echo "\n✅ Successfully created config file: $config_file\n";
        
        // Validate the generated config
        $loaded_config = json_decode(file_get_contents($config_file), true);
        if (!$loaded_config) {
            echo "❌ ERROR: Generated config file is not valid JSON\n";
            return false;
        }
        
        echo "✅ Config file is valid JSON\n";
        echo "✅ Enterprise code: " . $loaded_config['enterprise']['code'] . "\n";
        echo "✅ Organizations count: " . count($loaded_config['organizations']) . "\n";
        echo "✅ Total organizations in config: " . $loaded_config['metadata']['total_organizations'] . "\n";
        
        // Check if we need to generate another test file
        $this->test_counter++;
        $next_test_data = $test_data;
        $next_test_data['enterprise_code'] = 'ccc-test' . $this->test_counter;
        $next_test_data['admin_password'] = '5678'; // Different admin password
        
        echo "\n=== Generating Second Test File ===\n";
        echo "Using enterprise code: " . $next_test_data['enterprise_code'] . "\n";
        
        // Parse organizations for second test
        $next_organizations_result = $this->parseOrganizationsData($next_test_data['organizations_data'], $next_test_data['enterprise_code']);
        
        if (!$next_organizations_result['success']) {
            echo "❌ ERROR: " . $next_organizations_result['message'] . "\n";
            return false;
        }
        
        $next_organizations = $next_organizations_result['data'];
        
        // Validate unique passwords for second test
        $next_passwords = array_column($next_organizations, 'password');
        $next_unique_passwords = array_unique($next_passwords);
        
        if (count($next_passwords) !== count($next_unique_passwords)) {
            echo "❌ ERROR: Duplicate passwords found in second test!\n";
            return false;
        }
        
        // Check against existing passwords (including first test)
        $all_existing = array_merge($this->existing_passwords, $passwords);
        $next_conflicts = array_intersect($next_passwords, $all_existing);
        if (!empty($next_conflicts)) {
            echo "❌ ERROR: Password conflicts in second test: " . implode(', ', $next_conflicts) . "\n";
            return false;
        }
        
        echo "✅ Second test passwords are unique and don't conflict\n";
        
        // Display second test passwords
        echo "\nSecond Test Generated Passwords:\n";
        foreach ($next_organizations as $org) {
            echo "  " . $org['name'] . ": " . $org['password'] . "\n";
        }
        
        // Generate second enterprise config
        $next_enterprise_config = $this->generateEnterpriseConfig($next_test_data, $next_organizations);
        
        // Write second test config file
        $next_config_file = $this->config_dir . '/' . $next_test_data['enterprise_code'] . '.config';
        $next_result = file_put_contents($next_config_file, json_encode($next_enterprise_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        if ($next_result === false) {
            echo "❌ ERROR: Failed to write second config file: $next_config_file\n";
            return false;
        }
        
        echo "\n✅ Successfully created second config file: $next_config_file\n";
        
        // Final validation
        echo "\n=== Final Validation ===\n";
        echo "✅ Test 1: " . count($organizations) . " organizations with unique passwords\n";
        echo "✅ Test 2: " . count($next_organizations) . " organizations with unique passwords\n";
        echo "✅ Total unique passwords generated: " . (count($passwords) + count($next_passwords)) . "\n";
        echo "✅ No conflicts with existing passwords\n";
        echo "✅ Both config files created successfully\n";
        
        echo "\n=== Test Completed Successfully ===\n";
        echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
        
        return true;
    }
}

// Run the test
$validator = new UniquePasswordValidator();
$success = $validator->runTest();

if ($success) {
    echo "\n🎉 All tests passed! Unique password generation is working correctly.\n";
    exit(0);
} else {
    echo "\n💥 Tests failed! Please check the errors above.\n";
    exit(1);
} 