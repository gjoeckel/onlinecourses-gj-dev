<?php
/**
 * Test Session Persistence Across Requests
 */

require_once __DIR__ . '/../lib/session.php';
initializeSession();

echo "Session Persistence Test\n";
echo "=======================\n\n";

// Test 1: Set environment and check persistence
echo "1. Setting environment to production...\n";
$_SESSION['environment'] = 'production';
echo "   Session environment set to: " . $_SESSION['environment'] . "\n";

// Test 2: Simulate a new request (like settings page load)
echo "\n2. Simulating new request...\n";
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/unified_enterprise_config.php';

echo "   Session environment: " . ($_SESSION['environment'] ?? 'not set') . "\n";
echo "   getEnvironment(): " . getEnvironment() . "\n";
echo "   UnifiedEnterpriseConfig::getEnvironment(): " . UnifiedEnterpriseConfig::getEnvironment() . "\n";

// Test 3: Simulate API request
echo "\n3. Simulating API request...\n";
require_once __DIR__ . '/lib/direct_link.php';
require_once __DIR__ . '/lib/api/enterprise_api.php';

$jsonFile = DirectLink::getEnterpriseJsonPath();
$data = json_decode(file_get_contents($jsonFile), true);
$data['current_environment'] = getEnvironment();

echo "   API current_environment: " . $data['current_environment'] . "\n";

// Test 4: Check if session persists after multiple operations
echo "\n4. Testing session persistence after operations...\n";
$testPassword = '1234';
$directLink = DirectLink::getDirectLink($testPassword);
echo "   DirectLink generated: $directLink\n";
echo "   Session environment after operation: " . ($_SESSION['environment'] ?? 'not set') . "\n";

echo "\nTest completed.\n";