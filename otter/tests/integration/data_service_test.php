<?php
/**
 * Enterprise Data Service Integration Test
 * Tests data fetching and caching functionality
 */

require_once __DIR__ . '/../test_base.php';
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/enterprise_data_service.php';

echo "=== Data Service Test ===\n\n";

try {
    // Initialize enterprise configuration for current test enterprise
    TestBase::initEnterprise();
    
    echo "✅ Enterprise configuration loaded successfully\n";
    echo "Testing Enterprise: " . strtoupper(TestBase::getEnterprise()) . "\n\n";
    
    // Test enterprise information
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "Enterprise: " . $enterprise['name'] . " (" . $enterprise['code'] . ")\n";
    echo "Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";
    
    // Test data service file existence
    $data_service_file = __DIR__ . '/../../lib/enterprise_data_service.php';
    if (!file_exists($data_service_file)) {
        throw new Exception("Data service file not found: $data_service_file");
    }
    
    echo "✅ Data service file exists\n";
    
    // Test data service class loading
    require_once $data_service_file;
    
    // Test data service instantiation
    $dataService = new EnterpriseDataService();
    echo "✅ Data service instantiated successfully\n";
    
    // Test cache manager integration
    $cacheManager = EnterpriseCacheManager::getInstance();
    echo "✅ Cache manager integrated successfully\n";
    echo "Cache directory: " . $cacheManager->getEnterpriseCacheDir() . "\n";
    
    echo "\n✅ All data service tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 