import { Logger } from './utils.js';

export class ErrorHandler {
    constructor(lively) {
        this.lively = lively;
        this.logger = new Logger(lively.config.debug);
    }
    
    init() {
        // Nothing to initialize for error handler
    }
    
    // Handle component errors
    handleError(componentId, error, options = {}) {
        const {
            showError = true,
            logError = true,
            throwError = false
        } = options;
        
        // Log the error if enabled
        if (logError) {
            this.logger.error(`Error in component ${componentId}:`, error);
        }
        
        // Show error in UI if enabled
        if (showError) {
            this.showComponentError(componentId, error.message || 'An error occurred');
        }
        
        // Throw error if enabled
        if (throwError) {
            throw error;
        }
    }
    
    // Show component error in UI
    showComponentError(componentId, errorMessage) {
        const el = document.querySelector(`[lively\\:component="${componentId}"]`);
        
        if (el) {
            // Add error class
            el.classList.add('lively-error', 'lively-error-highlight');
            
            // Find or create error element
            let errorEl = el.querySelector('.lively-error-message');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'lively-error-message';
                el.appendChild(errorEl);
            }
            
            // Set error message
            errorEl.textContent = errorMessage;
        } else {
            this.logger.error(`Error displaying component error: Element with ID ${componentId} not found in DOM`);
        }
    }
    
    // Clear component error
    clearError(componentId) {
        const el = document.querySelector(`[lively\\:component="${componentId}"]`);
        
        if (el) {
            // Remove error classes
            el.classList.remove('lively-error', 'lively-error-highlight');
            
            // Remove error message element
            const errorEl = el.querySelector('.lively-error-message');
            if (errorEl) {
                errorEl.remove();
            }
        }
    }
} 