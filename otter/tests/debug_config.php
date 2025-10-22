<?php
/**
 * Debug Config Test
 * Tests the configuration retrieval to see why getColumnIndex is failing
 */

// Set enterprise code directly
$_GET['enterprise'] = 'csu';

require_once __DIR__ . '/../lib/unified_enterprise_config.php';

echo "=== Debug Config Test ===\n\n";

// Test 1: Check full config
echo "Test 1: Full Config\n";
try {
    $fullConfig = UnifiedEnterpriseConfig::getFullConfig();
    
    echo "  - Full config type: " . gettype($fullConfig) . "\n";
    
    if (is_array($fullConfig)) {
        echo "  - Full config keys: " . implode(', ', array_keys($fullConfig)) . "\n";
        
        if (isset($fullConfig['google_sheets'])) {
            echo "  - Google sheets keys: " . implode(', ', array_keys($fullConfig['google_sheets'])) . "\n";
            
            if (isset($fullConfig['google_sheets']['registrants'])) {
                echo "  - Registrants keys: " . implode(', ', array_keys($fullConfig['google_sheets']['registrants'])) . "\n";
                
                if (isset($fullConfig['google_sheets']['registrants']['columns'])) {
                    echo "  - Registrants columns keys: " . implode(', ', array_keys($fullConfig['google_sheets']['registrants']['columns'])) . "\n";
                    
                    if (isset($fullConfig['google_sheets']['registrants']['columns']['Submitted'])) {
                        echo "  - Registrants Submitted index: " . $fullConfig['google_sheets']['registrants']['columns']['Submitted']['index'] . "\n";
                    } else {
                        echo "  - FAIL: Registrants Submitted column not found\n";
                    }
                } else {
                    echo "  - FAIL: Registrants columns not found\n";
                }
            } else {
                echo "  - FAIL: Registrants config not found\n";
            }
        } else {
            echo "  - FAIL: Google sheets config not found\n";
        }
    } else {
        echo "  - FAIL: Full config is not an array\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 2: Check getGoogleSheets
echo "Test 2: getGoogleSheets\n";
try {
    $config = UnifiedEnterpriseConfig::getGoogleSheets();
    
    echo "  - Config type: " . gettype($config) . "\n";
    
    if (is_array($config)) {
        echo "  - Config keys: " . implode(', ', array_keys($config)) . "\n";
        
        if (isset($config['registrants'])) {
            echo "  - Registrants keys: " . implode(', ', array_keys($config['registrants'])) . "\n";
            
            if (isset($config['registrants']['columns'])) {
                echo "  - Registrants columns keys: " . implode(', ', array_keys($config['registrants']['columns'])) . "\n";
                
                if (isset($config['registrants']['columns']['Submitted'])) {
                    echo "  - Registrants Submitted index: " . $config['registrants']['columns']['Submitted']['index'] . "\n";
                } else {
                    echo "  - FAIL: Registrants Submitted column not found\n";
                }
            } else {
                echo "  - FAIL: Registrants columns not found\n";
            }
        } else {
            echo "  - FAIL: Registrants config not found\n";
        }
        
        if (isset($config['submissions'])) {
            echo "  - Submissions keys: " . implode(', ', array_keys($config['submissions'])) . "\n";
            
            if (isset($config['submissions']['columns'])) {
                echo "  - Submissions columns keys: " . implode(', ', array_keys($config['submissions']['columns'])) . "\n";
                
                if (isset($config['submissions']['columns']['Submitted'])) {
                    echo "  - Submissions Submitted index: " . $config['submissions']['columns']['Submitted']['index'] . "\n";
                } else {
                    echo "  - FAIL: Submissions Submitted column not found\n";
                }
            } else {
                echo "  - FAIL: Submissions columns not found\n";
            }
        } else {
            echo "  - FAIL: Submissions config not found\n";
        }
    } else {
        echo "  - FAIL: Config is not an array\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
?> 