// datalist-utils.js
// Utility for populating datalists from table data

export function populateDatalistFromTable(tableId, datalistId) {
  const table = document.getElementById(tableId);
  const datalist = document.getElementById(datalistId);
  
  if (!table) {
    return;
  }
  
  if (!datalist) {
    return;
  }
  
  const tbody = table.querySelector('tbody');
  
  if (!tbody) {
    return;
  }
  
  const rows = tbody.querySelectorAll('tr');
  
  const names = Array.from(rows)
    .map(row => {
      const firstCell = row.querySelector('td:first-child');
      if (firstCell) {
        const text = firstCell.textContent.trim();
        // Apply abbreviation to datalist options
        return typeof abbreviateOrganizationNameJS === 'function' 
          ? abbreviateOrganizationNameJS(text) 
          : text;
      }
      return '';
    })
    .filter(Boolean);
  
  const uniqueNames = Array.from(new Set(names));
  
  const options = uniqueNames
    .map(name => `<option value="${name}"></option>`)
    .join('');
  
  datalist.innerHTML = options;
} 