/*
 * reports-messaging.js
 *
 * Handles all date logic, validation, and user messaging for the date range picker UI.
 *
 * Functionality:
 * - Provides functions for parsing and validating MM-DD-YY date strings.
 * - Performs all date range checks (min/max, order, future dates) and sets error/success messages.
 * - Handles preset radio logic for updating date fields and clearing errors.
 * - Updates the Active Date Range display and custom messages based on user input.
 * - Does NOT handle enabling/disabling the Apply button (handled in date-range-picker.js).
 */
// reports-messaging.js
// Handles user messaging, status display, and ARIA updates for reports pages. 

import { getMinStartDate } from './date-range-picker.js';
import { getTodayMMDDYY, getPrevMonthRangeMMDDYY, isValidMMDDYYFormat, getMostRecentClosedQuarterMMDDYY } from './date-utils.js';

(async function() {
  // Get references
  const startInput = document.getElementById('start-date');
  const endInput = document.getElementById('end-date');
  const presetRadios = document.querySelectorAll('input[name="date-preset"]');
  const activeRangeValues = document.getElementById('active-range-values');
  const messageDisplay = document.getElementById('message-display');

  function updateActiveRangeMessage() {
    if (startInput && endInput && messageDisplay) {
      const startVal = startInput.value;
      const endVal = endInput.value;
      
      // Check if both dates are valid MM-DD-YY format
      if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
        // Show active date range when both dates are valid
        messageDisplay.classList.remove('visually-hidden-but-space');
        messageDisplay.removeAttribute('aria-hidden');
        messageDisplay.className = 'success-message';
        // Direct update to ensure content is displayed
        messageDisplay.innerHTML = `<strong>Active Date Range:</strong> <span id="active-range-values">${startVal} to ${endVal}</span>`;
      } else {
        // Hide message when no valid date range but preserve space
        messageDisplay.classList.add('visually-hidden-but-space');
        messageDisplay.setAttribute('aria-hidden', 'true');
        messageDisplay.className = 'date-range-status visually-hidden-but-space';
        messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
      }
    }
  }
  
  // Make the function globally available
  window.updateActiveRangeMessage = updateActiveRangeMessage;

  function hideMessageDisplay() {
    if (messageDisplay) {
      // Use centralized function to preserve space reservation
      if (window.clearMessageDisplay) {
        window.clearMessageDisplay();
      } else {
        // Fallback to direct update with space reservation
        messageDisplay.className = 'date-range-status visually-hidden-but-space';
        messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
        messageDisplay.setAttribute('aria-hidden', 'true');
        messageDisplay.setAttribute('aria-live', 'polite');
      }
    }
  }

  // Utility: MM-DD-YY format validation - now imported from date-utils.js

  // Utility: Check if MM-DD-YY string is a real calendar date
  function isValidCalendarDateMMDDYY(val) {
    if (!/^\d{2}-\d{2}-\d{2}$/.test(val)) return false;
    const [mm, dd, yy] = val.split('-').map(Number);
    if (mm < 1 || mm > 12) return false;
    const yyyy = yy < 50 ? 2000 + yy : 1900 + yy;
    const daysInMonth = new Date(yyyy, mm, 0).getDate();
    if (dd < 1 || dd > daysInMonth) return false;
    return true;
  }

  // Show/hide Active Date Range message based on input validity
  function updateActiveRangeMessageVisibility() {
    const startVal = startInput ? startInput.value : '';
    const endVal = endInput ? endInput.value : '';
    const bothValid = isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal);
    if (bothValid) {
      updateActiveRangeMessage();
    } else {
      hideMessageDisplay();
    }
  }

  // Attach to input events
  if (startInput) startInput.addEventListener('input', updateActiveRangeMessageVisibility);
  if (endInput) endInput.addEventListener('input', updateActiveRangeMessageVisibility);

  if (presetRadios.length && startInput && endInput) {
    // On DOMContentLoaded, set Active Date Range to None preset (default)
    document.addEventListener('DOMContentLoaded', function() {
      const noneRadio = Array.from(presetRadios).find(radio => radio.value === 'none');
      if (noneRadio) {
        noneRadio.checked = true;
        // Clear date fields for "None" preset
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
        // Use centralized function to preserve space reservation
        if (window.clearMessageDisplay) {
          window.clearMessageDisplay();
        } else {
          // Fallback to direct update with space reservation
          if (messageDisplay) {
            messageDisplay.className = 'date-range-status visually-hidden-but-space';
            messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
            messageDisplay.setAttribute('aria-hidden', 'true');
            messageDisplay.setAttribute('aria-live', 'polite');
          }
        }
        // Update button states based on current values
        updateButtonStates();
      }
    });
    // Show active range on page load (for default preset)
    updateActiveRangeMessage();
    presetRadios.forEach(radio => {
      radio.addEventListener('change', async function() {
        if (this.checked) {
          if (this.value === 'none') {
            // Use the same logic as Clear button - clear fields and message
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            // Use centralized function to preserve space reservation
            if (window.clearMessageDisplay) {
              window.clearMessageDisplay();
            } else {
              // Fallback to direct update with space reservation
              if (messageDisplay) {
                messageDisplay.className = 'date-range-status visually-hidden-but-space';
                messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
                messageDisplay.setAttribute('aria-hidden', 'true');
                messageDisplay.setAttribute('aria-live', 'polite');
              }
            }
            // Update button states based on current values
            updateButtonStates();
          } else if (this.value === 'today') {
            const today = getTodayMMDDYY();
            startInput.value = today;
            endInput.value = today;
            updateActiveRangeMessage();
          } else if (this.value === 'past-month') {
            const prevMonthRange = getPrevMonthRangeMMDDYY();
            startInput.value = prevMonthRange.start;
            endInput.value = prevMonthRange.end;
            updateActiveRangeMessage();
          } else if (["q1","q2","q3","q4"].includes(this.value)) {
            const range = getMostRecentClosedQuarterMMDDYY(this.value);
            startInput.value = range.start;
            endInput.value = range.end;
            updateActiveRangeMessage();
          } else if (this.value === 'all') {
            try {
              const minStartDate = await getMinStartDate();
              startInput.value = minStartDate;
              endInput.value = getTodayMMDDYY();
              updateActiveRangeMessage();
            } catch (error) {
          
              // Fallback to a default date if the async call fails
              startInput.value = '05-06-24';
              endInput.value = getTodayMMDDYY();
              updateActiveRangeMessage();
            }
          }
          // Update button states based on current values (DRY approach)
          updateButtonStates();
          // For "none" preset, don't call updateActiveRangeMessage as it will override the cleared state
          if (this.value !== 'none') {
            setTimeout(() => {
              if (typeof window.updateActiveRangeMessage === 'function') {
                window.updateActiveRangeMessage();
              }
              // Ensure button states are updated after DOM changes
              updateButtonStates();
            }, 0);
          }
        }
      });
    });
    // Listen for manual date input to deselect presets and show custom message
    startInput.addEventListener('input', function() {
      if (![...presetRadios].some(radio => radio.checked)) {
        // showCustomDateMessage();
      }
      // Update button states based on current values
      updateButtonStates();
    });
    endInput.addEventListener('input', function() {
      if (![...presetRadios].some(radio => radio.checked)) {
        // showCustomDateMessage();
      }
      // Update button states based on current values
      updateButtonStates();
      // --- Remove End Date before Start Date error logic from here ---
      // (Now only handled in window.handleApplyClick)
    });

    // Add focus and blur event listeners for date inputs
    startInput.addEventListener('focus', function() {
      // Select "None" preset when focusing on date input
      const noneRadio = Array.from(presetRadios).find(radio => radio.value === 'none');
      if (noneRadio) {
        noneRadio.checked = true;
      }
      // Use centralized function to preserve space reservation
      if (window.clearMessageDisplay) {
        window.clearMessageDisplay();
      } else {
        // Fallback to direct update
        hideMessageDisplay();
      }
      // Update button states based on current values
      updateButtonStates();
    });

    endInput.addEventListener('focus', function() {
      // Select "None" preset when focusing on date input
      const noneRadio = Array.from(presetRadios).find(radio => radio.value === 'none');
      if (noneRadio) {
        noneRadio.checked = true;
      }
      // Use centralized function to preserve space reservation
      if (window.clearMessageDisplay) {
        window.clearMessageDisplay();
      } else {
        // Fallback to direct update
        hideMessageDisplay();
      }
      // Update button states based on current values
      updateButtonStates();
    });

    startInput.addEventListener('blur', function() {
      // Check for valid date range when losing focus
      const startVal = startInput ? startInput.value : '';
      const endVal = endInput ? endInput.value : '';
      if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
        updateActiveRangeMessage();
      }
      // Update button states based on current values
      updateButtonStates();
    });

    endInput.addEventListener('blur', function() {
      // Check for valid date range when losing focus
      const startVal = startInput ? startInput.value : '';
      const endVal = endInput ? endInput.value : '';
      if (isValidMMDDYYFormat(startVal) && isValidMMDDYYFormat(endVal)) {
        updateActiveRangeMessage();
      }
      // Update button states based on current values
      updateButtonStates();
    });
  }

  // === Apply Button Handler for external use ===
  window.handleApplyClick = async function(callback) {
    // Validation logic (reuse isValidMMDDYYFormat and add range checks)
    const startVal = startInput ? startInput.value : '';
    const endVal = endInput ? endInput.value : '';
    const todayObj = new Date();
    const mm = String(todayObj.getMonth() + 1).padStart(2, '0');
    const dd = String(todayObj.getDate()).padStart(2, '0');
    const yy = String(todayObj.getFullYear()).slice(-2);
    const todayStr = `${mm}-${dd}-${yy}`;
    if (!isValidMMDDYYFormat(startVal) || !isValidMMDDYYFormat(endVal)) {
      if (messageDisplay) {
        messageDisplay.className = 'error-message display-block';
        messageDisplay.innerHTML = 'Only numbers and dashes in MM-DD-YY format allowed.';
      }
      return;
    }
    // New: Check for real calendar dates
    if (!isValidCalendarDateMMDDYY(startVal) || !isValidCalendarDateMMDDYY(endVal)) {
      if (messageDisplay) {
        messageDisplay.className = 'error-message display-block';
        messageDisplay.innerHTML = 'Please enter valid calendar dates in MM-DD-YY format.';
      }
      return;
    }
    // Parse dates
    const [mmS, ddS, yyS] = startVal.split('-').map(Number);
    const [mmE, ddE, yyE] = endVal.split('-').map(Number);
    const yyyyS = yyS < 50 ? 2000 + yyS : 1900 + yyS;
    const yyyyE = yyE < 50 ? 2000 + yyE : 1900 + yyE;
    const startDate = new Date(yyyyS, mmS - 1, ddS);
    const endDate = new Date(yyyyE, mmE - 1, ddE);
    const minStartDateStr = await getMinStartDate();
    const [mmMin, ddMin, yyMin] = minStartDateStr.split('-');
    const minStart = new Date(`20${yyMin}`, mmMin - 1, ddMin);
    todayObj.setHours(0,0,0,0);
    // First error check: start < minStart or end > today
    if (startDate < minStart || endDate > todayObj) {
      if (messageDisplay) {
        messageDisplay.className = 'error-message display-block';
        const mmMinStr = String(minStart.getMonth() + 1).padStart(2, '0');
        const ddMinStr = String(minStart.getDate()).padStart(2, '0');
        const yyMinStr = String(minStart.getFullYear()).slice(-2);
        const minStartStr = `${mmMinStr}-${ddMinStr}-${yyMinStr}`;
        messageDisplay.innerHTML = `Please provide dates within the available range: <b>${minStartStr}</b> to <b>${todayStr}</b>`;
      }
      return;
    }
    if (startDate > endDate) {
      if (messageDisplay) {
        messageDisplay.className = 'error-message display-block';
        messageDisplay.innerHTML = 'Start date cannot be after End date.';
      }
      return;
    }
    
    // If all validation passes, show the progress message first
    if (messageDisplay) {
      messageDisplay.classList.remove('visually-hidden-but-space');
              messageDisplay.className = 'info-message display-block';
      messageDisplay.setAttribute('aria-live', 'polite');
              messageDisplay.innerHTML = 'Retrieving your data...';
    }
    
    // Then add a 2-second delay before calling the callback
    await new Promise(resolve => setTimeout(resolve, 2000));
    if (typeof callback === 'function') callback();
  }

  // Centralized function to update button states
  function updateButtonStates() {
    const startVal = startInput ? startInput.value : '';
    const endVal = endInput ? endInput.value : '';
    const isReset = (val) => !val || val === 'MM-DD-YY';
    
    // Update Apply button state
    const applyBtn = document.getElementById('apply-range-button');
    if (applyBtn) applyBtn.disabled = isReset(startVal) || isReset(endVal);
    
    // Update Clear button state - enable when either start OR end has a value
    const clearBtn = document.getElementById('clear-dates-button');
    if (clearBtn) {
      const shouldDisable = !startVal && !endVal;
      clearBtn.disabled = shouldDisable;
    }
  }

  // Expose updateActiveRangeMessage globally for use by other modules
  window.updateActiveRangeMessage = updateActiveRangeMessage;
  
  // Global message functions for consistent behavior
  window.clearMessageDisplay = function() {
    const messageDisplay = document.getElementById('message-display');
    if (messageDisplay) {
      messageDisplay.className = 'date-range-status visually-hidden-but-space';
      messageDisplay.innerHTML = '<strong>Active Date Range:</strong> <span id="active-range-values">No date range selected</span>';
      messageDisplay.setAttribute('aria-hidden', 'true');
      messageDisplay.setAttribute('aria-live', 'polite');
    }
  };
  
  window.updateActiveRangeValues = function(start, end) {
    const activeRangeValues = document.getElementById('active-range-values');
    if (activeRangeValues) {
      activeRangeValues.textContent = `${start} to ${end}`;
    }
  };
  
  window.showActiveDateRange = function(start, end) {
    const messageDisplay = document.getElementById('message-display');
    if (messageDisplay) {
      messageDisplay.className = 'success-message';
      messageDisplay.classList.remove('visually-hidden-but-space');
      messageDisplay.removeAttribute('aria-hidden');
      messageDisplay.setAttribute('aria-live', 'polite');
      messageDisplay.innerHTML = `<strong>Active Date Range:</strong> <span id="active-range-values">${start} to ${end}</span>`;
    }
  };
})(); 