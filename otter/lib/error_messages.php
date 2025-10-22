<?php
// Centralized error messages for consistent user experience
class ErrorMessages {
    // Standard error messages
    const TECHNICAL_DIFFICULTIES = 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.';
    
    const GOOGLE_SERVICES_ISSUE = 'We are experiencing issues connecting to Google services. Please wait a few minutes and then retry. If problem persists, contact accessibledocs@webaim.org for support.';
    
    const INVALID_PASSWORD = 'Incorrect password. Support: accessibledocs@webaim.org';
    
    const EMPTY_PASSWORD = 'Please enter a password.';
    
    // Static methods for easy access
    public static function getTechnicalDifficulties() {
        return self::TECHNICAL_DIFFICULTIES;
    }
    
    public static function getGoogleServicesIssue() {
        return self::GOOGLE_SERVICES_ISSUE;
    }
    
    public static function getInvalidPassword() {
        return self::INVALID_PASSWORD;
    }
    
    public static function getEmptyPassword() {
        return self::EMPTY_PASSWORD;
    }
} 