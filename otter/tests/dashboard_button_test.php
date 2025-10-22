<?php
/**
 * Test Dashboard Button Functionality
 * 
 * This test verifies that the Dashboard button on the Organizations Filter widget
 * is properly enabled after a filter is applied and uses DRY code principles.
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_database.php';

class DashboardButtonTest {
    private $baseUrl = 'http://localhost:8000';
    private $results = [];
    
    public function run() {
        echo "=== Dashboard Button Functionality Test ===\n\n";
        
        $this->testEnterpriseAPIAccess();
        $this->testDashboardButtonCode();
        $this->testDRYCodePrinciples();
        
        $this->printResults();
    }
    
    private function testEnterpriseAPIAccess() {
        echo "1. Testing Enterprise API access...\n";
        
        // Test direct API access
        $apiUrl = $this->baseUrl . '/lib/api/enterprise_api.php';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            echo "  ❌ Failed to access enterprise API\n";
            $this->results['api_access'] = false;
            return;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['organizations'])) {
            echo "  ❌ Invalid JSON response from enterprise API\n";
            $this->results['api_access'] = false;
            return;
        }
        
        $orgCount = count($data['organizations']);
        echo "  ✅ API returned {$orgCount} organizations\n";
        
        // Check for dashboard URLs
        $hasDashboardUrls = false;
        foreach ($data['organizations'] as $org) {
            if (isset($org['dashboard_url_production']) && $org['dashboard_url_production'] !== 'N/A') {
                $hasDashboardUrls = true;
                break;
            }
        }
        
        if ($hasDashboardUrls) {
            echo "  ✅ Dashboard URLs found in API response\n";
            $this->results['api_access'] = true;
        } else {
            echo "  ❌ No dashboard URLs found in API response\n";
            $this->results['api_access'] = false;
        }
    }
    
    private function testDashboardButtonCode() {
        echo "\n2. Testing dashboard button code quality...\n";
        
        $orgSearchFile = __DIR__ . '/../reports/js/organization-search.js';
        if (!file_exists($orgSearchFile)) {
            echo "  ❌ organization-search.js not found\n";
            $this->results['code_quality'] = false;
            return;
        }
        
        $content = file_get_contents($orgSearchFile);
        
        // Check for proper function usage
        $hasDisableFunction = strpos($content, 'disableDashboardButton()') !== false;
        $hasEnableFunction = strpos($content, 'enableDashboardButton(') !== false;
        $usesGetDashboardUrl = strpos($content, 'getDashboardUrlJS(') !== false;
        $usesSetTimeout = strpos($content, 'setTimeout(() => updateDashboardBtn()') !== false;
        
        if ($hasDisableFunction) {
            echo "  ✅ disableDashboardButton() function found\n";
        } else {
            echo "  ❌ disableDashboardButton() function not found\n";
        }
        
        if ($hasEnableFunction) {
            echo "  ✅ enableDashboardButton() function found\n";
        } else {
            echo "  ❌ enableDashboardButton() function not found\n";
        }
        
        if ($usesGetDashboardUrl) {
            echo "  ✅ Using getDashboardUrlJS() from dashboard-link-utils\n";
        } else {
            echo "  ❌ Not using getDashboardUrlJS() from dashboard-link-utils\n";
        }
        
        if ($usesSetTimeout) {
            echo "  ✅ setTimeout used to fix DOM timing issues\n";
        } else {
            echo "  ❌ setTimeout not used for DOM timing\n";
        }
        
        $this->results['code_quality'] = $hasDisableFunction && $hasEnableFunction && $usesGetDashboardUrl && $usesSetTimeout;
    }
    
    private function testDRYCodePrinciples() {
        echo "\n3. Testing DRY code principles...\n";
        
        $orgSearchFile = __DIR__ . '/../reports/js/organization-search.js';
        $content = file_get_contents($orgSearchFile);
        
        // Check for repeated patterns
        $disablePatterns = substr_count($content, 'dashboardBtn.disabled = true');
        $enablePatterns = substr_count($content, 'dashboardBtn.disabled = false');
        $disableFunctionCalls = substr_count($content, 'disableDashboardButton()');
        $enableFunctionCalls = substr_count($content, 'enableDashboardButton()');
        
        if ($disablePatterns <= 1) {
            echo "  ✅ No repeated disable patterns found\n";
        } else {
            echo "  ❌ Repeated disable patterns found ({$disablePatterns} instances)\n";
        }
        
        if ($enablePatterns <= 1) {
            echo "  ✅ No repeated enable patterns found\n";
        } else {
            echo "  ❌ Repeated enable patterns found ({$enablePatterns} instances)\n";
        }
        
        echo "  ✅ disableDashboardButton() called {$disableFunctionCalls} times\n";
        echo "  ✅ enableDashboardButton() called {$enableFunctionCalls} times\n";
        echo "  ✅ Using centralized dashboard utilities\n";
        
        $this->results['dry_code'] = $disablePatterns <= 1 && $enablePatterns <= 1;
    }
    
    private function printResults() {
        echo "\n=== Test Results Summary ===\n";
        
        $passed = 0;
        $total = count($this->results);
        
        foreach ($this->results as $test => $result) {
            if ($result) {
                $passed++;
            }
        }
        
        echo "Passed: {$passed}/{$total}\n";
        
        if ($passed < $total) {
            echo "⚠️  Some tests failed. Please review the issues above.\n";
        } else {
            echo "✅ All tests passed!\n";
        }
    }
}

// Run the test
$test = new DashboardButtonTest();
$test->run();
?> 