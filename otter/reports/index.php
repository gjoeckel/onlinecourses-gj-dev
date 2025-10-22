<?php
// Start session first (same as admin page)
require_once __DIR__ . '/../lib/session.php';
initializeSession();

require_once __DIR__ . '/../lib/direct_link.php';
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_features.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

/**
 * Generate filter label from table caption
 * @param string $caption The table caption (e.g., "Districts Data", "Organizations Data")
 * @return string The filter label (e.g., "Districts Filter", "Organizations Filter")
 */
function generateFilterLabel($caption) {
    // Remove "Data" from the end of the caption and add "Filter"
    $baseName = trim(str_replace(' Data', '', $caption));
    return $baseName . ' Filter';
}

// Initialize enterprise and environment from single source of truth
// STANDARDIZED: Uses UnifiedEnterpriseConfig::initializeFromRequest() pattern
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Enterprise detection must succeed - no fallbacks allowed
if (isset($context['error'])) {
    require_once __DIR__ . '/../lib/error_messages.php';
    http_response_code(500);
    die(ErrorMessages::getTechnicalDifficulties());
}

// Get enterprise configuration
// STANDARDIZED: Uses UnifiedEnterpriseConfig::getEnterprise() pattern
$enterprise = UnifiedEnterpriseConfig::getEnterprise();
$displayName = $enterprise['display_name'] ?? 'Enterprise';
$page_name = 'Reports';
$title = "$displayName $page_name";



// Handle AJAX date range set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'set_date_range.php') !== false) {
  $input = json_decode(file_get_contents('php://input'), true);
  $start = $input['start_date'] ?? '';
  $end = $input['end_date'] ?? '';
  if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $end)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
  } else {
    header('Content-Type: application/json');
            require_once __DIR__ . '/../lib/error_messages.php';
        echo json_encode(['success' => false, 'error' => ErrorMessages::getTechnicalDifficulties()]);
    exit;
  }
}

// Get table caption bases from config
// STANDARDIZED: Uses UnifiedEnterpriseConfig::get() pattern for config values
$captions = UnifiedEnterpriseConfig::get('reports_table_captions', []);
$organizationsBase = $captions['organizations'] ?? 'Organizations';
$groupsBase = $captions['groups'] ?? 'Districts';

$organizationsCaption = $organizationsBase . ' Data';
$organizationsFilterLabel = $organizationsBase . ' Filter';

$groupsCaption = $groupsBase . ' Data';
$groupsFilterLabel = $groupsBase . ' Filter';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" href="css/reports-main.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/date-range-picker.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/reports-data.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/organization-search.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/groups-search.css?v=<?php echo time(); ?>">
      <link rel="stylesheet" href="../css/messages.css">
    <link rel="stylesheet" href="css/reports-messaging.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/buttons.css">
  <!-- Load flatpickr CSS/JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link rel="icon" type="image/svg+xml" href="../lib/otter.svg">
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <script src="https://unpkg.com/wicg-inert@3.1.2/dist/inert.min.js"></script>
      <link rel="stylesheet" href="../css/print.css?v=<?php echo time(); ?>" media="print">
  <script src="../lib/message-dismissal.js"></script>
  <style>
    /* Groups section visibility controlled by PHP logic */
    .groups-section-hidden {
      display: none;
    }

    .groups-section-visible {
      display: block;
    }
  </style>
  <script type="module">
    import { initializePrintFunctionality } from '../lib/print-utils.js';

    window.APP_PATH = '';
    window.HAS_GROUPS = <?php echo EnterpriseFeatures::supportsGroups() ? 'true' : 'false'; ?>;
    window.ENTERPRISE_CODE = '<?php echo UnifiedEnterpriseConfig::getEnterpriseCode(); ?>';

    // Shared abbreviation function
    <?php echo getAbbreviationJavaScript(); ?>

    document.addEventListener('DOMContentLoaded', function() {
      // Initialize print functionality
      initializePrintFunctionality({
        cssPath: '../css/print.css',
        printButtons: [
          {
            id: 'organization-search-print',
            type: 'window',
            sectionId: 'organization-section',
            title: '<?php echo htmlspecialchars($organizationsCaption); ?>',
            orientation: 'landscape'
          },
          {
            id: 'groups-search-print',
            type: 'window',
            sectionId: 'groups-section',
            title: '<?php echo htmlspecialchars($groupsCaption); ?>',
            orientation: 'portrait'
          }
        ]
      });
    });
  </script>
</head>

<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <header>
    <div class="header-spacer"></div>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <nav>
      <button id="edit-date-range" type="button" class="btn action-btn" aria-expanded="true" aria-controls="date-picker-container" aria-label="Edit date range" disabled>
        Edit Date Range
      </button>
      <a href="../admin/index.php?auth=1" id="back-btn" class="btn back-btn" aria-label="Back to admin home">Back</a>
      <form method="get" action="../login.php" id="logout-form">
        <input type="hidden" name="logout" value="1" aria-label="Logout confirmation">
        <?php if (UnifiedEnterpriseConfig::isLocal()): ?>
        <input type="hidden" name="local" value="1">
        <?php endif; ?>
        <button type="submit" class="link" id="logout-btn">Logout</button>
      </form>
    </nav>
  </header>

  <main id="main-content">
    <!-- Date Range Picker Container (Expandable/Collapsible) -->
    <div id="date-picker-container" class="date-picker-container expanded" tabindex="-1">
      <h2 class="date-picker-heading">Select Date Range</h2>
      <fieldset class="date-picker-presets" role="group" aria-labelledby="preset-ranges-label">
        <legend id="preset-ranges-label" class="date-picker-presets-legend">Preset Ranges</legend>
        <div class="date-picker-presets-options">
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="none" class="date-picker-presets-radio" checked> None
          </label>
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="today" class="date-picker-presets-radio"> Today
          </label>
          <label class="date-picker-presets-label">
            <input type="radio" name="date-preset" value="past-month" class="date-picker-presets-radio"> Past Month
          </label>
          <?php if (EnterpriseFeatures::supportsQuarterlyPresets()): ?>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q1" class="date-picker-presets-radio"> Q1
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q2" class="date-picker-presets-radio"> Q2
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q3" class="date-picker-presets-radio"> Q3
          </label>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="q4" class="date-picker-presets-radio"> Q4
          </label>
          <?php endif; ?>
          <label class="date-picker-presets-label-compact">
            <input type="radio" name="date-preset" value="all" class="date-picker-presets-radio"> All
          </label>
        </div>
      </fieldset>
      <div class="date-picker-content">
        <div class="accessible-date-range-picker">
          <div class="date-picker-group">
            <label for="start-date">Start Date (MM-DD-YY)</label>
            <input id="start-date" class="flatpickr" type="text" placeholder="MM-DD-YY" aria-label="Start date" required aria-describedby="start-date-help">
            <div id="start-date-help" class="help-text sr-only">Enter date in MM-DD-YY format (e.g., 01-15-24)</div>
          </div>
          <div class="date-picker-group">
            <label for="end-date">End Date (MM-DD-YY)</label>
            <input id="end-date" class="flatpickr" type="text" placeholder="MM-DD-YY" aria-label="End date" required aria-describedby="end-date-help">
            <div id="end-date-help" class="help-text sr-only">Enter date in MM-DD-YY format (e.g., 01-15-24)</div>
          </div>
        </div>
        <div class="date-picker-actions">
        <button id="apply-range-button" class="btn action-btn" type="button" disabled aria-describedby="apply-button-status">Apply</button>
        <button id="clear-dates-button" class="btn clear-btn" type="button" disabled aria-label="Clear date range" aria-describedby="clear-button-status">Clear</button>
        <div id="apply-button-status" class="sr-only">Button is disabled until both start and end dates are entered</div>
        <div id="clear-button-status" class="sr-only">Button is disabled when no dates are entered</div>
        </div>
        <div class="message-container">
            <div id="message-display" class="date-range-status" aria-live="polite"><strong>Active Date Range:</strong> <span id="active-range-values"></span></div>
        </div>
      </div>
    </div>
    </div>
    <div id="range-reports">
    <section id="systemwide-section">
      <div class="table-responsive systemwide-data">
        <table class="systemwide-data" id="systemwide-data" aria-label="Systemwide Data">
          <caption>
            Systemwide Data
          </caption>
          <thead>
            <tr>
              <th scope="col">Start Date</th>
              <th scope="col">End Date</th>
              <th scope="col">Registrations</th>
              <th scope="col">Enrollments</th>
              <th scope="col">Certificates</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="5">Select a date range to view systemwide data</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td style="height: 60px; padding: 0;"></td>
              <td style="height: 60px; padding: 0;"></td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="registrations-report-link" href="#" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Registrants Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="enrollments-report-link" href="#" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Enrollees Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
              <td style="height: 60px; padding: 0;">
                <div class="report-link-container">
                  <a id="certificates-report-link" href="#" class="link external-link" target="_blank" rel="noopener noreferrer">
                    Cert Earners Report
                    <span aria-label="Opens in a new tab" role="img" class="inline-block-middle">
                      <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false" class="svg-middle">
                        <path d="M14.5 2A1.5 1.5 0 0 1 16 3.5V7a1 1 0 1 1-2 0V5.41l-7.3 7.3a1 1 0 0 1-1.4-1.42l7.3-7.29H11a1 1 0 1 1 0-2h3.5ZM4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 1 2 0v3a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h3a1 1 0 1 1 0 2H4Z"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

    </section>

    <section id="organization-section">
      <div id="organization-search-widget" class="organization-search-widget">
        <div class="organization-data-display-wrapper">
          <label for="organization-data-display" class="organizations-data-display"><?php echo htmlspecialchars($organizationsBase); ?> Data Display</label>
          <div class="organization-data-display-container">
            <div class="organization-data-display-options">
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="all" class="organization-data-display-radio" checked> show all rows
              </label>
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="no-values" class="organization-data-display-radio"> show all rows with no data
              </label>
              <label class="organization-data-display-label">
                <input type="radio" name="organization-data-display" value="hide-empty" class="organization-data-display-radio"> show all rows with data
              </label>
            </div>
            <div class="message-container">
              <div id="organization-data-display-message" class="date-range-status" aria-live="polite"></div>
            </div>
          </div>
        </div>
        <form id="organization-search-form" autocomplete="off" class="organization-search-form">
          <div class="organization-search-input-container">
            <label for="organization-search-input" class="organization-search-label"><?php echo htmlspecialchars($organizationsFilterLabel); ?></label>
            <input id="organization-search-input" name="organization-search-input" type="text" list="organization-search-datalist" aria-label="<?php echo htmlspecialchars($organizationsFilterLabel); ?>" class="organization-search-input" data-table-id="organization-data" />
            <datalist id="organization-search-datalist"></datalist>
          </div>
          <div class="organization-search-buttons">
            <button type="submit" id="organization-search-find" class="btn action-btn" disabled>Filter</button>
            <button type="button" id="organization-dashboard-btn" class="btn action-btn" disabled>Dashboard</button>
            <button type="button" id="organization-search-clear" class="btn clear-btn" disabled>Clear</button>
            <button type="button" id="organization-search-print" class="btn action-btn">Print</button>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="organization-data" id="organization-data" aria-label="Organizations Data">
          <caption>
            <?php echo htmlspecialchars($organizationsCaption); ?>
            <button type="button" id="organization-toggle-btn" class="table-toggle-button" aria-expanded="false" aria-label="Show data rows"></button>
          </caption>
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Registrations</th>
              <th scope="col">Enrollments</th>
              <th scope="col">Certificates</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="4">Select a date range to view organization data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section id="groups-section" class="<?php echo EnterpriseFeatures::supportsGroups() ? 'groups-section-visible' : 'groups-section-hidden'; ?>">
      <div id="groups-search-widget" class="groups-search-widget">
        <div class="groups-data-display-wrapper">
          <label for="groups-data-display" class="groups-data-display"><?php echo htmlspecialchars($groupsBase); ?> Data Display</label>
          <div class="groups-data-display-container">
            <div class="groups-data-display-options">
              <label class="groups-data-display-label">
                <input type="radio" name="groups-data-display" value="all" class="groups-data-display-radio" checked> show all rows
              </label>
              <label class="groups-data-display-label">
                <input type="radio" name="groups-data-display" value="no-values" class="groups-data-display-radio"> show all rows with no data
              </label>
              <label class="groups-data-display-label">
                <input type="radio" name="groups-data-display" value="hide-empty" class="groups-data-display-radio"> show all rows with data
              </label>
            </div>
            <div class="message-container">
              <div id="groups-data-display-message" class="date-range-status" aria-live="polite"></div>
            </div>
          </div>
        </div>
        <form id="groups-search-form" autocomplete="off" class="groups-search-form">
          <div class="groups-search-input-container">
            <label for="groups-search-input" class="groups-search-label"><?php echo htmlspecialchars($groupsFilterLabel); ?></label>
            <input id="groups-search-input" name="groups-search-input" type="text" list="groups-search-datalist" aria-label="<?php echo htmlspecialchars($groupsFilterLabel); ?>" class="groups-search-input" data-table-id="groups-data" />
            <datalist id="groups-search-datalist"></datalist>
          </div>
          <div class="groups-search-buttons">
            <button type="submit" id="groups-search-find" class="btn action-btn" disabled>Filter</button>
            <button type="button" id="groups-search-clear" class="btn clear-btn" disabled>Clear</button>
            <button type="button" id="groups-search-print" class="btn action-btn">Print</button>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="groups-data" id="groups-data" aria-label="Districts Data">
          <caption>
            <?php echo htmlspecialchars($groupsCaption); ?>
            <button type="button" id="groups-toggle-btn" class="table-toggle-button" aria-expanded="false" aria-label="Show data rows"></button>
          </caption>
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Registrations</th>
              <th scope="col">Enrollments</th>
              <th scope="col">Certificates</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="4">Select a date range to view groups data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <!-- Load JavaScript files with consistent module loading -->
  <script type="module" src="js/filter-state-manager.js"></script>
  <script type="module" src="js/datalist-utils.js"></script>
  <script type="module" src="js/reports-data.js"></script>
  <script type="module" src="js/date-range-picker.js"></script>
  <script type="module" src="js/groups-search.js"></script>
  <script type="module" src="js/organization-search.js"></script>
  <script type="module" src="js/reports-messaging.js"></script>
  <script type="module" src="js/reports-main.js"></script>
  <script type="module" src="js/data-display-options.js"></script>
  <script src="../lib/table-filter-interaction.js"></script>

  <!-- Global Message Display Functions -->
  <script>
    // Global variables
    let currentDateRange = null;
    let currentOrganization = null;
    let currentGroup = null;
    let isDataLoading = false;
    let dataCache = {};
  </script>
</body>

</html>