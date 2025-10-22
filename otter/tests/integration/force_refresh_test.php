<?php
/**
 * Test file for EnterpriseDataService - Force Refresh
 * Run with: php test-force-refresh.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../test_base.php';

echo "=== Force Refresh Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded successfully\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    // Test enterprise information
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
    echo "Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";
    
    // Test cache manager
    require_once __DIR__ . '/../../lib/enterprise_cache_manager.php';
    $cacheManager = EnterpriseCacheManager::getInstance();
    
    echo "Cache directory: " . $cacheManager->getEnterpriseCacheDir() . "\n";
    
    // Test cache file operations
    $test_data = ['test' => 'data', 'timestamp' => time()];
    $test_filename = 'test-cache.json';
    
    // Write test cache file
    $write_success = $cacheManager->writeCacheFile($test_filename, $test_data);
    echo "Cache write test: " . ($write_success ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Read test cache file
    $read_data = $cacheManager->readCacheFile($test_filename);
    $read_success = $read_data !== null && $read_data['test'] === 'data';
    echo "Cache read test: " . ($read_success ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Delete test cache file
    $delete_success = $cacheManager->deleteCacheFile($test_filename);
    echo "Cache delete test: " . ($delete_success ? "✅ PASS" : "❌ FAIL") . "\n";
    
    if (!$write_success || !$read_success || !$delete_success) {
        throw new Exception("Cache operations failed");
    }
    
    echo "\n✅ All force refresh tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 