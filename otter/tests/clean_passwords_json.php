<?php
/**
 * Clean Passwords JSON Script
 * 
 * This script removes all CCC organizations from a passwords JSON file
 * and keeps only CSU and super admin passwords for production use.
 * Usage: php clean_passwords_json.php [target_file]
 */

$config_dir = __DIR__ . '/../config';
$default_file = $config_dir . '/passwords.json';
$target_file = $default_file;

if (isset($argv[1]) && !empty($argv[1])) {
    $target_file = $argv[1];
    if (strpos($target_file, '/') === false && strpos($target_file, '\\') === false) {
        $target_file = $config_dir . '/' . $target_file;
    }
}

$passwords_file = $target_file;

echo "=== Cleaning Passwords JSON ===\n";
echo "Reading $passwords_file...\n";

// Read current passwords.json
if (!file_exists($passwords_file)) {
    echo "❌ ERROR: $passwords_file not found\n";
    exit(1);
}

$passwords_data = json_decode(file_get_contents($passwords_file), true);
if (!$passwords_data) {
    echo "❌ ERROR: Invalid JSON in $passwords_file\n";
    exit(1);
}

echo "✅ Successfully loaded $passwords_file\n";

// Count organizations before cleaning
$total_organizations_before = count($passwords_data['organizations']);
$ccc_organizations_before = 0;
$csu_organizations_before = 0;
$other_organizations_before = 0;

foreach ($passwords_data['organizations'] as $org) {
    if ($org['enterprise'] === 'ccc') {
        $ccc_organizations_before++;
    } elseif ($org['enterprise'] === 'csu') {
        $csu_organizations_before++;
    } else {
        $other_organizations_before++;
    }
}

echo "\nBefore cleaning:\n";
echo "  Total organizations: $total_organizations_before\n";
echo "  CCC organizations: $ccc_organizations_before\n";
echo "  CSU organizations: $csu_organizations_before\n";
echo "  Other organizations: $other_organizations_before\n";

// Remove CCC organizations
$cleaned_organizations = [];
foreach ($passwords_data['organizations'] as $org) {
    if ($org['enterprise'] !== 'ccc') {
        $cleaned_organizations[] = $org;
    }
}

// Remove CCC admin passwords
$cleaned_admin_passwords = [];
foreach ($passwords_data['admin_passwords'] as $enterprise => $password) {
    if ($enterprise !== 'ccc') {
        $cleaned_admin_passwords[$enterprise] = $password;
    }
}

// Update the data structure
$passwords_data['organizations'] = $cleaned_organizations;
$passwords_data['admin_passwords'] = $cleaned_admin_passwords;

// Update metadata
$passwords_data['metadata']['last_updated'] = date('Y-m-d');
$passwords_data['metadata']['total_organizations'] = count($cleaned_organizations);

// Remove 'ccc' from enterprises list if it exists
if (isset($passwords_data['metadata']['enterprises'])) {
    $passwords_data['metadata']['enterprises'] = array_filter(
        $passwords_data['metadata']['enterprises'], 
        function($enterprise) { return $enterprise !== 'ccc'; }
    );
}

// Count organizations after cleaning
$total_organizations_after = count($passwords_data['organizations']);
$csu_organizations_after = 0;
$other_organizations_after = 0;

foreach ($passwords_data['organizations'] as $org) {
    if ($org['enterprise'] === 'csu') {
        $csu_organizations_after++;
    } else {
        $other_organizations_after++;
    }
}

echo "\nAfter cleaning:\n";
echo "  Total organizations: $total_organizations_after\n";
echo "  CSU organizations: $csu_organizations_after\n";
echo "  Other organizations: $other_organizations_after\n";
echo "  CCC organizations removed: $ccc_organizations_before\n";

// Write cleaned passwords.json
$result = file_put_contents($passwords_file, json_encode($passwords_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if ($result === false) {
    echo "❌ ERROR: Failed to write cleaned $passwords_file\n";
    exit(1);
}

echo "\n✅ Successfully wrote cleaned $passwords_file\n";
echo "✅ Removed $ccc_organizations_before CCC organizations\n";
echo "✅ Kept $csu_organizations_after CSU organizations\n";
echo "✅ Kept " . count($cleaned_admin_passwords) . " admin passwords (super, csu)\n";

// Verify the file is valid JSON
$verification_data = json_decode(file_get_contents($passwords_file), true);
if (!$verification_data) {
    echo "❌ ERROR: Generated $passwords_file is not valid JSON\n";
    exit(1);
}

echo "✅ Verified: $passwords_file is valid JSON\n";

// List remaining admin passwords
echo "\nRemaining admin passwords:\n";
foreach ($cleaned_admin_passwords as $enterprise => $password) {
    echo "  $enterprise: $password\n";
}

echo "\n=== Cleaning Complete ===\n";
echo "✅ $passwords_file cleaned successfully\n";
echo "✅ passwords-tests.json contains original data for testing\n";
echo "✅ Production passwords.json now contains only CSU and super admin data\n"; 