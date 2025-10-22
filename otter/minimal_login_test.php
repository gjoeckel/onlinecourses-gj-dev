<?php
// minimal_login_test.php - Minimal login test accessible via browser
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Minimal Login Test (Browser Accessible)</h1>";
echo "<p>This test can be accessed via browser to see if the issue is CLI vs Web</p>";

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Request Detected</h2>";
    echo "Password: " . htmlspecialchars($_POST['password'] ?? 'NOT SET') . "<br>";
    echo "CSRF Token: " . htmlspecialchars($_POST['csrf_token'] ?? 'NOT SET') . "<br>";
    
    try {
        // Load required files
        require_once __DIR__ . '/lib/session.php';
        require_once __DIR__ . '/lib/security_headers.php';
        require_once __DIR__ . '/lib/csrf_utils.php';
        require_once __DIR__ . '/lib/unified_database.php';
        require_once __DIR__ . '/lib/password_utils.php';
        require_once __DIR__ . '/lib/error_messages.php';
        require_once __DIR__ . '/lib/unified_enterprise_config.php';
        
        // Initialize
        initializeSession();
        initializeSecurity();
        
        echo "✅ Session and security initialized<br>";
        
        // Validate CSRF
        if (validateCSRFTokenFromPost()) {
            echo "✅ CSRF validation passed<br>";
            
            // Validate password
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                $db = new UnifiedDatabase();
                $org = $db->validateLogin($password);
                
                if ($org) {
                    echo "✅ Password validation passed<br>";
                    echo "Organization: " . htmlspecialchars($org['name']) . "<br>";
                    echo "Enterprise: " . htmlspecialchars($org['enterprise']) . "<br>";
                    
                    // Initialize enterprise config
                    $enterprise_code = $org['enterprise'];
                    UnifiedEnterpriseConfig::init($enterprise_code);
                    echo "✅ Enterprise config initialized<br>";
                    
                    // Set session variables
                    if (isset($org['is_admin']) && $org['is_admin'] === true) {
                        $_SESSION['admin_authenticated'] = true;
                        $_SESSION['enterprise_code'] = $enterprise_code;
                        $_SESSION['environment'] = UnifiedEnterpriseConfig::getEnvironment();
                        echo "✅ Admin session variables set<br>";
                        
                        // Test redirect
                        if ($enterprise_code === 'super') {
                            echo "Would redirect to: enterprise-builder.php<br>";
                        } else {
                            echo "Would redirect to: admin/index.php?login=1<br>";
                            
                            // Test admin file
                            ob_start();
                            include __DIR__ . '/admin/index.php';
                            $admin_output = ob_get_clean();
                            echo "✅ Admin file test passed<br>";
                        }
                    }
                    
                } else {
                    echo "❌ Password validation failed<br>";
                }
            } else {
                echo "❌ Empty password<br>";
            }
        } else {
            echo "❌ CSRF validation failed<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
    } catch (Error $e) {
        echo "❌ FATAL ERROR: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
    }
    
} else {
    echo "<h2>GET Request - Show Form</h2>";
    
    // Generate CSRF token
    require_once __DIR__ . '/lib/session.php';
    require_once __DIR__ . '/lib/csrf_utils.php';
    
    initializeSession();
    $csrf_token = generateCSRFToken();
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token) . "'>";
    echo "<label for='password'>Password:</label><br>";
    echo "<input type='password' id='password' name='password' required><br><br>";
    echo "<button type='submit'>Test Login</button>";
    echo "</form>";
    
    echo "<p><strong>Test with password: 4091</strong></p>";
}

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Server Information</h2>";
echo "Request Method: " . htmlspecialchars($_SERVER['REQUEST_METHOD']) . "<br>";
echo "Server Software: " . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "HTTP Host: " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
echo "Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown') . "<br>";

echo "<h2>Test Complete</h2>";
echo "<p><a href='login.php'>Try Original Login</a></p>";
?>
