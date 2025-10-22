<?php
/**
 * Unified Refresh Service
 * Centralized data refresh logic to eliminate duplication across admin, dashboard, and reports
 */

require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/enterprise_data_service.php';
require_once __DIR__ . '/enterprise_cache_manager.php';

class UnifiedRefreshService {
    private static $instance = null;
    private $dataService;
    private $cacheManager;

    private function __construct() {
        $this->dataService = new EnterpriseDataService();
        $this->cacheManager = EnterpriseCacheManager::getInstance();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if data needs refresh (cache is stale or missing)
     * @param int $ttl Time to live in seconds (default: 3 hours for dashboard, 6 hours for reports)
     * @return bool True if refresh is needed
     */
    public function needsRefresh($ttl = 10800) { // 3 hours default
        $registrantsCacheFile = $this->cacheManager->getRegistrantsCachePath();

        if (!file_exists($registrantsCacheFile)) {
            return true;
        }

        $cacheAge = time() - filemtime($registrantsCacheFile);
        return $cacheAge > $ttl;
    }

    /**
     * Force refresh all data (admin manual refresh)
     * @return array Result with success/error status and counts
     * 
     * USES SAME APPROACH AS DASHBOARD - autoRefreshIfNeeded with TTL=0
     */
    public function forceRefresh() {
        try {
            // Use the same working approach as autoRefreshIfNeeded but with TTL=0 to always refresh
            // Set up the request parameters for refresh
            $startDate = UnifiedEnterpriseConfig::getStartDate();
            $endDate = date('m-d-y');
            $_REQUEST['start_date'] = $startDate;
            $_REQUEST['end_date'] = $endDate;
            $_REQUEST['force_refresh'] = '1';

            // Call the internal API to refresh data (same as dashboard)
            $apiResult = require_once __DIR__ . '/../reports/reports_api_internal.php';

            // Check if the API call was successful
            if (isset($apiResult['error'])) {
                return ['error' => $apiResult['error']];
            }

            // Return success with counts from the data service
            return [
                'registrations' => strval(count($this->dataService->getRegistrations())),
                'enrollments' => strval(count($this->dataService->getEnrollments())),
                'certificates' => strval(count($this->dataService->getCertificates()))
            ];

        } catch (Exception $e) {
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }
    }

    /**
     * Auto-refresh if needed (dashboard automatic refresh)
     * @param int $ttl Time to live in seconds (default: 3 hours)
     * @return bool True if refresh was performed, false if not needed
     */
    public function autoRefreshIfNeeded($ttl = 10800) { // 3 hours default
        if (!$this->needsRefresh($ttl)) {
            return false;
        }

        // Set up the request parameters for refresh
        $startDate = UnifiedEnterpriseConfig::getStartDate();
        $endDate = date('m-d-y');
        $_REQUEST['start_date'] = $startDate;
        $_REQUEST['end_date'] = $endDate;
        $_REQUEST['force_refresh'] = '1';

        // Call the internal API to refresh data
        $apiResult = require_once __DIR__ . '/../reports/reports_api_internal.php';

        return true;
    }

    /**
     * Get cache status for display
     * @return array Cache status information
     */
    public function getCacheStatus() {
        $registrantsCache = $this->cacheManager->readCacheFile('all-registrants-data.json');
        $timestamp = $registrantsCache['global_timestamp'] ?? null;

        return [
            'timestamp' => $timestamp,
            'needs_refresh' => $this->needsRefresh(),
            'registrations_count' => count($this->dataService->getRegistrations()),
            'enrollments_count' => count($this->dataService->getEnrollments()),
            'certificates_count' => count($this->dataService->getCertificates())
        ];
    }
}