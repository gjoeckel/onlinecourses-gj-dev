// print-utils.js
// Centralized print utilities for all pages

/**
 * Preload print CSS to ensure it's available for print preview
 * @param {string} cssPath - Path to the print CSS file
 */
export function preloadPrintCSS(cssPath = '../css/print.css') {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = cssPath + '?v=' + Date.now();
    link.media = 'print';
    document.head.appendChild(link);
    return link;
}

/**
 * Ensure print CSS is loaded before print operations
 * @param {string} cssPath - Path to the print CSS file
 * @returns {Promise} Promise that resolves when CSS is loaded
 */
export function ensurePrintCSSLoaded(cssPath = '../css/print.css') {
    return new Promise((resolve) => {
        const link = document.querySelector('link[href*="print.css"]');
        if (link && link.sheet) {
            resolve();
        } else {
            // If not loaded, wait a bit and try again
            setTimeout(() => {
                preloadPrintCSS(cssPath);
                setTimeout(resolve, 100);
            }, 50);
        }
    });
}

/**
 * Create a print button handler that uses window.print() with CSS preloading
 * @param {string} buttonId - ID of the print button
 * @param {string} cssPath - Path to the print CSS file
 */
export function createPrintButtonHandler(buttonId, cssPath = '../css/print.css') {
    const printBtn = document.getElementById(buttonId);
    if (printBtn) {
        printBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            await ensurePrintCSSLoaded(cssPath);
            window.print();
        });
    }
}

/**
 * Create a print window with specified content and styling (for reports)
 * @param {string} title - Title for the print window
 * @param {string} content - HTML content to print
 * @param {string} orientation - Print orientation ('landscape' or 'portrait')
 * @param {string} cssPath - Path to the print CSS file
 * @param {string} startDate - Start date in MM-DD-YY format (optional)
 * @param {string} endDate - End date in MM-DD-YY format (optional)
 */
export function createPrintWindow(title, content, orientation = 'landscape', cssPath = '../css/print.css', startDate = null, endDate = null) {
    const printWindow = window.open('', '', 'height=700,width=900');
    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<link rel="stylesheet" href="css/reports-data.css" type="text/css" />');
    printWindow.document.write('<link rel="stylesheet" href="' + cssPath + '?v=' + Date.now() + '" type="text/css" />');
    printWindow.document.write('<style>@media print { @page { size: ' + orientation + '; } }</style>');
    printWindow.document.write('</head><body>');
    
    // Create a temporary div to parse the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    
    // Find all table captions
    const captions = tempDiv.querySelectorAll('caption');
    captions.forEach(caption => {
        const captionText = caption.textContent.trim();
        let newCaptionText = captionText;
        
        // Add date range if provided and not already present
        if (startDate && endDate && !captionText.includes('|') && !captionText.includes('to')) {
            newCaptionText += ' | ' + startDate + ' to ' + endDate;
        }
        
        // Add display mode information if available
        const displayMode = getCurrentDisplayMode(caption);
        if (displayMode) {
            const captionBase = getCaptionBase(caption);
            const count = getTableRowCount(caption);
            const displayModeText = getDisplayModeText(displayMode, count, captionBase);
            if (displayModeText) {
                newCaptionText += ' | ' + displayModeText;
            }
        }
        
        // Update caption text if it changed
        if (newCaptionText !== captionText) {
            caption.textContent = newCaptionText;
        }
    });
    
    const modifiedContent = tempDiv.innerHTML;
    printWindow.document.write(modifiedContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    
    // Ensure CSS is loaded before printing
    setTimeout(function() {
        // Check if CSS is loaded
        const links = printWindow.document.querySelectorAll('link[rel="stylesheet"]');
        let cssLoaded = true;
        
        links.forEach(link => {
            if (!link.sheet && !link.href.includes('data:')) {
                cssLoaded = false;
            }
        });
        
        if (cssLoaded) {
            printWindow.print();
            printWindow.close();
        } else {
            // Wait a bit more for CSS to load
            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    }, 250);
}

/**
 * Get the current display mode for a table based on its caption
 * @param {HTMLElement} caption - The caption element
 * @returns {string|null} - The current display mode or null if not found
 */
function getCurrentDisplayMode(caption) {
    // Check if this is an organizations table
    const captionText = caption.textContent.toLowerCase();
    if (captionText.includes('organizations') || captionText.includes('organization')) {
        // Check for organization display mode
        const organizationRadios = document.querySelectorAll('input[name="organization-data-display"]');
        for (const radio of organizationRadios) {
            if (radio.checked) {
                return radio.value;
            }
        }
    }
    
    // Check if this is a groups/districts table
    if (captionText.includes('districts') || captionText.includes('groups') || captionText.includes('group')) {
        // Check for groups display mode
        const groupsRadios = document.querySelectorAll('input[name="groups-data-display"]');
        for (const radio of groupsRadios) {
            if (radio.checked) {
                return radio.value;
            }
        }
    }
    
    return null;
}

/**
 * Get the display text for a display mode
 * @param {string} mode - The display mode
 * @param {number} count - The count of items
 * @param {string} captionBase - The base caption value (e.g., "organizations", "districts")
 * @returns {string|null} - The display text or null if mode is not recognized
 */
function getDisplayModeText(mode, count, captionBase) {
    const baseLower = captionBase.toLowerCase();
    
    switch (mode) {
        case 'all':
            return `all ${count} ${baseLower}`;
        case 'no-values':
            return `${count} ${baseLower} with no data`;
        case 'hide-empty':
            return `${count} ${baseLower} with data`;
        default:
            return null; // Don't add anything for unrecognized modes
    }
}

/**
 * Get the caption base (e.g., "organizations", "districts") from a table caption
 * @param {HTMLElement} caption - The caption element
 * @returns {string|null} - The caption base or null if not found
 */
function getCaptionBase(caption) {
    const captionText = caption.textContent.toLowerCase();
    if (captionText.includes('organizations') || captionText.includes('organization')) {
        return 'organizations';
    }
    if (captionText.includes('districts') || captionText.includes('groups') || captionText.includes('group')) {
        return 'districts';
    }
    return null;
}

/**
 * Get the number of rows in a table associated with a caption
 * @param {HTMLElement} caption - The caption element
 * @returns {number} - The number of rows
 */
function getTableRowCount(caption) {
    // Find the table that contains this caption
    const table = caption.closest('table');
    if (!table) return 0;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return 0;
    
    // Count visible rows (excluding any that might be hidden)
    let count = 0;
    for (const row of tbody.children) {
        if (row.style.display !== 'none') {
            count++;
        }
    }
    
    return count;
}

/**
 * Create print button handler for a specific section (for reports)
 * @param {string} buttonId - ID of the print button
 * @param {string} sectionId - ID of the section to print
 * @param {string} title - Title for the print window
 * @param {string} orientation - Print orientation
 * @param {string} cssPath - Path to the print CSS file
 */
export function createSectionPrintButtonHandler(buttonId, sectionId, title, orientation = 'landscape', cssPath = '../css/print.css') {
    const printBtn = document.getElementById(buttonId);
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            const section = document.getElementById(sectionId);
            if (!section) return;
            
            const table = section.querySelector('.table-responsive');
            if (!table) return;
            
            // Get current date range from the page
            let startDate = null;
            let endDate = null;
            
            // Try to get date range from the active range display
            const activeRangeElement = document.getElementById('active-range-values');
            if (activeRangeElement && activeRangeElement.textContent && !activeRangeElement.textContent.includes('No date range selected')) {
                const rangeText = activeRangeElement.textContent;
                const rangeMatch = rangeText.match(/(\d{2}-\d{2}-\d{2})\s+to\s+(\d{2}-\d{2}-\d{2})/);
                if (rangeMatch) {
                    startDate = rangeMatch[1];
                    endDate = rangeMatch[2];
                }
            }
            
            // Fallback: try to get from date inputs
            if (!startDate || !endDate) {
                const startInput = document.getElementById('start-date');
                const endInput = document.getElementById('end-date');
                if (startInput && endInput && startInput.value && endInput.value) {
                    startDate = startInput.value;
                    endDate = endInput.value;
                }
            }
            
            createPrintWindow(title, table.outerHTML, orientation, cssPath, startDate, endDate);
        });
    }
}

/**
 * Initialize print functionality for a page
 * @param {Object} options - Configuration options
 * @param {string} options.cssPath - Path to print CSS file
 * @param {Array} options.printButtons - Array of print button configurations
 */
export function initializePrintFunctionality(options = {}) {
    const {
        cssPath = '../css/print.css',
        printButtons = []
    } = options;
    
    // Preload print CSS immediately
    preloadPrintCSS(cssPath);
    
    // Override window.print to ensure CSS is loaded first
    const originalPrint = window.print;
    window.print = async function() {
        await ensurePrintCSSLoaded(cssPath);
        originalPrint.call(this);
    };
    
    // Set up print buttons
    printButtons.forEach(button => {
        if (button.type === 'window') {
            createSectionPrintButtonHandler(
                button.id, 
                button.sectionId, 
                button.title, 
                button.orientation, 
                cssPath
            );
        } else {
            createPrintButtonHandler(button.id, cssPath);
        }
    });
} 