<?php
// Start session first
require_once __DIR__ . '/../lib/session.php';
initializeSession();

// Load enterprise configuration
// STANDARDIZED: Uses UnifiedEnterpriseConfig for enterprise detection and config access
require_once __DIR__ . '/../lib/unified_enterprise_config.php';

// Initialize enterprise and environment from single source of truth
// STANDARDIZED: Uses UnifiedEnterpriseConfig::initializeFromRequest() pattern
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Enterprise detection must succeed - no fallbacks allowed
if (isset($context['error'])) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cache control headers to prevent caching issues
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include Database class
require_once(__DIR__ . '/../lib/unified_database.php');
require_once(__DIR__ . '/../lib/direct_link.php');
$db = new UnifiedDatabase();

// Message logic
$message_content = '';
$message_type = '';
$message_role = 'status';
$message_aria = 'polite';
$has_message = false;

// Load shared abbreviation utility
require_once __DIR__ . '/../lib/abbreviation_utils.php';

// Abbreviate organization names using prioritized, single-abbreviation logic
function abbreviateLinkText($name) {
    return abbreviateOrganizationName($name);
}

/**
 * Generate available passwords closest to the target password
 */
function generateAvailablePasswords($existing_passwords, $count = 3, $target_password = null) {
    $available = [];

    if ($target_password !== null) {
        // Find the closest available passwords numerically
        $target_num = intval($target_password);
        $candidates = [];

        // Generate candidates around the target password
        for ($i = 1; $i <= 1000; $i++) {
            // Check numbers above and below the target
            $above = $target_num + $i;
            $below = $target_num - $i;

            // Ensure 4-digit format
            if ($above <= 9999) {
                $above_str = str_pad($above, 4, '0', STR_PAD_LEFT);
                if (!in_array($above_str, $existing_passwords) && !in_array($above_str, $candidates)) {
                    $candidates[] = $above_str;
                }
            }

            if ($below >= 0) {
                $below_str = str_pad($below, 4, '0', STR_PAD_LEFT);
                if (!in_array($below_str, $existing_passwords) && !in_array($below_str, $candidates)) {
                    $candidates[] = $below_str;
                }
            }

            // Stop when we have enough candidates
            if (count($candidates) >= $count * 2) {
                break;
            }
        }

        // Sort candidates by distance from target
        usort($candidates, function($a, $b) use ($target_num) {
            $dist_a = abs(intval($a) - $target_num);
            $dist_b = abs(intval($b) - $target_num);
            return $dist_a - $dist_b;
        });

        // Take the closest ones
        $available = array_slice($candidates, 0, $count);
    }

    // Fallback: if we don't have enough candidates or no target, use random generation
    if (count($available) < $count) {
        $attempts = 0;
        $max_attempts = 100;

        while (count($available) < $count && $attempts < $max_attempts) {
            $password = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            if (!in_array($password, $existing_passwords) && !in_array($password, $available)) {
                $available[] = $password;
            }
            $attempts++;
        }
    }

    return $available;
}

// Handle AJAX Change Password only - no regular POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    try {
        $orgName = $_POST['org_name'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $success = false;
        $message = '';

        if (!empty($orgName) && !empty($newPassword)) {
                $currentPassword = '';
                $orgs = $db->getAllOrganizations();
                foreach ($orgs as $org) {
                    if ($org['name'] === $orgName) {
                        $currentPassword = $org['password'];
                        break;
                    }
                }

                if ($currentPassword === $newPassword) {
                    $message = 'New password is the same as current password.';
                    $success = false; // Explicitly set success to false for error case
                } else {
                    // Get enterprise code for ADMIN updates
                    // STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterpriseCode() pattern
                    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
                    if ($db->updatePassword($orgName, $newPassword, $enterprise_code)) {
                        try {
                            // Update related files and regenerate direct links
                            $reportsApi = __DIR__ . '/../reports/reports_api.php';
                            // STANDARDIZED: Uses UnifiedEnterpriseConfig::getStartDate() pattern
                            $startDate = UnifiedEnterpriseConfig::getStartDate();
                            $endDate = date('m-d-y');
                            shell_exec("php $reportsApi start_date=$startDate end_date=$endDate");

                            $message = "$orgName password updated to $newPassword.";
                            $success = true;
                        } catch (Exception $e) {
                            $message = "Password updated but encountered an error updating related files: " . $e->getMessage() . " $orgName password updated to $newPassword.";
                            $success = true; // Password was still updated
                        }
                    } else {
                        // Get all existing passwords to generate suggestions
                        $existing_passwords = [];
                        $orgs = $db->getAllOrganizations();
                        foreach ($orgs as $org) {
                            if (isset($org['password'])) {
                                $existing_passwords[] = $org['password'];
                            }
                        }

                        // Generate 3 available passwords closest to the entered password
                        $available_passwords = generateAvailablePasswords($existing_passwords, 3, $newPassword);
                        $available_list = implode(', ', $available_passwords);

                        $message = "Password already in use. Available passwords: [$available_list]";
                    }
                }
        } else {
            $message = 'Organization name and new password are required.';
        }

        echo json_encode(['success' => $success, 'message' => $message]);

    } catch (Exception $e) {
        http_response_code(500);
                        require_once __DIR__ . '/../lib/error_messages.php';
                echo json_encode(['success' => false, 'message' => ErrorMessages::getTechnicalDifficulties()]);
    }
    exit;
}

// Handle AJAX request for updated organization data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_organizations' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $organizations = getAllOrganizations();
    echo json_encode(['organizations' => $organizations]);
    exit;
}

// Handle AJAX request for table organization data (excluding ADMIN)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_table_organizations' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $table_organizations = getTableOrganizations();
    echo json_encode(['organizations' => $table_organizations]);
    exit;
}

// Get all organizations
function getAllOrganizations()
{
    global $db;
    // STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterpriseCode() pattern
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    $all_orgs = $db->getOrganizationsByEnterprise($enterprise_code);
    $admin_org = $db->getAdminOrganization($enterprise_code);
    $filtered_orgs = [];
    if ($admin_org && strtolower($admin_org['name']) !== 'super admin') {
        $filtered_orgs[] = $admin_org;
    }
    foreach ($all_orgs as $org) {
        if ((!isset($org['is_admin']) || $org['is_admin'] !== true) && strtolower($org['name']) !== 'super admin') {
            $filtered_orgs[] = $org;
        }
    }
    return $filtered_orgs;
}

// Get organizations for table display (excluding ADMIN)
function getTableOrganizations()
{
    global $db;
    // STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterpriseCode() pattern
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    $all_orgs = $db->getOrganizationsByEnterprise($enterprise_code);
    $filtered_orgs = [];
    foreach ($all_orgs as $org) {
        if ((!isset($org['is_admin']) || $org['is_admin'] !== true) && strtolower($org['name']) !== 'super admin') {
            $filtered_orgs[] = $org;
        }
    }
    return $filtered_orgs;
}

// Get organizations for display
$organizations = getAllOrganizations();

// The organizations array as returned by getAllOrganizations() already includes ADMIN as the first entry (from org_data.txt)
// The select menu and table below will always display ADMIN first as long as org_data.txt is not modified to move ADMIN elsewhere.

// Get enterprise information for display
// STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterprise() pattern
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$page_name = 'Settings';
$display_name = $enterprise['display_name'] ?? 'Enterprise';
$title = "$display_name $page_name";

// Get start date configuration
// STANDARDIZED: Uses UnifiedEnterpriseConfig::getStartDate() pattern
$startDate = UnifiedEnterpriseConfig::getStartDate();
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($title); ?> </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="../css/settings.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href="/lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="../css/print.css?v=<?php echo time(); ?>" media="print">
    <link rel="stylesheet" href="../css/messages.css">
    <script src="../lib/message-dismissal.js"></script>
    <script src="../lib/table-filter-interaction.js"></script>
    <script type="module" src="../lib/dashboard-link-utils.js"></script>

    <!-- Disable shared message dismissal for settings page - using custom logic -->
    <script>
        // Override shared message dismissal for settings page
        document.addEventListener('DOMContentLoaded', function() {
            // Disable the shared message dismissal utility for this page
            if (window.messageDismissal) {
                window.messageDismissal.disabled = true;
            }
        });
    </script>

    <script type="module">
        import { initializePrintFunctionality } from '../lib/print-utils.js';

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize print functionality
            initializePrintFunctionality({
                cssPath: '../css/print.css',
                printButtons: [
                    { id: 'dashboard-print-btn', type: 'page' }
                ]
            });

            // Accessibility: Move focus to Dashboards caption when skip link is used
            var skipLink = document.querySelector('.skip-link');
            var caption = document.getElementById('dashboard-caption');
            if (skipLink && caption) {
                skipLink.addEventListener('click', function(e) {
                    setTimeout(function() {
                        caption.focus();
                    }, 0);
                });
            }
        });
    </script>
</head>

<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <header>
        <div class="header-spacer"></div>
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <nav>
            <a href="../admin/index.php?auth=1" id="back-btn" class="link" aria-label="Back to admin home">Back</a>
        </nav>
    </header>

    <div class="container admin-home" id="main-content">
        <div class="change-passwords-section">
            <div class="section-header">
                <h2>
                    Change Passwords
                    <button type="button" id="toggle-passwords-button" aria-expanded="false" aria-label="Show change passwords section"></button>
                </h2>
            </div>
            <div class="section-content" id="passwords-content">
                <form method="POST" id="passwordForm" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-grid-row">
                        <div class="form-grid-col org-col">
                            <label for="org_name">Select Organization</label>
                            <select name="org_name" id="org_name" required class="searchable-select" data-placeholder="Type to search...">
                                <option value=""></option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo htmlspecialchars($org['name']); ?>" data-password="<?php echo htmlspecialchars($org['password']); ?>">
                                        <?php echo htmlspecialchars(abbreviateLinkText($org['name'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-grid-col current-col">
                            <label for="current_password">Current</label>
                            <input type="text"
                                id="current_password"
                                readonly
                                tabindex="0"
                                aria-label="Current Password"
                                aria-live="polite">
                        </div>
                        <div class="form-grid-col new-col">
                            <label for="new_password">New (4 digits)</label>
                            <input type="text" name="new_password" id="new_password" required maxlength="10">
                        </div>
                        <div class="form-grid-col button-col">
                            <label class="visually-hidden" for="change-btn">Change</label>
                            <button type="submit" id="change-btn">Change</button>
                        </div>
                    </div>
                </form>
                <div class="message-container">
                    <div id="message-display" class="display-block visually-hidden-but-space" role="status" aria-live="polite" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table id="dashboard-table">
                <caption id="dashboard-caption" tabindex="-1">Dashboards</caption>
                <div class="table-controls">
                    <button id="dashboard-print-btn" class="print-button" aria-label="Print organizations table">Print</button>
                </div>
                <thead>
                    <tr>
                        <th class="org-name-col">Name</th>
                        <th class="password-col">Password</th>
                        <th class="direct-link-col">Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $table_organizations = getTableOrganizations();
                    foreach ($table_organizations as $i => $org): ?>
                        <tr>
                            <td class="org-name-col org-name-cell"><?php echo htmlspecialchars($org['name']); ?></td>
                            <td class="password-col password-cell"><?php echo htmlspecialchars($org['password']); ?></td>
                            <td class="direct-link-col direct-link-cell">
                                <?php
                                $url = DirectLink::getDashboardUrlPHP($org['password']);
                                // Use directory traversal for subdirectory context
                                $url = '../' . $url;
                                // Use abbreviation function (no redeclaration)
                                $abbrevName = abbreviateLinkText($org['name']);
                                echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="dashboard-link" data-org="' . htmlspecialchars(strtolower(trim($org['name']))) . '">' . htmlspecialchars($abbrevName) . '</a>';
                                echo '<span class="print-url" style="display: none;">' . htmlspecialchars($url) . '</span>';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Pass PHP path value to JavaScript
        window.APP_PATH = "";
    </script>

    <script type="module">
    import { renderDashboardLink, clearEnterpriseCache } from '../lib/dashboard-link-utils.js';

    // Function to refresh table data without page reload
    async function refreshTableData() {
        try {
            // First, clear the enterprise cache to ensure fresh data
            if (typeof clearEnterpriseCache === 'function') {
                clearEnterpriseCache();
            }

            // Fetch fresh organization data for dropdown (includes ADMIN)
            const dropdownResponse = await fetch(window.location.pathname + '?action=get_organizations', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!dropdownResponse.ok) {
                throw new Error(`HTTP ${dropdownResponse.status}: ${dropdownResponse.statusText}`);
            }

            const dropdownData = await dropdownResponse.json();

            // Update the organization select dropdown
            const orgSelect = document.getElementById('org_name');
            if (orgSelect && dropdownData.organizations) {
                // Clear existing options except the first placeholder
                while (orgSelect.options.length > 1) {
                    orgSelect.remove(1);
                }

                // Add updated organizations (including ADMIN)
                <?php echo getAbbreviationJavaScript(); ?>
                dropdownData.organizations.forEach(org => {
                    const option = document.createElement('option');
                    option.value = org.name;
                    option.textContent = abbreviateOrganizationNameJS(org.name);
                    option.setAttribute('data-password', org.password);
                    orgSelect.appendChild(option);
                });
            }

            // Fetch fresh table organization data (excluding ADMIN)
            const tableResponse = await fetch(window.location.pathname + '?action=get_table_organizations', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!tableResponse.ok) {
                throw new Error(`HTTP ${tableResponse.status}: ${tableResponse.statusText}`);
            }

            const tableData = await tableResponse.json();

            if (tableData.organizations) {
                // Update the table rows (excluding ADMIN)
                const tbody = document.querySelector('#dashboard-table tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                    tableData.organizations.forEach((org, index) => {
                        const row = document.createElement('tr');
                        const normalizedOrgName = org.name.trim().toLowerCase();

                        // Regular organization row - create with proper structure
                        row.innerHTML = `
                            <td class="org-name-col org-name-cell">${org.name}</td>
                            <td class="password-col password-cell">${org.password}</td>
                            <td class="direct-link-col direct-link-cell" data-org="${normalizedOrgName}">
                                <span class="direct-link-placeholder">Loading...</span>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }

                // Fetch and update direct links
                await refreshDirectLinks();
            } else {
                console.error('No table organizations data received:', tableData);
            }
        } catch (err) {
            console.error('Error refreshing table data:', err);
            // Don't show error to user for table refresh - it's not critical
        }
    }

    // Function to refresh direct links (simplified approach)
    async function refreshDirectLinks() {
        try {
            // Get organization data from the table rows directly
            const tableRows = document.querySelectorAll('#dashboard-table tbody tr');

            tableRows.forEach(row => {
                const nameCell = row.querySelector('.org-name-cell');
                const passwordCell = row.querySelector('.password-cell');
                const linkCell = row.querySelector('.direct-link-cell');

                if (nameCell && passwordCell && linkCell) {
                    const orgName = nameCell.textContent.trim();
                    const password = passwordCell.textContent.trim();
                    const normalizedOrgName = orgName.toLowerCase();

                    // Remove any existing placeholder
                    const placeholder = linkCell.querySelector('.direct-link-placeholder');
                    if (placeholder) {
                        placeholder.remove();
                    }

                    // Remove any existing link
                    const existingLink = linkCell.querySelector('.dashboard-link');
                    if (existingLink) {
                        existingLink.remove();
                    }

                    // Create new link using simple URL format
                    const link = document.createElement('a');
                    link.href = '../dashboard.php?org=' + password;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'dashboard-link';
                    link.setAttribute('data-org', normalizedOrgName);

                    // Abbreviate organization names
                    <?php echo getAbbreviationJavaScript(); ?>
                    link.textContent = abbreviateOrganizationNameJS(orgName);

                    // Create print URL span
                    const printUrlSpan = document.createElement('span');
                    printUrlSpan.className = 'print-url';
                    printUrlSpan.style.display = 'none';
                    printUrlSpan.textContent = '../dashboard.php?org=' + password;

                    // Add both elements to cell
                    linkCell.appendChild(link);
                    linkCell.appendChild(printUrlSpan);
                }
            });
        } catch (error) {
            console.error('Error refreshing direct links:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', async function() {
        // Initialize toggle functionality for change passwords section
        const toggleButton = document.getElementById('toggle-passwords-button');
        const content = document.getElementById('passwords-content');

        if (toggleButton && content) {
            // Set default state: collapsed
            content.classList.remove('visible');
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('aria-label', 'Show change passwords section');

            toggleButton.addEventListener('click', function() {
                const expanded = toggleButton.getAttribute('aria-expanded') === 'true';

                if (expanded) {
                    // Collapse: hide content
                    content.classList.remove('visible');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    toggleButton.setAttribute('aria-label', 'Show change passwords section');
                } else {
                    // Expand: show content
                    content.classList.add('visible');
                    toggleButton.setAttribute('aria-expanded', 'true');
                    toggleButton.setAttribute('aria-label', 'Hide change passwords section');
                }
            });

            // Add keyboard handler (Enter/Space) - following validated pattern
            toggleButton.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        }

        // Initialize organization select functionality
        const orgSelect = document.getElementById('org_name');
        const currentPasswordInput = document.getElementById('current_password');

        if (orgSelect) {
            orgSelect.addEventListener('change', function() {
                const selected = orgSelect.options[orgSelect.selectedIndex];
                const password = selected.getAttribute('data-password') || '';
                currentPasswordInput.value = password;
            });

            // Optionally, set on page load if a value is pre-selected
            if (orgSelect.value) {
                const selected = orgSelect.options[orgSelect.selectedIndex];
                const password = selected.getAttribute('data-password') || '';
                currentPasswordInput.value = password;
            }
        }

        // AJAX Change Password
        const passwordForm = document.getElementById('passwordForm');
        const messageDisplay = document.getElementById('message-display');
        const sectionContent = document.getElementById('passwords-content');
        const submitBtn = passwordForm ? passwordForm.querySelector('button[type="submit"]') : null;
        const newPasswordInput = document.getElementById('new_password');
        let isSubmitting = false;

        // Function to handle error state
        function handleErrorState(message) {
            // Update message display
            messageDisplay.textContent = message;
            messageDisplay.className = 'display-block error-message';
            messageDisplay.setAttribute('aria-live', 'assertive');
            messageDisplay.removeAttribute('aria-hidden');

            // Set focus to New Password input
            if (newPasswordInput) {
                newPasswordInput.focus();
            }

            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            // Add custom error dismissal logic (reuse login pattern)
            if (newPasswordInput) {
                // Remove any existing listeners to prevent duplicates
                newPasswordInput.removeEventListener('input', handleErrorDismissal);
                // Add listener for error dismissal when input becomes non-blank
                newPasswordInput.addEventListener('input', handleErrorDismissal);
            }
        }

        // Function to handle success state
        function handleSuccessState(message) {
            // Update message display
            messageDisplay.textContent = message;
            messageDisplay.className = 'display-block success-message';
            messageDisplay.setAttribute('aria-live', 'polite');
            messageDisplay.removeAttribute('aria-hidden');

            // Set focus to Select Organization
            if (orgSelect) {
                orgSelect.focus();
            }

            // Enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
            }

            // Add custom success dismissal logic
            addSuccessDismissalListeners();
        }

        // Function to handle error dismissal (reuse login pattern)
        function handleErrorDismissal(event) {
            // Only dismiss if the input is no longer empty
            if (event.target.value.trim() !== '') {
                // Remove the listener to prevent multiple calls
                event.target.removeEventListener('input', handleErrorDismissal);
                // Clear the error message
                resetFormState();
            }
        }

        // Function to add success dismissal listeners
        function addSuccessDismissalListeners() {
            // Remove any existing listeners to prevent duplicates
            removeSuccessDismissalListeners();

            // Dismiss on new option selected in Select Organization
            if (orgSelect) {
                orgSelect.addEventListener('change', dismissSuccessMessage);
            }

            // Dismiss when Change Passwords is toggled to collapsed
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    if (expanded) {
                        // Will be collapsed, dismiss success message
                        setTimeout(dismissSuccessMessage, 100); // Small delay to ensure toggle completes
                    }
                });
            }

            // Dismiss on Print button or link in Dashboards table clicked
            const printButtons = document.querySelectorAll('.organization-search-print, .district-search-print, .dashboard-link');
            printButtons.forEach(button => {
                button.addEventListener('click', dismissSuccessMessage);
            });
        }

        // Function to remove success dismissal listeners
        function removeSuccessDismissalListeners() {
            if (orgSelect) {
                orgSelect.removeEventListener('change', dismissSuccessMessage);
            }
            if (toggleButton) {
                toggleButton.removeEventListener('click', dismissSuccessMessage);
            }
            const printButtons = document.querySelectorAll('.organization-search-print, .district-search-print, .dashboard-link');
            printButtons.forEach(button => {
                button.removeEventListener('click', dismissSuccessMessage);
            });
        }

        // Function to dismiss success message
        function dismissSuccessMessage() {
            // Only dismiss if it's a success message
            if (messageDisplay.className.includes('success-message')) {
                resetFormState();
                removeSuccessDismissalListeners();
            }
        }

        // Function to reset form state
        function resetFormState() {
            // Clear error message
            messageDisplay.textContent = '';
            messageDisplay.className = 'display-block visually-hidden-but-space';
            messageDisplay.setAttribute('aria-live', 'polite');
            messageDisplay.setAttribute('aria-hidden', 'true');

            // Enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }

        // Initialize form state on page load
        if (messageDisplay && submitBtn) {
            // Clear any existing error messages and enable button
            resetFormState();
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (isSubmitting) return;
                isSubmitting = true;
                if (submitBtn) submitBtn.disabled = true;
                const formData = new FormData(passwordForm);
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {

                    if (data.success) {
                        // Success state - use custom success handler
                        handleSuccessState(data.message);

                        // Reset form
                        passwordForm.reset();

                        // Add a small delay to ensure database write is complete before refreshing table
                        setTimeout(() => {
                            // Refresh table data without page reload
                            // The refreshTableData function now handles cache clearing internally
                            refreshTableData();
                        }, 100);
                    } else {
                        // Error state - use custom error handler
                        handleErrorState(data.message || 'Password change failed');
                    }
                })
                .catch((err) => {
                    console.error('AJAX error details:', err);
                    let errorMessage = 'An error occurred. Please try again.';

                    if (err.name === 'TypeError' && err.message.includes('JSON')) {
                        errorMessage = 'Server returned invalid response. Please try again.';
                    } else if (err.message.includes('HTTP')) {
                        errorMessage = `Server error: ${err.message}`;
                    } else if (err.name === 'NetworkError') {
                        errorMessage = 'Network connection error. Please check your connection and try again.';
                    }

                    handleErrorState(errorMessage);
                })
                .finally(() => {
                    isSubmitting = false;
                    // Don't re-enable button here - let input change handler manage it
                });
            });
        }

        // Initialize direct links functionality
        // Links are now server-side rendered, so no initial fetch needed
    });
    </script>

    <script>
    // Dynamically fit #message-display to text width + 40px (20px padding each side), but never exceed container width and never wrap
    function fitMessageDisplay() {
        const el = document.getElementById('message-display');
        if (!el || el.textContent.trim() === '') return;
        // Create a temporary span to measure the text width
        const temp = document.createElement('span');
        temp.style.visibility = 'hidden';
        temp.style.position = 'absolute';
        temp.style.whiteSpace = 'nowrap';
        temp.style.font = window.getComputedStyle(el).font;
        temp.textContent = el.textContent;
        document.body.appendChild(temp);
        const textWidth = temp.offsetWidth;
        document.body.removeChild(temp);

        // Get the container width (700px max)
        const container = el.closest('.container');
        const containerWidth = container ? container.offsetWidth : 700;
        const maxWidth = containerWidth;

        // Calculate desired width
        const desiredWidth = textWidth + 40;
        const finalWidth = Math.min(desiredWidth, maxWidth);

        el.style.width = finalWidth + 'px';
        el.style.display = 'inline-block';
        el.style.paddingLeft = '20px';
        el.style.paddingRight = '20px';
        el.style.margin = '1rem auto 0 auto';
        el.style.boxSizing = 'border-box';
        el.style.whiteSpace = 'nowrap'; // Prevent wrapping

        // Center the message
        if (el.parentElement) {
            el.parentElement.style.textAlign = 'center';
        }
    }

    // Observe changes to #message-display and fit width
    const msgEl = document.getElementById('message-display');
    if (msgEl) {
        const observer = new MutationObserver(() => {
            fitMessageDisplay();
        });
        observer.observe(msgEl, { childList: true, characterData: true, subtree: true });
        // Initial fit
        fitMessageDisplay();
    }
    </script>

</body>

</html>