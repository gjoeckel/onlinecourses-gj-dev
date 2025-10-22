/*
 * date-range-picker.js
 *
 * Handles UI element references for the date range picker, preset radio logic, and enabling/disabling the Apply button.
 *
 * Functionality:
 * - References start and end date input fields, Apply and Clear buttons, and preset radio buttons.
 * - Sets up event listeners for preset radio changes to update date fields.
 * - Enables or disables the Apply button based solely on whether both date fields match the MM-DD-YY format (two digits, dash, two digits, dash, two digits).
 * - All other date validation, range logic, and user messaging is handled in reports-messaging.js.
 */
// date-range-picker.js
// MVP, modular, WCAG-compliant date range picker
// Extracted from reports.js

import { fetchAndUpdateAllTables } from './reports-data.js';
import { getTodayMMDDYY, getPrevMonthRangeMMDDYY, isValidMMDDYYFormat, getMostRecentClosedQuarterMMDDYY } from './date-utils.js';
import { getMinStartDate } from '../../lib/enterprise-utils.js';

// Re-export for compatibility
export { getMinStartDate };

(function() {
  // === Element References ===
  const startInput = document.getElementById('start-date');
  const endInput = document.getElementById('end-date');
  const applyBtn = document.getElementById('apply-range-button');
  const clearBtn = document.getElementById('clear-dates-button');
  const activeRangeDisplay = document.getElementById('message-display');
  const datePickerContainer = document.getElementById('date-picker-container');
  const presetRadios = document.querySelectorAll('input[name="date-preset"]');
  const dateRangeError = document.getElementById('message-display');

  // === Utility Functions ===
  function getMostRecentClosedQuarterMMDDYY(q) {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    let fyStartYear = month >= 6 ? year : year - 1;
    let prevFY = fyStartYear - 1;
    let currFY = fyStartYear;
    let currentQuarter;
    if (month >= 6 && month <= 8) currentQuarter = 'q1';
    else if (month >= 9 && month <= 11) currentQuarter = 'q2';
    else if (month >= 0 && month <= 2) currentQuarter = 'q3';
    else if (month >= 3 && month <= 5) currentQuarter = 'q4';
    let start, end;
    if (q === 'q1') {
      if (currentQuarter === 'q1') {
        start = new Date(prevFY, 6, 1); end = new Date(prevFY, 8, 30);
      } else {
        start = new Date(currFY, 6, 1); end = new Date(currFY, 8, 30);
      }
    } else if (q === 'q2') {
      if (currentQuarter === 'q1' || currentQuarter === 'q2') {
        start = new Date(prevFY, 9, 1); end = new Date(prevFY, 11, 31);
      } else {
        start = new Date(currFY, 9, 1); end = new Date(currFY, 11, 31);
      }
    } else if (q === 'q3') {
      if (currentQuarter === 'q1' || currentQuarter === 'q2' || currentQuarter === 'q3') {
        start = new Date(prevFY + 1, 0, 1); end = new Date(prevFY + 1, 2, 31);
      } else {
        start = new Date(currFY + 1, 0, 1); end = new Date(currFY + 1, 2, 31);
      }
    } else if (q === 'q4') {
      if (currentQuarter !== 'q4') {
        start = new Date(prevFY + 1, 3, 1); end = new Date(prevFY + 1, 5, 30);
      } else {
        start = new Date(prevFY + 1, 3, 1); end = new Date(prevFY + 1, 5, 30);
      }
    }
    function toMMDDYY(date) {
      const mm = String(date.getMonth() + 1).padStart(2, '0');
      const dd = String(date.getDate()).padStart(2, '0');
      const yy = String(date.getFullYear()).slice(-2);
      return `${mm}-${dd}-${yy}`;
    }
    return { start: toMMDDYY(start), end: toMMDDYY(end) };
  }

  // === Error/Success Messaging ===
  function setDateRangeMessage(msg, type = 'error') {
    if (!activeRangeDisplay) return;
    activeRangeDisplay.innerHTML = msg;
    activeRangeDisplay.setAttribute('aria-live', 'polite');
    activeRangeDisplay.style.removeProperty('display');
    if (type === 'success') {
      activeRangeDisplay.className = 'message-success';
      activeRangeDisplay.style.backgroundColor = '';
      activeRangeDisplay.style.border = '';
      activeRangeDisplay.style.borderRadius = '';
      activeRangeDisplay.style.color = '';
    } else if (type === 'error') {
      activeRangeDisplay.className = 'message-error';
      activeRangeDisplay.style.backgroundColor = '#fff3f3';
      activeRangeDisplay.style.border = '1px solid #e74c3c';
      activeRangeDisplay.style.borderRadius = '4px';
      activeRangeDisplay.style.color = '#e74c3c';
    } else {
      activeRangeDisplay.style.display = 'none';
    }
    setActiveRangeDisplayVisibility();
  }

  // === Reusable Message Clearing Function ===
  function clearMessageDisplay() {
    // Use centralized function to preserve space reservation
    if (window.clearMessageDisplay) {
      window.clearMessageDisplay();
    } else {
      // Fallback to direct update with space reservation
      const messageDisplay = document.getElementById('message-display');
      if (messageDisplay) {
        messageDisplay.className = 'date-range-status visually-hidden-but-space';
        messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
        messageDisplay.setAttribute('aria-hidden', 'true');
        messageDisplay.setAttribute('aria-live', 'polite');
      }
    }
  }

  // === Reusable Clear Function ===
  function clearDateRange() {
    // Clear any message in div#message-display while preserving space
    clearMessageDisplay();
    // Set start and end date values to empty fields
    if (startInput) startInput.value = '';
    if (endInput) endInput.value = '';
    // Update button states based on current values
    updateApplyButtonEnabled();
    // Select the "None" preset radio button
    if (presetRadios && presetRadios.length) {
      presetRadios.forEach(radio => {
        radio.checked = (radio.value === 'none');
      });
    }
  }

  if (presetRadios.length && startInput && endInput) {
    presetRadios.forEach(radio => {
      radio.addEventListener('change', function() {
        // For "none" preset, don't call updateActiveRangeMessage as it will override the cleared state
        if (this.value !== 'none') {
          setTimeout(() => {
            if (typeof window.updateActiveRangeMessage === 'function') {
              window.updateActiveRangeMessage();
            }
            // Ensure button states are updated after DOM changes
            updateApplyButtonEnabled();
          }, 0);
        } else {
          // For "none" preset, just update button states
          updateApplyButtonEnabled();
        }
      });
    });
  }

  // Add focus event listeners to automatically select "None" when user focuses on date inputs
  if (startInput) {
    startInput.addEventListener('focus', function() {
      // Select "None" preset radio directly (same logic as clearDateRange)
      if (presetRadios && presetRadios.length) {
        presetRadios.forEach(radio => {
          radio.checked = (radio.value === 'none');
        });
      }
      // Clear Active Date Range message when focusing on inputs
      clearMessageDisplay();
      // Update button states based on current values
      updateApplyButtonEnabled();
    });
    startInput.addEventListener('blur', function() {
      // Check for valid date range when losing focus
      const startVal = startInput ? startInput.value : '';
      const endVal = endInput ? endInput.value : '';
      if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
        updateActiveDateDisplay();
      }
      // Update Apply button state
      updateApplyButtonEnabled();
    });
  }
  if (endInput) {
    endInput.addEventListener('focus', function() {
      // Select "None" preset radio directly (same logic as clearDateRange)
      if (presetRadios && presetRadios.length) {
        presetRadios.forEach(radio => {
          radio.checked = (radio.value === 'none');
        });
      }
      // Clear Active Date Range message when focusing on inputs
      clearMessageDisplay();
      // Update button states based on current values
      updateApplyButtonEnabled();
    });
    endInput.addEventListener('blur', function() {
      // Check for valid date range when losing focus
      const startVal = startInput ? startInput.value : '';
      const endVal = endInput ? endInput.value : '';
      if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
        updateActiveDateDisplay();
      }
      // Update Apply button state
      updateApplyButtonEnabled();
    });
  }

  // === Apply and Clear Button Logic ===
  function showActiveDateRange(start, end) {
    if (!activeRangeDisplay) return;
    activeRangeDisplay.className = 'success-message';
    activeRangeDisplay.style.removeProperty('display');
    activeRangeDisplay.setAttribute('aria-live', 'polite');
    activeRangeDisplay.removeAttribute('aria-hidden');
    // Direct update to ensure content is displayed
    activeRangeDisplay.innerHTML = `<strong>Active Date Range:</strong> <span id="active-range-values">${start} to ${end}</span>`;
  }
  function hideMessage() {
    if (activeRangeDisplay) {
      activeRangeDisplay.className = 'date-range-status visually-hidden-but-space';
      activeRangeDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
      activeRangeDisplay.setAttribute('aria-live', 'polite');
      activeRangeDisplay.setAttribute('aria-hidden', 'true');
    }
  }
  function updateActiveDateDisplay() {
    const startVal = startInput ? startInput.value : '';
    const endVal = endInput ? endInput.value : '';

    // Check if both dates are valid MM-DD-YY format
    if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
      // Show active date range when both dates are valid
      showActiveDateRange(startVal, endVal);
    } else {
      // Hide message when no valid date range but preserve space
      hideMessage();
    }
    // Update button states based on current values
    updateApplyButtonEnabled();
  }
  function setActiveRangeDisplayVisibility() {
    if (!activeRangeDisplay || !applyBtn) return;
    const errorVisible = dateRangeError && dateRangeError.style.display === 'block' && dateRangeError.classList.contains('error-message');
    const successVisible = dateRangeError && dateRangeError.style.display === 'block' && dateRangeError.classList.contains('success-message');
    if (errorVisible || (successVisible && dateRangeError.textContent !== '' && !dateRangeError.textContent.includes('No date range selected'))) {
      activeRangeDisplay.classList.add('hidden');
      return;
    }
    if (applyBtn.style.display !== 'none') {
      activeRangeDisplay.classList.remove('hidden');
      activeRangeDisplay.classList.add('success-message');
    } else {
      activeRangeDisplay.classList.add('hidden');
      activeRangeDisplay.classList.remove('success-message');
    }
  }

  // === New Apply button validation logic ===
  function updateApplyButtonEnabled() {
    // Disable Apply if either input is empty or set to 'MM-DD-YY'
    const startVal = startInput ? startInput.value : '';
    const endVal = endInput ? endInput.value : '';
    const isReset = (val) => !val || val === 'MM-DD-YY';
    if (applyBtn) applyBtn.disabled = isReset(startVal) || isReset(endVal);

    // Enable Clear button when either Start OR End date has a value
    if (clearBtn) {
      const shouldDisable = !startVal && !endVal;
      clearBtn.disabled = shouldDisable;
    }
  }

  if (startInput) startInput.addEventListener('input', updateApplyButtonEnabled);
  if (endInput) endInput.addEventListener('input', updateApplyButtonEnabled);

  // Ensure Apply button state is correct on initial page load (after default values are set)
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(updateApplyButtonEnabled, 50); // short pause to ensure values are populated
  });

  // === Reports-Specific Logic (moved from reports.js) ===
  // Import the data fetch/update logic

  if (applyBtn) {
    applyBtn.addEventListener('click', function(e) {
      // Add MM-DD-YY format validation here before calling window.handleApplyClick
      const startVal = startInput ? startInput.value : '';
      const endVal = endInput ? endInput.value : '';

      if (!isValidMMDDYYFormat(startVal) || !isValidMMDDYYFormat(endVal)) {
        const messageDisplay = document.getElementById('message-display');
        if (messageDisplay) {
          messageDisplay.className = 'message-error display-block';
          messageDisplay.classList.remove('visually-hidden-but-space');
          messageDisplay.removeAttribute('aria-hidden');
          messageDisplay.innerHTML = 'Only numbers and dashes in MM-DD-YY format allowed.';
        }
        return;
      }

      window.handleApplyClick(async function() {
        applyBtn.disabled = true;
        let hidePicker = false;
        const startDate = startInput ? startInput.value : '';
        const endDate = endInput ? endInput.value : '';

        try {
          await fetchAndUpdateAllTables(startDate, endDate);
          hidePicker = true;

          const systemwideSection = document.getElementById('systemwide-section');
          if (systemwideSection) {
            systemwideSection.style.display = '';
          }

          const organizationSection = document.getElementById('organization-section');
          if (organizationSection) {
            organizationSection.style.display = '';
          }

          const districtSection = document.getElementById('district-section');
          if (districtSection) {
            districtSection.style.display = '';
          }

        } catch (err) {
      
          // Optionally, show error message via reports-messaging.js
          if (window.handleApplyClick) {
            const messageDisplay = document.getElementById('message-display');
            if (messageDisplay) {
              messageDisplay.className = 'message-error display-block';
              messageDisplay.innerHTML = 'Error loading data. Please try again.';
            }
          }
        } finally {
          const datePickerContainer = document.getElementById('date-picker-container');
          if (datePickerContainer && hidePicker) {
            datePickerContainer.style.display = 'none';
            document.documentElement.style.overflowY = 'scroll';
          }
          const editRangeBtn = document.getElementById('edit-date-range');
          if (editRangeBtn) {
            editRangeBtn.disabled = false;
          }
          applyBtn.disabled = false;
          if (typeof updateOrganizationSearchWidgetVisibility === 'function') {
            updateOrganizationSearchWidgetVisibility();
          }
        }
      });
    });
  }

  // 2. Report Links Update
  function updateReportLinks() {
    const startInput = document.getElementById('start-date');
    const endInput = document.getElementById('end-date');

    if (startInput && endInput) {
      const start = encodeURIComponent(startInput.value);
      const end = encodeURIComponent(endInput.value);
      const enterprise = window.ENTERPRISE_CODE || 'demo'; // Default to demo if not available

      // Update Registrants Report link
      const registrationsLink = document.getElementById('registrations-report-link');
      if (registrationsLink) {
        registrationsLink.href = `registrants.php?start_date=${start}&end_date=${end}&ent=${enterprise}`;
      }

      // Update Enrollees Report link
      const enrollmentsLink = document.getElementById('enrollments-report-link');
      if (enrollmentsLink) {
        enrollmentsLink.href = `enrollees.php?start_date=${start}&end_date=${end}&ent=${enterprise}`;
      }

      // Update Certificates Report link
      const certificatesLink = document.getElementById('certificates-report-link');
      if (certificatesLink) {
        certificatesLink.href = `certificates-earned.php?start_date=${start}&end_date=${end}&ent=${enterprise}`;
      }
    }
  }
  if (applyBtn) {
    applyBtn.addEventListener('click', updateReportLinks);
  }
  document.addEventListener('DOMContentLoaded', updateReportLinks);

  // 4. Edit Date Range Button Logic
  const editRangeBtn = document.getElementById('edit-date-range');
  if (editRangeBtn) {
    editRangeBtn.addEventListener('click', function() {
      // Instead of reloading, just show the date picker container if hidden
      const datePickerContainer = document.getElementById('date-picker-container');
      if (datePickerContainer) {
        datePickerContainer.style.display = '';
        document.documentElement.style.overflowY = '';
      }
      // Optionally, re-enable Apply button if appropriate
      if (applyBtn) applyBtn.disabled = false;
      // Disable this button after click
      editRangeBtn.disabled = true;
      // Check if a preset is selected and trigger Active Date Range display
      const selectedPreset = document.querySelector('input[name="date-preset"]:checked');
      if (selectedPreset && selectedPreset.value !== 'none') {
        // Use a timeout to avoid race conditions and ensure DOM is ready
        setTimeout(() => {
          if (typeof window.updateActiveRangeMessage === 'function') {
            window.updateActiveRangeMessage();
          }
        }, 1000); // 1 second timeout as requested
      }
      // For "none" preset or no preset, don't call updateActiveRangeMessage as it will override the cleared state
      // Clear organization filter input and reset table
      const orgInput = document.getElementById('organization-search-input');
      if (orgInput) orgInput.value = '';
      const orgTable = document.getElementById('organization-data');
      if (orgTable) {
        const tbody = orgTable.querySelector('tbody');
        if (tbody) {
          Array.from(tbody.querySelectorAll('tr')).forEach(row => { row.style.display = ''; });
          tbody.style.display = 'none';
        }
      }
      // Collapse organization table and hide filter
      const orgToggleBtn = document.getElementById('organization-toggle-btn');
      if (orgToggleBtn) orgToggleBtn.setAttribute('aria-expanded', 'false');
      const orgWidget = document.getElementById('organization-search-widget');
      if (orgWidget) orgWidget.style.display = 'none';
      // Hide organization table tbody
      if (orgTable) {
        const tbody = orgTable.querySelector('tbody');
        if (tbody) tbody.style.display = 'none';
      }
      // Clear district filter input and reset table
      const distInput = document.getElementById('district-search-input');
      if (distInput) distInput.value = '';
      const distTable = document.getElementById('district-data');
      if (distTable) {
        const tbody = distTable.querySelector('tbody');
        if (tbody) {
          Array.from(tbody.querySelectorAll('tr')).forEach(row => { row.style.display = ''; });
          tbody.style.display = 'none';
        }
      }
      // Collapse district table and hide filter
      const distToggleBtn = document.getElementById('district-toggle-btn');
      if (distToggleBtn) distToggleBtn.setAttribute('aria-expanded', 'false');
      const distWidget = document.getElementById('district-search-widget');
      if (distWidget) distWidget.style.display = 'none';
    });
  }

  // === Initial Setup ===
  document.addEventListener('DOMContentLoaded', function() {
    // Clear date fields initially since "None" is the default
    if (startInput) startInput.value = '';
    if (endInput) endInput.value = '';
    // Select the 'None' preset radio by default
    if (presetRadios && presetRadios.length) {
      presetRadios.forEach(radio => {
        radio.checked = (radio.value === 'none');
      });
    }
    if (startInput) startInput.style.fontSize = '1rem';
    if (endInput) endInput.style.fontSize = '1rem';

    // Always show the default "Active Date Range" message on page load to reserve space
    if (activeRangeDisplay) {
      activeRangeDisplay.className = 'date-range-status visually-hidden-but-space';
      activeRangeDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
      activeRangeDisplay.setAttribute('aria-live', 'polite');
      activeRangeDisplay.setAttribute('aria-hidden', 'true');
    }

    updateActiveDateDisplay();
    setActiveRangeDisplayVisibility();
    updateApplyButtonEnabled(); // Ensure Apply and Clear buttons are disabled initially
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      clearDateRange();
    });
  }
})();
