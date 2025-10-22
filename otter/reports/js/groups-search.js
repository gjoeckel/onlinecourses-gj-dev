// groups-search.js
// Handles groups search/filter widget logic and datalist population. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { getTableNames, updateSearchButtonsState, filterTableRows, clearTableFilter } from './search-utils.js';
import FilterStateManager from './filter-state-manager.js';
// Print functionality is now handled by the shared print-utils.js module

document.addEventListener('DOMContentLoaded', () => {
  // Check if groups section exists (enterprise-agnostic feature detection)
  const groupsSection = document.getElementById('groups-section');
  if (!groupsSection) {
    return; // Exit if groups section doesn't exist
  }

  const groupsSearchForm = document.getElementById('groups-search-form');
  const groupsSearchInput = document.getElementById('groups-search-input');
  const groupsSearchClear = document.getElementById('groups-search-clear');
  const groupsSearchFind = document.getElementById('groups-search-find');
  const printBtn = document.getElementById('groups-search-print');

  let isGroupsFiltered = false;

  // Initialize filter state manager for groups
  FilterStateManager.updateState({ displayMode: 'all' }, 'groups');

  function updateGroupsSearchButtonsState() {
    updateSearchButtonsState(
      groupsSearchInput, 
      groupsSearchFind, 
      groupsSearchClear, 
      isGroupsFiltered
    );
  }

  if (groupsSearchInput) {
    groupsSearchInput.addEventListener('input', function() {
      if (isGroupsFiltered) return;
      updateGroupsSearchButtonsState();
      
      // Disable data display controls when a valid value is entered
      const value = groupsSearchInput.value.trim();
      if (value) {
        FilterStateManager.disableDataDisplayControls('groups');
      } else {
        FilterStateManager.enableDataDisplayControls('groups');
      }
    });
    groupsSearchInput.addEventListener('change', function() {
      if (isGroupsFiltered) return;
      updateGroupsSearchButtonsState();
      
      // Disable data display controls when a valid value is entered
      const value = groupsSearchInput.value.trim();
      if (value) {
        FilterStateManager.disableDataDisplayControls('groups');
      } else {
        FilterStateManager.enableDataDisplayControls('groups');
      }
    });
  }

  updateGroupsSearchButtonsState();

  if (groupsSearchForm && groupsSearchInput) {
    groupsSearchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const value = groupsSearchInput.value.trim().toLowerCase();
      const hasMatches = filterTableRows('groups-data', value, 'group');
      
      if (hasMatches) {
        isGroupsFiltered = true;
        updateGroupsSearchButtonsState();
        if (printBtn) printBtn.disabled = true;
        
        // Update filter state manager (data display controls already disabled by input event)
        FilterStateManager.setSearchFilter(value, 'groups');
      }
    });
  }
  
  if (groupsSearchClear && groupsSearchInput) {
    groupsSearchClear.addEventListener('click', function() {
      groupsSearchInput.value = '';
      clearTableFilter('groups-data');
      isGroupsFiltered = false;
      
      // Update filter state manager to clear filter and restore data display state
      FilterStateManager.setSearchFilter(null, 'groups');
      
      // Ensure data display controls are properly restored
      FilterStateManager.enableDataDisplayControls('groups');
      
      // Disable Filter and Clear buttons
      if (groupsSearchFind) groupsSearchFind.disabled = true;
      groupsSearchClear.disabled = true;
      groupsSearchInput.focus();
      if (printBtn) printBtn.disabled = false;
    });
  }

  // Populate datalist on load
  populateDatalistFromTable('groups-data', 'groups-search-datalist');

  // Print button is now handled by the shared print-utils.js module
}); 