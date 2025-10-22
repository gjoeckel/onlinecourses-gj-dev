<?php
// tests/builder_integration_test.php

function postToBuilder($postData) {
    $url = 'http://localhost:8000/enterprise-builder.php';
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\nX-Requested-With: XMLHttpRequest\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result;
}

function compareConfigStructure($a, $b, $path = '') {
    // Recursively compare keys and types
    foreach ($b as $key => $value) {
        $currentPath = $path ? "$path.$key" : $key;
        if (!array_key_exists($key, $a)) {
            echo "❌ Missing key: $currentPath\n";
            return false;
        }
        if (is_array($value)) {
            if (!is_array($a[$key])) {
                echo "❌ Type mismatch at: $currentPath\n";
                return false;
            }
            if (!compareConfigStructure($a[$key], $value, $currentPath)) {
                return false;
            }
        }
    }
    return true;
}

// 1. Prepare sample data
$enterprise_code = 'testent';
$postData = [
    'action' => 'build_enterprise',
    'enterprise_code' => $enterprise_code,
    'enterprise_name' => 'Test Enterprise',
    'display_name' => 'Test Enterprise',
    'admin_password' => '1234',
            'has_groups' => 'false',
    'google_api_key' => 'AIzaSyTestKey',
    'registrants_workbook_id' => 'test_registrants_id',
    'submissions_workbook_id' => 'test_submissions_id',
    'organizations_data' => "Test Org 1\nTest Org 2\nTest Org 3",
    'start_date' => '06-30-25',
    'timezone' => 'America/Los_Angeles'
];

    // 2. Submit to enterprise-builder.php
    echo "Submitting sample data to enterprise-builder.php...\n";
$response = postToBuilder($postData);
$data = json_decode($response, true);

if (!$data || empty($data['success'])) {
    echo "❌ Enterprise-builder.php did not return success: " . ($response ?: 'No response') . "\n";
    exit(1);
}
echo "✅ Enterprise-builder.php returned success: {$data['message']}\n";

// 3. Check config file exists
$configFile = __DIR__ . '/../config/' . $enterprise_code . '.config';
if (!file_exists($configFile)) {
    echo "❌ Config file not created: $configFile\n";
    exit(1);
}
echo "✅ Config file created: $configFile\n";

// 4. Compare structure to csu.config
$testConfig = json_decode(file_get_contents($configFile), true);
$csuConfig = json_decode(file_get_contents(__DIR__ . '/../config/csu.config'), true);

if (!$testConfig || !$csuConfig) {
    echo "❌ Could not load config files for comparison.\n";
    exit(1);
}

echo "Comparing config structure to csu.config...\n";
if (compareConfigStructure($testConfig, $csuConfig)) {
    echo "✅ Config structure matches csu.config\n";
} else {
    echo "❌ Config structure does not match csu.config\n";
    exit(1);
}

echo "All tests passed.\n";
exit(0); 