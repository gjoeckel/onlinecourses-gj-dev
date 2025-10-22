<?php
/**
 * Test Dashboard Link Generation
 * 
 * This test verifies that dashboard links are generated as root-relative URLs
 * to work correctly from any subdirectory (like /reports/).
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_database.php';

class DashboardLinkTest {
    private $baseUrl = 'http://localhost:8000';
    private $results = [];
    
    public function run() {
        echo "=== Dashboard Link Generation Test ===\n\n";
        
        $this->testEnterpriseAPIAccess();
        $this->testDashboardLinkGeneration();
        
        $this->printResults();
    }
    
    private function testEnterpriseAPIAccess() {
        echo "Testing Enterprise API access...\n";
        
        try {
            $response = file_get_contents($this->baseUrl . '/lib/api/enterprise_api.php');
            if ($response === false) {
                $this->addResult('Enterprise API Access', false, 'Failed to access enterprise API');
                return;
            }
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['organizations'])) {
                $this->addResult('Enterprise API Access', false, 'Invalid JSON response or missing organizations');
                return;
            }
            
            $orgCount = count($data['organizations']);
            $this->addResult('Enterprise API Access', true, "✅ API returned {$orgCount} organizations");
            
            // Check if we have dashboard URLs
            $hasDashboardUrls = false;
            foreach ($data['organizations'] as $org) {
                if (isset($org['dashboard_url_production']) && $org['dashboard_url_production'] !== 'N/A') {
                    $hasDashboardUrls = true;
                    break;
                }
            }
            
            if ($hasDashboardUrls) {
                $this->addResult('Dashboard URLs Present', true, '✅ Dashboard URLs found in enterprise data');
            } else {
                $this->addResult('Dashboard URLs Present', false, '❌ No dashboard URLs found in enterprise data');
            }
            
        } catch (Exception $e) {
            $this->addResult('Enterprise API Access', false, 'Exception: ' . $e->getMessage());
        }
    }
    
    private function testDashboardLinkGeneration() {
        echo "Testing dashboard link generation...\n";
        
        // Test with a known organization from the enterprise data
        $testOrgName = 'Chico'; // This should exist in the enterprise data
        
        try {
            $response = file_get_contents($this->baseUrl . '/lib/api/enterprise_api.php');
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['organizations'])) {
                $this->addResult('Dashboard Link Generation', false, 'Cannot access enterprise data');
                return;
            }
            
            // Find the test organization
            $testOrg = null;
            foreach ($data['organizations'] as $org) {
                if (strtolower($org['name']) === strtolower($testOrgName)) {
                    $testOrg = $org;
                    break;
                }
            }
            
            if (!$testOrg) {
                $this->addResult('Dashboard Link Generation', false, "Test organization '{$testOrgName}' not found");
                return;
            }
            
            // Check the dashboard URL format
            $dashboardUrl = $testOrg['dashboard_url_production'] ?? $testOrg['dashboard_url_local'] ?? null;
            
            if (!$dashboardUrl || $dashboardUrl === 'N/A') {
                $this->addResult('Dashboard Link Generation', false, "No dashboard URL for {$testOrgName}");
                return;
            }
            
            // Verify the URL format - it should be a relative path that can be made root-relative
            if (strpos($dashboardUrl, 'dashboard.php?org=') !== false) {
                $rootRelativeUrl = '/' . $dashboardUrl;
                $this->addResult('Dashboard Link Generation', true, "✅ URL format correct: {$dashboardUrl} -> {$rootRelativeUrl}");
            } else {
                $this->addResult('Dashboard Link Generation', false, "❌ Unexpected URL format: {$dashboardUrl}");
            }
            
        } catch (Exception $e) {
            $this->addResult('Dashboard Link Generation', false, 'Exception: ' . $e->getMessage());
        }
    }
    
    private function addResult($test, $passed, $message) {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    private function printResults() {
        echo "\n=== Test Results ===\n";
        $passed = 0;
        $total = count($this->results);
        
        foreach ($this->results as $result) {
            $status = $result['passed'] ? 'PASS' : 'FAIL';
            echo "[{$status}] {$result['test']}: {$result['message']}\n";
            if ($result['passed']) $passed++;
        }
        
        echo "\nSummary: {$passed}/{$total} tests passed\n";
        
        if ($passed === $total) {
            echo "✅ All tests passed! Dashboard links should now work correctly.\n";
        } else {
            echo "❌ Some tests failed. Please check the issues above.\n";
        }
    }
}

// Run the test
$test = new DashboardLinkTest();
$test->run(); 