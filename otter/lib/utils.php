<?php
// utils.php - Shared utility functions

function getOrganizationCacheFile($organizationName, $cacheDir) {
    // Convert to lowercase, replace non-alphanumeric with dash, and trim dashes
    $safeName = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $organizationName));
    $safeName = trim($safeName, '-');
    return rtrim($cacheDir, '/\\') . '/' . $safeName . '.json';
}

/**
 * Get the current environment (simplified for universal relative paths)
 * @return string Always returns 'local' for universal relative paths
 */
function getEnvironment() {
    // For universal relative paths, always return 'local'
    return 'local';
}