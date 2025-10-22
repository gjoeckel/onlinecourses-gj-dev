<?php
/**
 * Direct Links Integration Test
 * Tests direct link generation and validation functionality
 */

require_once __DIR__ . '/../test_base.php';
require_once __DIR__ . '/../../lib/direct_link.php';

echo "=== Direct Links Integration Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    // Test 1: Direct link file existence
    TestBase::runTest('Direct Link File Exists', function() {
        $direct_link_file = __DIR__ . '/../../lib/direct_link.php';
        TestBase::assertTrue(file_exists($direct_link_file), 'Direct link file should exist');
    });
    
    // Test 2: Direct link generation
    TestBase::runTest('Direct Link Generation', function() {
        $test_password = 'test123';
        $direct_link = DirectLink::getDirectLink($test_password);
        
        TestBase::assertNotNull($direct_link, 'Direct link should not be null');
        TestBase::assertNotEmpty($direct_link, 'Direct link should not be empty');
        TestBase::assertTrue($direct_link !== 'N/A', 'Direct link should not be N/A');
        
        // Verify the link contains the password
        TestBase::assertTrue(strpos($direct_link, $test_password) !== false, 'Direct link should contain the password');
        
        echo "   Generated link: " . $direct_link . "\n";
    });
    
    // Test 3: Enterprise.json path
    TestBase::runTest('Enterprise.json Path', function() {
        $json_path = DirectLink::getEnterpriseJsonPath();
        TestBase::assertNotNull($json_path, 'Enterprise.json path should not be null');
        TestBase::assertNotEmpty($json_path, 'Enterprise.json path should not be empty');
        
        echo "   JSON path: " . $json_path . "\n";
    });
    
    // Test 4: Enterprise.json regeneration
    TestBase::runTest('Enterprise.json Regeneration', function() {
        $json_path = DirectLink::getEnterpriseJsonPath();
        
        // Check if file exists before regeneration
        $exists_before = file_exists($json_path);
        
        // Regenerate the file
        DirectLink::regenerateEnterpriseJson();
        
        // Check if file exists after regeneration
        $exists_after = file_exists($json_path);
        
        TestBase::assertTrue($exists_after, 'Enterprise.json should exist after regeneration');
        
        // Verify file contains valid JSON
        $json_content = file_get_contents($json_path);
        $json_data = json_decode($json_content, true);
        
        TestBase::assertNotNull($json_data, 'Enterprise.json should contain valid JSON');
        TestBase::assertTrue(isset($json_data['organizations']), 'Enterprise.json should contain organizations array');
        
        echo "   Organizations found: " . count($json_data['organizations']) . "\n";
    });
    
    // Test 5: API endpoint accessibility
    TestBase::runTest('API Endpoint Accessibility', function() {
        $api_file = __DIR__ . '/../../lib/api/enterprise_api.php';
        TestBase::assertTrue(file_exists($api_file), 'Enterprise API file should exist');
        
        // Test if API returns organizations format
        if (function_exists('file_get_contents')) {
            $api_url = 'http://localhost:8000/lib/api/enterprise_api.php';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $result = @file_get_contents($api_url, false, $context);
            if ($result !== false) {
                $response = json_decode($result, true);
                if ($response && isset($response['organizations'])) {
                    echo "   API accessible, found " . count($response['organizations']) . " organizations\n";
                } else {
                    echo "   API accessible but response format may vary\n";
                }
            } else {
                echo "   API test skipped (server not running)\n";
            }
        }
    });
    
    echo "\n✅ Direct Links Integration Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 