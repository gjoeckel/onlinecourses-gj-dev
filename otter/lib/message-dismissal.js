/**
 * Message Dismissal Utility
 * Provides scoped message dismissal functionality across all pages
 * Error messages are scoped to specific input fields, success messages to all interactions
 */

class MessageDismissal {
    constructor() {
        this.messageDisplay = null;
        this.isDismissing = false;
        this.disabled = false; // Add disabled flag
        
        this.init();
    }
    
    init() {
        // Find message display element
        this.messageDisplay = document.getElementById('message-display');
        
        if (this.messageDisplay && !this.disabled) {
            // Set up message change detection
            this.observeMessageChanges();
            
            // Add dismissal listeners
            this.addDismissalListeners();
        }
    }
    
    observeMessageChanges() {
        // Create a MutationObserver to watch for message content changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    const hasContent = this.messageDisplay.textContent.trim() !== '';
                    const isVisible = !this.messageDisplay.className.includes('visually-hidden');
                    
                    if (hasContent && isVisible) {
                        // Message is being displayed
                        this.handleMessageDisplay();
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(this.messageDisplay, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }
    
    handleMessageDisplay() {
        const isError = this.messageDisplay.className.includes('error-message');
        
        if (isError) {
            // For error messages, focus the relevant input field
            this.focusErrorInput();
        }
        
        // Reset dismissal state
        this.isDismissing = false;
    }
    
    focusErrorInput() {
        // Page-specific input field mapping for error messages
        const errorInputMap = {
            'login.php': '#password',
            'settings/index.php': '#new_password',
            'reports/index.php': '#start_date'
        };
        
        // Determine current page
        const currentPage = this.getCurrentPage();
        const inputSelector = errorInputMap[currentPage];
        
        if (inputSelector) {
            const inputElement = document.querySelector(inputSelector);
            if (inputElement) {
                // For settings page, don't clear the input field as it has custom error handling
                if (currentPage !== 'settings/index.php') {
                    // Clear the input field
                    inputElement.value = '';
                }
                
                // Focus the input field
                inputElement.focus();
            }
        }
    }
    
    getCurrentPage() {
        // Extract page name from URL path with enhanced production handling
        const path = window.location.pathname;
        const parts = path.split('/').filter(part => part);
        
        if (parts.length === 0) {
            return 'index.php'; // Root page
        } else if (parts.length === 1) {
            return parts[0]; // Single level: login.php, dashboard.php, etc.
        } else {
            // For production paths like /training/online/otter/login.php
            // Return the last part (filename) instead of full path
            return parts[parts.length - 1];
        }
    }
    
    addDismissalListeners() {
        const isError = this.messageDisplay.className.includes('error-message');
        
        if (isError) {
            // For error messages, only add listeners to the specific input field
            this.addErrorDismissalListeners();
        } else {
            // For success/info messages, add listeners to all interactive elements
            this.addSuccessDismissalListeners();
        }
    }
    
    addErrorDismissalListeners() {
        // Page-specific input field mapping for error messages
        const errorInputMap = {
            'login.php': '#password',
            'settings/index.php': '#new_password',
            'reports/index.php': '#start_date'
        };
        
        // Page-specific dismissal behavior configuration
        const dismissalConfig = {
            'login.php': { strategy: 'non-empty', clearInput: true },
            'settings/index.php': { strategy: 'any-input', clearInput: false },
            'reports/index.php': { strategy: 'non-empty', clearInput: true }
        };
        
        const currentPage = this.getCurrentPage();

        const inputSelector = errorInputMap[currentPage];
        const config = dismissalConfig[currentPage] || { strategy: 'non-empty', clearInput: true };
        

        
        if (inputSelector) {
            const inputElement = document.querySelector(inputSelector);
            if (inputElement) {
                // Remove any existing listeners to prevent duplicates
                inputElement.removeEventListener('input', this.handleErrorDismissal);
                
                // Create bound event handler with configuration
                this.handleErrorDismissal = (event) => {
        
                    if (config.strategy === 'any-input') {
                        // Dismiss on any input (settings page behavior)

                        this.dismissMessage();
                    } else if (config.strategy === 'non-empty') {
                        // Only dismiss if the input becomes non-empty
                        if (event.target.value.trim() !== '') {
    
                            this.dismissMessage();
                        }
                    }
                };
                
                // Add listener with bound handler
                inputElement.addEventListener('input', this.handleErrorDismissal);
            }
        }
    }
    
    addSuccessDismissalListeners() {
        // Get all interactive elements for success messages
        const interactiveElements = document.querySelectorAll('button, a, input, select, textarea, [tabindex]');
        
        interactiveElements.forEach(element => {
            // Dismiss messages on focus, click, and keydown
            element.addEventListener('focus', () => this.dismissMessage());
            element.addEventListener('click', () => this.dismissMessage());
            element.addEventListener('keydown', () => this.dismissMessage());
            element.addEventListener('input', () => this.dismissMessage());
            element.addEventListener('change', () => this.dismissMessage());
        });
        
        // Also dismiss messages when clicking anywhere in form areas
        const formAreas = document.querySelectorAll('form, .form-content, .section-content, [role="form"]');
        formAreas.forEach(area => {
            area.addEventListener('click', () => this.dismissMessage());
        });
        
        // Dismiss messages when clicking on tables
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            table.addEventListener('click', () => this.dismissMessage());
        });
    }
    
    dismissMessage() {
        if (!this.messageDisplay || this.isDismissing || this.disabled) {
            return;
        }
        
        const hasContent = this.messageDisplay.textContent.trim() !== '';
        if (!hasContent) {
            return;
        }
        
        // Dismiss immediately (no 3-second minimum)
        this.performDismissal();
    }
    
    performDismissal() {
        if (!this.messageDisplay || this.isDismissing) {
            return;
        }
        
        this.isDismissing = true;
        
        // Clear main message display
        if (this.messageDisplay.textContent.trim() !== '') {
            this.messageDisplay.textContent = '';
            this.messageDisplay.className = 'display-block visually-hidden-but-space';
            this.messageDisplay.setAttribute('aria-live', 'polite');
            this.messageDisplay.setAttribute('aria-hidden', 'true');
        }
        
        // Clear password check results (if they exist)
        const passwordCheckResult = document.getElementById('password_check_result');
        if (passwordCheckResult && passwordCheckResult.textContent.trim() !== '') {
            passwordCheckResult.textContent = '';
            passwordCheckResult.className = 'password-check-result';
        }
        
        // Remove any results divs
        const resultsDivs = document.querySelectorAll('.results');
        resultsDivs.forEach(div => div.remove());
        
        // Reset state
        this.isDismissing = false;
    }
    
    // Static method to show a message with automatic focus for errors
    static showMessage(message, type = 'info', elementId = 'message-display') {
        const messageDisplay = document.getElementById(elementId);
        if (!messageDisplay) {
            return;
        }
        
        // Set message content
        messageDisplay.textContent = message;
        
        // Update classes based on type
        messageDisplay.className = `display-block ${type}-message`;
        
        // Set appropriate ARIA attributes
        if (type === 'error') {
            messageDisplay.setAttribute('aria-live', 'assertive');
        } else {
            messageDisplay.setAttribute('aria-live', 'polite');
        }
        messageDisplay.removeAttribute('aria-hidden');
        
        // Calculate dynamic width with exactly 20px padding
        MessageDismissal.adjustMessageWidth(messageDisplay);
        
        // For error messages, focus the relevant input field
        if (type === 'error') {
            const instance = window.messageDismissal;
            if (instance) {
                instance.focusErrorInput();
            }
        }
        
        // Reset dismissal state
        if (window.messageDismissal) {
            window.messageDismissal.isDismissing = false;
        }
    }
    
    // Method to adjust message width dynamically with exactly 20px padding
    static adjustMessageWidth(messageElement) {
        if (!messageElement) return;
        
        // Create a temporary span to measure text width
        const tempSpan = document.createElement('span');
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.position = 'absolute';
        tempSpan.style.whiteSpace = 'nowrap';
        tempSpan.style.font = window.getComputedStyle(messageElement).font;
        tempSpan.textContent = messageElement.textContent;
        document.body.appendChild(tempSpan);
        
        // Get the text width
        const textWidth = tempSpan.offsetWidth;
        document.body.removeChild(tempSpan);
        
        // Calculate optimal width: text width + 40px (20px padding each side) + some buffer
        const optimalWidth = Math.min(textWidth + 60, window.innerWidth - 40);
        
        // Set the width with exactly 20px padding
        messageElement.style.width = `${optimalWidth}px`;
        messageElement.style.paddingLeft = '20px';
        messageElement.style.paddingRight = '20px';
        messageElement.style.margin = '1rem auto 0 auto';
        messageElement.style.boxSizing = 'border-box';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Create global instance
    window.messageDismissal = new MessageDismissal();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MessageDismissal;
} 