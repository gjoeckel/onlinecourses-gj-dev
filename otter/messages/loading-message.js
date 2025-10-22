// loading-message.js
(function() {
  function ensureContainer() {
    let el = document.getElementById('loading-message');
    if (!el) {
      el = document.createElement('div');
      el.id = 'loading-message';
      el.className = 'loading-message';
      el.setAttribute('aria-live', 'polite');
      el.style.display = 'none';
      document.body.appendChild(el);
    }
    return el;
  }
  window.showLoadingMessage = function(msg, type = 'success', extraClass = '') {
    const el = ensureContainer();
    
    // Clear existing content
    el.innerHTML = '';
    
    // Add loading text
    const textEl = document.createElement('div');
    textEl.className = 'loading-text';
    textEl.textContent = msg;
    el.appendChild(textEl);
    
    // Set classes
    el.className = 'loading-message ' +
      (type === 'error' ? 'error-message' : 'success-message') +
      (extraClass ? ' ' + extraClass : '');
    
    el.style.display = 'block';
    
    // Focus for accessibility
    el.focus();
  };
  window.hideLoadingMessage = function() {
    const el = ensureContainer();
    el.style.display = 'none';
    el.innerHTML = '';
  };
  
  // Dashboard-specific loading function
  window.showDashboardLoading = function() {
    showLoadingMessage('Updating dashboard data...', 'success', 'dashboard-refresh');
  };
})(); 