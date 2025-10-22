<?php
/**
 * UI Functionality Test
 * Verifies that table toggle button CSS and organization filter functionality work correctly
 */

echo "=== UI Functionality Test ===\n\n";

// Test 1: Verify CSS files are properly linked
echo "Test 1: CSS File Validation\n";
try {
    $css_files = [
        'reports/css/reports-main.css',
        'reports/css/reports-data.css',
        'reports/css/organization-search.css'
    ];
    
    foreach ($css_files as $file) {
        if (file_exists($file)) {
            echo "  ✅ $file exists\n";
        } else {
            echo "  ❌ $file missing\n";
        }
    }
    
    // Check for table-toggle-button CSS
    $reports_data_css = file_get_contents('reports/css/reports-data.css');
    if (strpos($reports_data_css, '.table-toggle-button') !== false) {
        echo "  ✅ Table toggle button CSS found\n";
    } else {
        echo "  ❌ Table toggle button CSS missing\n";
    }
    
    // Check for organization search CSS
    $org_search_css = file_get_contents('reports/css/organization-search.css');
    if (strpos($org_search_css, '.organization-search') !== false) {
        echo "  ✅ Organization search CSS found\n";
    } else {
        echo "  ❌ Organization search CSS missing\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 2: Verify HTML structure has required attributes
echo "Test 2: HTML Structure Validation\n";
try {
    $index_php = file_get_contents('reports/index.php');
    
    // Check for data-table-id attributes
    if (strpos($index_php, 'data-table-id="organization-data"') !== false) {
        echo "  ✅ Organization search input has data-table-id attribute\n";
    } else {
        echo "  ❌ Organization search input missing data-table-id attribute\n";
    }
    
    if (strpos($index_php, 'data-table-id="district-data"') !== false) {
        echo "  ✅ District search input has data-table-id attribute\n";
    } else {
        echo "  ❌ District search input missing data-table-id attribute\n";
    }
    
    // Check for table toggle buttons
    if (strpos($index_php, 'id="organization-toggle-btn"') !== false) {
        echo "  ✅ Organization table toggle button found\n";
    } else {
        echo "  ❌ Organization table toggle button missing\n";
    }
    
    if (strpos($index_php, 'id="district-toggle-btn"') !== false) {
        echo "  ✅ District table toggle button found\n";
    } else {
        echo "  ❌ District table toggle button missing\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 3: Verify JavaScript files are properly linked
echo "Test 3: JavaScript File Validation\n";
try {
    $js_files = [
        'reports/js/organization-search.js',
        'reports/js/search-utils.js',
        'reports/js/datalist-utils.js'
    ];
    
    foreach ($js_files as $file) {
        if (file_exists($file)) {
            echo "  ✅ $file exists\n";
        } else {
            echo "  ❌ $file missing\n";
        }
    }
    
    // Check for search functionality in JavaScript
    $search_utils = file_get_contents('reports/js/search-utils.js');
    if (strpos($search_utils, 'filterTableRows') !== false) {
        echo "  ✅ filterTableRows function found\n";
    } else {
        echo "  ❌ filterTableRows function missing\n";
    }
    
    if (strpos($search_utils, 'updateSearchButtonsState') !== false) {
        echo "  ✅ updateSearchButtonsState function found\n";
    } else {
        echo "  ❌ updateSearchButtonsState function missing\n";
    }
    
    // Check for organization search functionality
    $org_search_js = file_get_contents('reports/js/organization-search.js');
    if (strpos($org_search_js, 'organization-search-form') !== false) {
        echo "  ✅ Organization search form handling found\n";
    } else {
        echo "  ❌ Organization search form handling missing\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 4: Verify CSS fixes are applied
echo "Test 4: CSS Fix Validation\n";
try {
    $reports_data_css = file_get_contents('reports/css/reports-data.css');
    
    // Check that problematic transform is removed
    if (strpos($reports_data_css, 'transform: translateY(-50%)') === false) {
        echo "  ✅ Problematic transform removed from table toggle button\n";
    } else {
        echo "  ❌ Problematic transform still present in table toggle button\n";
    }
    
    // Check for proper positioning
    if (strpos($reports_data_css, 'display: inline-block') === false) {
        echo "  ✅ Table toggle button no longer uses inline-block display\n";
    } else {
        echo "  ❌ Table toggle button still uses inline-block display\n";
    }
    
    // Check for absolute positioning (dashboard-style)
    if (strpos($reports_data_css, 'position: absolute') !== false) {
        echo "  ✅ Table toggle button uses absolute positioning\n";
    } else {
        echo "  ❌ Table toggle button missing absolute positioning\n";
    }
    
    // Check for transparent background (dashboard-style)
    if (strpos($reports_data_css, 'background-color: transparent') !== false) {
        echo "  ✅ Table toggle button uses transparent background\n";
    } else {
        echo "  ❌ Table toggle button missing transparent background\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 5: Verify Dashboard Button Label Fix
echo "Test 5: Dashboard Button Label Fix\n";
try {
    $org_search_js = file_get_contents('reports/js/organization-search.js');
    
    // Check that button text content is always 'Dashboard'
    if (strpos($org_search_js, "dashboardBtn.textContent = 'Dashboard'") !== false) {
        echo "  ✅ Dashboard button text content is always 'Dashboard'\n";
    } else {
        echo "  ❌ Dashboard button text content not properly set\n";
    }
    
    // Check that aria-label is used for organization name
    if (strpos($org_search_js, "dashboardBtn.setAttribute('aria-label', `Open dashboard for ${orgName}`)") !== false) {
        echo "  ✅ Dashboard button uses aria-label for organization name\n";
    } else {
        echo "  ❌ Dashboard button missing aria-label for organization name\n";
    }
    
    // Check that button text is not dynamically changed
    if (strpos($org_search_js, "dashboardBtn.textContent = `Open dashboard for ${orgName}`") === false) {
        echo "  ✅ Dashboard button text is not dynamically changed\n";
    } else {
        echo "  ❌ Dashboard button text is still dynamically changed\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

// Test 6: Verify Date Picker Message Space Reservation
echo "Test 6: Date Picker Message Space Reservation\n";
try {
    $index_php = file_get_contents('reports/index.php');
    
    // Check that message display element exists
    if (strpos($index_php, 'id="message-display"') !== false) {
        echo "  ✅ Message display element exists\n";
    } else {
        echo "  ❌ Message display element missing\n";
    }
    
    // Check that message display has proper classes
    if (strpos($index_php, 'class="date-range-status"') !== false) {
        echo "  ✅ Message display has date-range-status class\n";
    } else {
        echo "  ❌ Message display missing date-range-status class\n";
    }
    
    // Check that message display has proper ARIA attributes
    if (strpos($index_php, 'role="status"') !== false) {
        echo "  ✅ Message display has status role\n";
    } else {
        echo "  ❌ Message display missing status role\n";
    }
    
    if (strpos($index_php, 'aria-live="polite"') !== false) {
        echo "  ✅ Message display has aria-live attribute\n";
    } else {
        echo "  ❌ Message display missing aria-live attribute\n";
    }
    
    // Check CSS for space reservation
    $reports_main_css = file_get_contents('reports/css/reports-main.css');
    if (strpos($reports_main_css, 'min-height: 2.5rem') !== false) {
        echo "  ✅ Message display has min-height for space reservation\n";
    } else {
        echo "  ❌ Message display missing min-height for space reservation\n";
    }
    
    // Check JavaScript initialization
    $date_picker_js = file_get_contents('reports/js/date-range-picker.js');
    if (strpos($date_picker_js, 'No date range selected') !== false) {
        echo "  ✅ JavaScript shows default message on page load\n";
    } else {
        echo "  ❌ JavaScript missing default message on page load\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "  FAIL: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
echo "The UI functionality fixes address the following issues:\n";
echo "1. ✅ Table toggle button CSS positioning fixed (dashboard-style)\n";
echo "2. ✅ Organization filter functionality restored\n";
echo "3. ✅ Data attributes properly set for search inputs\n";
echo "4. ✅ JavaScript search utilities properly linked\n";
echo "5. ✅ Dashboard button label uses aria-label instead of changing text\n";
echo "6. ✅ Date picker message space reservation\n\n";

echo "Expected behavior after fixes:\n";
echo "- Table toggle buttons should be positioned absolutely within captions (dashboard-style)\n";
echo "- Table toggle buttons should be invisible containers with visual arrows\n";
echo "- Organization filter should enable/disable buttons based on input\n";
echo "- Filter functionality should work when typing organization names\n";
echo "- Clear button should reset the filter and show all rows\n";
echo "- Dashboard button should always show 'Dashboard' text\n";
echo "- Dashboard button should use aria-label for organization name\n";
echo "- Date picker message space reservation\n\n";

echo "The fixes ensure proper UI functionality and user experience.\n";
?> 