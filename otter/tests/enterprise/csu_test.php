<?php
/**
 * CSU Enterprise Test Suite
 * Runs all tests specifically for CSU enterprise
 */

require_once __DIR__ . '/../run_enterprise_tests.php';

// Set the enterprise to test
TestBase::setEnterprise('csu');

echo "=== CSU Enterprise Test Suite ===\n";
echo "This test suite validates all functionality for the CSU enterprise.\n\n";

// Run all tests for CSU
$results = runEnterpriseTests('csu');

// Additional CSU-specific tests can be added here
echo "=== CSU-Specific Tests ===\n";

// Test CSU-specific configuration
TestBase::runTest('CSU Enterprise Name', function() {
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    TestBase::assertEquals('California State University', $enterprise['name'], 'CSU enterprise should have correct name');
});

TestBase::runTest('CSU Enterprise Code', function() {
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    TestBase::assertEquals('csu', $enterprise['code'], 'CSU enterprise should have correct code');
});

TestBase::runTest('CSU Organizations Count', function() {
    $organizations = UnifiedEnterpriseConfig::getOrganizations();
    TestBase::assertGreaterThan(20, count($organizations), 'CSU should have more than 20 organizations');
});

echo "\n=== CSU Test Suite Complete ===\n";
?> 