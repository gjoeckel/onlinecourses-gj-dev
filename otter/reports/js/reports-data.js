// reports-data.js
// Handles data fetching from API and updating of systemwide, organization, and groups tables. 

import { populateDatalistFromTable } from './datalist-utils.js';
import { updateOrganizationTableWithDisplayMode, updateGroupsTableWithDisplayMode } from './data-display-options.js';

// Fetch with retry logic
async function fetchWithRetry(url, retries = 2, delay = 500) {
  for (let i = 0; i <= retries; i++) {
    try {
      const resp = await fetch(url);
      
      if (!resp.ok) {
        throw new Error(`Network error: ${resp.status} ${resp.statusText}`);
      }
      
      const data = await resp.json();
      
      if (typeof data !== 'object' || data === null) {
        throw new Error('Invalid data: not an object');
      }
      
      return data;
    } catch (err) {
      if (i === retries) {
        throw err;
      }
      await new Promise(res => setTimeout(res, delay));
    }
  }
}

function updateSystemwideTable(start, end, data) {
  const tbody = document.querySelector('#systemwide-data tbody');
  
  if (!tbody) {
    return;
  }
  
  // Count the arrays to get summary numbers
  const registrationsCount = Array.isArray(data.registrations) ? data.registrations.length : 0;
  const enrollmentsCount = Array.isArray(data.enrollments) ? data.enrollments.length : 0;
  const certificatesCount = Array.isArray(data.certificates) ? data.certificates.length : 0;
  
  const html = `<tr><td>${start}</td><td>${end}</td><td>${registrationsCount}</td><td>${enrollmentsCount}</td><td>${certificatesCount}</td></tr>`;
  
  tbody.innerHTML = html;
}

function updateOrganizationTable(organizationData) {
  // Use the new display mode filtering
  updateOrganizationTableWithDisplayMode(organizationData);
  populateDatalistFromTable('organization-data', 'organization-search-datalist');
}

function updateGroupsTable(groupsData) {
  // Only update groups table if enterprise has groups (CCC)
  if (!window.HAS_GROUPS) {
    return;
  }
  
  // Use the new display mode filtering
  updateGroupsTableWithDisplayMode(groupsData);
  populateDatalistFromTable('groups-data', 'groups-search-datalist');
}

function updateDataTable(tableId, datalistId, data, rowClass, columns, emptyMsg) {
  const tbody = document.querySelector(`#${tableId} tbody`);
  
  if (!tbody) {
    return;
  }
  
  if (!Array.isArray(data) || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="${columns}">${emptyMsg}</td></tr>`;
    return;
  }
  
  const htmlString = data.map(row => {
    // Apply abbreviation to organization/group names
    let displayName = row[rowClass];
    if (rowClass === 'organization' && row.organization_display) {
      // Use server-side abbreviated name if available
      displayName = row.organization_display;
    } else if (typeof abbreviateOrganizationNameJS === 'function') {
      // Fallback to client-side abbreviation
      displayName = abbreviateOrganizationNameJS(row[rowClass]);
    }
    return `<tr><td class="${rowClass}">${displayName}</td><td>${row.registrations}</td><td>${row.enrollments}</td><td>${row.certificates}</td></tr>`;
  }).join('');
  
  tbody.innerHTML = htmlString;
}

// Main exported function
export async function fetchAndUpdateAllTables(start, end) {
  try {
    const summaryUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
    const organizationUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&organization_data=1`;
    
    const summaryData = await fetchWithRetry(summaryUrl);
    const organizationData = await fetchWithRetry(organizationUrl);
    
    updateSystemwideTable(start, end, summaryData);
    
    // Handle both possible response structures:
    // 1. organizationData.organization_data (nested structure)
    // 2. organizationData (direct structure)
    const orgData = organizationData.organization_data || organizationData;
    updateOrganizationTable(orgData);
    
    // Only fetch groups data if enterprise has groups (CCC)
    if (window.HAS_GROUPS) {
      const groupsUrl = `reports_api.php?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&groups_data=1`;
      
      const groupsData = await fetchWithRetry(groupsUrl);
      updateGroupsTable(groupsData.groups_data);
    }
  } catch (error) {

    throw error;
  }
} 