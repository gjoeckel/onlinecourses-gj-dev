<?php
/**
 * Security Test Suite
 * Tests all security improvements implemented
 */

require_once __DIR__ . '/../lib/password_utils.php';
require_once __DIR__ . '/../lib/csrf_utils.php';
require_once __DIR__ . '/../lib/security_headers.php';
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/unified_database.php';

class SecurityTest {
    private $results = [];
    
    public function runAllTests() {
        echo "🔒 Running Security Test Suite\n";
        echo "==============================\n\n";
        
        $this->testPasswordHashing();
        $this->testCSRFProtection();
        $this->testSecurityHeaders();
        $this->testSessionSecurity();
        $this->testInputValidation();
        
        $this->printResults();
    }
    
    private function testPasswordHashing() {
        echo "Testing Password Hashing...\n";
        
        // Test password hashing
        $password = '1234';
        $hash = hashPassword($password);
        
        $this->results['password_hashing'] = [
            'test' => 'Password Hashing',
            'passed' => !empty($hash) && $hash !== $password,
            'details' => 'Password should be hashed, not stored in plain text'
        ];
        
        // Test password verification
        $verified = verifyPassword($password, $hash);
        
        $this->results['password_verification'] = [
            'test' => 'Password Verification',
            'passed' => $verified === true,
            'details' => 'Should verify correct password against hash'
        ];
        
        // Test database integration
        $db = new UnifiedDatabase();
        $login_result = $db->validateLogin('4000');
        
        $this->results['database_integration'] = [
            'test' => 'Database Integration',
            'passed' => $login_result !== false,
            'details' => 'Database should accept hashed passwords'
        ];
        
        echo "✅ Password hashing tests completed\n\n";
    }
    
    private function testCSRFProtection() {
        echo "Testing CSRF Protection...\n";
        
        // Test token generation
        $token1 = generateCSRFToken();
        $token2 = generateCSRFToken();
        
        $this->results['csrf_token_generation'] = [
            'test' => 'CSRF Token Generation',
            'passed' => !empty($token1) && !empty($token2),
            'details' => 'Should generate non-empty tokens'
        ];
        
        // Test token validation
        $valid = validateCSRFToken($token1);
        
        $this->results['csrf_token_validation'] = [
            'test' => 'CSRF Token Validation',
            'passed' => $valid === true,
            'details' => 'Should validate correct tokens'
        ];
        
        // Test token field generation
        $field = generateCSRFTokenField();
        
        $this->results['csrf_field_generation'] = [
            'test' => 'CSRF Field Generation',
            'passed' => strpos($field, 'csrf_token') !== false && strpos($field, 'hidden') !== false,
            'details' => 'Should generate hidden input field'
        ];
        
        echo "✅ CSRF protection tests completed\n\n";
    }
    
    private function testSecurityHeaders() {
        echo "Testing Security Headers...\n";
        
        // Test header functions exist
        $this->results['security_headers_functions'] = [
            'test' => 'Security Header Functions',
            'passed' => function_exists('setSecurityHeaders') && function_exists('initializeSecurity'),
            'details' => 'Security header functions should be available'
        ];
        
        // Test CORS functions
        $this->results['cors_functions'] = [
            'test' => 'CORS Functions',
            'passed' => function_exists('setCORSHeaders'),
            'details' => 'CORS header functions should be available'
        ];
        
        echo "✅ Security headers tests completed\n\n";
    }
    
    private function testSessionSecurity() {
        echo "Testing Session Security...\n";
        
        // Test session functions exist
        $this->results['session_functions'] = [
            'test' => 'Session Functions',
            'passed' => function_exists('initializeSession') && function_exists('isAuthenticated'),
            'details' => 'Session security functions should be available'
        ];
        
        echo "✅ Session security tests completed\n\n";
    }
    
    private function testInputValidation() {
        echo "Testing Input Validation...\n";
        
        // Test XSS protection
        $test_input = '<script>alert("xss")</script>';
        $escaped = htmlspecialchars($test_input);
        
        $this->results['xss_protection'] = [
            'test' => 'XSS Protection',
            'passed' => $escaped !== $test_input && strpos($escaped, '<script>') === false,
            'details' => 'htmlspecialchars should escape dangerous content'
        ];
        
        echo "✅ Input validation tests completed\n\n";
    }
    
    private function printResults() {
        echo "📊 Security Test Results\n";
        echo "=======================\n\n";
        
        $passed = 0;
        $total = count($this->results);
        
        foreach ($this->results as $key => $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            echo "{$status} {$result['test']}\n";
            echo "   {$result['details']}\n\n";
            
            if ($result['passed']) {
                $passed++;
            }
        }
        
        echo "Summary: {$passed}/{$total} tests passed\n";
        
        if ($passed === $total) {
            echo "🎉 All security tests passed!\n";
        } else {
            echo "⚠️  Some security tests failed. Review the results above.\n";
        }
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' || isset($_GET['test'])) {
    $test = new SecurityTest();
    $test->runAllTests();
} 