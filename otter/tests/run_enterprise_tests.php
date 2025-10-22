<?php
/**
 * Enterprise-Specific Test Runner
 * Runs all tests for a specified enterprise
 */

require_once __DIR__ . '/test_base.php';

/**
 * Run all tests for a specific enterprise
 * @param string $enterprise_code The enterprise code to test
 * @return array Test results summary
 */
function runEnterpriseTests($enterprise_code) {
    echo "=== Testing Enterprise: " . strtoupper($enterprise_code) . " ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

    // Initialize test environment
    TestBase::initEnterprise($enterprise_code);

    $results = [
        'enterprise' => $enterprise_code,
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Run configuration tests
    echo "Running Configuration Tests...\n";
    $config_results = runConfigTests();
    $results['tests']['configuration'] = $config_results;
    $results['passed'] += $config_results['passed'];
    $results['failed'] += $config_results['failed'];
    $results['total'] += $config_results['total'];

    // Run API tests
    echo "\nRunning API Tests...\n";
    $api_results = runApiTests();
    $results['tests']['api'] = $api_results;
    $results['passed'] += $api_results['passed'];
    $results['failed'] += $api_results['failed'];
    $results['total'] += $api_results['total'];

    // Run login tests
    echo "\nRunning Login Tests...\n";
    $login_results = runLoginTests();
    $results['tests']['login'] = $login_results;
    $results['passed'] += $login_results['passed'];
    $results['failed'] += $login_results['failed'];
    $results['total'] += $login_results['total'];

    // Run data service tests
    echo "\nRunning Data Service Tests...\n";
    $data_results = runDataServiceTests();
    $results['tests']['data_service'] = $data_results;
    $results['passed'] += $data_results['passed'];
    $results['failed'] += $data_results['failed'];
    $results['total'] += $data_results['total'];

    // Run direct links tests
    echo "\nRunning Direct Links Tests...\n";
    $links_results = runDirectLinksTests();
    $results['tests']['direct_links'] = $links_results;
    $results['passed'] += $links_results['passed'];
    $results['failed'] += $links_results['failed'];
    $results['total'] += $links_results['total'];

    // Summary
    echo "\n=== Enterprise Test Summary ===\n";
    echo "Enterprise: " . strtoupper($enterprise_code) . "\n";
    echo "Total Tests: {$results['total']}\n";
    echo "Passed: {$results['passed']}\n";
    echo "Failed: {$results['failed']}\n";
    echo "Success Rate: " . ($results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0) . "%\n";

    if ($results['failed'] === 0) {
        echo "✅ All tests passed for " . strtoupper($enterprise_code) . "!\n";
    } else {
        echo "❌ Some tests failed for " . strtoupper($enterprise_code) . ".\n";
    }

    echo "\n";
    return $results;
}

/**
 * Run configuration tests
 * @return array Test results
 */
function runConfigTests() {
    $results = ['passed' => 0, 'failed' => 0, 'total' => 0];

    // Test enterprise configuration loading
    $results['total']++;
    if (TestBase::runTest('Enterprise Config Loading', function() {
        $enterprise = UnifiedEnterpriseConfig::getEnterprise();
        TestBase::assertNotNull($enterprise, 'Enterprise configuration should be loaded');
        TestBase::assertNotEmpty($enterprise['name'], 'Enterprise name should not be empty');
        TestBase::assertNotEmpty($enterprise['code'], 'Enterprise code should not be empty');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    // Test organizations loading
    $results['total']++;
    if (TestBase::runTest('Organizations Loading', function() {
        $organizations = UnifiedEnterpriseConfig::getOrganizations();
        TestBase::assertNotNull($organizations, 'Organizations should be loaded');
        TestBase::assertNotEmpty($organizations, 'Organizations array should not be empty');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    // Test admin organization
    $results['total']++;
    if (TestBase::runTest('Admin Organization', function() {
        $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
        TestBase::assertNotNull($admin_org, 'Admin organization should exist');
        TestBase::assertTrue($admin_org['is_admin'], 'Admin organization should have is_admin flag');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    // Test URL generation
    $results['total']++;
    if (TestBase::runTest('URL Generation', function() {
        $dashboard_url = UnifiedEnterpriseConfig::generateUrl('', 'dashboard');
        TestBase::assertNotNull($dashboard_url, 'Dashboard URL should be generated');
        TestBase::assertNotEmpty($dashboard_url, 'Dashboard URL should not be empty');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    return $results;
}

/**
 * Run API tests
 * @return array Test results
 */
function runApiTests() {
    $results = ['passed' => 0, 'failed' => 0, 'total' => 0];

    // Test API endpoint accessibility
    $results['total']++;
    if (TestBase::runTest('API Endpoint', function() {
        $api_file = __DIR__ . '/../lib/api/enterprise_api.php';
        TestBase::assertTrue(file_exists($api_file), 'API file should exist');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    return $results;
}

/**
 * Run login tests
 * @return array Test results
 */
function runLoginTests() {
    $results = ['passed' => 0, 'failed' => 0, 'total' => 0];

    // Test password validation
    $results['total']++;
    if (TestBase::runTest('Password Validation', function() {
        $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
        $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($admin_org['password']);
        TestBase::assertTrue($is_valid, 'Admin password should be valid');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    // Test session management
    $results['total']++;
    if (TestBase::runTest('Session Management', function() {
        // Start output buffering to prevent headers already sent warning
        ob_start();
require_once __DIR__ . '/../lib/session.php';
initializeSession();
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();

        $is_authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
        $enterprise_matches = isset($_SESSION['enterprise_code']) && $_SESSION['enterprise_code'] === UnifiedEnterpriseConfig::getEnterpriseCode();

        TestBase::assertTrue($is_authenticated, 'Authentication should be set');
        TestBase::assertTrue($enterprise_matches, 'Enterprise code should match');

        // Clean up
        ob_clean();
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    return $results;
}

/**
 * Run data service tests
 * @return array Test results
 */
function runDataServiceTests() {
    $results = ['passed' => 0, 'failed' => 0, 'total' => 0];

    // Test data service file existence
    $results['total']++;
    if (TestBase::runTest('Data Service File', function() {
        $data_service_file = __DIR__ . '/../lib/enterprise_data_service.php';
        TestBase::assertTrue(file_exists($data_service_file), 'Data service file should exist');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    return $results;
}

/**
 * Run direct links tests
 * @return array Test results
 */
function runDirectLinksTests() {
    $results = ['passed' => 0, 'failed' => 0, 'total' => 0];

    // Test direct link file existence
    $results['total']++;
    if (TestBase::runTest('Direct Link File', function() {
        $direct_link_file = __DIR__ . '/../lib/direct_link.php';
        TestBase::assertTrue(file_exists($direct_link_file), 'Direct link file should exist');
    })) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }

    return $results;
}

// If run directly, test the current enterprise
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    if (!isset($argv[1])) {
        die("Usage: php run_enterprise_tests.php <enterprise_code>\n");
    }
    $enterprise = $argv[1];
    runEnterpriseTests($enterprise);
}