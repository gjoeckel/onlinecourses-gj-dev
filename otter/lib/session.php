<?php
// Centralized session configuration
function initializeSession() {
    // Set cache directory
    $cache_dir = __DIR__ . '/../cache';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }

    // Only modify session settings if session is not already active
    if (session_status() === PHP_SESSION_NONE) {
        // Check if headers have already been sent (prevents warnings)
        if (!headers_sent()) {
            // Set session path
            ini_set('session.save_path', $cache_dir);

            // Set session cookie parameters
            $cookie_params = [
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Secure in production
                'httponly' => true,
                'samesite' => 'Strict' // Stricter CSRF protection
            ];

            session_set_cookie_params($cookie_params);
        }

        // Start session (suppress warnings if headers already sent)
        @session_start();
    }
    // If session is already active, just ensure it exists (no warnings)
}

// Check if user is authenticated
function isAuthenticated() {
    // Check session timeout (30 minutes)
    $timeout = 30 * 60; // 30 minutes in seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        clearAuthentication();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

// Set authentication status
function setAuthenticated($status = true) {
    $_SESSION['admin_authenticated'] = $status;
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID for security
    if ($status === true) {
        session_regenerate_id(true);
    }
}

// Clear authentication
function clearAuthentication() {
    unset($_SESSION['admin_authenticated']);
}