<?php
// Temporary script to fix Chico URLs
require_once __DIR__ . '/lib/direct_link.php';

echo "Before fix:\n";
$jsonFile = DirectLink::getEnterpriseJsonPath();
$data = json_decode(file_get_contents($jsonFile), true);

// Find Chico entry
foreach ($data['organizations'] as $org) {
    if ($org['name'] === 'Chico') {
        echo "Chico - Password: {$org['password']}\n";
        echo "Chico - Local URL: {$org['dashboard_url_local']}\n";
        echo "Chico - Production URL: {$org['dashboard_url_production']}\n";
        break;
    }
}

// Regenerate enterprise.json
DirectLink::regenerateEnterpriseJson();

echo "\nAfter fix:\n";
$data = json_decode(file_get_contents($jsonFile), true);

// Find Chico entry again
foreach ($data['organizations'] as $org) {
    if ($org['name'] === 'Chico') {
        echo "Chico - Password: {$org['password']}\n";
        echo "Chico - Local URL: {$org['dashboard_url_local']}\n";
        echo "Chico - Production URL: {$org['dashboard_url_production']}\n";
        break;
    }
}

echo "\nFix completed!\n";
?> 