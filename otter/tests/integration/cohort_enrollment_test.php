<?php
/**
 * Test to verify that enrollments are calculated by cohort, not by individual dates
 */

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../../lib/data_processor.php';

echo "=== Cohort Enrollment Calculation Test ===\n\n";

// Initialize configuration with explicit enterprise
UnifiedEnterpriseConfig::init('csu');
$cacheManager = EnterpriseCacheManager::getInstance();

// Load registrants data
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
if (!$registrantsCache || !isset($registrantsCache['data'])) {
    echo "ERROR: No registrants data found\n";
    echo "Cache file path: " . $cacheManager->getRegistrantsCachePath() . "\n";
    exit(1);
}

$registrantsData = $registrantsCache['data'];
echo "Loaded " . count($registrantsData) . " registrant rows\n\n";

// Test 1: Check enrollment calculation for a specific month (June 2025)
echo "Test 1: June 2025 (06-01-25 to 06-30-25)\n";
$juneEnrollments = DataProcessor::processRegistrantsData($registrantsData, '06-01-25', '06-30-25');
echo "  - Registrations: " . count($juneEnrollments['registrations']) . "\n";
echo "  - Enrollments: " . count($juneEnrollments['enrollments']) . "\n";
echo "  - Certificates: " . count($juneEnrollments['certificates']) . "\n";

// Analyze enrollments by cohort
$cohortCounts = [];
foreach ($juneEnrollments['enrollments'] as $row) {
    $cohort = isset($row[3]) ? trim($row[3]) : '';
    $year = isset($row[4]) ? trim($row[4]) : '';
    $cohortKey = $cohort . '-' . $year;
    $cohortCounts[$cohortKey] = ($cohortCounts[$cohortKey] ?? 0) + 1;
}

echo "  - Enrollments by cohort:\n";
foreach ($cohortCounts as $cohort => $count) {
    echo "    * $cohort: $count enrollments\n";
}

// Test 2: Check enrollment calculation for Q1 2024 (should include multiple cohorts)
echo "\nTest 2: Q1 2024 (07-01-24 to 09-30-24)\n";
$q1Enrollments = DataProcessor::processRegistrantsData($registrantsData, '07-01-24', '09-30-24');
echo "  - Registrations: " . count($q1Enrollments['registrations']) . "\n";
echo "  - Enrollments: " . count($q1Enrollments['enrollments']) . "\n";
echo "  - Certificates: " . count($q1Enrollments['certificates']) . "\n";

// Analyze enrollments by cohort
$q1CohortCounts = [];
foreach ($q1Enrollments['enrollments'] as $row) {
    $cohort = isset($row[3]) ? trim($row[3]) : '';
    $year = isset($row[4]) ? trim($row[4]) : '';
    $cohortKey = $cohort . '-' . $year;
    $q1CohortCounts[$cohortKey] = ($q1CohortCounts[$cohortKey] ?? 0) + 1;
}

echo "  - Enrollments by cohort:\n";
foreach ($q1CohortCounts as $cohort => $count) {
    echo "    * $cohort: $count enrollments\n";
}

// Test 3: Verify that enrollments are NOT filtered by individual registration dates
echo "\nTest 3: Verification - Enrollments should be cohort-based\n";

// Get all enrollments for a specific cohort (e.g., 06-25)
$cohort06_25_enrollments = [];
foreach ($registrantsData as $row) {
    $enrolled = isset($row[2]) && $row[2] === 'Yes';
    $cohort = isset($row[3]) ? trim($row[3]) : '';
    $year = isset($row[4]) ? trim($row[4]) : '';
    
    if ($enrolled && $cohort === '06' && $year === '25') {
        $cohort06_25_enrollments[] = $row;
    }
}

echo "  - Total enrollments for cohort 06-25: " . count($cohort06_25_enrollments) . "\n";

// Check if June 2025 date range includes all 06-25 enrollments
$june06_25_count = 0;
foreach ($juneEnrollments['enrollments'] as $row) {
    $cohort = isset($row[3]) ? trim($row[3]) : '';
    $year = isset($row[4]) ? trim($row[4]) : '';
    if ($cohort === '06' && $year === '25') {
        $june06_25_count++;
    }
}

echo "  - June 2025 date range includes $june06_25_count enrollments from cohort 06-25\n";

if ($june06_25_count === count($cohort06_25_enrollments)) {
    echo "  ✅ PASS: All 06-25 cohort enrollments are included in June 2025 date range\n";
} else {
    echo "  ❌ FAIL: Not all 06-25 cohort enrollments are included in June 2025 date range\n";
    echo "    Expected: " . count($cohort06_25_enrollments) . ", Got: $june06_25_count\n";
}

echo "\n=== Test Complete ===\n";
?> 