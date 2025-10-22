// Fetches and caches enterprise.json
let _enterpriseCache = null;

/**
 * Clear the enterprise cache to force fresh data fetch
 */
export function clearEnterpriseCache() {
  _enterpriseCache = null;
}

/**
 * Fetch enterprise.json from the config folder.
 * @returns {Promise<Object>} The parsed JSON data
 */
export async function fetchEnterpriseData() {
  if (_enterpriseCache) {
    return _enterpriseCache;
  }
  
  // Determine the correct API path based on the current environment
  let apiPath;
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    // Local development - use relative path
    apiPath = '../lib/api/enterprise_api.php';
  } else {
    // Production - use absolute path from the root of the application
    apiPath = '/training/online/otter/lib/api/enterprise_api.php';
  }
  
  const fullUrl = new URL(apiPath, window.location.href).href;
  
  try {
    const response = await fetch(apiPath, { credentials: 'same-origin' });
    const contentType = response.headers.get('content-type') || '';

    if (!response.ok) {
      // Not a 2xx response
      const text = await response.text();
      throw new Error(`HTTP error ${response.status}: ${text}`);
    }

    if (!contentType.includes('application/json')) {
      // Not JSON, probably an error page
      const text = await response.text();
      throw new Error(`Expected JSON, got: ${text.substring(0, 200)}`);
    }

    const data = await response.json();
    
    _enterpriseCache = data;
    return _enterpriseCache;
  } catch (err) {
    throw err;
  }
}

/**
 * Get the dashboard URL for a given organization.
 * @param {string} orgName - The name of the organization
 * @returns {Promise<string|null>} The dashboard URL or null if not found
 */
export async function getDashboardUrlJS(orgName) {
  const data = await fetchEnterpriseData();
  if (!data || !data.organizations) return null;
  
  // First try exact match
  let org = data.organizations.find(org => org.name.toLowerCase() === orgName.toLowerCase());
  
  // If no exact match, try partial match (for cases like "Marin CCD" matching "Marin Community College District")
  if (!org) {
    org = data.organizations.find(org => {
      const fullName = org.name.toLowerCase();
      const searchName = orgName.toLowerCase();
      
      // Check if search term is contained in full name
      if (fullName.includes(searchName)) return true;
      
      // Check if full name is contained in search term
      if (searchName.includes(fullName)) return true;
      
      // Handle common abbreviations
      const abbreviations = {
        'ccd': 'community college district',
        'cc': 'community college',
        'university': 'univ',
        'college': 'col'
      };
      
      for (const [abbr, full] of Object.entries(abbreviations)) {
        const expandedSearch = searchName.replace(new RegExp(abbr, 'g'), full);
        if (fullName.includes(expandedSearch)) return true;
        
        const abbreviatedFull = fullName.replace(new RegExp(full, 'g'), abbr);
        if (abbreviatedFull.includes(searchName)) return true;
      }
      
      return false;
    });
  }
  
  if (!org) return null;
  
  // Use the same logic as settings page: add directory traversal prefix
  return `../dashboard.php?org=${org.password}`;
}

/**
 * Render a dashboard link or button with accessibility features.
 * @param {string} url - The dashboard URL.
 * @param {HTMLElement} container - The DOM element to append the link/button to.
 * @param {Object} [options] - Optional settings: { label, asButton }
 */
export function renderDashboardLink(url, container, options = {}) {
  if (!url || !container) return;
  const label = options.label || url;
  const asButton = options.asButton || false;
  let el;
  if (asButton) {
    el = document.createElement('button');
    el.type = 'button';
    el.onclick = () => window.open(url, '_blank', 'noopener');
    el.textContent = url;
  } else {
    el = document.createElement('a');
    el.href = url;
    el.target = '_blank';
    el.rel = 'noopener';
    el.textContent = url;
  }
  el.setAttribute('aria-label', label);
  el.setAttribute('title', label);
  el.classList.add('dashboard-link');
  container.appendChild(el);
} 