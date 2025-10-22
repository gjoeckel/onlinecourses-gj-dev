<?php
// Integration test for session-based environment detection
session_start();

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

// Test 1: No session environment (should default to production)
unset($_SESSION['environment']);
UnifiedEnterpriseConfig::init();
echo "Test 1: No session environment\n";
echo "  Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n";
echo "  Dashboard URL: " . UnifiedEnterpriseConfig::generateUrl('', 'dashboard') . "\n\n";

// Test 2: Local environment in session
$_SESSION['environment'] = 'local';
UnifiedEnterpriseConfig::init();
echo "Test 2: Local environment in session\n";
echo "  Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n";
echo "  Dashboard URL: " . UnifiedEnterpriseConfig::generateUrl('', 'dashboard') . "\n\n";

// Test 3: Production environment in session
$_SESSION['environment'] = 'production';
UnifiedEnterpriseConfig::init();
echo "Test 3: Production environment in session\n";
echo "  Environment: " . UnifiedEnterpriseConfig::getEnvironment() . "\n";
echo "  Dashboard URL: " . UnifiedEnterpriseConfig::generateUrl('', 'dashboard') . "\n\n";

echo "Session environment integration test completed.\n"; 