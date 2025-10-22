<?php
/**
 * Enterprise Features Utility
 *
 * Centralized utility for detecting enterprise-specific features
 * without hardcoding enterprise codes.
 */

require_once __DIR__ . '/unified_enterprise_config.php';

class EnterpriseFeatures {

    /**
     * Check if current enterprise supports groups functionality
     *
     * @return bool True if enterprise supports groups
     */
    public static function supportsGroups() {
        return UnifiedEnterpriseConfig::getHasGroups();
    }

    /**
     * Check if current enterprise supports quarterly presets
     *
     * @return bool True if enterprise supports quarterly presets
     */
    public static function supportsQuarterlyPresets() {
        $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
        // Check if enterprise config has quarterly preset support
        $settings = UnifiedEnterpriseConfig::getSettings();
        return isset($settings['supports_quarterly_presets']) && $settings['supports_quarterly_presets'];
    }

    /**
     * Get enterprise-specific features configuration
     *
     * @return array Features configuration for current enterprise
     */
    public static function getFeatures() {
        $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
        $settings = UnifiedEnterpriseConfig::getSettings();

        return [
            'supports_groups' => self::supportsGroups(),
            'supports_quarterly_presets' => self::supportsQuarterlyPresets(),
            'enterprise_code' => $enterpriseCode,
            'display_name' => $settings['display_name'] ?? 'Enterprise'
        ];
    }

    /**
     * Check if a specific feature is supported by current enterprise
     *
     * @param string $feature Feature name to check
     * @return bool True if feature is supported
     */
    public static function hasFeature($feature) {
        $features = self::getFeatures();
        return isset($features[$feature]) && $features[$feature];
    }
}