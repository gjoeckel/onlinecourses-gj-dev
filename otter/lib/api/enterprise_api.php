<?php
require_once __DIR__ . '/../output_buffer.php';
startJsonResponse();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Add proper cache headers to replace cache busting
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../unified_enterprise_config.php';
require_once __DIR__ . '/../unified_database.php';
require_once __DIR__ . '/../utils.php';

try {
    // Initialize the unified configuration system
    $context = UnifiedEnterpriseConfig::initializeFromRequest();

    // Clear any potential file system cache to ensure fresh data
    clearstatcache();

    // Get all organizations from the unified database
    $db = new UnifiedDatabase();
    $organizations = $db->getAllOrganizations();

    // Build organizations data with dashboard URLs (simplified for universal paths)
    $organizationsData = [];
    foreach ($organizations as $org) {
        $orgData = [
            'name' => $org['name'],
            'password' => $org['password'],
            'enterprise' => $org['enterprise'],
            'is_admin' => $org['is_admin'] ?? false
        ];

        // Generate dashboard URLs for non-admin organizations (query parameter format)
        if (!($org['is_admin'] ?? false)) {
            $orgData['dashboard_url_local'] = "dashboard.php?org={$org['password']}";
            $orgData['dashboard_url_production'] = "dashboard.php?org={$org['password']}";
        } else {
            $orgData['dashboard_url_local'] = 'N/A';
            $orgData['dashboard_url_production'] = 'N/A';
        }

        $organizationsData[] = $orgData;
    }

    // Build response data
    $data = [
        'organizations' => $organizationsData,
        'current_environment' => $context['environment'],
        'enterprise' => $context['enterprise_code'],
        'minStartDate' => UnifiedEnterpriseConfig::getStartDate()
    ];

    // Add debugging information
    $data['debug'] = [
        'environment' => $context['environment'],
        'enterprise_code' => $context['enterprise_code'],
        'organizations_count' => count($organizationsData),
        'config_source' => 'unified_system',
        'timestamp' => time(),
        'cache_cleared' => true
    ];

    sendJsonResponse($data, true);

} catch (Exception $e) {
    require_once __DIR__ . '/../error_messages.php';
    sendJsonErrorWithStatus(ErrorMessages::getTechnicalDifficulties(), 500);
}