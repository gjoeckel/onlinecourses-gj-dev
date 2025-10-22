<?php
// Enterprise-focused dashboard
require_once __DIR__ . '/lib/session_simple.php';
require_once __DIR__ . '/lib/security_headers.php';
require_once __DIR__ . '/lib/unified_enterprise_config.php';
require_once __DIR__ . '/lib/new_database_service.php';

// Initialize session and security
initializeSession();
initializeSecurity();

// Get enterprise code from session or URL
$enterprise_code = $_GET['enterprise'] ?? $_SESSION['enterprise_code'] ?? '';

if (empty($enterprise_code)) {
    // Redirect to login if no enterprise
    header('Location: login.php');
    exit;
}

// Initialize enterprise configuration
UnifiedEnterpriseConfig::init($enterprise_code);

// Get enterprise data using new database service
$enterpriseService = new NewDatabaseService();
$enterprise_data = $enterpriseService->getEnterpriseSummary($enterprise_code);

$showGenericError = false;
if (isset($enterprise_data['error'])) {
    $showGenericError = true;
}

// Get enterprise display name
$enterprise_name = $enterprise_data['enterprise_name'] ?? ucfirst($enterprise_code);
$total_orgs = $enterprise_data['totals']['organizations'] ?? 0;
$total_enrollments = $enterprise_data['totals']['enrollments'] ?? 0;
$total_completed = $enterprise_data['totals']['completed'] ?? 0;
$total_certificates = $enterprise_data['totals']['certificates'] ?? 0;
$organizations = $enterprise_data['organizations'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($enterprise_name); ?> Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/svg+xml" href="lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="config/config.js"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <header class="dashboard-header">
        <div class="header-center">
            <h1><?php echo htmlspecialchars($enterprise_name); ?> Dashboard</h1>
            <p class="last-updated">Data from Canvas LMS</p>
        </div>
    </header>
    
    <main id="main-content">
        <?php if ($showGenericError): ?>
            <p class="error-message" role="alert">An error occurred while retrieving data. Please try again later or contact support.</p>
        <?php else: ?>
            <div id="global-toggle-controls">
                <button type="button" id="dismiss-info-button" class="close-button" aria-label="Hide master toggle switch">&times;</button>
                <p>Use this button
                    <button type="button" id="toggle-all-button" aria-expanded="false" aria-label="Show or hide data rows on all tables."></button>
                to show/hide the data rows on <strong>all</strong> tables. Use the buttons on each of the four tables to show/hide its data rows.</p>
            </div>
            
            <!-- Enterprise Summary -->
            <section>
                <div class="table-responsive">
                    <table class="enrollment-summary" id="enrollment-summary">
                        <caption>
                            Enterprise Summary
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide enterprise summary data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Total Organizations</th>
                                <th scope="col">Total Enrollments</th>
                                <th scope="col">Total Completed</th>
                                <th scope="col">Total Certificates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($total_orgs); ?></td>
                                <td><?php echo htmlspecialchars($total_enrollments); ?></td>
                                <td><?php echo htmlspecialchars($total_completed); ?></td>
                                <td><?php echo htmlspecialchars($total_certificates); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Organizations Breakdown -->
            <section>
                <div class="table-responsive">
                    <table class="enrolled-participants" id="enrolled-participants">
                        <caption>
                            Organizations | <span class="caption-count"><?php echo $total_orgs; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide organizations data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Organization</th>
                                <th scope="col">Enrollments</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Certificates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($organizations)): ?>
                                <tr><td colspan="4">No organization data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($organizations as $org): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($org['name']); ?></td>
                                        <td><?php echo htmlspecialchars($org['enrollments']); ?></td>
                                        <td><?php echo htmlspecialchars($org['completed']); ?></td>
                                        <td><?php echo htmlspecialchars($org['certificates']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- All Enrollments -->
            <section>
                <div class="table-responsive">
                    <table class="enrolled-participants" id="all-enrollments">
                        <caption>
                            All Enrollments | <span class="caption-count"><?php echo $total_enrollments; ?></span>
                            <button type="button" class="table-toggle-button" aria-expanded="false" aria-label="Show or hide all enrollments data rows."></button>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Organization</th>
                                <th scope="col">Course</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Status</th>
                                <th scope="col">Completed</th>
                                <th scope="col">Certificate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_enrollments = $enterprise_data['all_enrollments'] ?? [];
                            if (empty($all_enrollments)): ?>
                                <tr><td colspan="7">No enrollment data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($all_enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['Organization'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['CourseName'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['Name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['Email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['Status'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['Completed'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['Certificate'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    
    <script src="lib/dashboard-link-utils.js"></script>
    <script src="lib/table-interaction.js"></script>
</body>
</html> 