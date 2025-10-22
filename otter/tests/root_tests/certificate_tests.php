<?php
/**
 * Consolidated Certificate Tests
 * Combines certificate page testing and certificate earners validation
 * Run with: php tests/root_tests/certificate_tests.php [enterprise]
 */

// Start session first to avoid headers already sent error
require_once __DIR__ . '/../../lib/session.php';
initializeSession();

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/enterprise_cache_manager.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== Consolidated Certificate Tests ===\n";
echo "Enterprise: $enterprise\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Initialize enterprise and environment
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$cacheManager = EnterpriseCacheManager::getInstance();

echo "✅ Enterprise: " . $context['enterprise_code'] . "\n";
echo "✅ Environment: " . $context['environment'] . "\n\n";

// Filter by issue date in range
function in_range($date, $start, $end) {
    $d = DateTime::createFromFormat('m-d-y', $date);
    $s = DateTime::createFromFormat('m-d-y', $start);
    $e = DateTime::createFromFormat('m-d-y', $end);
    if (!$d || !$s || !$e) return false;
    return $d >= $s && $d <= $e;
}

// Test 1: Certificate Page Functionality
echo "=== Test 1: Certificate Page Functionality ===\n";

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

// Test 2: Certificate Earners Data Validation
echo "=== Test 2: Certificate Earners Data Validation ===\n";

// Test certificate earners data for specific organizations
$testOrgs = ['Fullerton', 'Long Beach', 'Sacramento'];

foreach ($testOrgs as $org) {
    echo "Testing organization: $org\n";
    
    // Load cache data
    $registrationsCache = $cacheManager->readCacheFile('registrations.json');
    $enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
    $certificatesCache = $cacheManager->readCacheFile('certificates.json');
    
    $registrations = $registrationsCache ?? [];
    $enrollments = $enrollmentsCache ?? [];
    $certificates = $certificatesCache ?? [];
    
    // Find organization data
    $orgRegistrations = array_filter($registrations, function($row) use ($org) {
        return isset($row[9]) && trim($row[9]) === $org;
    });
    
    $orgEnrollments = array_filter($enrollments, function($row) use ($org) {
        return isset($row[9]) && trim($row[9]) === $org;
    });
    
    $orgCertificates = array_filter($certificates, function($row) use ($org) {
        return isset($row[9]) && trim($row[9]) === $org;
    });
    
    echo "  - Registrations: " . count($orgRegistrations) . "\n";
    echo "  - Enrollments: " . count($orgEnrollments) . "\n";
    echo "  - Certificates: " . count($orgCertificates) . "\n";
    
    if (!empty($orgCertificates)) {
        echo "  - Sample certificate earners:\n";
        $sample = array_slice($orgCertificates, 0, 2);
        foreach ($sample as $row) {
            $first = $row[5] ?? '';
            $last = $row[6] ?? '';
            $email = $row[7] ?? '';
            $issued = $row[11] ?? '';
            echo "    * {$first} {$last} ({$email}) - Issued: {$issued}\n";
        }
    }
    
    echo "\n";
}

// Test 3: Cache Data Structure Validation
echo "=== Test 3: Cache Data Structure Validation ===\n";

// Check cache files exist and have valid structure
$cacheFiles = [
    'all-registrants-data.json' => 'Registrants Data',
    'registrations.json' => 'Registrations',
    'enrollments.json' => 'Enrollments',
    'certificates.json' => 'Certificates'
];

foreach ($cacheFiles as $filename => $description) {
    $cacheData = $cacheManager->readCacheFile($filename);
    
    if ($cacheData === null) {
        echo "❌ $description: Cache file not found or invalid\n";
    } else {
        $count = is_array($cacheData) ? count($cacheData) : 0;
        echo "✅ $description: $count records\n";
        
        if ($count > 0 && is_array($cacheData)) {
            $sample = array_slice($cacheData, 0, 1);
            $keys = array_keys($sample[0]);
            echo "   Sample keys: " . implode(', ', array_slice($keys, 0, 5)) . "...\n";
        }
    }
}

echo "\n=== Certificate Tests Complete ===\n";
?> 