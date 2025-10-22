<?php
/**
 * Setup ASTHO Organizations
 * 
 * This script creates the ASTHO organizations in the database
 * based on the ASTHO configuration.
 */

// Load master includes for database connection
$master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
$db_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/db.php';

if (!file_exists($master_includes_path) || !file_exists($db_file_path)) {
    die("❌ ERROR: Required files not found\n");
}

require_once $master_includes_path;
require_once $db_file_path;

echo "<h1>Setup ASTHO Organizations</h1>\n";

try {
    $db = new db($dbhost, $dbuser, $dbpass, $dbname);
    
    // 1. Ensure ASTHO enterprise exists
    echo "<h2>1. Enterprise Setup</h2>\n";
    $db->query("SELECT * FROM enterprises WHERE name = 'ASTHO'");
    $enterprise = $db->fetchArray();
    
    if ($enterprise) {
        echo "✅ ASTHO enterprise already exists: ID = {$enterprise['id']}\n";
        $enterprise_id = $enterprise['id'];
    } else {
        echo "Creating ASTHO enterprise...\n";
        $db->query("INSERT INTO enterprises (name, description, status) VALUES ('ASTHO', 'Association of State and Territorial Health Officials', 'active')");
        $enterprise_id = $db->lastInsertId();
        echo "✅ Created ASTHO enterprise: ID = {$enterprise_id}\n";
    }
    
    // 2. Create ASTHO organizations
    echo "<h2>2. Organizations Setup</h2>\n";
    $astho_organizations = [
        'ASTHO',
        'State Health Department',
        'Territorial Health Department',
        'Health Officials'
    ];
    
    $created_count = 0;
    $existing_count = 0;
    
    foreach ($astho_organizations as $org_name) {
        // Check if organization already exists
        $db->query("SELECT id FROM organizations WHERE name = ? AND enterprise_id = ?", $org_name, $enterprise_id);
        $existing_org = $db->fetchArray();
        
        if ($existing_org) {
            echo "✅ Organization '{$org_name}' already exists (ID: {$existing_org['id']})\n";
            $existing_count++;
        } else {
            // Create organization
            $db->query("INSERT INTO organizations (name, type, enterprise_id) VALUES (?, 'org', ?)", $org_name, $enterprise_id);
            $org_id = $db->lastInsertId();
            echo "✅ Created organization '{$org_name}' (ID: {$org_id})\n";
            $created_count++;
        }
    }
    
    echo "<h2>3. Summary</h2>\n";
    echo "Created: {$created_count} organizations\n";
    echo "Already existed: {$existing_count} organizations\n";
    
    // 3. Verify setup
    echo "<h2>4. Verification</h2>\n";
    $db->query("SELECT * FROM organizations WHERE enterprise_id = ? ORDER BY name", $enterprise_id);
    $organizations = $db->fetchAll();
    
    echo "Total ASTHO organizations: " . count($organizations) . "\n";
    foreach ($organizations as $org) {
        echo "- ID: {$org['id']}, Name: '{$org['name']}'\n";
    }
    
    if (count($organizations) === 4) {
        echo "<p style='color: green;'><strong>✅ Setup complete! ASTHO organizations are ready.</strong></p>\n";
        echo "<p>You can now test the reports page to see if organization data displays correctly.</p>\n";
    } else {
        echo "<p style='color: red;'><strong>❌ Setup incomplete. Expected 4 organizations, found " . count($organizations) . "</strong></p>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
