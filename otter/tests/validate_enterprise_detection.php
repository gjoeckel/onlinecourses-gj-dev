<?php
// Validation test for enterprise detection alignment between login and dashboard
require_once __DIR__ . '/lib/unified_database.php';

echo "=== Enterprise Detection Validation Test ===\n\n";

$db = new UnifiedDatabase();

// Test cases: organization passwords and admin passwords
$testCases = [
    // CSU organizations
    ['password' => '2950', 'expected_enterprise' => 'csu', 'type' => 'organization', 'name' => "Chancellor's Office"],
    ['password' => '8471', 'expected_enterprise' => 'csu', 'type' => 'organization', 'name' => 'Bakersfield'],
    ['password' => '4000', 'expected_enterprise' => 'csu', 'type' => 'admin', 'name' => 'ADMIN'],
    
    // CCC organizations
    ['password' => '0523', 'expected_enterprise' => 'ccc', 'type' => 'organization', 'name' => 'Allan Hancock College'],
    ['password' => '5079', 'expected_enterprise' => 'ccc', 'type' => 'organization', 'name' => 'American River College'],
    ['password' => '4091', 'expected_enterprise' => 'ccc', 'type' => 'admin', 'name' => 'ADMIN'],
];

$allPassed = true;

foreach ($testCases as $testCase) {
    $password = $testCase['password'];
    $expectedEnterprise = $testCase['expected_enterprise'];
    $expectedType = $testCase['type'];
    $expectedName = $testCase['name'];
    
    echo "Testing password: $password\n";
    
    // Test 1: Login process (validateLogin)
    $loginResult = $db->validateLogin($password);
    $loginEnterprise = $loginResult ? $loginResult['enterprise'] : null;
    $loginName = $loginResult ? $loginResult['name'] : null;
    $loginIsAdmin = $loginResult ? (isset($loginResult['is_admin']) && $loginResult['is_admin'] === true) : false;
    
    // Test 2: Dashboard process (getOrganizationByPassword)
    $dashboardResult = $db->getOrganizationByPassword($password);
    $dashboardEnterprise = $dashboardResult ? $dashboardResult['enterprise'] : null;
    $dashboardName = $dashboardResult ? $dashboardResult['name'] : null;
    $dashboardIsAdmin = $dashboardResult ? (isset($dashboardResult['is_admin']) && $dashboardResult['is_admin'] === true) : false;
    
    // Validate results
    $loginPassed = ($loginEnterprise === $expectedEnterprise) && 
                   ($loginName === $expectedName) && 
                   ($expectedType === 'admin' ? $loginIsAdmin : !$loginIsAdmin);
    
    $dashboardPassed = ($dashboardEnterprise === $expectedEnterprise) && 
                       ($dashboardName === $expectedName) && 
                       ($expectedType === 'admin' ? $dashboardIsAdmin : !$dashboardIsAdmin);
    
    $aligned = ($loginEnterprise === $dashboardEnterprise) && 
               ($loginName === $dashboardName) && 
               ($loginIsAdmin === $dashboardIsAdmin);
    
    echo "  Expected: $expectedName ($expectedEnterprise, $expectedType)\n";
    echo "  Login: " . ($loginName ?? 'null') . " (" . ($loginEnterprise ?? 'null') . ", " . ($loginIsAdmin ? 'admin' : 'org') . ")\n";
    echo "  Dashboard: " . ($dashboardName ?? 'null') . " (" . ($dashboardEnterprise ?? 'null') . ", " . ($dashboardIsAdmin ? 'admin' : 'org') . ")\n";
    echo "  Login Pass: " . ($loginPassed ? 'YES' : 'NO') . "\n";
    echo "  Dashboard Pass: " . ($dashboardPassed ? 'YES' : 'NO') . "\n";
    echo "  Aligned: " . ($aligned ? 'YES' : 'NO') . "\n";
    
    if (!$loginPassed || !$dashboardPassed || !$aligned) {
        $allPassed = false;
        echo "  ❌ FAILED\n";
    } else {
        echo "  ✅ PASSED\n";
    }
    echo "\n";
}

// Test enterprise-specific admin password detection
echo "=== Admin Password Enterprise Detection Test ===\n\n";

$adminPasswords = [
    '4000' => 'csu',
    '4091' => 'ccc'
];

foreach ($adminPasswords as $password => $expectedEnterprise) {
    echo "Testing admin password: $password (expected: $expectedEnterprise)\n";
    
    $result = $db->validateLogin($password);
    if ($result && isset($result['is_admin']) && ($result['is_admin'] === true || $result['is_admin'] === 1)) {
        $enterprise = $result['enterprise'];
        $passed = ($enterprise === $expectedEnterprise);
        echo "  Result: $enterprise - " . ($passed ? '✅ PASSED' : '❌ FAILED') . "\n";
        if (!$passed) $allPassed = false;
    } else {
        echo "  Result: Not recognized as admin - ❌ FAILED\n";
        $allPassed = false;
    }
    echo "\n";
}

// Summary
echo "=== SUMMARY ===\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED: Enterprise detection is aligned between login and dashboard processes\n";
} else {
    echo "❌ SOME TESTS FAILED: Enterprise detection needs to be fixed\n";
}

echo "\n=== Test Complete ===\n";
?> 