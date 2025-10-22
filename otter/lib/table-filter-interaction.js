// Enhanced table interaction: toggles both <tbody> and associated Filter widget for each table
// Usage: For each table with a filter widget, ensure the filter widget's id is <table-id>-search-widget

document.addEventListener('DOMContentLoaded', function() {
    // Find all tables with a toggle button
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const tableId = table.id;
        if (!tableId) return;
        const toggleBtn = table.querySelector('.table-toggle-button');
        const tbody = table.querySelector('tbody');
        // Convention: filter widget id is <table-id>-search-widget, e.g., organization-data => organization-search-widget
        const filterWidgetId = tableId.replace(/-data$/, '') + '-search-widget';
        const filterWidget = document.getElementById(filterWidgetId);
        if (!toggleBtn || !tbody) return;

        // Set default state: collapsed
        tbody.style.display = 'none';
        if (filterWidget) filterWidget.style.display = 'none';
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.setAttribute('aria-label', 'Show table filter and data rows');

        // Store original scroll position for each table
        let originalScrollPosition = 0;
        
        toggleBtn.addEventListener('click', function () {
            const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                // Collapse: hide tbody and filter, then scroll back to original position
                tbody.style.display = 'none';
                if (filterWidget) filterWidget.style.display = 'none';
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('aria-label', 'Show table filter and data rows');
                
                // Scroll back to original position
                window.scrollTo({
                    top: originalScrollPosition,
                    behavior: 'smooth'
                });
            } else {
                // Store current scroll position before expanding
                originalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                
                // Expand: show tbody and filter
                tbody.style.display = '';
                if (filterWidget) filterWidget.style.display = '';
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.setAttribute('aria-label', 'Hide table filter and data rows');
                
                // Scroll to position the filter widget 30px from the bottom of the header
                if (filterWidget) {
                    setTimeout(() => {
                        const header = document.querySelector('header');
                        const headerHeight = header ? header.offsetHeight : 0;
                        const targetPosition = filterWidget.offsetTop - headerHeight - 30;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }, 100); // Small delay to ensure the widget is visible
                }
            }
        });
    });
}); 