<?php
/**
 * Debug ASTHO Organizations
 * 
 * This script helps debug why ASTHO organization data isn't displaying
 * in the reports page.
 */

// Load master includes for database connection
$master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
$db_file_path = '/var/websites/webaim/htdocs/onlinecourses/includes/db.php';

if (!file_exists($master_includes_path) || !file_exists($db_file_path)) {
    die("❌ ERROR: Required files not found\n");
}

require_once $master_includes_path;
require_once $db_file_path;

echo "<h1>ASTHO Organizations Debug</h1>\n";

try {
    $db = new db($dbhost, $dbuser, $dbpass, $dbname);
    
    // 1. Check if ASTHO enterprise exists
    echo "<h2>1. Enterprise Check</h2>\n";
    $db->query("SELECT * FROM enterprises WHERE name = 'ASTHO'");
    $enterprise = $db->fetchArray();
    
    if ($enterprise) {
        echo "✅ ASTHO enterprise found: ID = {$enterprise['id']}, Name = {$enterprise['name']}\n";
        $enterprise_id = $enterprise['id'];
    } else {
        echo "❌ ASTHO enterprise not found in database\n";
        echo "<p>Available enterprises:</p>\n";
        $db->query("SELECT * FROM enterprises");
        $all_enterprises = $db->fetchAll();
        foreach ($all_enterprises as $ent) {
            echo "- ID: {$ent['id']}, Name: {$ent['name']}\n";
        }
        exit;
    }
    
    // 2. Check ASTHO organizations
    echo "<h2>2. Organizations Check</h2>\n";
    $db->query("SELECT * FROM organizations WHERE enterprise_id = ? ORDER BY name", $enterprise_id);
    $organizations = $db->fetchAll();
    
    echo "Found " . count($organizations) . " organizations for ASTHO:\n";
    foreach ($organizations as $org) {
        echo "- ID: {$org['id']}, Name: '{$org['name']}'\n";
    }
    
    if (empty($organizations)) {
        echo "❌ No organizations found for ASTHO enterprise!\n";
        echo "<p>This is likely the problem. ASTHO organizations need to be created in the database.</p>\n";
    }
    
    // 3. Check ASTHO registrations
    echo "<h2>3. Registrations Check</h2>\n";
    $db->query("SELECT COUNT(*) as count FROM registrations WHERE enterprise_id = ? AND deletion_status = 'active'", $enterprise_id);
    $reg_count = $db->fetchArray();
    echo "Total ASTHO registrations: {$reg_count['count']}\n";
    
    if ($reg_count['count'] > 0) {
        echo "<p>Sample ASTHO registrations:</p>\n";
        $db->query("SELECT id, name, email, organization, college, organization_id FROM registrations WHERE enterprise_id = ? AND deletion_status = 'active' LIMIT 5", $enterprise_id);
        $sample_regs = $db->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Organization</th><th>College</th><th>Org ID</th></tr>\n";
        foreach ($sample_regs as $reg) {
            echo "<tr>";
            echo "<td>{$reg['id']}</td>";
            echo "<td>{$reg['name']}</td>";
            echo "<td>{$reg['email']}</td>";
            echo "<td>{$reg['organization']}</td>";
            echo "<td>{$reg['college']}</td>";
            echo "<td>{$reg['organization_id']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // 4. Test the matching logic
    echo "<h2>4. Matching Logic Test</h2>\n";
    if (!empty($organizations) && $reg_count['count'] > 0) {
        foreach ($organizations as $org) {
            $orgName = $org['name'];
            echo "<h3>Testing organization: '{$orgName}'</h3>\n";
            
            // Test the old logic (college field only)
            $db->query("SELECT COUNT(*) as count FROM registrations WHERE college = ? AND enterprise_id = ? AND deletion_status = 'active'", $orgName, $enterprise_id);
            $college_matches = $db->fetchArray();
            
            // Test the new logic (organization OR college field)
            $db->query("SELECT COUNT(*) as count FROM registrations WHERE (organization = ? OR college = ?) AND enterprise_id = ? AND deletion_status = 'active'", $orgName, $orgName, $enterprise_id);
            $new_matches = $db->fetchArray();
            
            echo "- Old logic (college field only): {$college_matches['count']} matches\n";
            echo "- New logic (organization OR college): {$new_matches['count']} matches\n";
            
            if ($new_matches['count'] > 0) {
                echo "✅ This organization should show data with the new logic!\n";
            } else {
                echo "❌ No matches found even with new logic\n";
            }
        }
    }
    
    // 5. Recommendations
    echo "<h2>5. Recommendations</h2>\n";
    if (empty($organizations)) {
        echo "<p><strong>Action needed:</strong> Create ASTHO organizations in the database.</p>\n";
        echo "<p>The ASTHO config defines these organizations:</p>\n";
        echo "<ul>\n";
        echo "<li>ASTHO</li>\n";
        echo "<li>State Health Department</li>\n";
        echo "<li>Territorial Health Department</li>\n";
        echo "<li>Health Officials</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>✅ Organizations exist. The fix in reports_api.php should resolve the display issue.</p>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
