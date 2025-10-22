<?php
/**
 * CSRF Protection Utilities
 * Handles CSRF token generation, validation, and form protection
 */

/**
 * Generate a CSRF token
 * @return string The generated token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @return bool True if token is valid
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate a CSRF token input field
 * @return string HTML input field with CSRF token
 */
function generateCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request
 * @return bool True if token is valid
 */
function validateCSRFTokenFromPost() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    
    $token = $_POST['csrf_token'] ?? '';
    return validateCSRFToken($token);
}

/**
 * Validate CSRF token from GET request
 * @return bool True if token is valid
 */
function validateCSRFTokenFromGet() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return false;
    }
    
    $token = $_GET['csrf_token'] ?? '';
    return validateCSRFToken($token);
}

/**
 * Regenerate CSRF token (for security)
 */
function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Check if CSRF protection is required for current request
 * @return bool True if CSRF protection should be applied
 */
function isCSRFProtectionRequired() {
    // Skip CSRF for GET requests (except for sensitive operations)
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
        return false;
    }
    
    // Apply CSRF protection to all POST requests
    return true;
} 