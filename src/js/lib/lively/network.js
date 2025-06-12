import { Logger } from './utils.js';

export class NetworkManager {
    constructor(lively) {
        this.lively = lively;
        this.logger = new Logger(lively.config.debug);
    }
    
    init() {
        // Nothing to initialize for network manager
    }
    
    // Update a component by calling a method on the server
    updateComponent(componentId, method, args = {}) {
        this.logger.log(`Updating component: ${method} on ${componentId}`);
        
        // Find the component element in the DOM
        const el = document.querySelector(`[lively\\:component="${componentId}"]`);
        
        if (!el) {
            this.logger.error(`Component element not found in DOM: ${componentId}`);
            return;
        }
        
        // If component isn't in our registry, add it
        if (!this.lively.components[componentId]) {
            const stateJson = el.getAttribute('lively:state');
            const className = this.lively.componentManager.getClassNameFromElement(el);
            
            this.logger.log(`Component not in registry, reading from DOM: ${componentId}`, {
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
                const potentialClasses = this.lively.componentManager.tryNamespaces(pascalCase);
                inferredClassName = potentialClasses[0]; // Default to first one, server will resolve
                this.logger.log(`Inferred class name from component ID: ${componentId} -> ${inferredClassName}`);
            }
            
            if (stateJson) {
                try {
                    const state = JSON.parse(stateJson);
                    this.lively.componentManager.registerComponent(componentId, state, inferredClassName);
                } catch (e) {
                    this.logger.error('Error parsing component state:', e);
                    return;
                }
            } else {
                this.lively.componentManager.registerComponent(componentId, {}, inferredClassName);
            }
        } else if (!this.lively.components[componentId].class) {
            // If component exists but has no class info, try to get it from the DOM
            let className = this.lively.componentManager.getClassNameFromElement(el);
            
            // Try to infer class name from component ID if still not found
            if (!className && componentId.includes('-')) {
                // Extract component type from ID (e.g., "counter" from "counter-2f910b64")
                const componentType = componentId.split('-')[0];
                // Convert to PascalCase and get potential namespaces
                const pascalCase = componentType.split('-')
                    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
                    .join('');
                const potentialClasses = this.lively.componentManager.tryNamespaces(pascalCase);
                className = potentialClasses[0]; // Default to first one, server will resolve
                this.logger.log(`Inferred class name from component ID: ${componentId} -> ${className}`);
            }
            
            if (className) {
                this.lively.components[componentId].class = className;
                this.logger.log(`Updated missing class info from DOM: ${componentId}`, {
                    class: this.lively.components[componentId].class
                });
            }
        }
        
        // Show loading state on the component
        this.setComponentLoading(componentId, true);
        
        // Make a copy of the component data to avoid modifying the original
        const componentData = JSON.parse(JSON.stringify(this.lively.components[componentId]));
        
        // Always use the class name from the DOM if available, as it's the most accurate
        const domClassName = this.lively.componentManager.getClassNameFromElement(el);
        if (domClassName) {
            componentData.class = domClassName;
            this.logger.log(`Using class name from DOM: ${domClassName}`);
        }
        
        // Log what we're sending
        this.logger.log(`Sending component data:`, componentData);
        
        // Create the request
        const data = {
            component_id: componentId,
            method: method,
            args: args,
            state: componentData
        };
        
        // Add CSRF token if available
        if (this.lively.csrfToken) {
            data.csrf_token = this.lively.csrfToken;
        } else {
            // Try to find the token again in case it was added after initialization
            this.lively.csrfToken = this.lively.findCsrfToken();
            if (this.lively.csrfToken) {
                data.csrf_token = this.lively.csrfToken;
            } else {
                this.logger.warn('CSRF token not found. This may cause security token validation errors.');
            }
        }
        
        // Send AJAX request to the server
        fetch('/?lively-action=update-component', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.lively.csrfToken || ''
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
                this.lively.components[componentId].state = result.component.state;
                if (result.component.class) {
                    this.lively.components[componentId].class = result.component.class;
                    this.logger.log(`Updated component class in registry: ${componentId} -> ${result.component.class}`);
                }
            } else {
                this.logger.error('Error updating component:', result.error);
                this.showComponentError(componentId, result.error);
                
                // Log additional debug information if available
                if (result.debug_info) {
                    this.logger.debug('Component debug info:', result.debug_info);
                }
            }
        })
        .catch(error => {
            // Remove loading state
            this.setComponentLoading(componentId, false);
            
            this.logger.error('Error updating component:', error);
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
            this.logger.error(`Error displaying component error: Element with ID ${componentId} not found in DOM`);
        }
    }
    
    // Update a component in the DOM
    updateComponentInDom(component) {
        const el = document.querySelector(`[lively\\:component="${component.id}"]`);
        
        if (el) {
            // Check if the HTML is empty or just a comment
            let html = component.html;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // If the HTML is empty or only contains comments, remove the component
            if (!html.trim() || (tempDiv.childNodes.length === 1 && tempDiv.firstChild.nodeType === 8)) {
                el.remove();
                return;
            }
            
            // Save existing class information if it's going to be lost
            let existingClassName = null;
            if (!component.class) {
                // If server didn't provide a class name, save the existing one
                existingClassName = this.lively.componentManager.getClassNameFromElement(el);
                if (existingClassName) {
                    this.logger.log(`Preserving existing class name: ${existingClassName}`);
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

            // Save the currently focused element and its selection state
            const activeElement = document.activeElement;
            const isInputElement = activeElement && (activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'INPUT');
            let selectionStart = null;
            let selectionEnd = null;
            let activeElementId = null;
            
            if (isInputElement && el.contains(activeElement)) {
                selectionStart = activeElement.selectionStart;
                selectionEnd = activeElement.selectionEnd;
                activeElementId = activeElement.id;
            }

            // Check if the HTML contains duplicate component attributes
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

            // Restore focus and selection if it was an input element
            if (isInputElement) {
                const newInputElement = activeElementId ? 
                    el.querySelector(`#${activeElementId}`) : 
                    el.querySelector(activeElement.tagName.toLowerCase());
                
                if (newInputElement) {
                    newInputElement.focus();
                    if (selectionStart !== null && selectionEnd !== null) {
                        newInputElement.setSelectionRange(selectionStart, selectionEnd);
                    }
                }
            }
            
            // Trigger a custom event that can be listened to
            el.dispatchEvent(new CustomEvent('lively:updated', {
                detail: { component: component }
            }));
        } else {
            this.logger.error(`Error updating component in DOM: Element with ID ${component.id} not found`);
        }
    }
} 