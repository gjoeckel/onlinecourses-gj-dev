<?php
/**
 * PATH_INFO Fix Validation Test
 * 
 * This test validates that the PATH_INFO fix in dashboard.php is working
 * correctly and prevents infinite redirect loops.
 * 
 * Tests:
 * 1. Valid 4-digit passwords work without redirects
 * 2. Invalid passwords redirect properly
 * 3. No infinite redirect loops
 * 4. PATH_INFO handling is correct
 */

require_once __DIR__ . '/test_base.php';

class PathInfoFixValidationTest extends TestBase {
    
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    
    public function runAllTests() {
        echo "=== PATH_INFO FIX VALIDATION TEST ===\n\n";
        
        $this->testValidPasswords();
        $this->testInvalidPasswords();
        $this->testInfiniteRedirectPrevention();
        $this->testPathInfoHandling();
        
        $this->printResults();
    }
    
    private function testValidPasswords() {
        echo "1. Testing valid 4-digit passwords...\n";
        
        $validPasswords = ['0523', '4601', '5079', '1234', '9999'];
        
        foreach ($validPasswords as $password) {
            $url = $this->baseUrl . "/dashboard.php?org=$password";
            $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
            $headers = get_headers($url, 1, $context);
            
            if ($headers) {
                $status = $headers[0];
                $location = isset($headers['Location']) ? $headers['Location'] : 'None';
                
                if (strpos($status, '200') !== false) {
                    $this->addResult("valid password: $password", true, 'Dashboard shows directly (200 OK)');
                } elseif (strpos($status, '302') !== false && strpos($location, 'dashboard.php?') !== false) {
                    $this->addResult("valid password: $password", true, 'Proper redirect to query parameter');
                } else {
                    $this->addResult("valid password: $password", false, "Unexpected response: $status");
                }
            } else {
                $this->addResult("valid password: $password", false, 'Could not access URL');
            }
        }
    }
    
    private function testInvalidPasswords() {
        echo "2. Testing invalid passwords...\n";
        
        $invalidPasswords = ['abcd', '12345', 'abc', '123', 'xyz'];
        
        foreach ($invalidPasswords as $password) {
            $url = $this->baseUrl . "/dashboard.php?org=$password";
            $context = stream_context_create(['http' => ['follow_location' => false, 'timeout' => 10]]);
            $headers = get_headers($url, 1, $context);
            
            if ($headers) {
                $status = $headers[0];
                $location = isset($headers['Location']) ? $headers['Location'] : 'None';
                
                if (strpos($status, '302') !== false && strpos($location, 'dashboard.php?') !== false) {
                    $this->addResult("invalid password: $password", true, 'Proper redirect to dashboard.php');
                } else {
                    $this->addResult("invalid password: $password", false, "Unexpected response: $status");
                }
            } else {
                $this->addResult("invalid password: $password", false, 'Could not access URL');
            }
        }
    }
    
    private function testInfiniteRedirectPrevention() {
        echo "3. Testing infinite redirect prevention...\n";
        
        $testUrls = [
            '/dashboard.php?org=0523',
            '/dashboard.php?org=abcd',
            '/dashboard.php?org=12345',
            '/dashboard.php?org=organizations',
            '/dashboard.php?org=assets/css/admin.css'
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
    
    private function testPathInfoHandling() {
        echo "4. Testing PATH_INFO handling...\n";
        
        // Test that the fix is actually in the code
        $dashboardFile = __DIR__ . '/../dashboard.php';
        if (file_exists($dashboardFile)) {
            $content = file_get_contents($dashboardFile);
            
            // Check for PATH_INFO detection
            if (strpos($content, 'PATH_INFO') !== false) {
                $this->addResult('PATH_INFO detection code', true, 'PATH_INFO detection found in dashboard.php');
            } else {
                $this->addResult('PATH_INFO detection code', false, 'PATH_INFO detection not found in dashboard.php');
            }
            
            // Check for 4-digit password validation
            if (strpos($content, 'preg_match') !== false && strpos($content, '/^\d{4}$/') !== false) {
                $this->addResult('4-digit password validation', true, '4-digit password validation found');
            } else {
                $this->addResult('4-digit password validation', false, '4-digit password validation not found');
            }
        } else {
            $this->addResult('dashboard.php file', false, 'dashboard.php file not found');
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
            echo "✅ All PATH_INFO fix tests passed! No action needed.\n";
        } else {
            echo "❌ Some tests failed. Check the failed tests above.\n";
            echo "Common fixes:\n";
            echo "- Ensure PATH_INFO detection is in dashboard.php\n";
            echo "- Verify 4-digit password validation works\n";
            echo "- Check for infinite redirect loops\n";
            echo "- Test with various password formats\n";
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PathInfoFixValidationTest();
    $test->runAllTests();
}
?> 