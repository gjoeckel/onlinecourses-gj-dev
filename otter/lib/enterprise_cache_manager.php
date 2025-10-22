<?php
/**
 * Enterprise Cache Manager
 * Handles enterprise-specific cache directories and file operations
 * Ensures data isolation between different enterprises
 */
class EnterpriseCacheManager {
    private static $instance = null;
    private $enterprise_code;
    private $cache_dir;
    private $enterprise_cache_dir;

    private function __construct() {
        $this->enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
        $this->cache_dir = __DIR__ . '/../cache';
        $this->enterprise_cache_dir = $this->cache_dir . '/' . $this->enterprise_code;

        // Ensure enterprise-specific cache directory exists
        if (!is_dir($this->enterprise_cache_dir)) {
            mkdir($this->enterprise_cache_dir, 0777, true);
        }
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
     * Get enterprise-specific cache directory
     */
    public function getEnterpriseCacheDir() {
        return $this->enterprise_cache_dir;
    }

    /**
     * Get path for a specific cache file
     */
    public function getCacheFilePath($filename) {
        return $this->enterprise_cache_dir . '/' . $filename;
    }

    /**
     * Get all-registrants-data.json path for current enterprise
     */
    public function getRegistrantsCachePath() {
        return $this->getCacheFilePath('all-registrants-data.json');
    }

    /**
     * Get all-submissions-data.json path for current enterprise
     */
    public function getSubmissionsCachePath() {
        return $this->getCacheFilePath('all-submissions-data.json');
    }

    /**
     * Get registrations.json path for current enterprise
     */
    public function getRegistrationsCachePath() {
        return $this->getCacheFilePath('registrations.json');
    }

    /**
     * Get enrollments.json path for current enterprise
     */
    public function getEnrollmentsCachePath() {
        return $this->getCacheFilePath('enrollments.json');
    }

    /**
     * Get certificates.json path for current enterprise
     */
    public function getCertificatesCachePath() {
        return $this->getCacheFilePath('certificates.json');
    }

    /**
     * Check if a cache file exists for current enterprise
     */
    public function cacheFileExists($filename) {
        return file_exists($this->getCacheFilePath($filename));
    }

    /**
     * Read cache file for current enterprise
     */
    public function readCacheFile($filename) {
        $filepath = $this->getCacheFilePath($filename);
        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return null;
        }

        return json_decode($content, true);
    }

    /**
     * Write cache file for current enterprise
     */
    public function writeCacheFile($filename, $data) {
        $filepath = $this->getCacheFilePath($filename);
        $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return file_put_contents($filepath, $json_content) !== false;
    }

    /**
     * Delete cache file for current enterprise
     */
    public function deleteCacheFile($filename) {
        $filepath = $this->getCacheFilePath($filename);
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }

    /**
     * Clear all cache files for current enterprise
     */
    public function clearAllCache() {
        $cache_files = [
            'all-registrants-data.json',
            'all-submissions-data.json',
            'registrations.json',
            'enrollments.json',
            'certificates.json'
        ];

        $success = true;
        foreach ($cache_files as $filename) {
            if (!$this->deleteCacheFile($filename)) {
                $success = false;
            }
        }

        // Also clean up old session files
        $this->clearSessionFiles();

        return $success;
    }

    /**
     * Clear old session files from cache directory
     * @param int $maxAge Maximum age in seconds (default: 24 hours)
     * @return array Results of cleanup operation
     */
    public function clearSessionFiles($maxAge = 24 * 60 * 60) {
        $sessionDir = $this->cache_dir; // Use main cache directory where sessions are stored
        $cutoffTime = time() - $maxAge;
        $deletedCount = 0;
        $errorCount = 0;
        $errors = [];

        if (!is_dir($sessionDir)) {
            return [
                'success' => false,
                'deleted' => 0,
                'errors' => 1,
                'message' => 'Session directory does not exist'
            ];
        }

        $files = glob($sessionDir . '/sess_*');
        if ($files === false) {
            return [
                'success' => false,
                'deleted' => 0,
                'errors' => 1,
                'message' => 'Failed to read session directory'
            ];
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                if ($fileTime < $cutoffTime) {
                    if (unlink($file)) {
                        $deletedCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to delete: " . basename($file);
                    }
                }
            }
        }

        return [
            'success' => $errorCount === 0,
            'deleted' => $deletedCount,
            'errors' => $errorCount,
            'error_details' => $errors,
            'message' => "Deleted $deletedCount old session files" . ($errorCount > 0 ? " with $errorCount errors" : "")
        ];
    }

    /**
     * Get cache file info (exists, size, modified time)
     */
    public function getCacheFileInfo($filename) {
        $filepath = $this->getCacheFilePath($filename);

        if (!file_exists($filepath)) {
            return [
                'exists' => false,
                'size' => 0,
                'modified' => null,
                'enterprise' => $this->enterprise_code
            ];
        }

        return [
            'exists' => true,
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
            'enterprise' => $this->enterprise_code
        ];
    }

    /**
     * Validate that cache data belongs to current enterprise
     */
    public function validateCacheData($data) {
        // Add enterprise validation logic here if needed
        // For now, we rely on directory isolation
        return true;
    }
}