<?php
require_once __DIR__ . '/lib/unified_database.php';

echo "=== Admin Password Debug Test ===\n\n";

$db = new UnifiedDatabase();

// Test admin passwords
$adminPasswords = ['4000', '4091'];

foreach ($adminPasswords as $password) {
    echo "Testing admin password: $password\n";
    
    $result = $db->validateLogin($password);
    
    if ($result) {
        echo "  Found: " . $result['name'] . "\n";
        echo "  Enterprise: " . $result['enterprise'] . "\n";
        echo "  Is Admin: " . (isset($result['is_admin']) && $result['is_admin'] ? 'YES' : 'NO') . "\n";
        echo "  Full result: " . print_r($result, true) . "\n";
    } else {
        echo "  Not found\n";
    }
    echo "\n";
}

// Test getOrganizationByPassword method
echo "=== Testing getOrganizationByPassword ===\n\n";

foreach ($adminPasswords as $password) {
    echo "Testing getOrganizationByPassword: $password\n";
    
    $result = $db->getOrganizationByPassword($password);
    
    if ($result) {
        echo "  Found: " . $result['name'] . "\n";
        echo "  Enterprise: " . $result['enterprise'] . "\n";
        echo "  Is Admin: " . (isset($result['is_admin']) && $result['is_admin'] ? 'YES' : 'NO') . "\n";
    } else {
        echo "  Not found\n";
    }
    echo "\n";
}
?> 