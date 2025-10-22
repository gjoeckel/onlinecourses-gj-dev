<?php
/**
 * Column Index Test
 * Verify the correct column indices for the data
 * Run with: php tests/integration/column_index_test.php [enterprise]
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force local environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

require_once __DIR__ . '/../../lib/unified_enterprise_config.php';

// Get enterprise from command line argument
$enterprise = $argv[1] ?? 'csu';

echo "=== Column Index Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    
    $registrations = json_decode(file_get_contents($registrations_file), true);
    
    echo "📊 Total registrations: " . count($registrations) . "\n\n";
    
    // Examine the first record to understand the structure
    if (!empty($registrations)) {
        $first_record = $registrations[0];
        echo "🔍 First record structure:\n";
        echo "   Length: " . count($first_record) . "\n";
        echo "   All values: " . json_encode($first_record) . "\n\n";
        
        echo "📋 Column analysis:\n";
        for ($i = 0; $i < count($first_record); $i++) {
            $value = $first_record[$i];
            $description = '';
            
            // Try to identify what each column contains
            if ($i === 0) $description = ' (possibly ID or status)';
            elseif ($i === 1) $description = ' (possibly registration date)';
            elseif ($i === 2) $description = ' (possibly enrollment status)';
            elseif ($i === 3) $description = ' (possibly month)';
            elseif ($i === 4) $description = ' (possibly year)';
            elseif ($i === 5) $description = ' (first name)';
            elseif ($i === 6) $description = ' (last name)';
            elseif ($i === 7) $description = ' (email)';
            elseif ($i === 8) $description = ' (role)';
            elseif ($i === 9) $description = ' (organization)';
            elseif ($i === 10) $description = ' (possibly certificate status)';
            elseif ($i === 11) $description = ' (possibly certificate date)';
            elseif ($i === 15) $description = ' (ACTUAL DATE - MM-DD-YY format)';
            
            $is_date = preg_match('/^\d{2}-\d{2}-\d{2}$/', $value) ? ' (DATE)' : '';
            echo "   Index $i: '$value'$description$is_date\n";
        }
        
        echo "\n💡 Current DataProcessor assumptions:\n";
        echo "   - Registration date: index 1 (current value: '{$first_record[1]}')\n";
        echo "   - Enrollment status: index 2 (current value: '{$first_record[2]}')\n";
        echo "   - Certificate status: index 10 (current value: '{$first_record[10]}')\n";
        echo "   - Certificate date: index 11 (current value: '{$first_record[11]}')\n";
        echo "   - Organization: index 9 (current value: '{$first_record[9]}')\n";
        
        echo "\n🔍 Actual date found at index 15: '{$first_record[15]}'\n";
        
        if ($first_record[1] === '-' || $first_record[1] === '') {
            echo "❌ Index 1 is empty or '-', so no registrations will be found!\n";
        } else {
            echo "✅ Index 1 has a value: '{$first_record[1]}'\n";
        }
        
        if ($first_record[15] !== '-' && $first_record[15] !== '') {
            echo "✅ Index 15 has the actual date: '{$first_record[15]}'\n";
        }
    }
    
    echo "\n📋 Recommendations:\n";
    echo "   1. The DataProcessor should use index 15 for the registration date\n";
    echo "   2. Update the processRegistrantsData method to use the correct indices\n";
    echo "   3. Verify other column indices (enrollment, certificate, organization)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 