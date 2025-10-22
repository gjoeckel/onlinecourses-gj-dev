// data-display-options.js
// Handles data display options for Organizations and Groups tables

import { filterTableData, showDataDisplayMessage, clearDataDisplayMessage } from './data-display-utility.js';
import { populateDatalistFromTable } from './datalist-utils.js';
import FilterStateManager from './filter-state-manager.js';

// Global state for current display modes
let currentOrganizationDisplayMode = 'all'; // 'all', 'no-values', 'hide-empty'
let currentGroupsDisplayMode = 'all'; // 'all', 'no-values', 'hide-empty'

// Store original data for re-filtering
let originalOrganizationData = [];
let originalGroupsData = [];

// Race condition controls with retry limits
let isProcessingOrganization = false;
let isProcessingGroups = false;
let organizationUpdateQueue = [];
let groupsUpdateQueue = [];

// Retry limits to prevent infinite loops
const MAX_RETRY_ATTEMPTS = 10;
const RETRY_DELAY = 10;

// DOM element cache to prevent repeated queries
let cachedElements = {
  organizationTbody: null,
  groupsTbody: null,
  organizationLabel: null,
  groupsLabel: null,
  organizationRadios: null,
  groupsRadios: null
};

// Initialize DOM element cache
function initializeElementCache() {
  cachedElements.organizationTbody = document.querySelector('#organization-data tbody');
  cachedElements.groupsTbody = document.querySelector('#groups-data tbody');
  cachedElements.organizationLabel = document.querySelector('.organizations-data-display');
  cachedElements.groupsLabel = document.querySelector('.groups-data-display');
  cachedElements.organizationRadios = document.querySelectorAll('input[name="organization-data-display"]');
  cachedElements.groupsRadios = document.querySelectorAll('input[name="groups-data-display"]');
}

// Validate cached DOM element
function isValidElement(element) {
  return element && element.nodeType === Node.ELEMENT_NODE && document.contains(element);
}

// Get caption base values from the page
function getOrganizationsBase() {
  if (isValidElement(cachedElements.organizationLabel)) {
    const text = cachedElements.organizationLabel.textContent;
    // Extract the base value (remove " Data Display")
    return text.replace(' Data Display', '').trim();
  }
  return 'Organizations'; // fallback
}

function getGroupsBase() {
  if (isValidElement(cachedElements.groupsLabel)) {
    const text = cachedElements.groupsLabel.textContent;
    // Extract the base value (remove " Data Display")
    return text.replace(' Data Display', '').trim();
  }
  return 'Districts'; // fallback
}

/**
 * Initialize data display options functionality
 */
export function initializeDataDisplayOptions() {
  // Initialize DOM element cache
  initializeElementCache();
  
  // Add event listeners to organization radio buttons
  if (cachedElements.organizationRadios) {
    cachedElements.organizationRadios.forEach(radio => {
      radio.addEventListener('change', handleOrganizationDisplayModeChange);
    });
  }
  
  // Add event listeners to groups radio buttons
  if (cachedElements.groupsRadios) {
    cachedElements.groupsRadios.forEach(radio => {
      radio.addEventListener('change', handleGroupsDisplayModeChange);
    });
  }
  
  // Set default modes
  const defaultOrganizationRadio = document.querySelector('input[name="organization-data-display"][value="all"]');
  if (defaultOrganizationRadio) {
    defaultOrganizationRadio.checked = true;
    currentOrganizationDisplayMode = 'all';
    FilterStateManager.updateState({ displayMode: 'all' }, 'organizations');
  }
  
  const defaultGroupsRadio = document.querySelector('input[name="groups-data-display"][value="all"]');
  if (defaultGroupsRadio) {
    defaultGroupsRadio.checked = true;
    currentGroupsDisplayMode = 'all';
  }
  
  // Listen for display mode restoration events from filter state manager
  // Use a flag to prevent duplicate event listeners
  if (!window.restoreDisplayModeListenerAdded) {
    document.addEventListener('restoreDisplayMode', function(event) {
      const { mode, tableType } = event.detail;

      
      // Add a small delay to ensure all state updates are complete
      setTimeout(() => {
        if (tableType === 'organizations') {
          currentOrganizationDisplayMode = mode;
          // Force immediate update instead of queuing to avoid race conditions
          if (!isProcessingOrganization) {
            applyDisplayModeToOrganizationsTable();
          } else {
            // If already processing, queue the update
            organizationUpdateQueue.push(() => applyDisplayModeToOrganizationsTable());
          }
        } else if (tableType === 'groups') {
          currentGroupsDisplayMode = mode;
          // Force immediate update instead of queuing to avoid race conditions
          if (!isProcessingGroups) {
            applyDisplayModeToGroupsTable();
          } else {
            // If already processing, queue the update
            groupsUpdateQueue.push(() => applyDisplayModeToGroupsTable());
          }
        }
      }, 50); // Small delay to ensure state is fully updated
    });
    window.restoreDisplayModeListenerAdded = true;
  }
}

/**
 * Handle organization display mode change
 */
function handleOrganizationDisplayModeChange(event) {
  const newMode = event.target.value;
  
  // Check if data display is disabled by filter
  if (FilterStateManager.getState('organizations').isDataDisplayDisabled) {
    return;
  }
  
  // Update state manager
  if (FilterStateManager.setDisplayMode(newMode, 'organizations')) {
    currentOrganizationDisplayMode = newMode;
    
    // Queue the update to prevent race conditions
    queueOrganizationUpdate();
  }
}

/**
 * Handle groups display mode change
 */
function handleGroupsDisplayModeChange(event) {
  const newMode = event.target.value;
  
  // Check if data display is disabled by filter
  if (FilterStateManager.getState('groups').isDataDisplayDisabled) {
    return;
  }
  
  // Update state manager
  if (FilterStateManager.setDisplayMode(newMode, 'groups')) {
    currentGroupsDisplayMode = newMode;
    
    // Queue the update to prevent race conditions
    queueGroupsUpdate();
  }
}

/**
 * Queue organization table update to prevent race conditions
 */
function queueOrganizationUpdate() {
  if (isProcessingOrganization) {
    // If already processing, queue this update
    organizationUpdateQueue.push(() => applyDisplayModeToOrganizationsTable());
    return;
  }
  
  isProcessingOrganization = true;
  applyDisplayModeToOrganizationsTable();
  isProcessingOrganization = false;
  
  // Process any queued updates
  while (organizationUpdateQueue.length > 0) {
    const nextUpdate = organizationUpdateQueue.shift();
    if (nextUpdate) {
      isProcessingOrganization = true;
      nextUpdate();
      isProcessingOrganization = false;
    }
  }
}

/**
 * Queue groups table update to prevent race conditions
 */
function queueGroupsUpdate() {
  if (isProcessingGroups) {
    // If already processing, queue this update
    groupsUpdateQueue.push(() => applyDisplayModeToGroupsTable());
    return;
  }
  
  isProcessingGroups = true;
  applyDisplayModeToGroupsTable();
  isProcessingGroups = false;
  
  // Process any queued updates
  while (groupsUpdateQueue.length > 0) {
    const nextUpdate = groupsUpdateQueue.shift();
    if (nextUpdate) {
      isProcessingGroups = true;
      nextUpdate();
      isProcessingGroups = false;
    }
  }
}

/**
 * Store original data for re-filtering with retry limits
 */
export function storeOriginalData(tableType, data, retryCount = 0) {
  // Use structured clone to ensure deep copy and prevent race conditions
  const dataCopy = structuredClone ? structuredClone(data) : JSON.parse(JSON.stringify(data));
  
  if (tableType === 'organization') {
    // Wait for any ongoing organization processing to complete
    if (isProcessingOrganization && retryCount < MAX_RETRY_ATTEMPTS) {
      setTimeout(() => storeOriginalData(tableType, data, retryCount + 1), RETRY_DELAY);
      return;
    }
    originalOrganizationData = dataCopy;
  } else if (tableType === 'groups') {
    // Wait for any ongoing groups processing to complete
    if (isProcessingGroups && retryCount < MAX_RETRY_ATTEMPTS) {
      setTimeout(() => storeOriginalData(tableType, data, retryCount + 1), RETRY_DELAY);
      return;
    }
    originalGroupsData = dataCopy;
  }
}

/**
 * Apply current display mode to organizations table
 */
function applyDisplayModeToOrganizationsTable() {
  if (!Array.isArray(originalOrganizationData) || originalOrganizationData.length === 0) {
    return;
  }
  
  // Filter data based on current mode
  const filteredData = filterTableData(originalOrganizationData, currentOrganizationDisplayMode, ['registrations', 'enrollments', 'certificates']);
  

  
  // Update table with filtered data using cached element with validation
  const tbody = cachedElements.organizationTbody;
  if (!isValidElement(tbody)) {
    // Re-initialize cache if element is invalid
    initializeElementCache();
    const newTbody = cachedElements.organizationTbody;
    if (!isValidElement(newTbody)) {
      return;
    }
  }
  
  try {
    const organizationsBase = getOrganizationsBase();
    
    if (filteredData.length === 0) {
      // Show message but DO NOT modify table content
      if (currentOrganizationDisplayMode === 'hide-empty') {
        showDataDisplayMessage('organization', `All ${organizationsBase.toLowerCase()} have at least one value`, 'info');
      } else if (currentOrganizationDisplayMode === 'no-values') {
        showDataDisplayMessage('organization', `All ${organizationsBase.toLowerCase()} have at least one value`, 'info');
      } else {
        showDataDisplayMessage('organization', 'No data available.', 'info');
      }
      // DO NOT modify table content - keep original data
      // Update the datalist to reflect the current state
      populateDatalistFromTable('organization-data', 'organization-search-datalist');
    } else {
      // Clear any existing message
      clearDataDisplayMessage('organization');
      
      // Generate appropriate message based on mode and data
      if (currentOrganizationDisplayMode === 'all') {
        showDataDisplayMessage('organization', `Showing data for all ${filteredData.length} ${organizationsBase.toLowerCase()}`, 'info');
      } else if (currentOrganizationDisplayMode === 'no-values') {
        showDataDisplayMessage('organization', `Showing ${filteredData.length} ${organizationsBase.toLowerCase()} with no data`, 'info');
      } else if (currentOrganizationDisplayMode === 'hide-empty') {
        // For hide-empty mode, we need to count the organizations that are being hidden (those with all zero values)
        const hiddenCount = originalOrganizationData.filter(row => {
          return ['registrations', 'enrollments', 'certificates'].every(col => {
            const value = row[col];
            return value === 0 || value === '0' || value === '' || value === null || value === undefined;
          });
        }).length;
        if (hiddenCount === 0) {
          showDataDisplayMessage('organization', `All ${organizationsBase.toLowerCase()} have data`, 'info');
        } else {
          showDataDisplayMessage('organization', `Showing ${filteredData.length} ${organizationsBase.toLowerCase()} with data`, 'info');
        }
      }
      
      // Generate HTML for filtered data
      const htmlString = filteredData.map(row => {
        const displayName = formatOrganizationName(row);
        const cells = [
          `<td class="organization">${displayName}</td>`,
          `<td>${row.registrations}</td>`,
          `<td>${row.enrollments}</td>`,
          `<td>${row.certificates}</td>`
        ];
        return `<tr>${cells.join('')}</tr>`;
      }).join('');
      
      tbody.innerHTML = htmlString;
      // Update the datalist to reflect the new visible rows
      populateDatalistFromTable('organization-data', 'organization-search-datalist');
      
      // Reapply search filter if one exists
      const currentSearchFilter = FilterStateManager.getState('organizations').searchFilter;
      if (currentSearchFilter) {
        // Reapply the search filter to the new table content
        const rows = tbody.querySelectorAll('tr');
        let hasMatches = false;
        
        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          let rowMatches = false;
          
          cells.forEach(cell => {
            if (cell.classList.contains('organization')) {
              const cellText = cell.textContent.toLowerCase();
              if (cellText.includes(currentSearchFilter.toLowerCase())) {
                rowMatches = true;
              }
            }
          });
          
          if (rowMatches) {
            row.style.display = '';
            hasMatches = true;
          } else {
            row.style.display = 'none';
          }
        });
        
        // Update the datalist again after applying the search filter
        populateDatalistFromTable('organization-data', 'organization-search-datalist');
      }
    }
  } catch (error) {

  }
}

/**
 * Apply current display mode to groups table
 */
function applyDisplayModeToGroupsTable() {
  if (!Array.isArray(originalGroupsData) || originalGroupsData.length === 0) {
    return;
  }
  
  // Filter data based on current mode
  const filteredData = filterTableData(originalGroupsData, currentGroupsDisplayMode, ['registrations', 'enrollments', 'certificates']);
  
  // Update table with filtered data using cached element with validation
  const tbody = cachedElements.groupsTbody;
  if (!isValidElement(tbody)) {
    // Re-initialize cache if element is invalid
    initializeElementCache();
    const newTbody = cachedElements.groupsTbody;
    if (!isValidElement(newTbody)) {
      return;
    }
  }
  
  try {
    const groupsBase = getGroupsBase();
    
    if (filteredData.length === 0) {
      // Show message but DO NOT modify table content
      if (currentGroupsDisplayMode === 'hide-empty') {
        showDataDisplayMessage('groups', `All ${groupsBase.toLowerCase()} have at least one value`, 'info');
      } else if (currentGroupsDisplayMode === 'no-values') {
        showDataDisplayMessage('groups', `All ${groupsBase.toLowerCase()} have at least one value`, 'info');
      } else {
        showDataDisplayMessage('groups', 'No data available.', 'info');
      }
      // DO NOT modify table content - keep original data
      // Update the datalist to reflect the current state
      populateDatalistFromTable('groups-data', 'groups-search-datalist');
    } else {
      // Clear any existing message
      clearDataDisplayMessage('groups');
      
      // Generate appropriate message based on mode and data
      if (currentGroupsDisplayMode === 'all') {
        showDataDisplayMessage('groups', `Showing data for all ${filteredData.length} ${groupsBase.toLowerCase()}`, 'info');
      } else if (currentGroupsDisplayMode === 'no-values') {
        showDataDisplayMessage('groups', `Showing ${filteredData.length} ${groupsBase.toLowerCase()} with no data`, 'info');
      } else if (currentGroupsDisplayMode === 'hide-empty') {
        // For hide-empty mode, we need to count the groups that are being hidden (those with all zero values)
        const hiddenCount = originalGroupsData.filter(row => {
          return ['registrations', 'enrollments', 'certificates'].every(col => {
            const value = row[col];
            return value === 0 || value === '0' || value === '' || value === null || value === undefined;
          });
        }).length;
        if (hiddenCount === 0) {
          showDataDisplayMessage('groups', `All ${groupsBase.toLowerCase()} have data`, 'info');
        } else {
          showDataDisplayMessage('groups', `Showing ${filteredData.length} ${groupsBase.toLowerCase()} with data`, 'info');
        }
      }
      
      // Generate HTML for filtered data
      const htmlString = filteredData.map(row => {
        const displayName = formatGroupName(row);
        const cells = [
          `<td class="group">${displayName}</td>`,
          `<td>${row.registrations}</td>`,
          `<td>${row.enrollments}</td>`,
          `<td>${row.certificates}</td>`
        ];
        return `<tr>${cells.join('')}</tr>`;
      }).join('');
      
      tbody.innerHTML = htmlString;
      // Update the datalist to reflect the new visible rows
      populateDatalistFromTable('groups-data', 'groups-search-datalist');
      
      // Reapply search filter if one exists
      const currentSearchFilter = FilterStateManager.getState('groups').searchFilter;
      if (currentSearchFilter) {
        // Reapply the search filter to the new table content
        const rows = tbody.querySelectorAll('tr');
        let hasMatches = false;
        
        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          let rowMatches = false;
          
          cells.forEach(cell => {
            if (cell.classList.contains('group')) {
              const cellText = cell.textContent.toLowerCase();
              if (cellText.includes(currentSearchFilter.toLowerCase())) {
                rowMatches = true;
              }
            }
          });
          
          if (rowMatches) {
            row.style.display = '';
            hasMatches = true;
          } else {
            row.style.display = 'none';
          }
        });
        
        // Update the datalist again after applying the search filter
        populateDatalistFromTable('groups-data', 'groups-search-datalist');
      }
    }
  } catch (error) {

  }
}

/**
 * Format organization name for display
 * @param {Object} row - Data row object
 * @returns {string} - Formatted organization name
 */
function formatOrganizationName(row) {
  let displayName = row.organization;
  
  // Use server-side abbreviated name if available
  if (row.organization_display) {
    displayName = row.organization_display;
  } else if (typeof abbreviateOrganizationNameJS === 'function') {
    // Fallback to client-side abbreviation
    displayName = abbreviateOrganizationNameJS(row.organization);
  }
  
  return displayName;
}

/**
 * Format group name for display
 * @param {Object} row - Data row object
 * @returns {string} - Formatted group name
 */
function formatGroupName(row) {
  let displayName = row.group;
  
  // Use server-side abbreviated name if available
  if (row.group_display) {
    displayName = row.group_display;
  } else if (typeof abbreviateOrganizationNameJS === 'function') {
    // Fallback to client-side abbreviation (can reuse organization abbreviation function)
    displayName = abbreviateOrganizationNameJS(row.group);
  }
  
  return displayName;
}

/**
 * Get current organization display mode
 * @returns {string} - Current display mode
 */
export function getCurrentOrganizationDisplayMode() {
  return currentOrganizationDisplayMode;
}

/**
 * Get current groups display mode
 * @returns {string} - Current display mode
 */
export function getCurrentGroupsDisplayMode() {
  return currentGroupsDisplayMode;
}

/**
 * Update organization table with current display mode
 * @param {Array} organizationData - Organization data to display
 */
export function updateOrganizationTableWithDisplayMode(organizationData) {
  storeOriginalData('organization', organizationData);
  applyDisplayModeToOrganizationsTable();
}

/**
 * Update groups table with current display mode
 * @param {Array} groupsData - Groups data to display
 */
export function updateGroupsTableWithDisplayMode(groupsData) {
  storeOriginalData('groups', groupsData);
  applyDisplayModeToGroupsTable();
} 