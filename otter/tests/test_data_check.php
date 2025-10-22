<?php
// Test script to check data structure and organization column
$registrations = json_decode(file_get_contents('cache/csu/registrations.json'), true);
$enrollments = json_decode(file_get_contents('cache/csu/enrollments.json'), true);
$certificates = json_decode(file_get_contents('cache/csu/certificates.json'), true);

echo "Data counts:\n";
echo "Registrations: " . count($registrations) . "\n";
echo "Enrollments: " . count($enrollments) . "\n";
echo "Certificates: " . count($certificates) . "\n\n";

if (count($registrations) > 0) {
    echo "Sample registration row (first 20 columns):\n";
    echo json_encode(array_slice($registrations[0], 0, 20)) . "\n\n";
    
    echo "Organization column (index 9): " . (isset($registrations[0][9]) ? $registrations[0][9] : 'NOT SET') . "\n";
    echo "Organization column (index 10): " . (isset($registrations[0][10]) ? $registrations[0][10] : 'NOT SET') . "\n";
    echo "Organization column (index 11): " . (isset($registrations[0][11]) ? $registrations[0][11] : 'NOT SET') . "\n\n";
    
    // Check for organization names in different columns
    $orgColumns = [];
    for ($i = 0; $i < 20; $i++) {
        if (isset($registrations[0][$i])) {
            $value = trim($registrations[0][$i]);
            if (strlen($value) > 0 && !is_numeric($value) && !preg_match('/^\d{1,2}-\d{1,2}-\d{2}$/', $value)) {
                $orgColumns[$i] = $value;
            }
        }
    }
    
    echo "Potential organization columns:\n";
    foreach ($orgColumns as $index => $value) {
        echo "  Column $index: $value\n";
    }
}
?> 