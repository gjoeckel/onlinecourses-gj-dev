<?php
// Debug script to test Google Sheets API access
require_once __DIR__ . '/lib/unified_enterprise_config.php';

// Test CSU enterprise
echo "Testing CSU Enterprise:\n";
UnifiedEnterpriseConfig::init('csu');
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
$config = UnifiedEnterpriseConfig::getGoogleSheets();
$workbookId = $config['registrants']['workbook_id'];
$sheetName = $config['registrants']['sheet_name'];

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Workbook ID: $workbookId\n";
echo "Sheet Name: $sheetName\n";

$url = sprintf(
    'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s!A:Z?key=%s',
    $workbookId,
    urlencode($sheetName),
    $apiKey
);

echo "URL: $url\n";

$response = file_get_contents($url);
if ($response === false) {
    echo "ERROR: file_get_contents failed\n";
    $error = error_get_last();
    if ($error) {
        echo "PHP Error: " . $error['message'] . "\n";
    }
} else {
    $data = json_decode($response, true);
    if (isset($data['error'])) {
        echo "Google API Error: " . $data['error']['message'] . "\n";
    } else {
        echo "SUCCESS: Got " . count($data['values']) . " rows\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n";

// Test DEMO enterprise
echo "Testing DEMO Enterprise:\n";
UnifiedEnterpriseConfig::init('demo');
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
$config = UnifiedEnterpriseConfig::getGoogleSheets();
$workbookId = $config['registrants']['workbook_id'];
$sheetName = $config['registrants']['sheet_name'];

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Workbook ID: $workbookId\n";
echo "Sheet Name: $sheetName\n";

$url = sprintf(
    'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s!A:Z?key=%s',
    $workbookId,
    urlencode($sheetName),
    $apiKey
);

echo "URL: $url\n";

$response = file_get_contents($url);
if ($response === false) {
    echo "ERROR: file_get_contents failed\n";
    $error = error_get_last();
    if ($error) {
        echo "PHP Error: " . $error['message'] . "\n";
    }
} else {
    $data = json_decode($response, true);
    if (isset($data['error'])) {
        echo "Google API Error: " . $data['error']['message'] . "\n";
    } else {
        echo "SUCCESS: Got " . count($data['values']) . " rows\n";
    }
} 