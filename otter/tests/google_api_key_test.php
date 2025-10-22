<?php
/**
 * Test Google API Key Configuration
 * 
 * This test verifies that the Google API key is properly configured
 * and accessible through the unified configuration system.
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';

class GoogleApiKeyTest {
    private $results = [];
    
    public function __construct() {
        echo "Google API Key Configuration Test\n";
        echo "=================================\n\n";
    }
    
    private function addResult($test, $passed, $message = '', $details = '') {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message,
            'details' => $details
        ];
        
        $status = $passed ? 'PASS' : 'FAIL';
        echo "[{$status}] {$test}\n";
        if ($message) {
            echo "    {$message}\n";
        }
        if ($details) {
            echo "    Details: {$details}\n";
        }
        echo "\n";
    }
    
    /**
     * Test that the unified configuration system is working
     */
    public function testUnifiedConfiguration() {
        echo "Testing Unified Configuration System...\n";
        echo "----------------------------------------\n";
        
        try {
            // Initialize the unified configuration
            UnifiedEnterpriseConfig::initializeFromRequest();
            
            $this->addResult(
                'Unified Configuration Initialization',
                true,
                'Unified configuration system initialized successfully'
            );
            
        } catch (Exception $e) {
            $this->addResult(
                'Unified Configuration Initialization',
                false,
                'Failed to initialize unified configuration',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Test that the Google API key is accessible
     */
    public function testGoogleApiKeyAccess() {
        echo "Testing Google API Key Access...\n";
        echo "-------------------------------\n";
        
        try {
            $apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
            
            if (empty($apiKey)) {
                $this->addResult(
                    'Google API Key Retrieval',
                    false,
                    'Google API key is empty or not configured'
                );
            } else {
                $this->addResult(
                    'Google API Key Retrieval',
                    true,
                    'Google API key retrieved successfully',
                    'Key length: ' . strlen($apiKey) . ' characters'
                );
            }
            
        } catch (Exception $e) {
            $this->addResult(
                'Google API Key Retrieval',
                false,
                'Failed to retrieve Google API key',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Test that the Google Sheets configuration is accessible
     */
    public function testGoogleSheetsConfiguration() {
        echo "Testing Google Sheets Configuration...\n";
        echo "--------------------------------------\n";
        
        try {
            $registrantsConfig = UnifiedEnterpriseConfig::getSheetConfig('registrants');
            $submissionsConfig = UnifiedEnterpriseConfig::getSheetConfig('submissions');
            
            if (!$registrantsConfig) {
                $this->addResult(
                    'Registrants Sheet Configuration',
                    false,
                    'Registrants sheet configuration not found'
                );
            } else {
                $this->addResult(
                    'Registrants Sheet Configuration',
                    true,
                    'Registrants sheet configuration loaded successfully',
                    'Workbook ID: ' . ($registrantsConfig['workbook_id'] ?? 'N/A')
                );
            }
            
            if (!$submissionsConfig) {
                $this->addResult(
                    'Submissions Sheet Configuration',
                    false,
                    'Submissions sheet configuration not found'
                );
            } else {
                $this->addResult(
                    'Submissions Sheet Configuration',
                    true,
                    'Submissions sheet configuration loaded successfully',
                    'Workbook ID: ' . ($submissionsConfig['workbook_id'] ?? 'N/A')
                );
            }
            
        } catch (Exception $e) {
            $this->addResult(
                'Google Sheets Configuration',
                false,
                'Failed to load Google Sheets configuration',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Test that the enterprise configuration is complete
     */
    public function testEnterpriseConfiguration() {
        echo "Testing Enterprise Configuration...\n";
        echo "-----------------------------------\n";
        
        try {
            $enterprise = UnifiedEnterpriseConfig::getEnterprise();
            $startDate = UnifiedEnterpriseConfig::getStartDate();
            $hasGroups = UnifiedEnterpriseConfig::getHasGroups();
            $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
            
            if (!$enterprise) {
                $this->addResult(
                    'Enterprise Configuration',
                    false,
                    'Enterprise configuration not found'
                );
            } else {
                $this->addResult(
                    'Enterprise Configuration',
                    true,
                    'Enterprise configuration loaded successfully',
                    'Name: ' . ($enterprise['name'] ?? 'N/A') . ', Code: ' . $enterpriseCode
                );
            }
            
            if (empty($startDate)) {
                $this->addResult(
                    'Start Date Configuration',
                    false,
                    'Start date not configured'
                );
            } else {
                $this->addResult(
                    'Start Date Configuration',
                    true,
                    'Start date configured successfully',
                    'Start date: ' . $startDate
                );
            }
            
            $this->addResult(
                'Has Groups Configuration',
                true,
                'Has groups configuration loaded successfully',
                'Has groups: ' . ($hasGroups ? 'Yes' : 'No')
            );
            
        } catch (Exception $e) {
            $this->addResult(
                'Enterprise Configuration',
                false,
                'Failed to load enterprise configuration',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Test the fetch_sheet_data function (simulated)
     */
    public function testFetchSheetDataFunction() {
        echo "Testing Fetch Sheet Data Function...\n";
        echo "------------------------------------\n";
        
        try {
            // Test that the function can be called with the unified configuration
            $apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
            $registrantsConfig = UnifiedEnterpriseConfig::getSheetConfig('registrants');
            
            if (empty($apiKey)) {
                $this->addResult(
                    'Fetch Sheet Data Function',
                    false,
                    'Cannot test fetch function - API key not configured'
                );
            } elseif (!$registrantsConfig) {
                $this->addResult(
                    'Fetch Sheet Data Function',
                    false,
                    'Cannot test fetch function - registrants config not found'
                );
            } else {
                $this->addResult(
                    'Fetch Sheet Data Function',
                    true,
                    'Fetch function can be called with unified configuration',
                    'API key available and config loaded'
                );
            }
            
        } catch (Exception $e) {
            $this->addResult(
                'Fetch Sheet Data Function',
                false,
                'Failed to test fetch function',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        $this->testUnifiedConfiguration();
        $this->testGoogleApiKeyAccess();
        $this->testGoogleSheetsConfiguration();
        $this->testEnterpriseConfiguration();
        $this->testFetchSheetDataFunction();
        
        $this->generateReport();
    }
    
    /**
     * Generate test report
     */
    public function generateReport() {
        echo str_repeat("=", 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->results);
        $passedTests = 0;
        
        foreach ($this->results as $result) {
            if ($result['passed']) {
                $passedTests++;
            }
        }
        
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: " . ($totalTests - $passedTests) . "\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";
        
        if ($passedTests === $totalTests) {
            echo "✅ ALL TESTS PASSED! Google API key configuration is working correctly.\n";
        } else {
            echo "❌ SOME TESTS FAILED. Please review the issues above.\n";
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['run_tests'])) {
    $test = new GoogleApiKeyTest();
    $test->runAllTests();
} else {
    echo "Google API Key Configuration Test\n";
    echo "=================================\n\n";
    echo "To run tests:\n";
    echo "1. Via command line: php google_api_key_test.php\n";
    echo "2. Via web browser: google_api_key_test.php?run_tests=1\n\n";
    echo "This test verifies that the Google API key is properly configured\n";
    echo "and accessible through the unified configuration system.\n";
}
?> 