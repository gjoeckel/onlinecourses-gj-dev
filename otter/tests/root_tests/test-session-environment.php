<?php
/**
 * Test Session-Based Environment Detection
 */

session_start();

// Include required files
require_once __DIR__ . '/lib/utils.php';

// Simulate different login scenarios
echo "Testing Session-Based Environment Detection\n";
echo "==========================================\n\n";

// Test 1: No session environment (should default to production)
echo "1. No session environment:\n";
unset($_SESSION['environment']);
require_once __DIR__ . '/lib/unified_enterprise_config.php';
UnifiedEnterpriseConfig::init();
echo "   Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";

// Test 2: Local environment in session
echo "2. Local environment in session:\n";
$_SESSION['environment'] = 'local';
echo "   Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";

// Test 3: Production environment in session
echo "3. Production environment in session:\n";
$_SESSION['environment'] = 'production';
echo "   Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n\n";

// Test 4: URL generation test
echo "4. URL Generation Test:\n";
echo "   Dashboard URL: " . UnifiedEnterpriseConfig::generateUrl('test123', 'dashboard') . "\n";
echo "   Login URL: " . UnifiedEnterpriseConfig::generateUrl('test123', 'login') . "\n\n";

// Test 5: Direct link generation
echo "5. Direct Link Generation:\n";
require_once __DIR__ . '/lib/direct_link.php';
echo "   Environment: " . getEnvironment() . "\n";
echo "   Direct Link for test123: " . DirectLink::getDirectLink('test123') . "\n\n";

echo "Test completed.\n";
?> 