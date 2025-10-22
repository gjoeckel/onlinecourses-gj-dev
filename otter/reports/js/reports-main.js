// reports-main.js
// Entry point for reports page JS. Imports and initializes all modules.

import { initializeDataDisplayOptions } from './data-display-options.js';

document.addEventListener('DOMContentLoaded', function() {
  // Initialize data display options
  initializeDataDisplayOptions();

  // Toggle header button visibility based on date picker state
  function toggleHeaderButtons() {
    var datePicker = document.getElementById('date-picker-container');
    var backBtn = document.getElementById('back-btn');
    var logoutForm = document.getElementById('logout-form');
    if (!datePicker || !backBtn || !logoutForm) return;

    // When date picker is visible (Edit Range disabled), show Back button and hide Logout
    // When date picker is hidden (Edit Range enabled), hide Back button and show Logout
    if (datePicker.classList.contains('hidden') || datePicker.style.display === 'none') {
      backBtn.style.display = 'none';
      logoutForm.style.display = '';
    } else {
      backBtn.style.display = '';
      logoutForm.style.display = 'none';
    }
  }
  toggleHeaderButtons();
  var headerObserver = new MutationObserver(toggleHeaderButtons);
  var datePicker = document.getElementById('date-picker-container');
  if (datePicker) {
    headerObserver.observe(datePicker, {
      attributes: true,
      attributeFilter: ['class', 'style']
    });
  }

  // Toggle #range-reports and #date-picker-container visibility
  function toggleRangeReportsVisibility() {
    var datePicker = document.getElementById('date-picker-container');
    var rangeReports = document.getElementById('range-reports');
    var header = document.querySelector('header');
    if (!datePicker || !rangeReports) return;
    // Consider visible if not hidden by class or style
    var datePickerVisible = !datePicker.classList.contains('hidden') && datePicker.style.display !== 'none';
    if (datePickerVisible) {
      rangeReports.style.display = 'none';
      // When date picker is visible, make header not sticky
      if (header) {
        header.classList.add('not-sticky');
      }
    } else {
      rangeReports.style.display = 'block';
      // When range-reports is visible, make header sticky
      if (header) {
        header.classList.remove('not-sticky');
      }
    }
  }
  toggleRangeReportsVisibility();
  if (datePicker) {
    var rangeObserver = new MutationObserver(toggleRangeReportsVisibility);
    rangeObserver.observe(datePicker, { attributes: true, attributeFilter: ['class', 'style'] });
  }
});