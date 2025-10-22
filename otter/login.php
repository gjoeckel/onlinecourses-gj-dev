<?php
// Start session first
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/security_headers.php';

// Initialize session and security
initializeSession();
initializeSecurity();

// Load unified enterprise configuration first
// STANDARDIZED: Uses UnifiedEnterpriseConfig for enterprise detection and config access
require_once __DIR__ . '/lib/unified_enterprise_config.php';

// Initialize variables early to prevent undefined variable warnings
$message_content = '';
$message_type = '';
$message_role = 'status';
$message_aria = 'polite';
$message_location = '';

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    // Redirect to generic login page
    header('Location: login.php');
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/lib/csrf_utils.php';
    
    // Validate CSRF token
    if (!validateCSRFTokenFromPost()) {
        require_once __DIR__ . '/lib/error_messages.php';
        $message_content = 'Invalid security token. Please try again.';
        $message_type = 'error-message';
        $message_role = 'alert';
        $message_aria = 'assertive';
        $message_location = 'error';
    } else {
        $password = $_POST['password'] ?? '';

        if (!empty($password)) {
        // Check for enterprise builder and groups builder passwords
        $passwords_file = __DIR__ . '/config/passwords.json';
        if (file_exists($passwords_file)) {
            $passwords_data = json_decode(file_get_contents($passwords_file), true);

            // Check for enterprise builder password
            $enterprise_builder_password = $passwords_data['admin_passwords']['enterprise_builder'] ?? null;
            if ($enterprise_builder_password && $password === $enterprise_builder_password) {
                header('Location: enterprise-builder.php');
                exit;
            }

            // Check for groups builder password
            $groups_builder_password = $passwords_data['admin_passwords']['groups_builder'] ?? null;
            if ($groups_builder_password && $password === $groups_builder_password) {
                header('Location: groups-builder.php');
                exit;
            }
        }



        // Load unified database to validate password and detect enterprise
        require_once __DIR__ . '/lib/unified_database.php';
        require_once __DIR__ . '/lib/password_utils.php';
        $db = new UnifiedDatabase();

        // Validate password and get organization data
        $org = $db->validateLogin($password);

        if ($org) {
            // Password is valid, detect enterprise from organization data
            // STANDARDIZED: Uses UnifiedEnterpriseConfig::init() pattern for enterprise initialization
            $enterprise_code = $org['enterprise'];

            // Initialize enterprise configuration
            UnifiedEnterpriseConfig::init($enterprise_code);

            // Check if this is an admin password
            if (isset($org['is_admin']) && $org['is_admin'] === true) {
                // Admin login
                $_SESSION['admin_authenticated'] = true;
                $_SESSION['enterprise_code'] = $enterprise_code;
                // STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnvironment() pattern
                $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();

                // Redirect super admin to enterprise-builder.php
                if ($enterprise_code === 'super') {
                    header('Location: enterprise-builder.php');
                } else {
                    header('Location: admin/index.php?login=1');
                }
                exit;
            } else {
                // Organization login
                $_SESSION['organization_authenticated'] = true;
                $_SESSION['organization_name'] = $org['name'];
                $_SESSION['organization_password'] = $password;
                $_SESSION['enterprise_code'] = $enterprise_code;
                // STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnvironment() pattern
                $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();

                // OPTIMIZED: Skip expensive multiple organization check
                // Go directly to enterprise dashboard
                $dashboard_url = 'dashboard_enterprise.php?enterprise=' . urlencode($enterprise_code);
                header('Location: ' . $dashboard_url);
                exit;
            }
        } else {
            // Invalid password
            require_once __DIR__ . '/lib/error_messages.php';
            $message_content = ErrorMessages::getInvalidPassword();
            $message_type = 'error-message';
            $message_role = 'alert';
            $message_aria = 'assertive';
            $message_location = 'error';
        }
            } else {
            // Empty password
            require_once __DIR__ . '/lib/error_messages.php';
            $message_content = ErrorMessages::getEmptyPassword();
            $message_type = 'error-message';
            $message_role = 'alert';
            $message_aria = 'assertive';
            $message_location = 'error';
        }
    }
}

// For display purposes, initialize with generic login (will be overridden on login)
$enterprise_name = 'Login';

$has_message = !empty($message_content);
$message_classes = $has_message
    ? "display-block $message_type"
    : "display-block visually-hidden-but-space";
$message_aria_hidden = $has_message ? '' : 'aria-hidden="true"';
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($enterprise_name); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="icon" type="image/svg+xml" href="lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="lib/message-dismissal.js"></script>
</head>

<body class="status-page">
    <!-- Main Container: 650px wide with 50px padding -->
    <div class="main-container">
        <!-- Row 1: Heading Container -->
        <div class="heading-container">
            <h1><?php echo htmlspecialchars($enterprise_name); ?></h1>
        </div>

        <!-- Row 2: Label Container -->
        <div class="label-container">
            <label for="password">Password:</label>
        </div>

        <!-- Row 3: Buttons Container -->
        <div class="buttons-container">
            <form method="post" autocomplete="off">
                <?php require_once __DIR__ . '/lib/csrf_utils.php'; echo generateCSRFTokenField(); ?>
                <input type="password" id="password" name="password" aria-label="Password" required class="login-password-input">
                <button type="submit" class="button login-btn">Login</button>
            </form>
        </div>

        <!-- Row 4: Message Container -->
        <div class="message-container">
            <div id="message-display"
                class="<?php echo $message_classes; ?>"
                role="<?php echo $message_role; ?>"
                aria-live="<?php echo $message_aria; ?>"
                <?php echo $message_aria_hidden; ?>>
                <?php echo $has_message ? htmlspecialchars($message_content) : ''; ?>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            var pw = document.getElementById('password');
            if (pw) { pw.focus(); }
        };
    </script>
</body>

</html>