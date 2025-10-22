// search-utils.js
// Centralized search utilities for reports pages

/**
 * Get table names from the first column of a table
 * @param {string} tableId - ID of the table to extract names from
 * @returns {Array} Array of lowercase table names
 */
export function getTableNames(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return [];
  const tbody = table.querySelector('tbody');
  if (!tbody) return [];
  return Array.from(tbody.querySelectorAll('tr td:first-child'))
    .map(td => td.textContent.trim().toLowerCase())
    .filter(Boolean);
}

/**
 * Update search buttons state based on input value and filtered state
 * @param {HTMLElement} input - Search input element
 * @param {HTMLElement} findBtn - Find/Filter button
 * @param {HTMLElement} clearBtn - Clear button
 * @param {boolean} isFiltered - Whether the table is currently filtered
 */
export function updateSearchButtonsState(input, findBtn, clearBtn, isFiltered) {
  if (!input || !findBtn || !clearBtn) return;
  const value = input.value.trim().toLowerCase();
  const names = getTableNames(input.dataset.tableId);
  const match = names.includes(value);
  
  if (isFiltered) {
    findBtn.disabled = true;
    clearBtn.disabled = false;
  } else {
    findBtn.disabled = !match;
    // Enable Clear button when there's any input value, not just when there's a match
    clearBtn.disabled = !value;
  }
}

/**
 * Filter table rows based on search value
 * @param {string} tableId - ID of the table to filter
 * @param {string} searchValue - Value to search for
 * @param {string} columnClass - CSS class of the column to search in
 * @returns {boolean} True if any rows match the search
 */
export function filterTableRows(tableId, searchValue, columnClass) {
  const table = document.getElementById(tableId);
  if (!table) return false;
  
  const tbody = table.querySelector('tbody');
  if (!tbody) return false;
  
  const rows = tbody.querySelectorAll('tr');
  const searchLower = searchValue.toLowerCase();
  let hasMatches = false;
  
  rows.forEach(row => {
    const cell = row.querySelector(`td.${columnClass}`);
    if (cell) {
      const cellText = cell.textContent.trim().toLowerCase();
      const matches = cellText.includes(searchLower);
      row.style.display = matches ? '' : 'none';
      if (matches) hasMatches = true;
    }
  });
  
  return hasMatches;
}

/**
 * Clear table filter and show all rows
 * @param {string} tableId - ID of the table to clear filter for
 */
export function clearTableFilter(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  
  const tbody = table.querySelector('tbody');
  if (!tbody) return;
  
  const rows = tbody.querySelectorAll('tr');
  rows.forEach(row => {
    row.style.display = '';
  });
} 