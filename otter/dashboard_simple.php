<?php
// Simple dashboard that shows when there's no data
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic setup
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/direct_link.php';
require_once __DIR__ . '/lib/unified_database.php';
require_once __DIR__ . '/lib/password_utils.php';
require_once __DIR__ . '/lib/api/organizations_api.php';
require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/enterprise_cache_manager.php';

initializeSession();

$db = new UnifiedDatabase();

// Get organization code and name
$organizationCode = $_GET['org'] ?? '';
$org_name = $_GET['org_name'] ?? '';

echo "<h1>Simple Dashboard Test</h1>";
echo "<p>Organization: " . htmlspecialchars($org_name) . "</p>";
echo "<p>Code: " . htmlspecialchars($organizationCode) . "</p>";

if ($organizationCode && $org_name) {
    // Validate organization
    $all_orgs = $db->getAllOrganizations();
    $org = null;
    $enterprise_code = null;
    $valid = false;
    
    foreach ($all_orgs as $org_check) {
        if ($org_check['name'] === $org_name && 
            ($org_check['password'] === $organizationCode || password_verify($organizationCode, $org_check['password']))) {
            $org = $org_name;
            $enterprise_code = $org_check['enterprise'];
            $valid = true;
            break;
        }
    }
    
    if ($valid) {
        echo "<h2>✅ Organization Validated</h2>";
        echo "<p>Enterprise: " . htmlspecialchars($enterprise_code) . "</p>";
        
        // Initialize enterprise config
        UnifiedEnterpriseConfig::init($enterprise_code);
        
        // Get organization data
        $orgData = OrganizationsAPI::getOrgData($org);
        
        if ($orgData) {
            $enrollmentData = $orgData['enrollment'] ?? [];
            $enrolled = $orgData['enrolled'] ?? [];
            $invited = $orgData['invited'] ?? [];
            
            echo "<h2>Data Summary</h2>";
            echo "<p>Enrollment records: " . count($enrollmentData) . "</p>";
            echo "<p>Enrolled records: " . count($enrolled) . "</p>";
            echo "<p>Invited records: " . count($invited) . "</p>";
            
            if (count($enrollmentData) === 0 && count($enrolled) === 0 && count($invited) === 0) {
                echo "<h2>📊 No Data Available</h2>";
                echo "<p>This organization currently has no enrollment data.</p>";
                echo "<p>This could mean:</p>";
                echo "<ul>";
                echo "<li>No one has enrolled yet</li>";
                echo "<li>The data hasn't been loaded yet</li>";
                echo "<li>There's an issue with the data source</li>";
                echo "</ul>";
            } else {
                echo "<h2>📊 Data Available</h2>";
                if (count($enrollmentData) > 0) {
                    echo "<h3>Sample Enrollment Data:</h3>";
                    echo "<pre>" . print_r(array_slice($enrollmentData, 0, 2), true) . "</pre>";
                }
            }
        } else {
            echo "<h2>❌ No Data Retrieved</h2>";
            echo "<p>The API returned no data for this organization.</p>";
        }
    } else {
        echo "<h2>❌ Invalid Organization</h2>";
        echo "<p>The organization could not be validated.</p>";
    }
} else {
    echo "<h2>❌ Missing Parameters</h2>";
    echo "<p>Please provide both 'org' and 'org_name' parameters.</p>";
}

echo "<h2>Navigation</h2>";
echo "<p><a href='login_working.php'>← Back to Login</a></p>";
echo "<p><a href='debug_dashboard.php?org=" . urlencode($organizationCode) . "&org_name=" . urlencode($org_name) . "'>Debug Dashboard</a></p>";
?> 