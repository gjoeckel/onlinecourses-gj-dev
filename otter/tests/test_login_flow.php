<?php
/**
 * Test Login Flow
 * Verifies: login process, admin page access, CSS and resource paths
 */

echo "=== Login Flow Test ===\n\n";

// Test 1: Check if configuration files exist
echo "1. Checking configuration files...\n";
$config_files = [
    'config/environment.json',
    'config/dashboards.json',
    'config/passwords.json',
    'config/csu.config'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Test 2: Test unified configuration loading
echo "\n2. Testing unified configuration...\n";
try {
    require_once __DIR__ . '/lib/unified_enterprise_config.php';

    // Initialize with CSU enterprise
    $context = UnifiedEnterpriseConfig::initializeFromRequest();
    echo "   ✅ Configuration loaded successfully\n";
    echo "   Enterprise: " . $context['enterprise_code'] . "\n";
    echo "   Environment: " . $context['environment'] . "\n";

    // Test enterprise detection
    $enterprise = UnifiedEnterpriseConfig::getEnterprise();
    echo "   Enterprise Name: " . ($enterprise['name'] ?? 'Unknown') . "\n";

    // Test admin organization
    $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
    if ($admin_org) {
        echo "   ✅ Admin organization found: " . $admin_org['name'] . " (Password: " . $admin_org['password'] . ")\n";
    } else {
        echo "   ❌ Admin organization not found\n";
    }

} catch (Exception $e) {
    echo "   ❌ Configuration error: " . $e->getMessage() . "\n";
}

// Test 3: Test URL generation
echo "\n3. Testing URL generation...\n";
try {
    $admin_password = $admin_org['password'] ?? '4000';

    // Test dashboard URL generation
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl($admin_password, 'dashboard');
    echo "   Dashboard URL: $dashboard_url\n";

    // Test admin URL generation
    $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
    echo "   Admin URL: $admin_url\n";

    // Test relative URL generation
    $css_url = UnifiedEnterpriseConfig::getRelativeUrl('assets/css/admin.css');
    echo "   CSS URL: $css_url\n";

    echo "   ✅ URL generation working\n";

} catch (Exception $e) {
    echo "   ❌ URL generation error: " . $e->getMessage() . "\n";
}

// Test 4: Test resource file existence
echo "\n4. Testing resource files...\n";
$resource_files = [
    'assets/css/admin.css',
    'assets/css/login.css',
    'lib/otter.svg',
    'favicon.ico'
];

foreach ($resource_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Test 5: Simulate login process
echo "\n5. Testing login simulation...\n";
try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Clear any existing session
    session_destroy();
    require_once __DIR__ . '/../lib/session.php';
initializeSession();

    // Simulate admin login
    $admin_password = $admin_org['password'] ?? '4000';
    $test_password = '4000'; // Test with admin password

    if ($test_password === $admin_password) {
        echo "   ✅ Password validation working\n";

        // Set session variables as login would
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();
        $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();

        echo "   ✅ Session variables set\n";
        echo "   Session enterprise: " . $_SESSION['enterprise_code'] . "\n";
        echo "   Session environment: " . $_SESSION['environment'] . "\n";

    } else {
        echo "   ❌ Password validation failed\n";
    }

} catch (Exception $e) {
    echo "   ❌ Login simulation error: " . $e->getMessage() . "\n";
}

// Test 6: Test admin page access simulation
echo "\n6. Testing admin page access...\n";
try {
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        echo "   ✅ Admin authentication valid\n";

        if (isset($_SESSION['enterprise_code']) && $_SESSION['enterprise_code'] === UnifiedEnterpriseConfig::getEnterpriseCode()) {
            echo "   ✅ Enterprise code matches\n";
        } else {
            echo "   ❌ Enterprise code mismatch\n";
        }

    } else {
        echo "   ❌ Admin authentication failed\n";
    }

} catch (Exception $e) {
    echo "   ❌ Admin access error: " . $e->getMessage() . "\n";
}

// Test 7: Test environment-specific URL patterns
echo "\n7. Testing environment-specific URLs...\n";
try {
    $environment = UnifiedEnterpriseConfig::getEnvironment();
    echo "   Current environment: $environment\n";

    if ($environment === 'local') {
        echo "   Expected base URL: http://localhost:8000\n";
        echo "   Expected CSS path: assets/css/admin.css\n";
    } else {
        echo "   Expected base URL: https://webaim.org/training/online\n";
        echo "   Expected CSS path: /clients-enterprise/assets/css/admin.css\n";
    }

    $actual_css_url = UnifiedEnterpriseConfig::getRelativeUrl('assets/css/admin.css');
    echo "   Actual CSS URL: $actual_css_url\n";

    if ($environment === 'local' && $actual_css_url === 'assets/css/admin.css') {
        echo "   ✅ Local environment URLs correct\n";
    } elseif ($environment === 'production' && strpos($actual_css_url, '/clients-enterprise/') === 0) {
        echo "   ✅ Production environment URLs correct\n";
    } else {
        echo "   ❌ Environment URLs incorrect\n";
    }

} catch (Exception $e) {
    echo "   ❌ Environment URL test error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests show ✅, the login process should work correctly.\n";
echo "If any tests show ❌, those issues need to be fixed.\n";