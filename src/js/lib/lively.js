class Lively {
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
        
        // Log initial configuration if debug is enabled
        if (this.config.debug) {
            console.log('Lively framework initialized with debug mode enabled');
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
        this.log('Lively framework initialized');
        // Register any initial components that were server-side rendered
        this.scanForComponents();
        
        // Setup a global event listener for all interactive events
        document.addEventListener('click', this.handleGlobalEvent.bind(this));
        document.addEventListener('input', this.handleGlobalEvent.bind(this));
        document.addEventListener('change', this.handleGlobalEvent.bind(this));
    }
    
    // Handle global events for all supported event types
    handleGlobalEvent(e) {
        if (!e.target) return;
        
        // Get element attributes for both formats
        const eventType = e.type; // 'click', 'input', or 'change'
        const componentId = this.getComponentId(e.target);
        
        if (!componentId) return;
        
        // Check both formats: data-lively-* and lively:*
        let action = null;
        let debounceTimeout = null;
        
        // First check for the shorthand chained format: data-lively-action:click or lively:onclick
        const shorthandAction = e.target.getAttribute(`lively:on${eventType}`);
        if (shorthandAction) {
            action = shorthandAction;
            // Check for debounce with colon syntax: lively:onchange:300
            const attrParts = e.target.getAttribute(`lively:on${eventType}:debounce`);
            if (attrParts) {
                debounceTimeout = parseInt(attrParts) || this.config.debounceTimeout;
            }
        }
        
        // Check for legacy format with potential chaining: data-lively-action:click
        if (!action) {
            const legacyAttr = e.target.getAttribute('data-lively-action');
            if (legacyAttr) {
                // Check if it has event type chained with colon
                const legacyParts = legacyAttr.split(':');
                if (legacyParts.length > 1) {
                    // Format is: action:eventType[:debounce]
                    const [actionMethod, legacyEventType, legacyDebounce] = legacyParts;
                    if (legacyEventType === eventType) {
                        action = actionMethod;
                        if (legacyDebounce) {
                            debounceTimeout = parseInt(legacyDebounce) || this.config.debounceTimeout;
                        }
                    }
                } 
                // Check for separate event type attribute
                else {
                    const targetEventType = e.target.getAttribute('data-lively-event') || 'click';
                    if (targetEventType === eventType) {
                        action = legacyAttr;
                        // Check for separate debounce attribute
                        const hasDebounce = e.target.hasAttribute('data-lively-debounce');
                        if (hasDebounce) {
                            debounceTimeout = parseInt(e.target.getAttribute('data-lively-debounce-timeout')) || 
                                             this.config.debounceTimeout;
                        }
                    }
                }
            }
        }
        
        // If no action was found for this event type, exit early
        if (!action) return;
        
        // For input/change events, look for value attribute in both formats
        let valueAttr = null;
        if (eventType === 'input' || eventType === 'change') {
            valueAttr = e.target.getAttribute('data-lively-value-attr') || 
                        e.target.getAttribute('lively:value-attr') || 
                        'value';
        }
        
        // Create arguments for the action
        const args = {};
        if (valueAttr && (eventType === 'input' || eventType === 'change')) {
            args[valueAttr] = e.target.value;
        }
        
        // Apply debounce if needed, otherwise update immediately
        if (debounceTimeout !== null) {
            const timerKey = `${componentId}-${action}`;
            
            // Clear any existing timer
            if (this.debounceTimers[timerKey]) {
                clearTimeout(this.debounceTimers[timerKey]);
            }
            
            // Set new timer
            this.debounceTimers[timerKey] = setTimeout(() => {
                this.updateComponent(componentId, action, args);
                delete this.debounceTimers[timerKey];
            }, debounceTimeout);
        } else {
            this.updateComponent(componentId, action, args);
        }
    }
    
    // Helper to get component ID from element
    getComponentId(element) {
        return element.getAttribute('lively:component') || 
               this.findParentComponentId(element);
    }
    
    // Find component ID by walking up the DOM tree
    findParentComponentId(element) {
        let current = element;
        
        while (current && current !== document.body) {
            const id = current.getAttribute('lively:component');
            
            if (id) return id;
            current = current.parentElement;
        }
        
        return null;
    }
    
    // Enable or disable debug logging
    setDebug(enabled) {
        this.config.debug = enabled;
        this.log(`Debug logging ${enabled ? 'enabled' : 'disabled'}`);
    }
    
    // Logging helper that respects debug configuration
    log(...args) {
        if (this.config.debug) {
            console.log(...args);
        }
    }
    
    // Error logging (always enabled regardless of debug setting)
    error(...args) {
        console.error(...args);
    }
    
    // Warning logging (always enabled regardless of debug setting)
    warn(...args) {
        console.warn(...args);
    }
    
    // Debug level logging (only when debug is enabled)
    debug(...args) {
        if (this.config.debug) {
            console.debug(...args);
        }
    }
    
    // Register a component
    registerComponent(id, state, className) {
        this.log(`Registering component: ${id}`, { state, class: className });
        this.components[id] = {
            id: id,
            state: state,
            class: className
        };
    }
    
    // Helper function to try different namespaces for a component
    tryNamespaces(baseName) {
        // If the name already contains a namespace, return it as is
        if (baseName.includes('\\')) {
            return [baseName];
        }

        // Start with the base namespace
        const baseNamespace = 'Lively\\Resources\\Components\\';
        
        // Convert kebab-case to PascalCase
        const pascalCase = baseName.split('-')
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join('');
        
        // If the name contains a path (like 'Layouts/Header'), split and construct namespace
        if (baseName.includes('/')) {
            const parts = baseName.split('/');
            const componentName = parts.pop(); // Get the last part as component name
            const namespacePath = parts.join('\\'); // Convert path separators to namespace separators
            return [`${baseNamespace}${namespacePath}\\${componentName}`];
        }

        // Try both the original name and PascalCase version
        return [
            `${baseNamespace}${baseName}`,
            `${baseNamespace}${pascalCase}`
        ];
    }
    
    // Scan the DOM for component placeholders
    scanForComponents() {
        const registeredComponents = new Set();
        
        // Query for elements with lively:component attribute
        document.querySelectorAll('[lively\\:component]').forEach(el => {
            const id = el.getAttribute('lively:component');
            
            // Look for state in script tag at the bottom of body
            let stateJson = null;
            let className = null;
            
            // Try to find state in script tag at the bottom of body
            const stateScript = document.querySelector(`body > script[id="${id}"][type="application/json"]`);
            if (stateScript) {
                try {
                    const stateData = JSON.parse(stateScript.textContent);
                    stateJson = JSON.stringify(stateData.value);
                    className = stateData['json-class'];
                } catch (e) {
                    this.error(`Error parsing component state from script tag: ${id}`, e);
                }
            }
            
            // Fallback to attributes if script tag not found
            if (!stateJson) {
                stateJson = el.getAttribute('lively:state');
                className = this.getClassNameFromElement(el);
            }
            
            // If no class name found, try to infer it from the component ID
            if (!className && id && id.includes('-')) {
                // Extract component type from ID (e.g., "counter" from "counter-2f910b64")
                const componentType = id.split('-')[0];
                // Convert to PascalCase
                const pascalCase = componentType.charAt(0).toUpperCase() + componentType.slice(1);
                
                // Use the new helper function to get namespaces
                const potentialClasses = this.tryNamespaces(pascalCase);
                className = potentialClasses[0]; // Default to first one for now, server can correct later
                
                this.log(`Inferred class name from component ID: ${id} -> ${className}`);
            }
            
            this.log(`Found component in DOM: ${id}`, { 
                stateAttr: stateJson ? stateJson.substring(0, 30) + '...' : null,
                className: className
            });
            
            // Skip components that don't have a valid ID
            if (!id) return;
            
            registeredComponents.add(id);
            
            // If this component is already in our registry, only update if needed
            if (this.components[id]) {
                // If we had no class name but DOM has one, update it
                if (!this.components[id].class && className) {
                    this.log(`Updating component class from DOM: ${id} -> ${className}`);
                    this.components[id].class = className;
                }
                
                // No need to re-parse the state, we already have it
                return;
            }
            
            // Otherwise register this component as new
            try {
                // Default to empty state if none provided
                const state = stateJson ? JSON.parse(stateJson) : {};
                
                // Check if the class name is valid
                if (!className) {
                    this.warn(`Component ${id} has no class name, this may cause issues`);
                }
                
                // Register the component
                this.registerComponent(id, state, className);
                
            } catch (e) {
                this.error(`Error registering component ${id}:`, e);
            }
        });
        
        // Log any components that were in our registry but not found in the DOM
        Object.keys(this.components).forEach(id => {
            if (!registeredComponents.has(id)) {
                this.debug(`Component in registry but not in DOM: ${id}`);
            }
        });
    }
    
    // Get the class name from DOM element
    getClassNameFromElement(el) {
        // Try JSON-encoded class attribute first (most reliable)
        const jsonClass = el.getAttribute('lively:json-class');
        if (jsonClass) {
            try {
                // This will correctly parse the JSON string with all escape sequences
                const className = JSON.parse(jsonClass);
                this.log(`Parsed JSON class name: ${jsonClass} -> ${className}`);
                return className;
            } catch (e) {
                this.error('Error parsing JSON class name:', e);
            }
        }
        
        // Try to find state in script tag at the bottom of body
        const componentId = el.getAttribute('lively:component');
        if (componentId) {
            const stateScript = document.querySelector(`body > script[id="${componentId}"][type="application/json"]`);
            if (stateScript) {
                try {
                    const stateData = JSON.parse(stateScript.textContent);
                    if (stateData['json-class']) {
                        this.log(`Found class name in state script: ${stateData['json-class']}`);
                        return stateData['json-class'];
                    }
                } catch (e) {
                    this.error('Error parsing state script:', e);
                }
            }
        }
        
        // Fall back to the regular class attribute
        const rawClassName = el.getAttribute('lively:class');
        if (rawClassName) {
            try {
                // Try to decode backslashes
                const decoded = rawClassName.replace(/\\\\/g, '\\');
                this.log(`Decoded class name: ${rawClassName} -> ${decoded}`);
                return decoded;
            } catch (e) {
                this.error('Error decoding class name:', e);
                return rawClassName; // Return as-is if there's an error
            }
        }
        
        // Try to infer from component ID
        if (componentId && componentId.includes('-')) {
            // Extract component type from ID (e.g., "my-component" from "my-component-f3515237")
            const componentType = componentId.split('-').slice(0, -1).join('-'); // Get all parts except the hash
            
            // Check if the component is in a nested folder by looking at the element's class
            const elementClass = el.className;
            if (elementClass) {
                // Try to extract folder structure from class name
                const classParts = elementClass.split(' ');
                for (const part of classParts) {
                    if (part.includes('/')) {
                        // Found a path-like class, use it to construct the namespace
                        const pathParts = part.split('/');
                        const componentName = pathParts.pop(); // Get the last part
                        const namespacePath = pathParts.join('\\');
                        const fullClassName = `Lively\\Resources\\Components\\${namespacePath}\\${componentName}`;
                        this.log(`Inferred class name from element class: ${componentId} -> ${fullClassName}`);
                        return fullClassName;
                    }
                }
            }
            
            // If no folder structure found, try the default namespace
            const pascalCase = componentType.split('-')
                .map(part => part.charAt(0).toUpperCase() + part.slice(1))
                .join('');
            
            // Try different namespace patterns
            const potentialClasses = this.tryNamespaces(pascalCase);
            if (potentialClasses.length > 0) {
                this.log(`Inferred class name from component ID: ${componentId} -> ${potentialClasses[0]}`);
                return potentialClasses[0];
            }
        }
        
        return null;
    }
    
    // Handle events from component elements
    handleEvent(event, componentId, method, params = {}) {
        // Prevent default behavior for links or form elements
        event.preventDefault();
        
        this.log(`Handling event for component ${componentId}, method ${method}`, params);
        
        // Extract additional data from event target attributes if any
        if (event.target && event.target.hasAttribute('data-lively-params')) {
            try {
                const attributeParams = JSON.parse(event.target.getAttribute('data-lively-params'));
                params = { ...params, ...attributeParams };
            } catch (e) {
                this.error('Error parsing data-lively-params attribute:', e);
            }
        }
        
        // Handle form data if the event is from a form
        if (event.target && event.target.tagName === 'FORM') {
            const formData = new FormData(event.target);
            const formParams = {};
            
            for (const [key, value] of formData.entries()) {
                formParams[key] = value;
            }
            
            params = { ...params, ...formParams };
        }
        
        // Call the component's method on the server
        this.updateComponent(componentId, method, params);
        
        return false; // Prevent default and stop propagation
    }
    
    // Update a component by calling a method on the server
    updateComponent(componentId, method, args = {}) {
        this.log(`Updating component: ${method} on ${componentId}`);
        
        // Find the component element in the DOM
        const el = document.querySelector(`[lively\\:component="${componentId}"]`);
        
        if (!el) {
            this.error(`Component element not found in DOM: ${componentId}`);
            return;
        }
        
        // If component isn't in our registry, add it
        if (!this.components[componentId]) {
            const stateJson = el.getAttribute('lively:state');
            const className = this.getClassNameFromElement(el);
            
            this.log(`Component not in registry, reading from DOM: ${componentId}`, {
                className
            });
            
            // Try to infer class name from component ID if not provided
            let inferredClassName = className;
            if (!inferredClassName && componentId.includes('-')) {
                // Extract component type from ID (e.g., "counter" from "counter-2f910b64")
                const componentType = componentId.split('-')[0];
                // Convert to PascalCase and get potential namespaces
                const pascalCase = componentType.split('-')
                    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
                    .join('');
                const potentialClasses = this.tryNamespaces(pascalCase);
                inferredClassName = potentialClasses[0]; // Default to first one, server will resolve
                this.log(`Inferred class name from component ID: ${componentId} -> ${inferredClassName}`);
            }
            
            if (stateJson) {
                try {
                    const state = JSON.parse(stateJson);
                    this.registerComponent(componentId, state, inferredClassName);
                } catch (e) {
                    this.error('Error parsing component state:', e);
                    return;
                }
            } else {
                this.registerComponent(componentId, {}, inferredClassName);
            }
        } else if (!this.components[componentId].class) {
            // If component exists but has no class info, try to get it from the DOM
            let className = this.getClassNameFromElement(el);
            
            // Try to infer class name from component ID if still not found
            if (!className && componentId.includes('-')) {
                // Extract component type from ID (e.g., "counter" from "counter-2f910b64")
                const componentType = componentId.split('-')[0];
                // Convert to PascalCase and get potential namespaces
                const pascalCase = componentType.split('-')
                    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
                    .join('');
                const potentialClasses = this.tryNamespaces(pascalCase);
                className = potentialClasses[0]; // Default to first one, server will resolve
                this.log(`Inferred class name from component ID: ${componentId} -> ${className}`);
            }
            
            if (className) {
                this.components[componentId].class = className;
                this.log(`Updated missing class info from DOM: ${componentId}`, {
                    class: this.components[componentId].class
                });
            }
        }
        
        // Show loading state on the component
        this.setComponentLoading(componentId, true);
        
        // Make a copy of the component data to avoid modifying the original
        const componentData = JSON.parse(JSON.stringify(this.components[componentId]));
        
        // Always use the class name from the DOM if available, as it's the most accurate
        const domClassName = this.getClassNameFromElement(el);
        if (domClassName) {
            componentData.class = domClassName;
            this.log(`Using class name from DOM: ${domClassName}`);
        }
        
        // Log what we're sending
        this.log(`Sending component data:`, componentData);
        
        // Create the request
        const data = {
            component_id: componentId,
            method: method,
            args: args,
            state: componentData
        };
        
        // Add CSRF token if available
        if (this.csrfToken) {
            data.csrf_token = this.csrfToken;
        } else {
            // Try to find the token again in case it was added after initialization
            this.csrfToken = this.findCsrfToken();
            if (this.csrfToken) {
                data.csrf_token = this.csrfToken;
            } else {
                this.warn('CSRF token not found. This may cause security token validation errors.');
            }
        }
        
        // Send AJAX request to the server
        fetch('/?lively-action=update-component', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.csrfToken || ''
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            // Check if the response is valid JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error(`Invalid response content-type: ${contentType}`);
            }
            return response.json();
        })
        .then(result => {
            // Remove loading state
            this.setComponentLoading(componentId, false);
            
            if (result.success) {
                // Update the component in the DOM
                this.updateComponentInDom(result.component);
                
                // Update the component state and class in our registry
                this.components[componentId].state = result.component.state;
                if (result.component.class) {
                    this.components[componentId].class = result.component.class;
                    this.log(`Updated component class in registry: ${componentId} -> ${result.component.class}`);
                }
            } else {
                this.error('Error updating component:', result.error);
                this.showComponentError(componentId, result.error);
                
                // Log additional debug information if available
                if (result.debug_info) {
                    this.debug('Component debug info:', result.debug_info);
                }
            }
        })
        .catch(error => {
            // Remove loading state
            this.setComponentLoading(componentId, false);
            
            this.error('Error updating component:', error);
            this.showComponentError(componentId, 'Server error. Check the console for details.');
        });
    }
    
    // Set component loading state
    setComponentLoading(componentId, isLoading) {
        const el = document.querySelector(`[lively\\:component="${componentId}"]`);
        
        if (el) {
            if (isLoading) {
                el.classList.add('lively-loading');
                // Optional: Add a loading spinner overlay
                // el.innerHTML += '<div class="lively-loading-spinner"></div>';
            } else {
                el.classList.remove('lively-loading');
                // Optional: Remove loading spinner
                // const spinner = el.querySelector('.lively-loading-spinner');
                // if (spinner) spinner.remove();
            }
        }
    }
    
    // Show component error
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
            
            
            // Auto-hide after 5 seconds
            /*
            setTimeout(() => {
                el.classList.remove('lively-error');
                if (errorEl.parentNode) {
                    errorEl.parentNode.removeChild(errorEl);
                }
            }, 5000);
            */
        } else {
            this.error(`Error displaying component error: Element with ID ${componentId} not found in DOM`);
        }
    }
    
    // Update a component in the DOM
    updateComponentInDom(component) {
        const el = document.querySelector(`[lively\\:component="${component.id}"]`);
        
        if (el) {
            // Save existing class information if it's going to be lost
            let existingClassName = null;
            if (!component.class) {
                // If server didn't provide a class name, save the existing one
                existingClassName = this.getClassNameFromElement(el);
                if (existingClassName) {
                    this.log(`Preserving existing class name: ${existingClassName}`);
                }
            }
            
            // Remember any existing lively attributes we need to preserve
            const attributes = {};
            Array.from(el.attributes).forEach(attr => {
                // Save lively:* attributes except for component/state/class
                if (attr.name.startsWith('lively:') && 
                    attr.name !== 'lively:component' && 
                    attr.name !== 'lively:state' &&
                    attr.name !== 'lively:class' &&
                    attr.name !== 'lively:json-class') {
                    attributes[attr.name] = attr.value;
                }
            });

            // Check if the HTML contains duplicate component attributes
            let html = component.html;
            
            // Create a temporary div to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // If the HTML contains elements with lively:component, we need to extract the inner content
            const innerComponent = tempDiv.querySelector(`[lively\\:component="${component.id}"]`);
            if (innerComponent) {
                // Use the inner HTML of the matching component
                html = innerComponent.innerHTML;
            }
            
            // Update the inner HTML
            el.innerHTML = html;
            
            // Update or create the state script tag at the bottom of body
            let stateScript = document.querySelector(`body > script[id="${component.id}"][type="application/json"]`);
            if (!stateScript) {
                stateScript = document.createElement('script');
                stateScript.id = component.id;
                stateScript.type = 'application/json';
                document.body.appendChild(stateScript);
            }
            
            // Update the state and class in the script tag
            stateScript.textContent = JSON.stringify({
                value: component.state,
                'json-class': component.class || existingClassName
            });
            
            // Restore any other data attributes
            Object.entries(attributes).forEach(([name, value]) => {
                el.setAttribute(name, value);
            });
            
            // Trigger a custom event that can be listened to
            el.dispatchEvent(new CustomEvent('lively:updated', {
                detail: { component: component }
            }));
        } else {
            this.error(`Error updating component in DOM: Element with ID ${component.id} not found`);
        }
    }
}

// Create the global lively instance
const lively = new Lively({
    debug: false
});

// Define updateState function globally
function updateState(componentId, method, args = {}) {
    lively.updateComponent(componentId, method, args);
}

// Initialize the framework when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    lively.init();
}); 