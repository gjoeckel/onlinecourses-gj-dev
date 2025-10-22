<?php
// Start session first to avoid headers already sent error
require_once __DIR__ . '/../lib/session.php';
initializeSession();

require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/api/organizations_api.php';
require_once __DIR__ . '/lib/enterprise_cache_manager.php';

// Initialize enterprise and environment
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$cacheManager = EnterpriseCacheManager::getInstance();

echo "=== Certificate Earners Test ===\n";
echo "Enterprise: " . $context['enterprise_code'] . "\n";
echo "Environment: " . $context['environment'] . "\n\n";

// Test certificate earners data for a few organizations
$testOrgs = ['Fullerton', 'Long Beach', 'Sacramento'];

foreach ($testOrgs as $org) {
    echo "Testing organization: $org\n";
    
    try {
        $certificatesData = OrganizationsAPI::getAllCertificatesEarnedRowsAllRange($org);
        
        echo "  - Found " . count($certificatesData) . " certificate earners\n";
        
        if (!empty($certificatesData)) {
            echo "  - Sample data:\n";
            $sample = array_slice($certificatesData, 0, 3);
            foreach ($sample as $row) {
                echo "    * {$row['first']} {$row['last']} ({$row['email']}) - Cohort: {$row['cohort']}, Year: {$row['year']}\n";
            }
        }
        
        // Check for data quality issues
        $issues = [];
        foreach ($certificatesData as $row) {
            if (empty($row['first']) || empty($row['last'])) {
                $issues[] = "Missing name data";
            }
            if (empty($row['email'])) {
                $issues[] = "Missing email";
            }
            if (empty($row['cohort']) || empty($row['year'])) {
                $issues[] = "Missing cohort/year";
            }
        }
        
        if (!empty($issues)) {
            echo "  - Data quality issues found: " . implode(', ', array_unique($issues)) . "\n";
        } else {
            echo "  - Data quality: OK\n";
        }
        
    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test the cache data structure
echo "=== Cache Data Structure Test ===\n";
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');

if ($registrantsCache) {
    echo "Cache file found\n";
    echo "Global timestamp: " . ($registrantsCache['global_timestamp'] ?? 'Not set') . "\n";
    echo "Data rows: " . count($registrantsCache['data'] ?? []) . "\n";
    
    // Check for certificate earners in raw data
    $certificateCount = 0;
    $orgIdx = 9; // Organization column
    $certificateIdx = 10; // Certificate column
    
    foreach ($registrantsCache['data'] as $row) {
        if (isset($row[$orgIdx], $row[$certificateIdx]) && 
            trim($row[$certificateIdx]) === 'Yes') {
            $certificateCount++;
        }
    }
    
    echo "Total certificate earners in raw data: $certificateCount\n";
    
} else {
    echo "Cache file not found or empty\n";
}

echo "\n=== Test Complete ===\n";
?> 