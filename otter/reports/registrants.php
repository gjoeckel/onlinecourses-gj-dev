<?php
require_once __DIR__ . '/../lib/session.php';
initializeSession();

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_features.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Enterprise detection must succeed - no fallbacks allowed
if (isset($context['error'])) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

// Get enterprise configuration
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$displayName = $enterprise['display_name'] ?? 'Enterprise';
$page_name = 'Registrants';
$title = "$displayName $page_name";

// Load the data processing logic
require __DIR__ . '/registrations_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="css/reports-main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/date-range-picker.css">
    <link rel="stylesheet" href="css/reports-data.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/organization-search.css">
    <link rel="stylesheet" href="css/district-search.css">
    <link rel="stylesheet" href="css/reports-messaging.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/print.css" media="print">
    <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <script src="../lib/message-dismissal.js"></script>
</head>
<body>
    <main>
    <?php if (!$validRange): ?>
        <div class="error-message" role="alert" style="color:#b00; font-weight:bold; margin:2em 0;">A valid date range must be selected on the main reports page.</div>
    <?php else: ?>
    <div class="table-responsive">
        <button id="print-registrants-report" class="print-button no-print" onclick="window.print()">Print</button>
        <table id="registrants-data">
            <caption>Registrants | <?php echo htmlspecialchars($start); ?> to <?php echo htmlspecialchars($end); ?></caption>
            <thead>
                <tr>
                    <th scope="col">Cohort</th>
                    <th scope="col">Year</th>
                    <th scope="col">First</th>
                    <th scope="col">Last</th>
                    <th scope="col">Email</th>
                    <th scope="col">Organization</th>
                    <th scope="col">Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($filtered)): ?>
                <tr><td colspan="7">No registrants in this range.</td></tr>
            <?php else: ?>
                <?php foreach ($filtered as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row[3] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row[4] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row[5] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row[6] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row[7] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(isset($row[9]) ? abbreviateLinkText($row[9]) : ''); ?></td>
                    <td><?php echo htmlspecialchars($row[15] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    </main>
</body>
</html>