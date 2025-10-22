<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'error_reporting' => ini_get('error_reporting'),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log'),
    'loaded_extensions' => [
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl'),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'openssl' => extension_loaded('openssl')
    ],
    'file_permissions' => [
        'config_readable' => is_readable('config/'),
        'cache_writable' => is_writable('cache/'),
        'logs_writable' => is_writable('./')
    ],
    'database_connection' => 'unknown',
    'enterprise_configs' => []
];

// Test database connection if possible
try {
    if (file_exists('lib/unified_database.php')) {
        require_once 'lib/unified_database.php';
        $db = new UnifiedDatabase();
        $health['database_connection'] = 'tested';
    }
} catch (Exception $e) {
    $health['database_connection'] = 'error: ' . $e->getMessage();
}

// Check enterprise configurations
if (file_exists('config/')) {
    $config_files = glob('config/*.config');
    foreach ($config_files as $config) {
        $health['enterprise_configs'][] = basename($config);
    }
}

// Check for recent errors
if (file_exists('php_errors.log')) {
    $health['recent_errors'] = count(file('php_errors.log'));
}

ob_start();
echo json_encode($health, JSON_PRETTY_PRINT);
ob_end_flush();
