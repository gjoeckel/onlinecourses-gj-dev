<?php
/**
 * Cache Utilities
 * Centralized cache management utilities for reports pages
 */
class CacheUtils {
    /**
     * Check if cache file is fresh based on TTL
     * @param object $cacheManager - Cache manager instance
     * @param string $filename - Cache filename to check
     * @param int $ttl - Time to live in seconds (default: 6 hours)
     * @return bool True if cache is fresh
     */
    public static function isCacheFresh($cacheManager, $filename, $ttl = 6 * 60 * 60) {
        if (!$cacheManager->cacheFileExists($filename)) {
            return false;
        }
        $json = $cacheManager->readCacheFile($filename);
        $cacheTimestamp = isset($json['global_timestamp']) ? $json['global_timestamp'] : null;
        if (!$cacheTimestamp) {
            return false;
        }
        $dt = DateTime::createFromFormat('m-d-y \a\t g:i A', $cacheTimestamp, new DateTimeZone('America/Los_Angeles'));
        if ($dt === false) {
            return false;
        }
        $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        return $diff < $ttl;
    }
    /**
     * Create timestamped data structure
     * @param array $data - Data to timestamp
     * @return array Data with timestamp added
     */
    public static function createTimestampedData($data) {
        $dt = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
        $formatted = $dt->format('m-d-y');
        $hour = $dt->format('g');
        $minute = $dt->format('i');
        $ampm = $dt->format('A');
        $time = $hour . ':' . $minute . ' ' . $ampm;
        $global_timestamp = $formatted . ' at ' . $time;
        return [
            'global_timestamp' => $global_timestamp,
            'data' => $data
        ];
    }
    /**
     * Validate date format MM-DD-YY
     * @param string $date - Date string to validate
     * @return bool True if valid format
     */
    public static function isValidMMDDYY($date) {
        return preg_match('/^\d{2}-\d{2}-\d{2}$/', $date);
    }
    /**
     * Check if date is within range
     * @param string $date - Date in MM-DD-YY format
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return bool True if date is within range
     */
    public static function inRange($date, $start, $end) {
        $d = DateTime::createFromFormat('m-d-y', $date);
        $s = DateTime::createFromFormat('m-d-y', $start);
        $e = DateTime::createFromFormat('m-d-y', $end);
        if (!$d || !$s || !$e) return false;
        return $d >= $s && $d <= $e;
    }
}
