// organization-search.js
// Handles organization search/filter widget logic and datalist population. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { fetchEnterpriseData, getDashboardUrlJS, renderDashboardLink } from '../../lib/dashboard-link-utils.js';
import { getTableNames, updateSearchButtonsState, filterTableRows, clearTableFilter } from './search-utils.js';
import FilterStateManager from './filter-state-manager.js';
// Print functionality is now handled by the shared print-utils.js module

document.addEventListener('DOMContentLoaded', () => {
  const organizationSearchForm = document.getElementById('organization-search-form');
  const organizationSearchInput = document.getElementById('organization-search-input');
  const organizationSearchClear = document.getElementById('organization-search-clear');
  const organizationSearchFind = document.getElementById('organization-search-find');
  const printBtn = document.getElementById('organization-search-print');

  let isFiltered = false;
  let dashboardUpdateTimeout = null;
  let tableObserver = null;

  // Initialize filter state manager
  FilterStateManager.updateState({ displayMode: 'all' }, 'organizations');

  function updateOrganizationSearchButtonsState() {
    updateSearchButtonsState(
      organizationSearchInput, 
      organizationSearchFind, 
      organizationSearchClear, 
      isFiltered
    );
  }

  if (organizationSearchInput) {
    organizationSearchInput.addEventListener('input', function() {
      if (isFiltered) return;
      updateOrganizationSearchButtonsState();
      
      // Disable data display controls when a valid value is entered
      const value = organizationSearchInput.value.trim();
      if (value) {
        FilterStateManager.disableDataDisplayControls('organizations');
      } else {
        FilterStateManager.enableDataDisplayControls('organizations');
      }
    });
    organizationSearchInput.addEventListener('change', function() {
      if (isFiltered) return;
      updateOrganizationSearchButtonsState();
      
      // Disable data display controls when a valid value is entered
      const value = organizationSearchInput.value.trim();
      if (value) {
        FilterStateManager.disableDataDisplayControls('organizations');
      } else {
        FilterStateManager.enableDataDisplayControls('organizations');
      }
    });
  }

  updateOrganizationSearchButtonsState();

  if (organizationSearchForm && organizationSearchInput) {
    organizationSearchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const value = organizationSearchInput.value.trim().toLowerCase();
      const hasMatches = filterTableRows('organization-data', value, 'organization');
      
      if (hasMatches) {
        isFiltered = true;
        updateOrganizationSearchButtonsState();
        if (printBtn) printBtn.disabled = true;
        
        // Update filter state manager (data display controls already disabled by input event)
        FilterStateManager.setSearchFilter(value, 'organizations');
        
        // Update dashboard button after filter is applied
        scheduleDashboardUpdate();
      }
    });
  }
  
  if (organizationSearchClear && organizationSearchInput) {
    organizationSearchClear.addEventListener('click', function() {
      organizationSearchInput.value = '';
      clearTableFilter('organization-data');
      isFiltered = false;
      
      // Update filter state manager to clear filter and restore data display state
      FilterStateManager.setSearchFilter(null, 'organizations');
      
      // Ensure data display controls are properly restored
      FilterStateManager.enableDataDisplayControls('organizations');
      
      // Disable Filter, Dashboard, and Clear buttons
      if (organizationSearchFind) organizationSearchFind.disabled = true;
      disableDashboardButton();
      organizationSearchClear.disabled = true;
      if (printBtn) printBtn.disabled = false;
      
      // Update dashboard button after filter is cleared
      scheduleDashboardUpdate();
    });
  }

  // Populate datalist on load
  populateDatalistFromTable('organization-data', 'organization-search-datalist');

  // Print button is now handled by the shared print-utils.js module

  // Dashboard button logic - DRY implementation
  const dashboardBtn = document.getElementById('organization-dashboard-btn');
  const orgInput = document.getElementById('organization-search-input');
  const orgTable = document.getElementById('organization-data');

  // Helper: check if table is filtered to a single visible row
  function isTableFilteredToOneRow() {
    if (!orgTable) return false;
    const tbody = orgTable.querySelector('tbody');
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
      if (!firstCell || !firstCell.classList.contains('organization')) return false;
      
      return true;
    });
    

    
    return visibleRows.length === 1;
  }

  // DRY function to disable dashboard button
  function disableDashboardButton() {
    if (!dashboardBtn) return;
    dashboardBtn.disabled = true;
    dashboardBtn.removeAttribute('aria-label');
    dashboardBtn.removeAttribute('tabindex');
    dashboardBtn.onclick = null;
    dashboardBtn.textContent = 'Dashboard';
  }

  // DRY function to enable dashboard button
  function enableDashboardButton(url, orgName) {
    if (!dashboardBtn) return;
    dashboardBtn.disabled = false;
    dashboardBtn.setAttribute('aria-label', `Open dashboard for ${orgName}`);
    dashboardBtn.removeAttribute('tabindex');
    dashboardBtn.onclick = () => { window.open(url, '_blank', 'noopener'); };
    dashboardBtn.textContent = 'Dashboard';
  }

  // Schedule dashboard update with debouncing to prevent accumulation
  function scheduleDashboardUpdate() {
    if (dashboardUpdateTimeout) {
      clearTimeout(dashboardUpdateTimeout);
    }
    dashboardUpdateTimeout = setTimeout(updateDashboardBtn, 0);
  }

  // Update Dashboard button state using DRY principles
  async function updateDashboardBtn() {
    if (!dashboardBtn || !orgInput) return;
    
    const orgName = orgInput.value.trim();
    
    // Always disable immediately
    disableDashboardButton();
    
    // Add a delayed check to ensure filtering is complete
    if (dashboardUpdateTimeout) {
      clearTimeout(dashboardUpdateTimeout);
    }
    dashboardUpdateTimeout = setTimeout(async () => {
      if (!orgName || !isTableFilteredToOneRow()) {
        disableDashboardButton();
        return;
      }
      
      // Use the existing getDashboardUrlJS function for DRY code
      const url = await getDashboardUrlJS(orgName);
      if (url && url !== 'N/A') {
        enableDashboardButton(url, orgName);
      } else {
        disableDashboardButton();
      }
    }, 500); // 500ms delay to ensure DOM filtering is complete
  }

  // Listen for input changes
  if (orgInput) {
    orgInput.addEventListener('input', scheduleDashboardUpdate);
    orgInput.addEventListener('change', scheduleDashboardUpdate);
  }

  // Listen for table changes (filter applied/cleared) with proper lifecycle management
  if (orgTable) {
    const tbody = orgTable.querySelector('tbody');
    if (tbody) {
      // Disconnect any existing observer
      if (tableObserver) {
        tableObserver.disconnect();
      }
      
      tableObserver = new MutationObserver((mutations) => {
        // Only trigger dashboard update if the mutations are relevant to organization data
        const relevantMutations = mutations.filter(mutation => {
          // Check if the mutation is in the organization table
          return mutation.target.closest('#organization-data') !== null;
        });
        
        if (relevantMutations.length > 0) {
          scheduleDashboardUpdate();
        }
      });
      tableObserver.observe(tbody, { attributes: true, childList: true, subtree: true });
    }
  }

  // Cleanup function for proper lifecycle management
  function cleanup() {
    if (dashboardUpdateTimeout) {
      clearTimeout(dashboardUpdateTimeout);
      dashboardUpdateTimeout = null;
    }
    if (tableObserver) {
      tableObserver.disconnect();
      tableObserver = null;
    }
  }

  // Cleanup on page unload
  window.addEventListener('beforeunload', cleanup);

  // Initial state
  updateDashboardBtn();
  

}); 