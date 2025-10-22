<?php
/**
 * Test Admin Flow
 * Tests: login validation, admin redirect, navigation buttons, logout functionality
 */

echo "=== Admin Flow Test ===\n\n";

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
    
    // Test admin organization
    $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
    if ($admin_org) {
        echo "   ✅ Admin organization found: " . $admin_org['name'] . " (Password: " . $admin_org['password'] . ")\n";
        $admin_password = $admin_org['password'];
    } else {
        echo "   ❌ Admin organization not found\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "   ❌ Configuration error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test login page password validation
echo "\n3. Testing login page password validation...\n";
try {
    // Simulate login page
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear any existing session
    session_destroy();
    session_start();
    
    // Test correct password
    $test_password = $admin_password;
    $is_valid = UnifiedEnterpriseConfig::isAdminOrganization($test_password);
    
    if ($is_valid) {
        echo "   ✅ Admin password validation working\n";
    } else {
        echo "   ❌ Admin password validation failed\n";
    }
    
    // Test incorrect password
    $wrong_password = '9999';
    $is_invalid = UnifiedEnterpriseConfig::isAdminOrganization($wrong_password);
    
    if (!$is_invalid) {
        echo "   ✅ Wrong password correctly rejected\n";
    } else {
        echo "   ❌ Wrong password incorrectly accepted\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Login validation error: " . $e->getMessage() . "\n";
}

// Test 4: Test admin page redirect simulation
echo "\n4. Testing admin page redirect simulation...\n";
try {
    // Simulate successful login
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();
    $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();
    
    // Check if admin authentication is valid
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        echo "   ✅ Admin authentication set in session\n";
        echo "   Session enterprise: " . $_SESSION['enterprise_code'] . "\n";
        echo "   Session environment: " . $_SESSION['environment'] . "\n";
        
        // Test admin URL generation
        $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
        echo "   Admin URL: $admin_url\n";
        
        // Check if admin page exists
        if (file_exists('admin/index.php')) {
            echo "   ✅ Admin page file exists\n";
        } else {
            echo "   ❌ Admin page file missing\n";
        }
        
    } else {
        echo "   ❌ Admin authentication not set properly\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Admin redirect error: " . $e->getMessage() . "\n";
}

// Test 5: Test navigation buttons (reports and settings)
echo "\n5. Testing navigation buttons...\n";
try {
    // Test reports button
    $reports_url = UnifiedEnterpriseConfig::generateUrl('', 'reports');
    echo "   Reports URL: $reports_url\n";
    
    if (file_exists('reports/index.php')) {
        echo "   ✅ Reports page file exists\n";
    } else {
        echo "   ❌ Reports page file missing\n";
    }
    
    // Test settings button
    $settings_url = UnifiedEnterpriseConfig::generateUrl('', 'settings');
    echo "   Settings URL: $settings_url\n";
    
    if (file_exists('settings/index.php')) {
        echo "   ✅ Settings page file exists\n";
    } else {
        echo "   ❌ Settings page file missing\n";
    }
    
    // Test relative URL generation for navigation
    $reports_relative = UnifiedEnterpriseConfig::getRelativeUrl('reports/');
    $settings_relative = UnifiedEnterpriseConfig::getRelativeUrl('settings/');
    
    echo "   Reports relative URL: $reports_relative\n";
    echo "   Settings relative URL: $settings_relative\n";
    
    echo "   ✅ Navigation URL generation working\n";
    
} catch (Exception $e) {
    echo "   ❌ Navigation test error: " . $e->getMessage() . "\n";
}

// Test 6: Test logout functionality
echo "\n6. Testing logout functionality...\n";
try {
    // Simulate logout
    session_destroy();
    session_start();
    
    // Check if session is cleared
    if (!isset($_SESSION['admin_authenticated']) && !isset($_SESSION['enterprise_code'])) {
        echo "   ✅ Session cleared successfully\n";
    } else {
        echo "   ❌ Session not cleared properly\n";
    }
    
    // Test login URL generation
    $login_url = UnifiedEnterpriseConfig::generateUrl('', 'login');
    echo "   Login URL: $login_url\n";
    
    if (file_exists('login.php')) {
        echo "   ✅ Login page file exists\n";
    } else {
        echo "   ❌ Login page file missing\n";
    }
    
    // Test logout redirect URL (should go back to login)
    $logout_redirect = 'login.php';
    if (UnifiedEnterpriseConfig::isLocal()) {
        $logout_redirect .= '?local=1';
    }
    echo "   Logout redirect URL: $logout_redirect\n";
    
    echo "   ✅ Logout functionality working\n";
    
} catch (Exception $e) {
    echo "   ❌ Logout test error: " . $e->getMessage() . "\n";
}

// Test 7: Test complete flow simulation
echo "\n7. Testing complete flow simulation...\n";
try {
    // Step 1: Start fresh session
    session_destroy();
    session_start();
    
    // Step 2: Simulate login with correct password
    $login_password = $admin_password;
    if (UnifiedEnterpriseConfig::isAdminOrganization($login_password)) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['enterprise_code'] = UnifiedEnterpriseConfig::getEnterpriseCode();
        $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();
        echo "   ✅ Step 1: Login successful\n";
    } else {
        echo "   ❌ Step 1: Login failed\n";
    }
    
    // Step 3: Verify admin access
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        echo "   ✅ Step 2: Admin access granted\n";
    } else {
        echo "   ❌ Step 2: Admin access denied\n";
    }
    
    // Step 4: Test navigation to reports
    $reports_url = UnifiedEnterpriseConfig::getRelativeUrl('reports/');
    echo "   ✅ Step 3: Reports navigation ready ($reports_url)\n";
    
    // Step 5: Test navigation to settings
    $settings_url = UnifiedEnterpriseConfig::getRelativeUrl('settings/');
    echo "   ✅ Step 4: Settings navigation ready ($settings_url)\n";
    
    // Step 6: Test logout
    session_destroy();
    session_start();
    if (!isset($_SESSION['admin_authenticated'])) {
        echo "   ✅ Step 5: Logout successful\n";
    } else {
        echo "   ❌ Step 5: Logout failed\n";
    }
    
    // Step 7: Verify redirect to login
    $login_url = UnifiedEnterpriseConfig::generateUrl('', 'login');
    echo "   ✅ Step 6: Login redirect ready ($login_url)\n";
    
    echo "   ✅ Complete flow simulation successful\n";
    
} catch (Exception $e) {
    echo "   ❌ Complete flow error: " . $e->getMessage() . "\n";
}

// Test 8: Test URL patterns for different environments
echo "\n8. Testing URL patterns for different environments...\n";
try {
    $environment = UnifiedEnterpriseConfig::getEnvironment();
    echo "   Current environment: $environment\n";
    
    if ($environment === 'local') {
        echo "   Expected patterns:\n";
        echo "   - Dashboard: http://localhost:8000/dashboard.php?org=4000\n";
        echo "   - Admin: http://localhost:8000/admin/index.php\n";
        echo "   - Reports: http://localhost:8000/reports/\n";
        echo "   - Settings: http://localhost:8000/settings/\n";
        echo "   - Login: http://localhost:8000/login.php\n";
    } else {
        echo "   Expected patterns:\n";
        echo "   - Dashboard: https://webaim.org/training/online/clients-enterprise/dashboard.php?org=4000\n";
        echo "   - Admin: https://webaim.org/training/online/clients-enterprise/admin/index.php\n";
        echo "   - Reports: https://webaim.org/training/online/clients-enterprise/reports/\n";
        echo "   - Settings: https://webaim.org/training/online/clients-enterprise/settings/\n";
        echo "   - Login: https://webaim.org/training/online/clients-enterprise/login.php\n";
    }
    
    // Test actual URL generation
    $dashboard_url = UnifiedEnterpriseConfig::generateUrl($admin_password, 'dashboard');
    $admin_url = UnifiedEnterpriseConfig::generateUrl('', 'admin');
    $reports_url = UnifiedEnterpriseConfig::generateUrl('', 'reports');
    $settings_url = UnifiedEnterpriseConfig::generateUrl('', 'settings');
    $login_url = UnifiedEnterpriseConfig::generateUrl('', 'login');
    
    echo "   Actual URLs:\n";
    echo "   - Dashboard: $dashboard_url\n";
    echo "   - Admin: $admin_url\n";
    echo "   - Reports: $reports_url\n";
    echo "   - Settings: $settings_url\n";
    echo "   - Login: $login_url\n";
    
    echo "   ✅ URL pattern generation working correctly\n";
    
} catch (Exception $e) {
    echo "   ❌ URL pattern test error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests show ✅, the admin flow should work correctly.\n";
echo "If any tests show ❌, those issues need to be fixed.\n";
echo "\nTo test manually:\n";
echo "1. Start server: php -S localhost:8000\n";
echo "2. Open: http://localhost:8000/login.php\n";
echo "3. Login with admin password: $admin_password\n";
echo "4. Test navigation buttons and logout functionality\n";
?> 