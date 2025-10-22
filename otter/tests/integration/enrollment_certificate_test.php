<?php
/**
 * Enrollment Certificate Test
 * Check the enrollment and certificate status columns
 * Run with: php tests/integration/enrollment_certificate_test.php [enterprise]
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

echo "=== Enrollment Certificate Test ===\n";
echo "Enterprise: $enterprise\n\n";

try {
    // Initialize enterprise configuration
    UnifiedEnterpriseConfig::init($enterprise);
    
    // Load cache data
    $cache_dir = __DIR__ . "/../../cache/$enterprise";
    $registrations_file = "$cache_dir/registrations.json";
    
    $registrations = json_decode(file_get_contents($registrations_file), true);
    
    echo "📊 Total registrations: " . count($registrations) . "\n\n";
    
    // Check enrollment and certificate status columns
    $enrollment_values = [];
    $certificate_values = [];
    
    echo "🔍 Checking enrollment and certificate status columns:\n";
    
    for ($i = 0; $i < min(20, count($registrations)); $i++) {
        $record = $registrations[$i];
        
        if (is_array($record)) {
            $enrollment_status = isset($record[2]) ? $record[2] : 'undefined';
            $certificate_status = isset($record[10]) ? $record[10] : 'undefined';
            
            if (!isset($enrollment_values[$enrollment_status])) {
                $enrollment_values[$enrollment_status] = 0;
            }
            $enrollment_values[$enrollment_status]++;
            
            if (!isset($certificate_values[$certificate_status])) {
                $certificate_values[$certificate_status] = 0;
            }
            $certificate_values[$certificate_status]++;
            
            echo "   Record $i: enrollment[2]='$enrollment_status', certificate[10]='$certificate_status'\n";
        }
    }
    
    echo "\n📋 Enrollment status values (index 2):\n";
    foreach ($enrollment_values as $value => $count) {
        echo "   '$value': $count records\n";
    }
    
    echo "\n📋 Certificate status values (index 10):\n";
    foreach ($certificate_values as $value => $count) {
        echo "   '$value': $count records\n";
    }
    
    // Check if there are any "Yes" values
    $has_enrollments = isset($enrollment_values['Yes']);
    $has_certificates = isset($certificate_values['Yes']);
    
    echo "\n💡 Analysis:\n";
    if ($has_enrollments) {
        echo "   ✅ Found 'Yes' values for enrollment status\n";
    } else {
        echo "   ❌ No 'Yes' values found for enrollment status\n";
        echo "   💡 Enrollment status might be in a different column or use different values\n";
    }
    
    if ($has_certificates) {
        echo "   ✅ Found 'Yes' values for certificate status\n";
    } else {
        echo "   ❌ No 'Yes' values found for certificate status\n";
        echo "   💡 Certificate status might be in a different column or use different values\n";
    }
    
    // Check other columns for potential enrollment/certificate indicators
    echo "\n🔍 Checking other columns for enrollment/certificate indicators:\n";
    if (!empty($registrations)) {
        $first_record = $registrations[0];
        for ($i = 0; $i < count($first_record); $i++) {
            $value = $first_record[$i];
            if ($value === 'Yes' || $value === 'yes' || $value === 'Y' || $value === 'y') {
                echo "   Index $i has 'Yes' value: '$value'\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 