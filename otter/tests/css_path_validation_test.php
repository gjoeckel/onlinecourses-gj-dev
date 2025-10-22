<?php
/**
 * CSS Path Validation Test
 * 
 * This test validates that all CSS files in the workspace:
 * 1. Exist as files on disk
 * 2. Are accessible via web server
 * 3. Return proper CSS content (not HTML/errors)
 * 4. Have valid CSS syntax (basic check)
 * 5. Are referenced correctly in PHP files
 * 
 * Tests all CSS files across:
 * - assets/css/ (main stylesheets)
 * - reports/css/ (reports-specific styles)
 * - organizations/ (organization styles)
 * - admin/ (admin-specific styles)
 */

require_once __DIR__ . '/test_base.php';

class CSSPathValidationTest extends TestBase {
    
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    private $cssFiles = [];
    
    public function runAllTests() {
        echo "=== CSS PATH VALIDATION TEST ===\n\n";
        
        $this->discoverCSSFiles();
        $this->testFileExistence();
        $this->testWebAccessibility();
        $this->testCSSContentValidation();
        $this->testPHPReferences();
        $this->testCSSSyntax();
        
        $this->printResults();
    }
    
    private function discoverCSSFiles() {
        echo "1. Discovering CSS files in workspace...\n";
        
        // Unified CSS directory (new structure)
        $this->cssFiles['css/admin.css'] = '/css/admin.css';
        $this->cssFiles['css/login.css'] = '/css/login.css';
        $this->cssFiles['css/print.css'] = '/css/print.css';
        $this->cssFiles['css/enterprise-builder.css'] = '/css/enterprise-builder.css';
        $this->cssFiles['css/buttons.css'] = '/css/buttons.css';
        $this->cssFiles['css/messages.css'] = '/css/messages.css';
        $this->cssFiles['css/settings.css'] = '/css/settings.css';

        $this->cssFiles['css/dashboard.css'] = '/css/dashboard.css';
        $this->cssFiles['css/loading-message.css'] = '/css/loading-message.css';
        
        // Reports directory (still in reports/css/)
        $this->cssFiles['reports/css/reports-main.css'] = '/reports/css/reports-main.css';
        $this->cssFiles['reports/css/reports-messaging.css'] = '/reports/css/reports-messaging.css';
        $this->cssFiles['reports/css/organization-search.css'] = '/reports/css/organization-search.css';
        $this->cssFiles['reports/css/reports-data.css'] = '/reports/css/reports-data.css';
        $this->cssFiles['reports/css/date-range-picker.css'] = '/reports/css/date-range-picker.css';
        $this->cssFiles['reports/css/groups-search.css'] = '/reports/css/groups-search.css';
        
        echo "   Found " . count($this->cssFiles) . " CSS files to test\n";
    }
    
    private function testFileExistence() {
        echo "\n2. Testing file existence on disk...\n";
        
        foreach ($this->cssFiles as $filePath => $webPath) {
            if (file_exists($filePath)) {
                $this->addResult("file existence: $filePath", true, 'File exists on disk');
            } else {
                $this->addResult("file existence: $filePath", false, 'File missing from disk');
            }
        }
    }
    
    private function testWebAccessibility() {
        echo "\n3. Testing web accessibility...\n";
        
        foreach ($this->cssFiles as $filePath => $webPath) {
            $url = $this->baseUrl . $webPath;
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $headers = get_headers($url, 1, $context);
            
            if ($headers && strpos($headers[0], '200') !== false) {
                $this->addResult("web access: $webPath", true, 'Accessible via web server');
            } else {
                $status = $headers ? $headers[0] : 'No response';
                $this->addResult("web access: $webPath", false, "Web access failed: $status");
            }
        }
    }
    
    private function testCSSContentValidation() {
        echo "\n4. Testing CSS content validation...\n";
        
        foreach ($this->cssFiles as $filePath => $webPath) {
            $url = $this->baseUrl . $webPath;
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $content = file_get_contents($url, false, $context);
            
            if ($content === false) {
                $this->addResult("content validation: $webPath", false, 'Could not fetch content');
                continue;
            }
            
            // Check if content looks like CSS (not HTML or error)
            $isHtml = strpos($content, '<!DOCTYPE') !== false || 
                      strpos($content, '<html') !== false ||
                      strpos($content, '<?php') !== false ||
                      strpos($content, 'Fatal error') !== false;
            
            if ($isHtml) {
                $this->addResult("content validation: $webPath", false, 'Returns HTML instead of CSS');
            } else {
                $this->addResult("content validation: $webPath", true, 'Returns valid CSS content');
            }
        }
    }
    
    private function testPHPReferences() {
        echo "\n5. Testing PHP file references...\n";
        
        // Check main PHP files for CSS references
        $phpFiles = [
            'dashboard.php',
            'login.php',
            'enterprise-builder.php',
            'settings/index.php',
            'admin/index.php',
            'reports/index.php',
            'organizations/index.php',
            'reports/certificates.php'
        ];
        
        // Define expected CSS paths for each file
        $expectedPaths = [
            'dashboard.php' => ['css/dashboard.css'],
            'login.php' => ['css/login.css', 'css/messages.css'],
            'enterprise-builder.php' => ['css/enterprise-builder.css', 'css/settings.css', 'css/print.css'],
            'settings/index.php' => ['../css/settings.css', '../css/messages.css', '../css/print.css'],
            'admin/index.php' => ['../css/admin.css'],
            'reports/index.php' => ['css/reports-main.css', 'css/date-range-picker.css', 'css/reports-data.css', 'css/organization-search.css', 'css/groups-search.css', '../css/messages.css', 'css/reports-messaging.css', '../css/buttons.css', '../css/print.css'],

            'reports/certificates.php' => ['css/reports-main.css', 'css/date-range-picker.css', 'css/reports-data.css', 'css/organization-search.css', 'css/district-search.css', 'css/reports-messaging.css', '../css/print.css']
        ];
        
        foreach ($phpFiles as $phpFile) {
            if (file_exists($phpFile)) {
                $content = file_get_contents($phpFile);
                $cssReferences = [];
                $invalidReferences = [];
                
                // Extract CSS references from the file
                preg_match_all('/href=["\']([^"\']*\.css[^"\']*)["\']/', $content, $matches);
                
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $cssRef) {
                        // Clean up the reference (remove query parameters)
                        $cleanRef = preg_replace('/\?.*$/', '', $cssRef);
                        $cssReferences[] = $cleanRef;
                        
                        // Check if this reference is valid
                        $expectedPathsForFile = $expectedPaths[$phpFile] ?? [];
                        $isValid = false;
                        
                        // Allow external CDN URLs
                        if (strpos($cleanRef, 'http') === 0 || strpos($cleanRef, '//') === 0) {
                            $isValid = true;
                        } else {
                            foreach ($expectedPathsForFile as $expectedPath) {
                                if (strpos($cleanRef, $expectedPath) !== false) {
                                    $isValid = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$isValid) {
                            $invalidReferences[] = $cleanRef;
                        }
                    }
                    
                    if (empty($invalidReferences)) {
                        $this->addResult("PHP references: $phpFile", true, 'Contains ' . count($cssReferences) . ' valid CSS references');
                    } else {
                        $this->addResult("PHP references: $phpFile", false, 'Contains invalid CSS references: ' . implode(', ', $invalidReferences));
                    }
                } else {
                    $this->addResult("PHP references: $phpFile", false, 'No CSS references found');
                }
            } else {
                $this->addResult("PHP references: $phpFile", false, 'PHP file not found');
            }
        }
    }
    
    private function testCSSSyntax() {
        echo "\n6. Testing basic CSS syntax validation...\n";
        
        foreach ($this->cssFiles as $filePath => $webPath) {
            if (!file_exists($filePath)) {
                continue; // Skip if file doesn't exist
            }
            
            $content = file_get_contents($filePath);
            if ($content === false) {
                $this->addResult("CSS syntax: $filePath", false, 'Could not read file');
                continue;
            }
            
            // Basic CSS syntax checks
            $issues = [];
            
            // Check for unclosed braces
            $openBraces = substr_count($content, '{');
            $closeBraces = substr_count($content, '}');
            if ($openBraces !== $closeBraces) {
                $issues[] = "Mismatched braces: $openBraces open, $closeBraces close";
            }
            
            // Check for basic CSS structure
            if (!preg_match('/[a-zA-Z-]+\s*\{/', $content)) {
                $issues[] = 'No CSS rules found';
            }
            
            // Check for common CSS properties
            if (!preg_match('/(color|background|margin|padding|border|font|display|position|width|height)\s*:/', $content)) {
                $issues[] = 'No common CSS properties found';
            }
            
            if (empty($issues)) {
                $this->addResult("CSS syntax: $filePath", true, 'Basic syntax appears valid');
            } else {
                $this->addResult("CSS syntax: $filePath", false, 'Syntax issues: ' . implode(', ', $issues));
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
        
        echo "\n=== CSS FILES TESTED ===\n";
        foreach ($this->cssFiles as $filePath => $webPath) {
            $status = file_exists($filePath) ? '✅' : '❌';
            echo "$status $filePath\n";
        }
        
        echo "\n=== RECOMMENDATIONS ===\n";
        if ($failed === 0) {
            echo "✅ All CSS path tests passed! No action needed.\n";
        } else {
            echo "❌ Some tests failed. Check the failed tests above.\n";
            echo "Common fixes:\n";
            echo "- Ensure CSS files exist in expected locations\n";
            echo "- Verify web server can access CSS files\n";
            echo "- Check CSS syntax for basic errors\n";
            echo "- Update PHP files to reference correct CSS paths\n";
        }
    }
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new CSSPathValidationTest();
    $test->runAllTests();
}
?> 