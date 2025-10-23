

<?php
/**
 * Canvas Configuration Loader
 * Securely loads Canvas API credentials from master_includes file
 */

class CanvasConfig {
    private static $config = null;

    /**
     * Load Canvas configuration from master_includes file
     */
    public static function loadConfig() {
        if (self::$config !== null) {
            return self::$config;
        }

        // Path to the master_includes file - Check cursor-otter-dev first, then production
        $master_includes_path = '/Users/a00288946/cursor-global/projects/cursor-otter-dev/master_includes/onlinecourses_common.php';

        // Check if we're in the new location first
        if (!file_exists($master_includes_path)) {
            // Try the production location
            $master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
        }

        // Check if the file exists
        if (!file_exists($master_includes_path)) {
            return [
                'error' => 'Master includes file not found: ' . $master_includes_path
            ];
        }

        // Include the file to get the variables
        $CANVAS_API_TOKEN = null;
        $CANVAS_API_URL = null;

        // Include the file and capture the variables
        ob_start();
        include $master_includes_path;
        ob_end_clean();

        // Check if variables were loaded
        if (empty($CANVAS_API_TOKEN) || empty($CANVAS_API_URL)) {
            return [
                'error' => 'Canvas API credentials not found in master_includes file'
            ];
        }

        // Parse the Canvas URL to get base URL and account ID
        $parsed_url = parse_url($CANVAS_API_URL);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $account_id = '1'; // Correct account ID for usucourses.instructure.com

        self::$config = [
            'base_url' => $base_url,
            'api_token' => $CANVAS_API_TOKEN,
            'account_id' => $account_id,
            'full_api_url' => $CANVAS_API_URL
        ];

        return self::$config;
    }

    /**
     * Get Canvas API configuration
     */
    public static function getConfig() {
        return self::loadConfig();
    }

    /**
     * Test if configuration is valid
     */
    public static function isValid() {
        $config = self::getConfig();
        return !isset($config['error']);
    }

    /**
     * Get configuration for CanvasAPI class
     */
    public static function getCanvasAPIConfig() {
        $config = self::getConfig();

        if (isset($config['error'])) {
            return $config;
        }

        return [
            'base_url' => $config['base_url'],
            'api_token' => $config['api_token'],
            'account_id' => $config['account_id']
        ];
    }
}
?>
