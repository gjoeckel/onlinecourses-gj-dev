// Debug script for filter state manager issues
// Run this in browser console to diagnose the problem

console.log('=== Filter State Manager Debug ===');

// Check if FilterStateManager exists
if (typeof FilterStateManager !== 'undefined') {
  console.log('✅ FilterStateManager found');
  
  // Check current state
  console.log('Current organizations state:', FilterStateManager.getState('organizations'));
  console.log('Current groups state:', FilterStateManager.getState('groups'));
  
  // Check if state is valid
  console.log('Organizations state valid:', FilterStateManager.validateState('organizations'));
  console.log('Groups state valid:', FilterStateManager.validateState('groups'));
  
  // Check VALID_DISPLAY_MODES
  console.log('Valid display modes:', VALID_DISPLAY_MODES);
  
  // Check if 'no-values' is in valid modes
  console.log("'no-values' in VALID_DISPLAY_MODES:", VALID_DISPLAY_MODES.includes('no-values'));
  
  // Check the actual state object
  console.log('Raw organizations state:', FilterStateManager.state.organizations);
  console.log('Raw groups state:', FilterStateManager.state.groups);
  
} else {
  console.log('❌ FilterStateManager not found');
}

// Check for duplicate event listeners
console.log('=== Event Listener Check ===');
const events = getEventListeners ? getEventListeners(document) : 'getEventListeners not available';
console.log('Document event listeners:', events);

// Check current display mode variables
if (typeof currentOrganizationDisplayMode !== 'undefined') {
  console.log('currentOrganizationDisplayMode:', currentOrganizationDisplayMode);
} else {
  console.log('❌ currentOrganizationDisplayMode not found');
}

if (typeof currentGroupsDisplayMode !== 'undefined') {
  console.log('currentGroupsDisplayMode:', currentGroupsDisplayMode);
} else {
  console.log('❌ currentGroupsDisplayMode not found');
}

// Check processing flags
if (typeof isProcessingOrganization !== 'undefined') {
  console.log('isProcessingOrganization:', isProcessingOrganization);
} else {
  console.log('❌ isProcessingOrganization not found');
}

if (typeof isProcessingGroups !== 'undefined') {
  console.log('isProcessingGroups:', isProcessingGroups);
} else {
  console.log('❌ isProcessingGroups not found');
}

// Test the validation logic directly
console.log('=== Validation Test ===');
if (typeof VALID_DISPLAY_MODES !== 'undefined') {
  console.log("Testing 'no-values' validation:");
  console.log("'no-values' in VALID_DISPLAY_MODES:", VALID_DISPLAY_MODES.includes('no-values'));
  console.log("'all' in VALID_DISPLAY_MODES:", VALID_DISPLAY_MODES.includes('all'));
  console.log("'hide-empty' in VALID_DISPLAY_MODES:", VALID_DISPLAY_MODES.includes('hide-empty'));
  console.log("'invalid-mode' in VALID_DISPLAY_MODES:", VALID_DISPLAY_MODES.includes('invalid-mode'));
}

// Check if there are multiple instances of the same script loaded
console.log('=== Script Loading Check ===');
const scripts = document.querySelectorAll('script[src*="filter-state-manager"]');
console.log('Filter state manager scripts found:', scripts.length);
scripts.forEach((script, index) => {
  console.log(`Script ${index + 1}:`, script.src);
});

const dataDisplayScripts = document.querySelectorAll('script[src*="data-display-options"]');
console.log('Data display options scripts found:', dataDisplayScripts.length);
dataDisplayScripts.forEach((script, index) => {
  console.log(`Script ${index + 1}:`, script.src);
});

console.log('=== Debug Complete ===');

// Function to reset state (run this if needed)
window.resetFilterState = function() {
  if (typeof FilterStateManager !== 'undefined') {
    FilterStateManager.state.organizations.displayMode = 'all';
    FilterStateManager.state.organizations.previousDisplayMode = null;
    FilterStateManager.state.organizations.isDataDisplayDisabled = false;
    
    FilterStateManager.state.groups.displayMode = 'all';
    FilterStateManager.state.groups.previousDisplayMode = null;
    FilterStateManager.state.groups.isDataDisplayDisabled = false;
    
    console.log('✅ Filter state reset to defaults');
  }
}; 