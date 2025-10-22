<?php
/**
 * Test script to verify timestamp functionality
 * Run with: php tests/test_timestamp_functionality.php
 */

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/api/organizations_api.php';

echo "=== Timestamp Functionality Test ===\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init('csu');
    $cacheManager = EnterpriseCacheManager::getInstance();
    
    echo "✅ Enterprise configuration loaded\n";
    echo "Enterprise: " . UnifiedEnterpriseConfig::getEnterpriseCode() . "\n";
    echo "Cache directory: " . $cacheManager->getEnterpriseCacheDir() . "\n\n";
    
    // Test 1: Check if cache file exists
    $cacheFile = $cacheManager->getRegistrantsCachePath();
    if (file_exists($cacheFile)) {
        echo "✅ Cache file exists: " . basename($cacheFile) . "\n";
        echo "File size: " . number_format(filesize($cacheFile)) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($cacheFile)) . "\n\n";
    } else {
        echo "❌ Cache file missing: " . $cacheFile . "\n";
        exit(1);
    }
    
    // Test 2: Read cache file and check timestamp
    $cacheData = $cacheManager->readCacheFile('all-registrants-data.json');
    if (isset($cacheData['global_timestamp'])) {
        echo "✅ Cache timestamp found: " . $cacheData['global_timestamp'] . "\n";
        echo "Data rows: " . count($cacheData['data'] ?? []) . "\n\n";
    } else {
        echo "❌ No timestamp found in cache file\n";
        exit(1);
    }
    
    // Test 3: Test OrganizationsAPI timestamp retrieval
    $orgData = OrganizationsAPI::getOrgData('Sacramento');
    if (isset($orgData['api_retrieval_timestamp'])) {
        echo "✅ OrganizationsAPI timestamp: " . $orgData['api_retrieval_timestamp'] . "\n";
        echo "Enrollment data rows: " . count($orgData['enrollment'] ?? []) . "\n";
        echo "Enrolled data rows: " . count($orgData['enrolled'] ?? []) . "\n";
        echo "Invited data rows: " . count($orgData['invited'] ?? []) . "\n\n";
    } else {
        echo "❌ No timestamp from OrganizationsAPI\n";
        exit(1);
    }
    
    // Test 4: Verify timestamps match
    if ($cacheData['global_timestamp'] === $orgData['api_retrieval_timestamp']) {
        echo "✅ Timestamps match between cache and API\n\n";
    } else {
        echo "❌ Timestamps don't match:\n";
        echo "  Cache: " . $cacheData['global_timestamp'] . "\n";
        echo "  API: " . $orgData['api_retrieval_timestamp'] . "\n\n";
        exit(1);
    }
    
    // Test 5: Test certificates data
    $certificatesData = OrganizationsAPI::getAllCertificatesEarnedRowsAllRange('Sacramento');
    echo "✅ Certificates data retrieved: " . count($certificatesData) . " rows\n\n";
    
    // Test 6: Test organizations data
    $organizationsData = OrganizationsAPI::getAllOrganizationsDataAllRange();
    echo "✅ Organizations data retrieved: " . count($organizationsData) . " organizations\n\n";
    
    echo "🎉 All tests passed! Timestamp functionality is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    exit(1);
} 