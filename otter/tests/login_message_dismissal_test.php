<?php
/**
 * Login Message Dismissal Test
 * Tests the message dismissal functionality to identify production vs local differences
 */

// Start session
require_once __DIR__ . '/../lib/session.php';
initializeSession();

// Set error message for testing
require_once __DIR__ . '/../lib/error_messages.php';
$_SESSION['test_error'] = ErrorMessages::getInvalidPassword();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Message Dismissal Test</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/messages.css">
    <script src="../lib/message-dismissal.js"></script>
</head>
<body class="status-page">
    <div class="main-container">
        <div class="heading-container">
            <h1>Message Dismissal Test</h1>
        </div>
        
        <div class="label-container">
            <label for="password">Password:</label>
        </div>
        
        <div class="buttons-container">
            <form method="post" autocomplete="off">
                <input type="password" id="password" name="password" aria-label="Password" required class="login-password-input">
                <button type="submit" class="button login-btn">Login</button>
            </form>
        </div>
        
        <div class="message-container">
            <div id="message-display" class="display-block error-message" role="alert" aria-live="assertive">
                <?php echo htmlspecialchars($_SESSION['test_error'] ?? 'Test error message'); ?>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #f5f5f5; border-radius: 4px;">
            <h3>Debug Information</h3>
            <div id="debug-info"></div>
        </div>
    </div>
    
    <script>
        // Debug information
        function logDebugInfo() {
            const debugDiv = document.getElementById('debug-info');
            const info = {
                'Current URL': window.location.href,
                'Pathname': window.location.pathname,
                'Hostname': window.location.hostname,
                'Protocol': window.location.protocol,
                'Message Display Found': !!document.getElementById('message-display'),
                'Password Input Found': !!document.getElementById('password'),
                'Message Dismissal Script Loaded': typeof MessageDismissal !== 'undefined',
                'Message Display Content': document.getElementById('message-display')?.textContent?.trim(),
                'Message Display Classes': document.getElementById('message-display')?.className,
                'Current Page (getCurrentPage)': window.messageDismissal?.getCurrentPage?.()
            };
            
            let html = '<ul>';
            for (const [key, value] of Object.entries(info)) {
                html += `<li><strong>${key}:</strong> ${value}</li>`;
            }
            html += '</ul>';
            
            debugDiv.innerHTML = html;
        }
        
        // Test message dismissal functionality
        function testMessageDismissal() {
            const passwordInput = document.getElementById('password');
            const messageDisplay = document.getElementById('message-display');
            
            if (passwordInput && messageDisplay) {
                console.log('Testing message dismissal...');
                console.log('Initial message content:', messageDisplay.textContent.trim());
                console.log('Initial message classes:', messageDisplay.className);
                
                // Simulate typing in password field
                passwordInput.value = 'test';
                passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                
                setTimeout(() => {
                    console.log('After input event - message content:', messageDisplay.textContent.trim());
                    console.log('After input event - message classes:', messageDisplay.className);
                    logDebugInfo();
                }, 100);
            }
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing test...');
            logDebugInfo();
            
            // Wait for message dismissal to initialize
            setTimeout(() => {
                console.log('Message dismissal instance:', window.messageDismissal);
                testMessageDismissal();
            }, 500);
        });
        
        // Add manual test button
        document.addEventListener('DOMContentLoaded', function() {
            const testButton = document.createElement('button');
            testButton.textContent = 'Test Message Dismissal';
            testButton.onclick = testMessageDismissal;
            testButton.style.marginTop = '1rem';
            document.querySelector('.main-container').appendChild(testButton);
        });
    </script>
</body>
</html> 