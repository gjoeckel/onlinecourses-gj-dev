<?php
// Start session first
require_once __DIR__ . '/lib/session.php';
initializeSession();

require_once __DIR__ . '/lib/unified_database.php';
require_once __DIR__ . '/lib/password_utils.php';

$db = new UnifiedDatabase();

// Get password from URL parameter
$password = $_GET['password'] ?? '';

if (empty($password)) {
    header('Location: login.php');
    exit;
}

// Get all organizations with this password
$all_orgs = $db->getAllOrganizations();
$matching_orgs = [];

foreach ($all_orgs as $org) {
    // Check if this organization has the same password (either plain text or hashed)
    if ($org['password'] === $password) {
        $matching_orgs[] = $org;
    } elseif (password_verify($password, $org['password'])) {
        $matching_orgs[] = $org;
    }
}

// If only one organization, redirect directly
if (count($matching_orgs) === 1) {
    $org = $matching_orgs[0];
    header('Location: dashboard.php?org=' . urlencode($password));
    exit;
}

// If no organizations found, redirect to login
if (empty($matching_orgs)) {
    header('Location: login.php');
    exit;
}

// Group organizations by enterprise
$enterprises = [];
foreach ($matching_orgs as $org) {
    $enterprise = $org['enterprise'];
    if (!isset($enterprises[$enterprise])) {
        $enterprises[$enterprise] = [];
    }
    $enterprises[$enterprise][] = $org;
}

$enterprise_names = [
    'csu' => 'California State University',
    'ccc' => 'California Community Colleges', 
    'demo' => 'Demo Organizations',
    'super' => 'Super Admin'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Organization</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href="lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .org-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
        .org-item {
            padding: 8px;
            margin: 2px 0;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .org-item:hover {
            background-color: #f0f0f0;
        }
        .enterprise-section {
            margin: 20px 0;
        }
        .enterprise-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="status-page">
    <div class="main-container">
        <div class="heading-container">
            <h1>Select Organization</h1>
        </div>
        
        <div class="label-container">
            <p>Multiple organizations found with this password. Please select one:</p>
        </div>
        
        <div class="buttons-container">
            <?php foreach ($enterprises as $enterprise_code => $orgs): ?>
                <div class="enterprise-section">
                    <div class="enterprise-title">
                        <?php echo htmlspecialchars($enterprise_names[$enterprise_code] ?? ucfirst($enterprise_code)); ?>
                        (<?php echo count($orgs); ?> organization<?php echo count($orgs) !== 1 ? 's' : ''; ?>)
                    </div>
                    <div class="org-list">
                        <?php foreach ($orgs as $org): ?>
                            <div class="org-item" onclick="selectOrganization('<?php echo htmlspecialchars($org['name']); ?>')">
                                <strong><?php echo htmlspecialchars($org['name']); ?></strong>
                                <?php if ($org['name'] === 'ADMIN'): ?>
                                    <span style="color: #007bff;">(Admin Access)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="message-container">
            <p><a href="login.php" style="color: #007bff;">← Back to Login</a></p>
        </div>
    </div>
    
    <script>
        function selectOrganization(orgName) {
            window.location.href = 'dashboard.php?org=<?php echo urlencode($password); ?>&org_name=' + encodeURIComponent(orgName);
        }
    </script>
</body>
</html> 