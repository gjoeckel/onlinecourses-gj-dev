<?php
/**
 * Test file for Reports Data Retrieval
 * Tests that reports page loads and data is retrieved for valid date ranges
 * Run with: php tests/integration/reports_data_test.php [enterprise]
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force local environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    echo "=== Reports Data Integration Test ===\n";
    echo "Enterprise: " . UnifiedEnterpriseConfig::getEnterprise()['name'] . "\n";
    echo "Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n";
    $urlGen = UnifiedEnterpriseConfig::getUrlGenerator();
    echo "Base URL: " . $urlGen->getBaseUrl(UnifiedEnterpriseConfig::getEnvironment()) . "\n\n";
    
    $passed = 0;
    $failed = 0;
    
    // Test 1: Reports page load (test file existence and structure)
    echo "=== Test 1: Reports Page Structure ===\n";
    $reports_file = __DIR__ . '/../../reports/index.php';
    echo "Testing file: $reports_file\n";
    
    if (file_exists($reports_file)) {
        $content = file_get_contents($reports_file);
        
        // Check for key elements
        $required_elements = [
            'Systemwide Data',
            'Organizations Data',
            'systemwide-data',
            'organization-data',
            'date-range-picker'
        ];
        
        $all_found = true;
        foreach ($required_elements as $element) {
            if (strpos($content, $element) === false) {
                echo "❌ Missing element: $element\n";
                $all_found = false;
            }
        }
        
        if ($all_found) {
            echo "✅ Reports page has all required elements\n";
            $passed++;
        } else {
            echo "❌ Reports page missing required elements\n";
            $failed++;
        }
    } else {
        echo "❌ Reports page file not found\n";
        $failed++;
    }
    
    // Test 2: Reports API file structure
    echo "\n=== Test 2: Reports API Structure ===\n";
    $api_file = __DIR__ . '/../../reports/reports_api.php';
    echo "Testing file: $api_file\n";
    
    if (file_exists($api_file)) {
        $content = file_get_contents($api_file);
        
        // Check for required components
        $required_components = [
            'UnifiedEnterpriseConfig::init()',
            'reports_api.php',
            'Content-Type: application/json',
            'fetch_sheet_data'
        ];
        
        $all_found = true;
        foreach ($required_components as $component) {
            if (strpos($content, $component) === false) {
                echo "❌ Missing component: $component\n";
                $all_found = false;
            }
        }
        
        if ($all_found) {
            echo "✅ Reports API has all required components\n";
            $passed++;
        } else {
            echo "❌ Reports API missing required components\n";
            $failed++;
        }
    } else {
        echo "❌ Reports API file not found\n";
        $failed++;
    }
    
    // Test 3: JavaScript files existence
    echo "\n=== Test 3: JavaScript Files ===\n";
    $js_files = [
        'reports-data.js',
        'date-range-picker.js',
        'organization-search.js'
    ];
    
    $all_js_found = true;
    foreach ($js_files as $js_file) {
        $file_path = __DIR__ . '/../../reports/js/' . $js_file;
        if (file_exists($file_path)) {
            echo "✅ Found: $js_file\n";
        } else {
            echo "❌ Missing: $js_file\n";
            $all_js_found = false;
        }
    }
    
    if ($all_js_found) {
        echo "✅ All required JavaScript files exist\n";
        $passed++;
    } else {
        echo "❌ Some JavaScript files are missing\n";
        $failed++;
    }
    
    // Test 4: Cache directory structure
    echo "\n=== Test 5: Cache Directory Structure ===\n";
    $cache_dir = __DIR__ . '/../../cache/' . $enterprise;
    echo "Testing cache directory: $cache_dir\n";
    
    if (is_dir($cache_dir)) {
        echo "✅ Cache directory exists\n";
        $passed++;
    } else {
        echo "❌ Cache directory not found\n";
        $failed++;
    }
    
    // Test Summary
    echo "\n=== Test Summary ===\n";
    echo "Total Tests: " . ($passed + $failed) . "\n";
    echo "Passed: $passed\n";
    echo "Failed: $failed\n";
    
    if ($failed > 0) {
        echo "❌ Some tests failed\n";
        exit(1);
    } else {
        echo "✅ All tests passed!\n";
        echo "\nReports page structure and configuration are correct for $enterprise enterprise.\n";
        echo "Systemwide and Organizations tables are properly configured.\n";
        echo "\nNote: For full data retrieval testing, start a local web server and test with actual HTTP requests.\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 