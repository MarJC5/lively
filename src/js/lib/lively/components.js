import { Logger } from './utils.js';

export class ComponentManager {
    constructor(lively) {
        this.lively = lively;
        this.logger = new Logger(lively.config.debug);
    }
    
    init() {
        this.scanForComponents();
    }
    
    // Register a component
    registerComponent(id, state, className) {
        this.logger.log(`Registering component: ${id}`, { state, class: className });
        this.lively.components[id] = {
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
                    this.logger.error(`Error parsing component state from script tag: ${id}`, e);
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
                
                this.logger.log(`Inferred class name from component ID: ${id} -> ${className}`);
            }
            
            this.logger.log(`Found component in DOM: ${id}`, { 
                stateAttr: stateJson ? stateJson.substring(0, 30) + '...' : null,
                className: className
            });
            
            // Skip components that don't have a valid ID
            if (!id) return;
            
            registeredComponents.add(id);
            
            // If this component is already in our registry, only update if needed
            if (this.lively.components[id]) {
                // If we had no class name but DOM has one, update it
                if (!this.lively.components[id].class && className) {
                    this.logger.log(`Updating component class from DOM: ${id} -> ${className}`);
                    this.lively.components[id].class = className;
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
                    this.logger.warn(`Component ${id} has no class name, this may cause issues`);
                }
                
                // Register the component
                this.registerComponent(id, state, className);
                
            } catch (e) {
                this.logger.error(`Error registering component ${id}:`, e);
            }
        });
        
        // Log any components that were in our registry but not found in the DOM
        Object.keys(this.lively.components).forEach(id => {
            if (!registeredComponents.has(id)) {
                this.logger.debug(`Component in registry but not in DOM: ${id}`);
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
                this.logger.log(`Parsed JSON class name: ${jsonClass} -> ${className}`);
                return className;
            } catch (e) {
                this.logger.error('Error parsing JSON class name:', e);
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
                        this.logger.log(`Found class name in state script: ${stateData['json-class']}`);
                        return stateData['json-class'];
                    }
                } catch (e) {
                    this.logger.error('Error parsing state script:', e);
                }
            }
        }
        
        // Fall back to the regular class attribute
        const rawClassName = el.getAttribute('lively:class');
        if (rawClassName) {
            try {
                // Try to decode backslashes
                const decoded = rawClassName.replace(/\\\\/g, '\\');
                this.logger.log(`Decoded class name: ${rawClassName} -> ${decoded}`);
                return decoded;
            } catch (e) {
                this.logger.error('Error decoding class name:', e);
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
                        this.logger.log(`Inferred class name from element class: ${componentId} -> ${fullClassName}`);
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
                this.logger.log(`Inferred class name from component ID: ${componentId} -> ${potentialClasses[0]}`);
                return potentialClasses[0];
            }
        }
        
        return null;
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
} 