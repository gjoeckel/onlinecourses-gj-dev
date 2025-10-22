<?php
/**
 * Comprehensive Path Issues Test
 * Tests for API files, JS ES6 issues, and potential JS loading as HTML
 */

echo "=== COMPREHENSIVE PATH ISSUES TEST ===\n";
echo "Testing for API files, JS ES6 issues, and potential JS loading as HTML\n\n";

$baseUrl = 'http://localhost:8000';
$results = [];

// Test 1: API Endpoints
echo "1. Testing API Endpoints...\n";
$apiEndpoints = [
    'lib/api/console_log.php',
    'lib/api/enterprise_api.php', 
    'lib/api/organizations_api.php',
    'reports/api/min-start-date.php',
    'reports/check_cache.php',
    'reports/clear_cache.php',
    'reports/reports_api.php'
];
foreach ($apiEndpoints as $api) {
    $url = $baseUrl . '/' . $api;
    $headers = get_headers($url, 1);
    $status = $headers[0] ?? 'ERROR';
    $results['api_' . basename($api)] = $status;
    echo "  $api: $status\n";
}

// Test 2: JavaScript ES6 Module Files
echo "\n2. Testing JavaScript ES6 Module Files...\n";
$es6Modules = [
    'reports/js/datalist-utils.js',
    'reports/js/reports-data.js', 
    'reports/js/date-range-picker.js',
    'reports/js/organization-search.js',
    'reports/js/reports-messaging.js',
    'reports/js/reports-main.js',
    'reports/js/search-utils.js',
    'reports/js/print-utils.js',
    'reports/js/date-utils.js',
    'reports/js/groups-search.js',
    'lib/dashboard-link-utils.js'
];
foreach ($es6Modules as $module) {
    $url = $baseUrl . '/' . $module;
    $headers = get_headers($url, 1);
    $status = $headers[0] ?? 'ERROR';
    $results['es6_' . basename($module)] = $status;
    echo "  $module: $status\n";
}

// Test 3: Regular JavaScript Files (Non-ES6)
echo "\n3. Testing Regular JavaScript Files...\n";
$regularJs = [
    'lib/message-dismissal.js',

    'lib/table-interaction.js',
    'lib/table-filter-interaction.js',

    'messages/loading-message.js'
];
foreach ($regularJs as $js) {
    $url = $baseUrl . '/' . $js;
    $headers = get_headers($url, 1);
    $status = $headers[0] ?? 'ERROR';
    $results['js_' . basename($js)] = $status;
    echo "  $js: $status\n";
}

// Test 4: Potential JS Loading as HTML Issues
echo "\n4. Testing for Potential JS Loading as HTML Issues...\n";
$potentialIssues = [
    'lib/dashboard-link-utils.js' => 'ES6 module imported by organization-search.js',
    'reports/js/organization-search.js' => 'ES6 module with external imports',
    'reports/js/date-range-picker.js' => 'ES6 module with API calls',

];
foreach ($potentialIssues as $file => $description) {
    $url = $baseUrl . '/' . $file;
    $content = file_get_contents($url);
    if ($content === false) {
        $results['html_' . basename($file)] = 'ERROR: Cannot load file';
        echo "  $file: ERROR - Cannot load file ($description)\n";
    } else {
        // Check if content looks like HTML instead of JS
        $isHtml = strpos($content, '<!DOCTYPE') !== false || 
                  strpos($content, '<html') !== false ||
                  strpos($content, '<?php') !== false;
        
        if ($isHtml) {
            $results['html_' . basename($file)] = 'ERROR: Loads as HTML';
            echo "  $file: ERROR - Loads as HTML instead of JS ($description)\n";
        } else {
            $results['html_' . basename($file)] = 'OK';
            echo "  $file: OK - Loads as JS ($description)\n";
        }
    }
}

// Test 5: ES6 Import Path Validation
echo "\n5. Testing ES6 Import Path Validation...\n";
$importTests = [
    'reports/js/organization-search.js' => [
        'import { populateDatalistFromTable } from \'./datalist-utils.js\'',
        'import { fetchEnterpriseData, getDashboardUrlJS, renderDashboardLink } from \'../../lib/dashboard-link-utils.js\'',
        'import { getTableNames, updateSearchButtonsState, filterTableRows, clearTableFilter } from \'./search-utils.js\'',
        'import { createPrintButtonHandler } from \'./print-utils.js\''
    ],
    'reports/js/date-range-picker.js' => [
        'import { fetchAndUpdateAllTables } from \'./reports-data.js\'',
        'import { getTodayMMDDYY, getPrevMonthRangeMMDDYY, isValidMMDDYYFormat, getMostRecentClosedQuarterMMDDYY } from \'./date-utils.js\'',
        'fetch(\'../reports/api/min-start-date.php\')'
    ],
    'reports/js/reports-messaging.js' => [
        'import { getMinStartDate } from \'./date-range-picker.js\'',
        'import { getTodayMMDDYY, getPrevMonthRangeMMDDYY, isValidMMDDYYFormat, getMostRecentClosedQuarterMMDDYY } from \'./date-utils.js\''
    ]
];

foreach ($importTests as $file => $imports) {
    $url = $baseUrl . '/' . $file;
    $content = file_get_contents($url);
    if ($content === false) {
        $results['import_' . basename($file)] = 'ERROR: Cannot load file';
        echo "  $file: ERROR - Cannot load file\n";
    } else {
        $allImportsValid = true;
        foreach ($imports as $import) {
            if (strpos($content, $import) === false) {
                $allImportsValid = false;
                break;
            }
        }
        if ($allImportsValid) {
            $results['import_' . basename($file)] = 'OK';
            echo "  $file: OK - All imports found\n";
        } else {
            $results['import_' . basename($file)] = 'ERROR: Missing imports';
            echo "  $file: ERROR - Missing expected imports\n";
        }
    }
}

// Test 6: API Path Issues in JavaScript
echo "\n6. Testing API Path Issues in JavaScript...\n";
$apiPathTests = [

    'lib/dashboard-link-utils.js' => 'lib/api/enterprise_api.php',
    'reports/js/date-range-picker.js' => '../reports/api/min-start-date.php',
    'reports/js/reports-data.js' => 'reports_api.php',
    'reports/js/reports-main.js' => 'reports_api.php'
];

foreach ($apiPathTests as $file => $expectedApiPath) {
    $url = $baseUrl . '/' . $file;
    $content = file_get_contents($url);
    if ($content === false) {
        $results['apipath_' . basename($file)] = 'ERROR: Cannot load file';
        echo "  $file: ERROR - Cannot load file\n";
    } else {
        if (strpos($content, $expectedApiPath) !== false) {
            $results['apipath_' . basename($file)] = 'OK';
            echo "  $file: OK - Uses correct API path: $expectedApiPath\n";
        } else {
            $results['apipath_' . basename($file)] = 'ERROR: Wrong API path';
            echo "  $file: ERROR - Uses wrong API path (expected: $expectedApiPath)\n";
        }
    }
}

// Summary
echo "\n=== COMPREHENSIVE TEST SUMMARY ===\n";
$totalTests = count($results);
$successCount = 0;
$errorCount = 0;

foreach ($results as $test => $status) {
    if (strpos($status, '200') !== false || $status === 'OK') {
        $successCount++;
    } else {
        $errorCount++;
    }
}

echo "Total Tests: $totalTests\n";
echo "Successful: $successCount\n";
echo "Errors: $errorCount\n";

if ($errorCount > 0) {
    echo "\n=== ERROR DETAILS ===\n";
    foreach ($results as $test => $status) {
        if (strpos($status, '200') === false && $status !== 'OK') {
            echo "$test: $status\n";
        }
    }
}

echo "\n=== PATH ISSUES ANALYSIS ===\n";

// Check for specific issues
$issues = [];

// Check for absolute paths in JavaScript


// Check for ES6 module loading issues
$es6Issues = 0;
foreach ($es6Modules as $module) {
    if (strpos($results['es6_' . basename($module)], '200') === false) {
        $es6Issues++;
    }
}
if ($es6Issues > 0) {
    $issues[] = "❌ $es6Issues ES6 module files have loading issues";
} else {
    echo "✅ All ES6 module files load correctly\n";
}

// Check for API endpoint issues
$apiIssues = 0;
foreach ($apiEndpoints as $api) {
    if (strpos($results['api_' . basename($api)], '200') === false) {
        $apiIssues++;
    }
}
if ($apiIssues > 0) {
    $issues[] = "❌ $apiIssues API endpoints have issues";
} else {
    echo "✅ All API endpoints are accessible\n";
}

// Check for JS loading as HTML issues
$htmlIssues = 0;
foreach ($potentialIssues as $file => $description) {
    if (strpos($results['html_' . basename($file)], 'ERROR') !== false) {
        $htmlIssues++;
    }
}
if ($htmlIssues > 0) {
    $issues[] = "❌ $htmlIssues JavaScript files may be loading as HTML";
} else {
    echo "✅ All JavaScript files load correctly as JS\n";
}

if (empty($issues)) {
    echo "✅ No path issues found!\n";
} else {
    echo "\n=== ISSUES FOUND ===\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
}

echo "\n=== COMPREHENSIVE TEST COMPLETE ===\n";
?> 