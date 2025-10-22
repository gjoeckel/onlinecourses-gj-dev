// enterprise-utils.js
// Unified enterprise utilities for consistent enterprise code access

/**
 * Get enterprise code from window context
 * @returns {string|null} Enterprise code or null if not available
 */
export function getEnterpriseCode() {
  return window.ENTERPRISE_CODE || null;
}

/**
 * Check if enterprise code is available
 * @returns {boolean} True if enterprise code is available
 */
export function hasEnterpriseCode() {
  return !!getEnterpriseCode();
}

/**
 * Check if current enterprise matches specified code
 * @param {string} enterpriseCode Enterprise code to check
 * @returns {boolean} True if current enterprise matches
 */
export function isEnterprise(enterpriseCode) {
  return getEnterpriseCode() === enterpriseCode;
}

/**
 * Get minimum start date from enterprise data
 * Uses the same enterprise data source as dashboard functionality
 * @returns {Promise<string>} Minimum start date in MM-DD-YY format
 */
export async function getMinStartDate() {
  try {
    // Import fetchEnterpriseData dynamically to avoid circular dependencies
    const { fetchEnterpriseData } = await import('./dashboard-link-utils.js');
    const data = await fetchEnterpriseData();
    return data.minStartDate || '01-01-24'; // Default fallback
  } catch (error) {

    return '01-01-24'; // Default fallback
  }
} 