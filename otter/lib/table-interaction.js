document.addEventListener('DOMContentLoaded', function() {
    // --- Get references to elements --- 
    const allTableToggleButtons = document.querySelectorAll('.table-toggle-button'); // Select new buttons
    const allTbodies = document.querySelectorAll('.table-responsive tbody');
    const toggleAllButton = document.getElementById('toggle-all-button');
    const dismissButton = document.getElementById('dismiss-info-button'); // Target new button ID
    const globalToggleContainer = document.getElementById('global-toggle-controls'); // Get its container

    // Removed scroll position tracking - no automatic scrolling

    // --- Initialize individual table toggle buttons --- 
    allTableToggleButtons.forEach(button => {
        // Only set aria attributes if not already set in HTML
        if (!button.hasAttribute('aria-expanded')) {
            button.setAttribute('aria-expanded', 'false');
        }
        if (!button.hasAttribute('aria-label')) {
            button.setAttribute('aria-label', 'Show data rows');
        }
        // Add click handler
        button.addEventListener('click', toggleSingleTable);
        
        // Add keyboard handler (Enter/Space)
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleSingleTable.call(this);
            }
        });
    });

    // --- Initialize and handle Global Toggle Button --- 
    if (toggleAllButton) {
        toggleAllButton.addEventListener('click', function() {
            const currentStateIsExpanded = this.getAttribute('aria-expanded') === 'true';
            const targetStateIsExpanded = !currentStateIsExpanded;
            const targetLabel = targetStateIsExpanded ? 'Data rows on all tables are visible.' : 'Data rows on all tables are hidden.'; 
            const individualButtonLabel = targetStateIsExpanded ? 'Hide data rows' : 'Show data rows'; 

            if (targetStateIsExpanded) {
                // Expand all tables
                allTbodies.forEach(tbody => {
                    tbody.classList.add('visible');
                });

                // Update all individual table toggle buttons
                allTableToggleButtons.forEach(button => {
                    button.setAttribute('aria-expanded', 'true');
                    button.setAttribute('aria-label', 'Hide data rows');
                });
            } else {
                // Collapse all tables
                allTbodies.forEach(tbody => {
                    tbody.classList.remove('visible');
                });

                // Update all individual table toggle buttons
                allTableToggleButtons.forEach(button => {
                    button.setAttribute('aria-expanded', 'false');
                    button.setAttribute('aria-label', 'Show data rows');
                });
            }

            this.setAttribute('aria-expanded', targetStateIsExpanded);
            this.setAttribute('aria-label', targetLabel); 
        });
    }

    // --- Handle Dismiss Button Click --- 
    if (dismissButton && globalToggleContainer) {
        dismissButton.addEventListener('click', function() {
            globalToggleContainer.style.display = 'none';
            // No focus management needed for simple dismiss
        });
    }

    // --- Function to toggle a single table (used by individual buttons) --- 
    function toggleSingleTable() {
        const caption = this.closest('caption');
        const table = caption?.closest('table'); 
        if (!table) return; 
        const tbody = table.querySelector('tbody');
        if (!tbody) return; 
        
        const isVisible = tbody.classList.toggle('visible');
        this.setAttribute('aria-expanded', isVisible);
        this.setAttribute('aria-label', isVisible ? 'Hide data rows' : 'Show data rows');
    }
}); 