<?php
/**
 * Base Test Class for Enterprise-Agnostic Testing
 * Provides common functionality for all tests
 */
class TestBase {
    protected static $test_enterprise = null;
    protected static $test_environment = null;
    
    /**
     * Set the enterprise to test
     * @param string $enterprise_code The enterprise code (e.g., 'csu', 'ccc')
     */
    public static function setEnterprise($enterprise_code) {
        self::$test_enterprise = $enterprise_code;
    }
    
    /**
     * Get the current test enterprise
     * @return string The enterprise code
     */
    public static function getEnterprise() {
        if (!self::$test_enterprise) {
            throw new Exception('Test enterprise not initialized. Call initEnterprise() first.');
        }
        return self::$test_enterprise;
    }
    
    /**
     * Set the environment to test
     * @param string $environment The environment ('local' or 'production')
     */
    public static function setEnvironment($environment) {
        self::$test_environment = $environment;
    }
    
    /**
     * Get the current test environment
     * @return string The environment
     */
    public static function getEnvironment() {
        if (!self::$test_environment) {
            throw new Exception('Test environment not initialized. Call setEnvironment() first.');
        }
        return self::$test_environment;
    }
    
    /**
     * Initialize enterprise configuration for testing
     * @param string $enterprise_code Optional enterprise code override
     */
    public static function initEnterprise($enterprise_code = null) {
        if ($enterprise_code) {
            self::setEnterprise($enterprise_code);
        }
        
        require_once __DIR__ . '/../lib/unified_enterprise_config.php';
        UnifiedEnterpriseConfig::init(self::getEnterprise());
    }
    
    /**
     * Assert that a condition is true
     * @param bool $condition The condition to test
     * @param string $message The assertion message
     */
    public static function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }
    
    /**
     * Assert that a condition is false
     * @param bool $condition The condition to test
     * @param string $message The assertion message
     */
    public static function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception("Assertion failed: $message");
        }
    }
    
    /**
     * Assert that two values are equal
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: Expected '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Assert that two values are not equal
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            throw new Exception("Assertion failed: Expected values to be different, but both are '$expected'. $message");
        }
    }
    
    /**
     * Assert that a value is not null
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception("Assertion failed: Value is null. $message");
        }
    }
    
    /**
     * Assert that a value is empty
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertEmpty($value, $message = '') {
        if (!empty($value)) {
            throw new Exception("Assertion failed: Value is not empty. $message");
        }
    }
    
    /**
     * Assert that a value is not empty
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            throw new Exception("Assertion failed: Value is empty. $message");
        }
    }
    
    /**
     * Assert that an array has a specific key
     * @param string $key The key to check for
     * @param array $array The array to check
     * @param string $message The assertion message
     */
    public static function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Array does not have key '$key'. $message");
        }
    }
    
    /**
     * Assert that a value is not false
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotFalse($value, $message = '') {
        if ($value === false) {
            throw new Exception("Assertion failed: Value is false. $message");
        }
    }
    
    /**
     * Assert that a URL is valid
     * @param string $url The URL to validate
     * @param string $message The assertion message
     */
    public static function assertValidUrl($url, $message = '') {
        if (!filter_var($url, FILTER_VALIDATE_URL) && $url !== 'N/A') {
            throw new Exception("Assertion failed: Invalid URL '$url'. $message");
        }
    }
    
    /**
     * Assert that a value is greater than another
     * @param mixed $expected The expected minimum value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertGreaterThan($expected, $actual, $message = '') {
        if ($actual <= $expected) {
            throw new Exception("Assertion failed: Expected value greater than '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Log a test result
     * @param string $status The test status ('PASS' or 'FAIL')
     * @param string $test_name The test name
     * @param string $message The test message
     */
    public static function logResult($status, $test_name, $message = '') {
        $status_icon = $status === 'PASS' ? '✅' : '❌';
        echo "[$status] $test_name: $message\n";
    }
    
    /**
     * Run a test with error handling
     * @param string $test_name The test name
     * @param callable $test_function The test function to run
     * @return bool True if test passed, false if failed
     */
    public static function runTest($test_name, $test_function) {
        try {
            $test_function();
            self::logResult('PASS', $test_name);
            return true;
        } catch (Exception $e) {
            self::logResult('FAIL', $test_name, $e->getMessage());
            return false;
        }
    }
}
?> 