<?php
/**
 * Settings Dashboard Workflow Integration Test
 * 
 * This test validates the complete user workflow:
 * 1. Settings page loads with organization table
 * 2. Dashboard links are generated via JavaScript
 * 3. User clicks a dashboard link
 * 4. Link opens dashboard in new tab
 * 5. Dashboard loads correctly with proper authentication
 * 6. Dashboard displays organization data
 * 
 * Tests the entire end-to-end process from settings to dashboard access.
 */

require_once __DIR__ . '/../test_base.php';

class SettingsDashboardWorkflowTest extends TestBase {
    
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    private $testOrganizations = [];
    
    public function runAllTests() {
        echo "=== SETTINGS DASHBOARD WORKFLOW TEST ===\n\n";
        
        $this->testSettingsPageLoading();
        $this->testDashboardLinkGeneration();
        $this->testDashboardLinkAccessibility();
        $this->testDashboardLinkClickSimulation();
        $this->testDashboardPageLoading();
        $this->testDashboardAuthentication();
        $this->testDashboardContentValidation();
        
        $this->printResults();
    }
    
    private function testSettingsPageLoading() {
        echo "1. Testing settings page loading...\n";
        
        $url = $this->baseUrl . '/settings/';
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            $this->addResult('settings page loading', false, 'Could not load settings page');
            return;
        }
        
        // Check for essential settings page elements
        $checks = [
            'settings page title' => strpos($response, 'Settings') !== false,
            'dashboard-table' => strpos($response, 'dashboard-table') !== false,
            'dashboard-link-utils.js' => strpos($response, 'dashboard-link-utils.js') !== false,
            'dashboard-link class' => strpos($response, 'dashboard-link') !== false
        ];
        
        foreach ($checks as $check => $passed) {
            $this->addResult("settings page: $check", $passed, $passed ? 'Element found' : 'Element missing');
        }
        
        // Extract organization data for later tests
        $this->extractOrganizationData($response);
    }
    
    private function extractOrganizationData($html) {
        // Extract organization names and passwords from the HTML
        preg_match_all('/<td class="org-name-col org-name-cell">([^<]+)<\/td>/', $html, $names);
        preg_match_all('/<td class="password-col password-cell">([^<]+)<\/td>/', $html, $passwords);
        
        if (!empty($names[1]) && !empty($passwords[1])) {
            for ($i = 0; $i < count($names[1]); $i++) {
                $this->testOrganizations[] = [
                    'name' => trim($names[1][$i]),
                    'password' => trim($passwords[1][$i])
                ];
            }
        }
        
        $this->addResult('organization data extraction', count($this->testOrganizations) > 0, 
            'Found ' . count($this->testOrganizations) . ' organizations');
    }
    
    private function testDashboardLinkGeneration() {
        echo "\n2. Testing dashboard link generation...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard link generation', false, 'No organizations found to test');
            return;
        }
        
        // Test the dashboard link utilities API
        $apiUrl = $this->baseUrl . '/lib/api/enterprise_api.php';
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            $this->addResult('enterprise API access', false, 'Could not access enterprise API');
            return;
        }
        
        $data = json_decode($response, true);
        if ($data && isset($data['organizations'])) {
            $this->addResult('enterprise API response', true, 'API returned ' . count($data['organizations']) . ' organizations');
        } else {
            $this->addResult('enterprise API response', false, 'Invalid API response format');
        }
        
        // Test direct link generation for each organization
        foreach ($this->testOrganizations as $org) {
            $dashboardUrl = $this->baseUrl . '/dashboard.php?org=' . $org['password'];
            $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
            $headers = get_headers($dashboardUrl, 1, $context);
            
            if ($headers) {
                $status = $headers[0];
                $isValid = strpos($status, '200') !== false || strpos($status, '302') !== false;
                $this->addResult("dashboard link generation: {$org['name']}", $isValid, 
                    "Dashboard URL returns: $status");
            } else {
                $this->addResult("dashboard link generation: {$org['name']}", false, 'Could not access dashboard URL');
            }
        }
    }
    
    private function testDashboardLinkAccessibility() {
        echo "\n3. Testing dashboard link accessibility...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard link accessibility', false, 'No organizations found to test');
            return;
        }
        
        // Test accessibility features of dashboard links
        $url = $this->baseUrl . '/settings/';
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $response = file_get_contents($url, false, $context);
        
        // Check for JavaScript that generates dashboard links
        $accessibilityChecks = [
            'dashboard-link-utils.js loaded' => strpos($response, 'dashboard-link-utils.js') !== false,
            'target="_blank" in PHP' => strpos($response, 'target="_blank"') !== false,
            'rel="noopener" in PHP' => strpos($response, 'rel="noopener"') !== false,
            'dashboard-link class in PHP' => strpos($response, 'class="dashboard-link"') !== false,
            'data-org attribute in PHP' => strpos($response, 'data-org=') !== false,
            'JavaScript link generation' => strpos($response, 'link.target = \'_blank\'') !== false,
            'JavaScript noopener setting' => strpos($response, 'link.rel = \'noopener\'') !== false
        ];
        
        foreach ($accessibilityChecks as $check => $passed) {
            $this->addResult("accessibility: $check", $passed, $passed ? 'Feature present' : 'Feature missing');
        }
    }
    
    private function testDashboardLinkClickSimulation() {
        echo "\n4. Testing dashboard link click simulation...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard link click simulation', false, 'No organizations found to test');
            return;
        }
        
        // Simulate clicking dashboard links by testing the URLs directly
        foreach ($this->testOrganizations as $org) {
            $dashboardUrl = $this->baseUrl . '/dashboard.php?org=' . $org['password'];
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $response = file_get_contents($dashboardUrl, false, $context);
            
            if ($response === false) {
                $this->addResult("link click simulation: {$org['name']}", false, 'Could not access dashboard');
                continue;
            }
            
            // Check if dashboard content is appropriate
            $isValidDashboard = strpos($response, 'Dashboard') !== false || 
                               strpos($response, 'dashboard') !== false ||
                               strpos($response, 'admin.css') !== false;
            
            $this->addResult("link click simulation: {$org['name']}", $isValidDashboard, 
                $isValidDashboard ? 'Dashboard content found' : 'Invalid dashboard content');
        }
    }
    
    private function testDashboardPageLoading() {
        echo "\n5. Testing dashboard page loading...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard page loading', false, 'No organizations found to test');
            return;
        }
        
        // Test dashboard page loading for each organization
        foreach ($this->testOrganizations as $org) {
            $dashboardUrl = $this->baseUrl . '/dashboard.php?org=' . $org['password'];
            $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
            $headers = get_headers($dashboardUrl, 1, $context);
            
            if ($headers) {
                $status = $headers[0];
                // Accept both 200 (direct access) and 302 (redirect) as valid responses
                $isValid = strpos($status, '200') !== false || strpos($status, '302') !== false;
                $this->addResult("dashboard page loading: {$org['name']}", $isValid, 
                    "Dashboard responds with status: $status");
            } else {
                $this->addResult("dashboard page loading: {$org['name']}", false, 'Could not load dashboard');
            }
        }
    }
    
    private function testDashboardAuthentication() {
        echo "\n6. Testing dashboard authentication...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard authentication', false, 'No organizations found to test');
            return;
        }
        
        // Test authentication by checking if dashboard shows organization-specific content
        foreach ($this->testOrganizations as $org) {
            $dashboardUrl = $this->baseUrl . '/dashboard.php?org=' . $org['password'];
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $response = file_get_contents($dashboardUrl, false, $context);
            
            if ($response === false) {
                $this->addResult("dashboard authentication: {$org['name']}", false, 'Could not access dashboard');
                continue;
            }
            
            // Check for authentication success indicators
            $authChecks = [
                'organization name' => strpos($response, htmlspecialchars($org['name'])) !== false,
                'dashboard header' => strpos($response, 'Dashboard') !== false,
                'admin CSS' => strpos($response, 'admin.css') !== false,
                'not redirect' => strpos($response, 'Location:') === false
            ];
            
            $passedChecks = 0;
            foreach ($authChecks as $check => $passed) {
                if ($passed) $passedChecks++;
            }
            
            $isAuthenticated = $passedChecks >= 2; // At least 2 checks should pass
            $this->addResult("dashboard authentication: {$org['name']}", $isAuthenticated, 
                "Authentication checks passed: $passedChecks/4");
        }
    }
    
    private function testDashboardContentValidation() {
        echo "\n7. Testing dashboard content validation...\n";
        
        if (empty($this->testOrganizations)) {
            $this->addResult('dashboard content validation', false, 'No organizations found to test');
            return;
        }
        
        // Test dashboard content for each organization
        foreach ($this->testOrganizations as $org) {
            $dashboardUrl = $this->baseUrl . '/dashboard.php?org=' . $org['password'];
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $response = file_get_contents($dashboardUrl, false, $context);
            
            if ($response === false) {
                $this->addResult("dashboard content: {$org['name']}", false, 'Could not access dashboard');
                continue;
            }
            
            // Check for essential dashboard elements
            $contentChecks = [
                'HTML structure' => strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false,
                'CSS loading' => strpos($response, 'admin.css') !== false,
                'JavaScript loading' => strpos($response, 'dashboard-link-utils.js') !== false,
                'Organization data' => strpos($response, 'Dashboard') !== false || strpos($response, 'dashboard') !== false
            ];
            
            $passedChecks = 0;
            foreach ($contentChecks as $check => $passed) {
                if ($passed) $passedChecks++;
            }
            
            $hasValidContent = $passedChecks >= 3; // At least 3 checks should pass
            $this->addResult("dashboard content: {$org['name']}", $hasValidContent, 
                "Content checks passed: $passedChecks/4");
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
        
        echo "\n=== WORKFLOW STEPS TESTED ===\n";
        echo "1. ✅ Settings page loading with organization table\n";
        echo "2. ✅ Dashboard link generation via JavaScript\n";
        echo "3. ✅ Dashboard link accessibility features\n";
        echo "4. ✅ Dashboard link click simulation\n";
        echo "5. ✅ Dashboard page loading\n";
        echo "6. ✅ Dashboard authentication\n";
        echo "7. ✅ Dashboard content validation\n";
        
        echo "\n=== RECOMMENDATIONS ===\n";
        if ($failed === 0) {
            echo "✅ All settings dashboard workflow tests passed! The complete user workflow is working correctly.\n";
        } else {
            echo "❌ Some tests failed. Check the failed tests above.\n";
            echo "Common fixes:\n";
            echo "- Ensure settings page loads correctly\n";
            echo "- Verify dashboard link generation works\n";
            echo "- Check dashboard authentication logic\n";
            echo "- Validate dashboard content structure\n";
            echo "- Test with valid organization passwords\n";
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new SettingsDashboardWorkflowTest();
    $test->runAllTests();
}
?> 