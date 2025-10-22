// filter-state-manager.js
// Unified state management for Organizations and Groups Filter and Data Display tools
// Prevents conflicts between CSS-based filtering and DOM manipulation

// Valid display modes for state validation
const VALID_DISPLAY_MODES = ['all', 'no-values', 'hide-empty'];

// Single source of truth for all filter state
const filterState = {
  // Organizations state
  organizations: {
    searchFilter: null,           // Current search term
    displayMode: 'all',          // Current display mode (all, no-values, hide-empty)
    isDataDisplayDisabled: false, // Whether data display controls are disabled
    previousDisplayMode: null,    // Display mode before filter was applied
    previousMessage: null         // Message content before filter was applied
  },
  
  // Groups state
  groups: {
    searchFilter: null,           // Current search term
    displayMode: 'all',          // Current display mode (all, no-values, hide-empty)
    isDataDisplayDisabled: false, // Whether data display controls are disabled
    previousDisplayMode: null,    // Display mode before filter was applied
    previousMessage: null         // Message content before filter was applied
  }
};

// State management utilities with error handling and validation
const FilterStateManager = {
  state: filterState,

  /**
   * Save current state before applying filter
   */
  saveCurrentState(tableType = 'organizations') {
    try {
      const state = this.state[tableType];
      // Validate current display mode before saving
      if (state.displayMode && VALID_DISPLAY_MODES.includes(state.displayMode)) {
        state.previousDisplayMode = state.displayMode;
      } else {
        // Use safe default if current display mode is invalid
        state.previousDisplayMode = 'all';
    
      }
      state.previousMessage = this.getCurrentMessage(tableType);
    } catch (error) {
      // Fallback to safe defaults
      this.state[tableType].previousDisplayMode = 'all';
      this.state[tableType].previousMessage = null;
    }
  },

  /**
   * Restore previous state after filter is cleared
   */
  restorePreviousState(tableType = 'organizations') {
    try {
      const state = this.state[tableType];
      

      
      // Validate previous display mode before restoration
      if (state.previousDisplayMode && 
          VALID_DISPLAY_MODES.includes(state.previousDisplayMode)) {
        state.displayMode = state.previousDisplayMode;
        state.previousDisplayMode = null;
      } else {
        // Fallback to safe default
        state.displayMode = 'all';
      }

      if (state.previousMessage !== null) {
        this.setMessage(state.previousMessage, tableType);
        state.previousMessage = null;
      }
    } catch (error) {
      // Ensure system remains in a valid state
      this.state[tableType].displayMode = 'all';
      this.state[tableType].isDataDisplayDisabled = false;
    }
  },

  /**
   * Update state and trigger UI updates
   */
  updateState(newState, tableType = 'organizations') {
    try {
      // Validate new state before applying
      if (newState.displayMode && !VALID_DISPLAY_MODES.includes(newState.displayMode)) {
        throw new Error(`Invalid display mode: ${newState.displayMode}`);
      }
      
      Object.assign(this.state[tableType], newState);
      this.updateUI(tableType);
    } catch (error) {
      // Revert to last known good state
      this.state[tableType].displayMode = 'all';
      this.state[tableType].isDataDisplayDisabled = false;
    }
  },

  /**
   * Update UI based on current state
   */
  updateUI(tableType = 'organizations') {
    try {
      this.updateDataDisplayControls(tableType);
      this.updateFilterControls(tableType);
    } catch (error) {
      // Error handling for UI updates
    }
  },

  /**
   * Check if current state is valid
   */
  validateState(tableType = 'organizations') {
    const state = this.state[tableType];
    return VALID_DISPLAY_MODES.includes(state.displayMode) &&
           typeof state.isDataDisplayDisabled === 'boolean';
  },

  /**
   * Get current message from the message container
   */
  getCurrentMessage(tableType = 'organizations') {
    const messageId = tableType === 'organizations' ? 'organization-data-display-message' : 'groups-data-display-message';
    const messageElement = document.getElementById(messageId);
    return messageElement ? messageElement.innerHTML : null;
  },

  /**
   * Set message in the message container
   */
  setMessage(message, tableType = 'organizations') {
    const messageId = tableType === 'organizations' ? 'organization-data-display-message' : 'groups-data-display-message';
    const messageElement = document.getElementById(messageId);
    if (messageElement) {
      messageElement.innerHTML = message || '';
    }
  },

  /**
   * Update data display controls based on current state
   */
  updateDataDisplayControls(tableType = 'organizations') {
    const radioName = tableType === 'organizations' ? 'organization-data-display' : 'groups-data-display';
    const radioButtons = document.querySelectorAll(`input[name="${radioName}"]`);
    const state = this.state[tableType];
    
    radioButtons.forEach(radio => {
      if (state.isDataDisplayDisabled) {
        radio.disabled = true;
        radio.checked = false;
        radio.setAttribute('aria-disabled', 'true');
      } else {
        radio.disabled = false;
        radio.removeAttribute('aria-disabled');
        if (radio.value === state.displayMode) {
          radio.checked = true;
        }
      }
    });

    // Update message
    if (state.isDataDisplayDisabled) {
      this.setMessage('Data display options disabled while Filter tool in use', tableType);
    }
  },

  /**
   * Update filter controls based on current state
   */
  updateFilterControls(tableType = 'organizations') {
    // This will be called by the existing filter logic
    // No specific action needed here as filter controls are managed separately
  },

  /**
   * Disable data display controls
   */
  disableDataDisplayControls(tableType = 'organizations') {
    this.saveCurrentState(tableType);
    this.state[tableType].isDataDisplayDisabled = true;
    this.updateDataDisplayControls(tableType);
  },

  /**
   * Enable data display controls
   */
  enableDataDisplayControls(tableType = 'organizations') {

    
    // Prevent duplicate calls
    if (!this.state[tableType].isDataDisplayDisabled) {
  
      return;
    }
    
    this.state[tableType].isDataDisplayDisabled = false;
    this.restorePreviousState(tableType);
    this.updateDataDisplayControls(tableType);
    
    // Trigger table update to apply the restored display mode with proper timing
    setTimeout(() => {
      if (tableType === 'organizations') {
        // Dispatch custom event to trigger the update
        const currentMode = this.state[tableType].displayMode;
        const event = new CustomEvent('restoreDisplayMode', { 
          detail: { mode: currentMode, tableType: 'organizations' } 
        });
        document.dispatchEvent(event);
      } else if (tableType === 'groups') {
        // Dispatch custom event to trigger the update
        const currentMode = this.state[tableType].displayMode;
        const event = new CustomEvent('restoreDisplayMode', { 
          detail: { mode: currentMode, tableType: 'groups' } 
        });
        document.dispatchEvent(event);
      }
    }, 100); // Small delay to ensure DOM updates are complete
  },

  /**
   * Check if table is filtered to a single visible row
   */
  isTableFilteredToOneRow(tableType = 'organizations') {
    const tableId = tableType === 'organizations' ? 'organization-data' : 'groups-data';
    const table = document.getElementById(tableId);
    if (!table) return false;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return false;
    
    // Get all rows that are actually visible (not display:none and not empty message rows)
    const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
      // Skip rows that are hidden via CSS
      if (row.style.display === 'none') return false;
      
      // Skip rows that are empty message rows (single cell spanning all columns)
      const cells = row.querySelectorAll('td');
      if (cells.length === 1 && cells[0].hasAttribute('colspan')) return false;
      
      // Skip rows that are just message text (no organization/group class)
      const firstCell = row.querySelector('td');
      const expectedClass = tableType === 'organizations' ? 'organization' : 'group';
      if (!firstCell || !firstCell.classList.contains(expectedClass)) return false;
      
      return true;
    });
    
    return visibleRows.length === 1;
  },

  /**
   * Get current state for external access
   */
  getState(tableType = 'organizations') {
    return { ...this.state[tableType] };
  },

  /**
   * Set display mode (called by data display tool)
   */
  setDisplayMode(mode, tableType = 'organizations') {
    const state = this.state[tableType];
    if (state.isDataDisplayDisabled) {
      return false;
    }
    
    if (!VALID_DISPLAY_MODES.includes(mode)) {
      return false;
    }
    
    state.displayMode = mode;
    return true;
  },

  /**
   * Set search filter (called by filter tool)
   */
  setSearchFilter(filter, tableType = 'organizations') {
    this.state[tableType].searchFilter = filter;
    
    // Data display controls are now managed at the input level
    // This method only updates the search filter state
  }
};

// Export for use in other modules
export default FilterStateManager;

// Make available globally for debugging and direct access
window.FilterStateManager = FilterStateManager;
window.VALID_DISPLAY_MODES = VALID_DISPLAY_MODES; 