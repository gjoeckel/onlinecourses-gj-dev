<?php
/**
 * ES6 Module Validation Test
 * 
 * This test validates that ES6 modules load correctly and don't cause
 * "Unexpected token 'export'" errors in the browser.
 * 
 * Tests:
 * 1. dashboard-link-utils.js loads as ES6 module
 * 2. Dashboard page loads without JS errors
 * 3. All asset paths resolve correctly
 * 4. No infinite redirect loops
 */

require_once __DIR__ . '/test_base.php';

class ES6ModuleValidationTest extends TestBase {
    
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    
    public function runAllTests() {
        echo "=== ES6 MODULE VALIDATION TEST ===\n\n";
        
        $this->testDashboardLinkUtilsModule();
        $this->testDashboardPageLoading();
        $this->testAssetPathResolution();
        $this->testNoInfiniteRedirects();
        
        $this->printResults();
    }
    
    private function testDashboardLinkUtilsModule() {
        echo "1. Testing dashboard-link-utils.js ES6 module loading...\n";
        
        $url = $this->baseUrl . '/lib/dashboard-link-utils.js';
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            $this->addResult('dashboard-link-utils.js loading', false, 'Could not load dashboard-link-utils.js');
            return;
        }
        
        // Check for ES6 export statements
        if (strpos($response, 'export function') !== false) {
            $this->addResult('dashboard-link-utils.js ES6 content', true, 'ES6 module content found');
        } else {
            $this->addResult('dashboard-link-utils.js ES6 content', false, 'ES6 module content not found');
        }
        
        // Check for proper script type in HTML
        $dashboardUrl = $this->baseUrl . '/dashboard.php?org=0523';
        $dashboardResponse = file_get_contents($dashboardUrl, false, $context);
        
        if ($dashboardResponse !== false) {
            if (strpos($dashboardResponse, 'type="module" src="/lib/dashboard-link-utils.js"') !== false) {
                $this->addResult('dashboard.php module script tag', true, 'Correct type="module" attribute found');
            } else {
                $this->addResult('dashboard.php module script tag', false, 'Missing or incorrect type="module" attribute');
            }
        } else {
            $this->addResult('dashboard.php module script tag', false, 'Could not load dashboard page');
        }
    }
    
    private function testDashboardPageLoading() {
        echo "2. Testing dashboard page loading with ES6 modules...\n";
        
        $url = $this->baseUrl . '/dashboard.php?org=0523';
        $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
        $headers = get_headers($url, 1, $context);
        
        if ($headers) {
            $status = $headers[0];
            if (strpos($status, '200') !== false) {
                $this->addResult('dashboard page loading', true, 'Dashboard loads successfully');
            } else {
                $this->addResult('dashboard page loading', false, "Dashboard returned status: $status");
            }
        } else {
            $this->addResult('dashboard page loading', false, 'Could not access dashboard');
        }
    }
    
    private function testAssetPathResolution() {
        echo "3. Testing asset path resolution...\n";
        
        $assets = [
            '/lib/dashboard-link-utils.js',
            '/lib/message-dismissal.js',
            '/lib/table-interaction.js',
            '/config/config.js',
            '/css/admin.css',
            '/favicon.ico'
        ];
        
        foreach ($assets as $asset) {
            $url = $this->baseUrl . $asset;
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $headers = get_headers($url, 1, $context);
            
            if ($headers && strpos($headers[0], '200') !== false) {
                $this->addResult("asset loading: $asset", true, 'Asset loads successfully');
            } else {
                $this->addResult("asset loading: $asset", false, 'Asset failed to load');
            }
        }
    }
    
    private function testNoInfiniteRedirects() {
        echo "4. Testing for infinite redirect loops...\n";
        
        $testUrls = [
            '/dashboard.php?org=0523',
            '/dashboard.php?org=abcd',
            '/dashboard.php?org=12345'
        ];
        
        foreach ($testUrls as $url) {
            $fullUrl = $this->baseUrl . $url;
            $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
            $headers = get_headers($fullUrl, 1, $context);
            
            if ($headers) {
                $status = $headers[0];
                $location = isset($headers['Location']) ? $headers['Location'] : 'None';
                
                // Check for redirect loops (same URL redirecting to itself)
                if (strpos($status, '302') !== false && strpos($location, $url) !== false) {
                    $this->addResult("redirect loop check: $url", false, "Potential redirect loop detected");
                } else {
                    $this->addResult("redirect loop check: $url", true, 'No redirect loop detected');
                }
            } else {
                $this->addResult("redirect loop check: $url", false, 'Could not access URL');
            }
        }
    }
    
    private function addResult($test, $passed, $message) {
        $this->testResults[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "   $status: $message\n";
    }
    
    private function printResults() {
        echo "\n=== TEST SUMMARY ===\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "Total tests: " . count($this->testResults) . "\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ($failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- {$result['test']}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n=== RECOMMENDATIONS ===\n";
        if ($failed === 0) {
            echo "✅ All ES6 module tests passed! No action needed.\n";
        } else {
            echo "❌ Some tests failed. Check the failed tests above.\n";
            echo "Common fixes:\n";
            echo "- Ensure <script type=\"module\" src=\"...\"> for ES6 modules\n";
            echo "- Verify asset paths are absolute (start with /)\n";
            echo "- Check for infinite redirect loops in dashboard.php\n";
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ES6ModuleValidationTest();
    $test->runAllTests();
}
?> 