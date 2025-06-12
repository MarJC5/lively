import { ComponentManager } from './components.js';
import { EventHandler } from './events.js';
import { NetworkManager } from './network.js';
import { StateManager } from './state.js';
import { ErrorHandler } from './errors.js';
import { Logger } from './utils.js';

export class Lively {
    constructor(config = {}) {
        // Store component state
        this.components = {};
        
        // Debug configuration with defaults
        this.config = {
            debug: false,
            debounceTimeout: 300, // Default debounce timeout in milliseconds
            ...config // Apply any provided config options
        };
        
        // Store debounce timers
        this.debounceTimers = {};
        
        // Store CSRF token
        this.csrfToken = this.findCsrfToken();
        
        // Initialize managers
        this.logger = new Logger(this.config.debug);
        this.componentManager = new ComponentManager(this);
        this.eventHandler = new EventHandler(this);
        this.networkManager = new NetworkManager(this);
        this.stateManager = new StateManager(this);
        this.errorHandler = new ErrorHandler(this);
        
        // Log initial configuration if debug is enabled
        if (this.config.debug) {
            this.logger.log('Lively framework initialized with debug mode enabled');
        }
    }
    
    // Find CSRF token in the page
    findCsrfToken() {
        // Look for a meta tag with name="csrf-token"
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        // Look for an input field with name="csrf_token"
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            return csrfInput.value;
        }
        
        return null;
    }
    
    // Initialize the framework
    init() {
        this.logger.log('Lively framework initialized');
        
        // Initialize all managers
        this.componentManager.init();
        this.eventHandler.init();
        this.networkManager.init();
        this.stateManager.init();
        
        // Setup timeout event handlers
        this.setupTimeoutHandlers();
    }
    
    // Setup timeout event handlers
    setupTimeoutHandlers() {
        document.querySelectorAll('[lively\\:ontimeout]').forEach(el => {
            const timeout = parseInt(el.getAttribute('lively:ontimeout:debounce')) || 0;
            if (timeout > 0) {
                setTimeout(() => {
                    const componentId = this.componentManager.getComponentId(el);
                    const action = el.getAttribute('lively:ontimeout');
                    if (componentId && action) {
                        this.updateComponent(componentId, action, {});
                    }
                }, timeout);
            }
        });
    }
    
    // Enable or disable debug logging
    setDebug(enabled) {
        this.config.debug = enabled;
        this.logger.setDebug(enabled);
        this.logger.log(`Debug logging ${enabled ? 'enabled' : 'disabled'}`);
    }
    
    // Update a component by calling a method on the server
    updateComponent(componentId, method, args = {}) {
        this.logger.log(`Updating component: ${method} on ${componentId}`);
        return this.networkManager.updateComponent(componentId, method, args);
    }
} 