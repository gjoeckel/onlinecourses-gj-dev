<?php
require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

class TargetFolderUrlTest {
    
    public function runTests() {
        echo "Testing Universal Relative Paths URL generation...\n";
        
        $this->testRelativeUrlGeneration();
        $this->testDashboardUrlGeneration();
        $this->testAdminUrlGeneration();
        $this->testLoginUrlGeneration();
        
        echo "All Universal Relative Paths tests passed!\n";
    }
    
    private function testRelativeUrlGeneration() {
        // Test relative URL generation
        $relativeUrl = UnifiedEnterpriseConfig::getRelativeUrl('css/admin.css');
        $expected = 'css/admin.css';
        
        if ($relativeUrl !== $expected) {
            throw new Exception("Relative URL test failed. Expected: $expected, Got: $relativeUrl");
        }
        
        echo "✓ Relative URL generation test passed\n";
    }
    
    private function testDashboardUrlGeneration() {
        // Test dashboard URL generation
        $dashboardUrl = UnifiedEnterpriseConfig::generateUrl('1234', 'dashboard');
        $expected = 'dashboard.php?org=1234';
        
        if ($dashboardUrl !== $expected) {
            throw new Exception("Dashboard URL test failed. Expected: $expected, Got: $dashboardUrl");
        }
        
        echo "✓ Dashboard URL generation test passed\n";
    }
    
    private function testAdminUrlGeneration() {
        // Test admin URL generation
        $adminUrl = UnifiedEnterpriseConfig::generateUrl('', 'admin');
        $expected = 'admin/index.php?auth=1';
        
        if ($adminUrl !== $expected) {
            throw new Exception("Admin URL test failed. Expected: $expected, Got: $adminUrl");
        }
        
        echo "✓ Admin URL generation test passed\n";
    }
    
    private function testLoginUrlGeneration() {
        // Test login URL generation
        $loginUrl = UnifiedEnterpriseConfig::generateUrl('', 'login');
        $expected = 'login.php';
        
        if ($loginUrl !== $expected) {
            throw new Exception("Login URL test failed. Expected: $expected, Got: $loginUrl");
        }
        
        echo "✓ Login URL generation test passed\n";
    }
}

// Run the test
try {
    $test = new TargetFolderUrlTest();
    $test->runTests();
} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
    exit(1);
} 