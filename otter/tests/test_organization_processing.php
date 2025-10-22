<?php
// Test script to check DataProcessor organization processing
require_once 'lib/data_processor.php';

$registrations = json_decode(file_get_contents('cache/csu/registrations.json'), true);
$enrollments = json_decode(file_get_contents('cache/csu/enrollments.json'), true);
$certificates = json_decode(file_get_contents('cache/csu/certificates.json'), true);

echo "Testing DataProcessor::processOrganizationData()\n";
echo "Input data counts:\n";
echo "  Registrations: " . count($registrations) . "\n";
echo "  Enrollments: " . count($enrollments) . "\n";
echo "  Certificates: " . count($certificates) . "\n\n";

// Test the organization processing
$organizationData = DataProcessor::processOrganizationData($registrations, $enrollments, $certificates);

echo "Output organization data:\n";
echo "  Count: " . count($organizationData) . "\n";

if (count($organizationData) > 0) {
    echo "  Sample organization: " . json_encode($organizationData[0]) . "\n";
    
    // Show first 5 organizations
    echo "  First 5 organizations:\n";
    for ($i = 0; $i < min(5, count($organizationData)); $i++) {
        $org = $organizationData[$i];
        echo "    {$org['organization']}: {$org['registrations']} reg, {$org['enrollments']} enr, {$org['certificates']} cert\n";
    }
} else {
    echo "  No organization data returned!\n";
}
?> 