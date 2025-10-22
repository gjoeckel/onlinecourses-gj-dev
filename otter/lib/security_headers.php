<?php
/**
 * Security Headers Utilities
 * Adds important security headers to HTTP responses
 */

/**
 * Set security headers for the application
 */
function setSecurityHeaders() {
    // Only set headers if they haven't been sent yet
    if (headers_sent()) {
        return;
    }
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';");
    
    // Prevent clickjacking
    header("X-Frame-Options: DENY");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Permissions Policy (formerly Feature Policy)
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // Cache control for sensitive pages
    if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || 
        strpos($_SERVER['REQUEST_URI'], '/settings/') !== false)) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
}

/**
 * Set CORS headers if needed
 */
function setCORSHeaders() {
    if (headers_sent()) {
        return;
    }
    
    // Only allow same-origin requests
    header("Access-Control-Allow-Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
}

/**
 * Initialize security for the application
 */
function initializeSecurity() {
    setSecurityHeaders();
    setCORSHeaders();
} 