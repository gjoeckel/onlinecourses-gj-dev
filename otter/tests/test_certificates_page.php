<?php
// Start session first to avoid headers already sent error
require_once __DIR__ . '/../lib/session.php';
initializeSession();

require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/enterprise_cache_manager.php';

// Initialize enterprise and environment
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$cacheManager = EnterpriseCacheManager::getInstance();

echo "=== Certificates Page Test ===\n";
echo "Enterprise: " . $context['enterprise_code'] . "\n";
echo "Environment: " . $context['environment'] . "\n\n";

// Filter by issue date in range
function in_range($date, $start, $end) {
    $d = DateTime::createFromFormat('m-d-y', $date);
    $s = DateTime::createFromFormat('m-d-y', $start);
    $e = DateTime::createFromFormat('m-d-y', $end);
    if (!$d || !$s || !$e) return false;
    return $d >= $s && $d <= $e;
}

// Test with different date ranges
$testRanges = [
    ['start' => '05-06-24', 'end' => '06-28-25', 'description' => 'All range'],
    ['start' => '01-01-25', 'end' => '03-31-25', 'description' => 'Q1 2025'],
    ['start' => '06-01-25', 'end' => '06-30-25', 'description' => 'June 2025']
];

foreach ($testRanges as $range) {
    echo "Testing date range: {$range['description']} ({$range['start']} to {$range['end']})\n";
    
    // Simulate the certificates.php logic
    $start = $range['start'];
    $end = $range['end'];
    
    // Load registrants data from cache
    $registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
    $registrantsData = $registrantsCache['data'] ?? [];
    
    echo "  - Total registrants in cache: " . count($registrantsData) . "\n";
    
    // Get the minimum start date from configuration
    $minStartDate = UnifiedEnterpriseConfig::getStartDate();
    
    $isAllRange = ($start === $minStartDate && $end === date('m-d-y'));
    
    // Column indices
    $certificateIdx = 10; // Certificate
    $issuedIdx = 11;     // Issued
    
    if ($isAllRange) {
        // For 'All', include all Certificate == 'Yes'
        $filtered = array_filter($registrantsData, function($row) use ($certificateIdx) {
            return isset($row[$certificateIdx]) && trim($row[$certificateIdx]) === 'Yes';
        });
    } else {
        // For other ranges, filter by Issued in range and Certificate == 'Yes'
        $filtered = array_filter($registrantsData, function($row) use ($start, $end, $certificateIdx, $issuedIdx) {
            return isset($row[$certificateIdx], $row[$issuedIdx]) && 
                   trim($row[$certificateIdx]) === 'Yes' && 
                   preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$issuedIdx]) && 
                   in_range($row[$issuedIdx], $start, $end);
        });
    }
    
    echo "  - Certificate earners found: " . count($filtered) . "\n";
    
    if (!empty($filtered)) {
        echo "  - Sample certificate earners:\n";
        $sample = array_slice($filtered, 0, 3);
        foreach ($sample as $row) {
            $first = $row[5] ?? '';
            $last = $row[6] ?? '';
            $email = $row[7] ?? '';
            $org = $row[9] ?? '';
            $issued = $row[11] ?? '';
            echo "    * {$first} {$last} ({$email}) - {$org} - Issued: {$issued}\n";
        }
    }
    
    echo "\n";
}

echo "=== Test Complete ===\n";
?> 