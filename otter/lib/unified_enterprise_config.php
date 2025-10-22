<?php
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/unified_database.php';

/**
 * Unified Enterprise Configuration Manager
 * Handles loading and accessing enterprise-specific configuration using the new three-file system
 */
class UnifiedEnterpriseConfig {
    private static $config = null;
    private static $enterprise_code = null;
    private static $environment = null;
    private static $database = null;

    /**
     * Initialize the enterprise configuration
     * @param string $enterprise_code The enterprise code (e.g., 'csu', 'ccc')
     */
    public static function init($enterprise_code = null) {
        // If no enterprise code provided, try to detect from context
        if (empty($enterprise_code)) {
            $enterprise_code = self::detectEnterprise();
        }
        self::$enterprise_code = $enterprise_code;
        self::loadConfig();
        self::loadEnvironment();
    }

    /**
     * Detect enterprise from organization password
     * @param string $password The 4-digit password
     * @return string|false Enterprise code if found, false otherwise
     */
    public static function detectEnterpriseFromPassword($password) {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }

        // Use UnifiedDatabase to get enterprise from password
        $enterprise = self::$database->getEnterpriseByPassword($password);

        if ($enterprise) {
            return $enterprise;
        }

        // Fallback: check if it's an admin password
        $adminPasswords = self::getAdminPasswords();
        foreach ($adminPasswords as $ent => $adminPass) {
            if ($adminPass === $password) {
                return $ent;
            }
        }

        return false;
    }

    /**
     * Detect enterprise from URL path parameters
     * @return string|false Enterprise code if found, false otherwise
     */
    public static function detectEnterpriseFromUrl() {
        // Check for organization parameter in URL path
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $pathParts = explode('/', trim($requestUri, '/'));

        // Look for dashboard.php/{password} pattern
        foreach ($pathParts as $i => $part) {
            if ($part === 'dashboard.php' && isset($pathParts[$i + 1])) {
                $password = $pathParts[$i + 1];
                // Clean the password (remove query string if present)
                $password = explode('?', $password)[0];
                if (preg_match('/^\d{4}$/', $password)) {
                    return self::detectEnterpriseFromPassword($password);
                }
            }
        }

        return false;
    }

    /**
     * Get admin passwords from configuration
     * @return array Array of admin passwords by enterprise
     */
    private static function getAdminPasswords() {
        $passwordsFile = __DIR__ . '/../config/passwords.json';
        if (!file_exists($passwordsFile)) {
            return [];
        }

        $passwordsData = json_decode(file_get_contents($passwordsFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $passwordsData['admin_passwords'] ?? [];
    }

    /**
     * Detect enterprise from session, URL parameter, or default
     * @return string Enterprise code
     */
    public static function detectEnterprise() {
        // Start session if not already started (suppress warnings if headers already sent)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Check session first (for logged-in users)
        if (isset($_SESSION['enterprise_code'])) {
            return $_SESSION['enterprise_code'];
        }

        // Check URL parameter 'ent' (for initial access)
        if (isset($_GET['ent'])) {
            return $_GET['ent'];
        }

        // Check URL parameter 'enterprise' (for initial access)
        if (isset($_GET['enterprise'])) {
            return $_GET['enterprise'];
        }

        // Check for enterprise detection from URL path (clean URLs)
        $enterpriseFromUrl = self::detectEnterpriseFromUrl();
        if ($enterpriseFromUrl) {
            return $enterpriseFromUrl;
        }

        // Check for enterprise detection from organization parameter
        if (isset($_GET['organization']) && preg_match('/^\d{4}$/', $_GET['organization'])) {
            $enterpriseFromPassword = self::detectEnterpriseFromPassword($_GET['organization']);
            if ($enterpriseFromPassword) {
                return $enterpriseFromPassword;
            }
        }

        // Check for enterprise detection from org parameter
        if (isset($_GET['org']) && preg_match('/^\d{4}$/', $_GET['org'])) {
            $enterpriseFromPassword = self::detectEnterpriseFromPassword($_GET['org']);
            if ($enterpriseFromPassword) {
                return $enterpriseFromPassword;
            }
        }

        // No enterprise detected - return false to indicate detection failure
        return false;
    }

    /**
     * Load environment (simplified - always production for universal paths)
     */
    private static function loadEnvironment() {
        // Always use production for universal relative paths
        self::$environment = 'production';
    }

    /**
     * Initialize enterprise from simple detection
     * This should be called early in the application lifecycle
     */
    public static function initializeFromRequest() {
        // Detect enterprise (this will handle session management)
        $enterprise_code = self::detectEnterprise();

        // Check if enterprise detection failed
        if ($enterprise_code === false) {
            return [
                'enterprise_code' => false,
                'environment' => self::$environment,
                'error' => 'enterprise_not_detected'
            ];
        }

        // Set class variables
        self::$enterprise_code = $enterprise_code;

        // Initialize configuration
        self::init($enterprise_code);

        return [
            'enterprise_code' => $enterprise_code,
            'environment' => self::$environment
        ];
    }

    /**
     * Load configuration from enterprise-specific config file
     */
    private static function loadConfig() {
        $enterprise_code = self::$enterprise_code;
        $config_file = __DIR__ . "/../config/$enterprise_code.config";

        if (!file_exists($config_file)) {
            throw new Exception("Enterprise configuration file not found: $config_file");
        }

        $json_content = file_get_contents($config_file);
        if ($json_content === false) {
            throw new Exception("Could not read enterprise configuration file");
        }

        $config = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in enterprise configuration: " . json_last_error_msg());
        }

        // Validate required sections
        $required_sections = ['enterprise', 'google_sheets', 'settings', 'api'];
        foreach ($required_sections as $section) {
            if (!isset($config[$section])) {
                throw new Exception("Missing required configuration section: $section");
            }
        }

        // Validate enterprise section
        if (!isset($config['enterprise']['code'])) {
            throw new Exception("Invalid enterprise code in configuration");
        }

        self::$config = $config;
    }

    /**
     * Get the current enterprise code
     * @return string The enterprise code
     */
    public static function getEnterpriseCode() {
        return self::$enterprise_code ?? self::detectEnterprise();
    }

    /**
     * Get enterprise information
     * @return array Enterprise information
     */
    public static function getEnterprise() {
        return self::$config['enterprise'] ?? [];
    }

    /**
     * Get all organizations for the current enterprise
     * @return array Array of organizations
     */
    public static function getOrganizations() {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->getOrganizationsByEnterprise(self::getEnterpriseCode());
    }

    /**
     * Get organization by password
     * @param string $password The 4-digit password
     * @return array|false Organization data if found, false otherwise
     */
    public static function getOrganizationByPassword($password) {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->getOrganizationByPassword($password);
    }

    /**
     * Get organization by name
     * @param string $name The organization name
     * @return array|false Organization data if found, false otherwise
     */
    public static function getOrganizationByName($name) {
        $organizations = self::getOrganizations();
        foreach ($organizations as $org) {
            if (isset($org['name']) && $org['name'] === $name) {
                return $org;
            }
        }
        return false;
    }

    /**
     * Get Google Sheets configuration
     * @return array Google Sheets configuration
     */
    public static function getGoogleSheets() {
        return self::$config['google_sheets'] ?? [];
    }

    /**
     * Get specific sheet configuration
     * @param string $sheet_type The sheet type (e.g., 'registrants', 'submissions')
     * @return array|false Sheet configuration if found, false otherwise
     */
    public static function getSheetConfig($sheet_type) {
        $sheets = self::getGoogleSheets();
        return $sheets[$sheet_type] ?? false;
    }

    /**
     * Get settings configuration
     * @return array Settings configuration
     */
    public static function getSettings() {
        return self::$config['settings'] ?? [];
    }

    /**
     * Get API configuration
     * @return array API configuration
     */
    public static function getApi() {
        return self::$config['api'] ?? [];
    }

    /**
     * Get configuration value by key
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }

    /**
     * Get full configuration
     * @return array Full configuration
     */
    public static function getFullConfig() {
        return self::$config;
    }

    /**
     * Get environment
     * @return string Current environment
     */
    public static function getEnvironment() {
        return self::$environment ?? 'production';
    }

    /**
     * Check if running in local environment
     * @return bool True if local, false otherwise
     */
    public static function isLocal() {
        return self::getEnvironment() === 'local';
    }

    /**
     * Check if running in production environment
     * @return bool True if production, false otherwise
     */
    public static function isProduction() {
        return self::getEnvironment() === 'production';
    }

    /**
     * Get Google API key
     * @return string Google API key
     */
    public static function getGoogleApiKey() {
        $api = self::getApi();
        return $api['google_api_key'] ?? '';
    }

    /**
     * Get start date
     * @return string Start date
     */
    public static function getStartDate() {
        $settings = self::getSettings();
        return $settings['start_date'] ?? '';
    }

    /**
     * Get cache TTL
     * @return int Cache TTL in seconds
     */
    public static function getCacheTtl() {
        $settings = self::getSettings();
        return $settings['cache_ttl'] ?? 21600;
    }

    /**
     * Get timezone
     * @return string Timezone
     */
    public static function getTimezone() {
        $settings = self::getSettings();
        return $settings['timezone'] ?? 'America/Los_Angeles';
    }

    /**
     * Get date format
     * @return string Date format
     */
    public static function getDateFormat() {
        $settings = self::getSettings();
        return $settings['date_format'] ?? 'm-d-y';
    }

    /**
     * Get time format
     * @return string Time format
     */
    public static function getTimeFormat() {
        $settings = self::getSettings();
        return $settings['time_format'] ?? 'g:i A';
    }

    /**
     * Check if password is valid for any organization
     * @param string $password The 4-digit password
     * @return bool True if valid, false otherwise
     */
    public static function isValidOrganizationPassword($password) {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->isValidPassword($password);
    }

    /**
     * Get admin password for the current enterprise
     * @return string|false Admin password if found, false otherwise
     */
    public static function getAdminPassword() {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->getAdminPassword(self::getEnterpriseCode());
    }

    /**
     * Get admin organization for the current enterprise
     * @return array|false Admin organization data if found, false otherwise
     */
    public static function getAdminOrganization() {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->getAdminOrganization(self::getEnterpriseCode());
    }

    /**
     * Check if password belongs to admin organization
     * @param string $password The 4-digit password
     * @return bool True if admin, false otherwise
     */
    public static function isAdminOrganization($password) {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->isAdminPassword($password);
    }

    /**
     * Get URL generator instance
     * @return UnifiedUrlGenerator
     */
    public static function getUrlGenerator() {
        if (self::$database === null) {
            self::$database = new UnifiedDatabase();
        }
        return self::$database->getUrlGenerator();
    }

    /**
     * Generate URL for organization (simplified for universal relative paths)
     * @param string $password The 4-digit password
     * @param string $type The URL type (dashboard, dashboard_clean, dashboard_org, admin, login, reports, settings)
     * @return string Generated URL
     */
    public static function generateUrl($password, $type = 'dashboard') {
        switch ($type) {
            case 'dashboard':
            case 'dashboard_org':
                return "dashboard.php?org={$password}";
            case 'admin':
                return "admin/index.php?auth=1";
            case 'login':
                return "login.php";
            case 'reports':
                return "reports/";
            case 'settings':
                return "settings/";
            default:
                return "dashboard.php?org={$password}";
        }
    }

    /**
     * Get relative URL (simplified for universal paths)
     * @param string $path The path
     * @return string Relative URL
     */
    public static function getRelativeUrl($path = '') {
        // For universal relative paths, just return the path as-is
        return $path;
    }

    /**
     * Get enterprise name
     * @return string Enterprise name
     */
    public static function getEnterpriseName() {
        $enterprise = self::getEnterprise();
        return $enterprise['name'] ?? '';
    }

    /**
     * Get display name
     * @return string Display name
     */
    public static function getDisplayName() {
        $enterprise = self::getEnterprise();
        return $enterprise['display_name'] ?? '';
    }

    /**
     * Check if enterprise has groups configured
     * @return bool True if enterprise has groups configured, false otherwise
     */
    public static function getHasGroups() {
        $captions = self::get('reports_table_captions', []);
        return isset($captions['groups']) && !empty($captions['groups']);
    }

    /**
     * Get registrants workbook ID
     * @return string Registrants workbook ID
     */
    public static function getRegistrantsWorkbookId() {
        $googleSheets = self::getGoogleSheets();
        return $googleSheets['registrants']['workbook_id'] ?? '';
    }

    /**
     * Get submissions workbook ID
     * @return string Submissions workbook ID
     */
    public static function getSubmissionsWorkbookId() {
        $googleSheets = self::getGoogleSheets();
        return $googleSheets['submissions']['workbook_id'] ?? '';
    }
}