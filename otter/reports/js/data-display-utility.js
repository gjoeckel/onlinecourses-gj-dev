// data-display-utility.js
// Common utility for data display filtering - reusable for Organizations and Districts tables

// Race condition controls for message display
let messageDisplayQueue = [];
let isDisplayingMessage = false;

/**
 * Common utility for filtering table data based on display mode
 * @param {Array} data - Array of data objects
 * @param {string} mode - Display mode: 'all', 'no-values', 'hide-empty'
 * @param {Array} numericColumns - Array of column names that should be treated as numeric
 * @returns {Array} - Filtered data array
 */
export function filterTableData(data, mode, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  if (!Array.isArray(data) || data.length === 0) {
    return [];
  }

  switch (mode) {
    case 'all':
      // Show all rows as-is
      return data;
      
    case 'no-values':
      // Show all rows with no values: hide all rows that have at least one non-zero column
      return data.filter(row => {
        return numericColumns.every(col => {
          const value = row[col];
          return value === 0 || value === '0' || value === '' || value === null || value === undefined;
        });
      });
      
    case 'hide-empty':
      // Hide rows where all numeric values are zero
      return data.filter(row => {
        return numericColumns.some(col => {
          const value = row[col];
          return value !== 0 && value !== '0' && value !== '' && value !== null && value !== undefined;
        });
      });
      
    default:
      return data;
  }
}

/**
 * Check if a row has any non-zero values in numeric columns
 * @param {Object} row - Data row object
 * @param {Array} numericColumns - Array of column names to check
 * @returns {boolean} - True if row has at least one non-zero value
 */
export function hasNonZeroValues(row, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  return numericColumns.some(col => {
    const value = row[col];
    return value !== 0 && value !== '0' && value !== '' && value !== null && value !== undefined;
  });
}

/**
 * Check if a row has all zero values in numeric columns
 * @param {Object} row - Data row object
 * @param {Array} numericColumns - Array of column names to check
 * @returns {boolean} - True if row has all zero values
 */
export function hasAllZeroValues(row, numericColumns = ['registrations', 'enrollments', 'certificates']) {
  return numericColumns.every(col => {
    const value = row[col];
    return value === 0 || value === '0' || value === '' || value === null || value === undefined;
  });
}

/**
 * Generate HTML for a table row
 * @param {Object} row - Data row object
 * @param {string} nameColumn - Name of the column containing the display name
 * @param {Array} numericColumns - Array of numeric column names
 * @param {Function} nameFormatter - Optional function to format the display name
 * @returns {string} - HTML string for the table row
 */
export function generateTableRowHTML(row, nameColumn, numericColumns = ['registrations', 'enrollments', 'certificates'], nameFormatter = null) {
  let displayName = row[nameColumn];
  
  // Apply name formatting if provided
  if (nameFormatter && typeof nameFormatter === 'function') {
    displayName = nameFormatter(row);
  }
  
  const cells = [
    `<td class="${nameColumn}">${displayName}</td>`
  ];
  
  numericColumns.forEach(col => {
    const value = row[col];
    cells.push(`<td>${value}</td>`);
  });
  
  return `<tr>${cells.join('')}</tr>`;
}

/**
 * Update table with filtered data
 * @param {string} tableId - ID of the table to update
 * @param {Array} data - Data array
 * @param {string} mode - Display mode
 * @param {string} nameColumn - Name of the column containing the display name
 * @param {Array} numericColumns - Array of numeric column names
 * @param {Function} nameFormatter - Optional function to format the display name
 * @param {string} emptyMessage - Message to show when no data
 */
export function updateTableWithFilteredData(tableId, data, mode, nameColumn, numericColumns = ['registrations', 'enrollments', 'certificates'], nameFormatter = null, emptyMessage = 'No data available') {
  const tbody = document.querySelector(`#${tableId} tbody`);
  if (!tbody) {
    return;
  }

  // Filter data based on mode
  const filteredData = filterTableData(data, mode, numericColumns);
  
  if (filteredData.length === 0) {
    let message = emptyMessage;
    if (mode === 'hide-empty') {
      message = 'No rows with data for this range.';
    } else if (mode === 'no-values') {
      message = 'No rows with all zero values for this range.';
    }
    tbody.innerHTML = `<tr><td colspan="${numericColumns.length + 1}">${message}</td></tr>`;
    return;
  }
  
  // Generate HTML for filtered data
  const htmlString = filteredData.map(row => 
    generateTableRowHTML(row, nameColumn, numericColumns, nameFormatter)
  ).join('');
  
  tbody.innerHTML = htmlString;
}

/**
 * Display message in data display message element
 * @param {string} tableType - 'organization' or 'groups'
 * @param {string} message - Message to display
 * @param {string} type - Message type: 'info', 'warning', 'error', 'success'
 */
export function showDataDisplayMessage(tableType, message, type = 'info') {
  // Queue message display to prevent race conditions
  const messageAction = () => {
    const messageElement = document.getElementById(`${tableType}-data-display-message`);
    if (!messageElement) {
      return;
    }
    
    try {
      // Remove all message type classes
      messageElement.classList.remove('error-message', 'success-message', 'info-message', 'warning-message');
      
      if (message && message.trim()) {
        // Show message with appropriate styling (same as date picker)
        messageElement.classList.add(`${type}-message`);
        messageElement.innerHTML = message;
        messageElement.setAttribute('aria-live', 'polite');
      } else {
        // Hide message by clearing content (same as date picker)
        messageElement.innerHTML = '';
      }
    } catch (error) {
  
    }
  };
  
  // Queue the message display to prevent race conditions
  queueMessageDisplay(messageAction);
}

/**
 * Clear message in data display message element
 * @param {string} tableType - 'organization' or 'groups'
 */
export function clearDataDisplayMessage(tableType) {
  const messageElement = document.getElementById(`${tableType}-data-display-message`);
  if (messageElement) {
    messageElement.innerHTML = '';
    messageElement.classList.remove('error-message', 'success-message', 'info-message', 'warning-message');
  }
}

/**
 * Queue message display to prevent race conditions
 * @param {Function} messageAction - Function to execute for message display
 */
function queueMessageDisplay(messageAction) {
  if (isDisplayingMessage) {
    // If already displaying, queue this action
    messageDisplayQueue.push(messageAction);
    return;
  }
  
  isDisplayingMessage = true;
  messageAction();
  isDisplayingMessage = false;
  
  // Process any queued message displays
  while (messageDisplayQueue.length > 0) {
    const nextAction = messageDisplayQueue.shift();
    if (nextAction) {
      isDisplayingMessage = true;
      nextAction();
      isDisplayingMessage = false;
    }
  }
} 