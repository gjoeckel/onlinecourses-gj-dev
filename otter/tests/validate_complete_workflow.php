<?php
/**
 * Complete Workflow Validation Test
 * 
 * This test validates the complete workflow using builder-test.php and passwords-tests.json
 * Tests: Check → Delete → Add → Validate process
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_database.php';

class CompleteWorkflowValidator {
    private $config_dir;
    private $test_enterprise_code = 'test-enterprise';
    
    public function __construct() {
        $this->config_dir = __DIR__ . '/../config';
    }
    
    /**
     * Test the complete workflow
     */
    public function runCompleteTest() {
        echo "=== Complete Workflow Validation Test ===\n";
        echo "Test started at: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Step 1: Check initial state
        echo "STEP 1: Checking initial state of passwords-tests.json\n";
        $initial_state = $this->checkInitialState();
        if (!$initial_state['success']) {
            echo "❌ FAILED: " . $initial_state['message'] . "\n";
            return false;
        }
        echo "✅ Initial state verified\n";
        
        // Step 2: Test enterprise code check (should not exist)
        echo "\nSTEP 2: Testing enterprise code check (should not exist)\n";
        $check_result = $this->testEnterpriseCodeCheck();
        if (!$check_result['success']) {
            echo "❌ FAILED: " . $check_result['message'] . "\n";
            return false;
        }
        echo "✅ Enterprise code check passed\n";
        
        // Step 3: Test adding enterprise data
        echo "\nSTEP 3: Testing add enterprise data\n";
        $add_result = $this->testAddEnterpriseData();
        if (!$add_result['success']) {
            echo "❌ FAILED: " . $add_result['message'] . "\n";
            return false;
        }
        echo "✅ Add enterprise data passed\n";
        
        // Step 4: Test enterprise code check (should now exist)
        echo "\nSTEP 4: Testing enterprise code check (should now exist)\n";
        $check_result2 = $this->testEnterpriseCodeCheckExists();
        if (!$check_result2['success']) {
            echo "❌ FAILED: " . $check_result2['message'] . "\n";
            return false;
        }
        echo "✅ Enterprise code check (exists) passed\n";
        
        // Step 5: Test delete enterprise data
        echo "\nSTEP 5: Testing delete enterprise data\n";
        $delete_result = $this->testDeleteEnterpriseData();
        if (!$delete_result['success']) {
            echo "❌ FAILED: " . $delete_result['message'] . "\n";
            return false;
        }
        echo "✅ Delete enterprise data passed\n";
        
        // Step 6: Verify final state
        echo "\nSTEP 6: Verifying final state\n";
        $final_state = $this->verifyFinalState();
        if (!$final_state['success']) {
            echo "❌ FAILED: " . $final_state['message'] . "\n";
            return false;
        }
        echo "✅ Final state verified\n";
        
        echo "\n=== All Tests Passed! ===\n";
        echo "✅ Complete workflow validation successful\n";
        echo "✅ Ready to apply to production (enterprise-builder.php and passwords.json)\n";
        
        return true;
    }
    
    /**
     * Check initial state of passwords-tests.json
     */
    private function checkInitialState() {
        $passwords_file = $this->config_dir . '/passwords-tests.json';
        
        if (!file_exists($passwords_file)) {
            return ['success' => false, 'message' => 'passwords-tests.json not found'];
        }
        
        $passwords_data = json_decode(file_get_contents($passwords_file), true);
        if (!$passwords_data) {
            return ['success' => false, 'message' => 'Invalid JSON in passwords-tests.json'];
        }
        
        // Check that test enterprise doesn't exist
        if (isset($passwords_data['admin_passwords'][$this->test_enterprise_code])) {
            return ['success' => false, 'message' => 'Test enterprise already exists in admin_passwords'];
        }
        
        foreach ($passwords_data['organizations'] as $org) {
            if (isset($org['enterprise']) && $org['enterprise'] === $this->test_enterprise_code) {
                return ['success' => false, 'message' => 'Test enterprise already exists in organizations'];
            }
        }
        
        if (isset($passwords_data['metadata']['enterprises']) && 
            in_array($this->test_enterprise_code, $passwords_data['metadata']['enterprises'])) {
            return ['success' => false, 'message' => 'Test enterprise already exists in metadata'];
        }
        
        return ['success' => true, 'message' => 'Initial state clean'];
    }
    
    /**
     * Test enterprise code check (should not exist)
     */
    private function testEnterpriseCodeCheck() {
        $postData = [
            'action' => 'check_enterprise_code',
            'enterprise_code' => $this->test_enterprise_code
        ];
        
        $response = $this->postToBuilder($postData);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success'])) {
            return ['success' => false, 'message' => 'Invalid response from check_enterprise_code'];
        }
        
        if (!$data['success']) {
            return ['success' => false, 'message' => 'check_enterprise_code failed: ' . $data['message']];
        }
        
        if ($data['exists'] !== false) {
            return ['success' => false, 'message' => 'Enterprise code should not exist but was found'];
        }
        
        return ['success' => true, 'message' => 'Enterprise code check passed'];
    }
    
    /**
     * Test adding enterprise data
     */
    private function testAddEnterpriseData() {
        $postData = [
            'action' => 'add_enterprise_data',
            'enterprise_code' => $this->test_enterprise_code,
            'enterprise_name' => 'Test Enterprise',
            'display_name' => 'Test Enterprise',
            'admin_password' => '1234',
            'has_groups' => 'false',
            'google_api_key' => 'AIzaSyTestKey',
            'registrants_workbook_id' => 'test_registrants_id',
            'submissions_workbook_id' => 'test_submissions_id',
            'organizations_data' => "Test Org 1\nTest Org 2\nTest Org 3",
            'start_date' => '06-30-25',
            'timezone' => 'America/Los_Angeles'
        ];
        
        $response = $this->postToBuilder($postData);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success'])) {
            return ['success' => false, 'message' => 'Invalid response from add_enterprise_data'];
        }
        
        if (!$data['success']) {
            return ['success' => false, 'message' => 'add_enterprise_data failed: ' . $data['message']];
        }
        
        return ['success' => true, 'message' => 'Add enterprise data passed'];
    }
    
    /**
     * Test enterprise code check (should now exist)
     */
    private function testEnterpriseCodeCheckExists() {
        $postData = [
            'action' => 'check_enterprise_code',
            'enterprise_code' => $this->test_enterprise_code
        ];
        
        $response = $this->postToBuilder($postData);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success'])) {
            return ['success' => false, 'message' => 'Invalid response from check_enterprise_code'];
        }
        
        if (!$data['success']) {
            return ['success' => false, 'message' => 'check_enterprise_code failed: ' . $data['message']];
        }
        
        if ($data['exists'] !== true) {
            return ['success' => false, 'message' => 'Enterprise code should exist but was not found'];
        }
        
        return ['success' => true, 'message' => 'Enterprise code check (exists) passed'];
    }
    
    /**
     * Test delete enterprise data
     */
    private function testDeleteEnterpriseData() {
        $postData = [
            'action' => 'delete_enterprise_passwords',
            'enterprise_code' => $this->test_enterprise_code
        ];
        
        $response = $this->postToBuilder($postData);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['success'])) {
            return ['success' => false, 'message' => 'Invalid response from delete_enterprise_passwords'];
        }
        
        if (!$data['success']) {
            return ['success' => false, 'message' => 'delete_enterprise_passwords failed: ' . $data['message']];
        }
        
        return ['success' => true, 'message' => 'Delete enterprise data passed'];
    }
    
    /**
     * Verify final state
     */
    private function verifyFinalState() {
        $passwords_file = $this->config_dir . '/passwords-tests.json';
        
        $passwords_data = json_decode(file_get_contents($passwords_file), true);
        if (!$passwords_data) {
            return ['success' => false, 'message' => 'Invalid JSON in passwords-tests.json after test'];
        }
        
        // Check that test enterprise doesn't exist anymore
        if (isset($passwords_data['admin_passwords'][$this->test_enterprise_code])) {
            return ['success' => false, 'message' => 'Test enterprise still exists in admin_passwords after deletion'];
        }
        
        foreach ($passwords_data['organizations'] as $org) {
            if (isset($org['enterprise']) && $org['enterprise'] === $this->test_enterprise_code) {
                return ['success' => false, 'message' => 'Test enterprise still exists in organizations after deletion'];
            }
        }
        
        if (isset($passwords_data['metadata']['enterprises']) && 
            in_array($this->test_enterprise_code, $passwords_data['metadata']['enterprises'])) {
            return ['success' => false, 'message' => 'Test enterprise still exists in metadata after deletion'];
        }
        
        return ['success' => true, 'message' => 'Final state clean'];
    }
    
    /**
     * Post data to builder-test.php
     */
    private function postToBuilder($postData) {
        $url = 'http://localhost:8000/builder-test.php';
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\nX-Requested-With: XMLHttpRequest\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        return $result;
    }
}

// Run the test
$validator = new CompleteWorkflowValidator();
$success = $validator->runCompleteTest();

if ($success) {
    echo "\n🎉 Complete workflow validation successful!\n";
    echo "✅ Ready to apply the same process to production (enterprise-builder.php and passwords.json)\n";
    exit(0);
} else {
    echo "\n💥 Complete workflow validation failed!\n";
    echo "❌ Do not apply to production until issues are resolved\n";
    exit(1);
} 