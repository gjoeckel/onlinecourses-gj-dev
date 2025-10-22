<?php
// Debug script to test the complete refresh process
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_refresh_service.php';
require_once __DIR__ . '/../lib/enterprise_data_service.php';

// Simulate the refresh process step by step

echo "=== Testing CSU Enterprise Refresh Process ===\n";

// Step 1: Initialize enterprise configuration
echo "Step 1: Initializing enterprise configuration...\n";
UnifiedEnterpriseConfig::init('csu');
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
echo "Enterprise Code: $enterpriseCode\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";

// Step 2: Test EnterpriseDataService constructor
echo "\nStep 2: Testing EnterpriseDataService constructor...\n";
try {
    $dataService = new EnterpriseDataService();
    echo "✓ EnterpriseDataService created successfully\n";
} catch (Exception $e) {
    echo "✗ EnterpriseDataService creation failed: " . $e->getMessage() . "\n";
    exit;
}

// Step 3: Test UnifiedRefreshService
echo "\nStep 3: Testing UnifiedRefreshService...\n";
try {
    $refreshService = UnifiedRefreshService::getInstance();
    echo "✓ UnifiedRefreshService created successfully\n";
} catch (Exception $e) {
    echo "✗ UnifiedRefreshService creation failed: " . $e->getMessage() . "\n";
    exit;
}

// Step 4: Test forceRefresh method
echo "\nStep 4: Testing forceRefresh method...\n";
try {
    $result = $refreshService->forceRefresh();
    if (isset($result['error'])) {
        echo "✗ Refresh failed: " . $result['error'] . "\n";
    } elseif (isset($result['warning'])) {
        echo "⚠ Refresh completed with warning: " . $result['warning'] . "\n";
        if (isset($result['registrations'])) echo "Registrations: " . $result['registrations'] . "\n";
        if (isset($result['enrollments'])) echo "Enrollments: " . $result['enrollments'] . "\n";
        if (isset($result['certificates'])) echo "Certificates: " . $result['certificates'] . "\n";
    } else {
        echo "✓ Refresh completed successfully\n";
        if (isset($result['registrations'])) echo "Registrations: " . $result['registrations'] . "\n";
        if (isset($result['enrollments'])) echo "Enrollments: " . $result['enrollments'] . "\n";
        if (isset($result['certificates'])) echo "Certificates: " . $result['certificates'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Refresh failed with exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

echo "=== Testing DEMO Enterprise Refresh Process ===\n";

// Step 1: Initialize enterprise configuration
echo "Step 1: Initializing enterprise configuration...\n";
UnifiedEnterpriseConfig::init('demo');
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
echo "Enterprise Code: $enterpriseCode\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";

// Step 2: Test EnterpriseDataService constructor
echo "\nStep 2: Testing EnterpriseDataService constructor...\n";
try {
    $dataService = new EnterpriseDataService();
    echo "✓ EnterpriseDataService created successfully\n";
} catch (Exception $e) {
    echo "✗ EnterpriseDataService creation failed: " . $e->getMessage() . "\n";
    exit;
}

// Step 3: Test UnifiedRefreshService
echo "\nStep 3: Testing UnifiedRefreshService...\n";
try {
    $refreshService = UnifiedRefreshService::getInstance();
    echo "✓ UnifiedRefreshService created successfully\n";
} catch (Exception $e) {
    echo "✗ UnifiedRefreshService creation failed: " . $e->getMessage() . "\n";
    exit;
}

// Step 4: Test forceRefresh method
echo "\nStep 4: Testing forceRefresh method...\n";
try {
    $result = $refreshService->forceRefresh();
    if (isset($result['error'])) {
        echo "✗ Refresh failed: " . $result['error'] . "\n";
    } elseif (isset($result['warning'])) {
        echo "⚠ Refresh completed with warning: " . $result['warning'] . "\n";
        if (isset($result['registrations'])) echo "Registrations: " . $result['registrations'] . "\n";
        if (isset($result['enrollments'])) echo "Enrollments: " . $result['enrollments'] . "\n";
        if (isset($result['certificates'])) echo "Certificates: " . $result['certificates'] . "\n";
    } else {
        echo "✓ Refresh completed successfully\n";
        if (isset($result['registrations'])) echo "Registrations: " . $result['registrations'] . "\n";
        if (isset($result['enrollments'])) echo "Enrollments: " . $result['enrollments'] . "\n";
        if (isset($result['certificates'])) echo "Certificates: " . $result['certificates'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Refresh failed with exception: " . $e->getMessage() . "\n";
} 