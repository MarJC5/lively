import { Logger } from './utils.js';

export class EventHandler {
    constructor(lively) {
        this.lively = lively;
        this.logger = new Logger(lively.config.debug);
    }
    
    init() {
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
        
        // For click events, we need to check both the target and its parents
        let targetElement = e.target;
        let componentId = null;
        let action = null;
        let debounceTimeout = null;
        let actionParams = [];
        let isDeepClick = false;
        
        // For click events, walk up the DOM tree to find the first element with a click handler
        if (eventType === 'click') {
            while (targetElement && targetElement !== document.body) {
                componentId = this.lively.componentManager.getComponentId(targetElement);
                if (componentId) {
                    // Check for deep click handler first
                    const deepAction = targetElement.getAttribute(`lively:on${eventType}:deep`);
                    if (deepAction) {
                        // For deep clicks, allow clicks to propagate through children
                        const actionMatch = deepAction.match(/^(\w+)(?:\((.*)\))?$/);
                        if (actionMatch) {
                            action = actionMatch[1];
                            if (actionMatch[2]) {
                                actionParams = this.parseActionParams(actionMatch[2]);
                            }
                            isDeepClick = true;
                            break;
                        }
                    }
                    
                    // Check for regular click handler
                    const shorthandAction = targetElement.getAttribute(`lively:on${eventType}`);
                    if (shorthandAction) {
                        // For buttons, treat as deep by default unless explicitly specified as non-deep
                        const isButton = targetElement.tagName === 'BUTTON';
                        const isExplicitlyNonDeep = targetElement.hasAttribute(`lively:on${eventType}:shallow`);
                        
                        // Only check for direct click if it's not a button or explicitly marked as shallow
                        if (!isButton || isExplicitlyNonDeep) {
                            if (e.target === targetElement) {
                                const actionMatch = shorthandAction.match(/^(\w+)(?:\((.*)\))?$/);
                                if (actionMatch) {
                                    action = actionMatch[1];
                                    if (actionMatch[2]) {
                                        actionParams = this.parseActionParams(actionMatch[2]);
                                    }
                                    // Check for debounce with colon syntax: lively:onchange:300
                                    const attrParts = targetElement.getAttribute(`lively:on${eventType}:debounce`);
                                    if (attrParts) {
                                        debounceTimeout = parseInt(attrParts) || this.lively.config.debounceTimeout;
                                    }
                                    break; // Found a click handler, stop searching
                                }
                            }
                        } else {
                            // For buttons, treat as deep by default
                            const actionMatch = shorthandAction.match(/^(\w+)(?:\((.*)\))?$/);
                            if (actionMatch) {
                                action = actionMatch[1];
                                if (actionMatch[2]) {
                                    actionParams = this.parseActionParams(actionMatch[2]);
                                }
                                // Check for debounce with colon syntax: lively:onchange:300
                                const attrParts = targetElement.getAttribute(`lively:on${eventType}:debounce`);
                                if (attrParts) {
                                    debounceTimeout = parseInt(attrParts) || this.lively.config.debounceTimeout;
                                }
                                break; // Found a click handler, stop searching
                            }
                        }
                    }
                }
                targetElement = targetElement.parentElement;
            }
        } else {
            // For non-click events, use the original target element
            componentId = this.lively.componentManager.getComponentId(targetElement);
            if (componentId) {
                // Check both formats: data-lively-* and lively:*
                const shorthandAction = targetElement.getAttribute(`lively:on${eventType}`);
                if (shorthandAction) {
                    const actionMatch = shorthandAction.match(/^(\w+)(?:\((.*)\))?$/);
                    if (actionMatch) {
                        action = actionMatch[1];
                        if (actionMatch[2]) {
                            actionParams = this.parseActionParams(actionMatch[2]);
                        }
                        // Check for debounce with colon syntax: lively:onchange:300
                        const attrParts = targetElement.getAttribute(`lively:on${eventType}:debounce`);
                        if (attrParts) {
                            debounceTimeout = parseInt(attrParts) || this.lively.config.debounceTimeout;
                        }
                    }
                }
            }
        }
        
        if (!componentId) return;
        
        // Check for legacy format with potential chaining: data-lively-action:click
        if (!action) {
            const legacyAttr = targetElement.getAttribute('data-lively-action');
            if (legacyAttr) {
                // Check if it has event type chained with colon
                const legacyParts = legacyAttr.split(':');
                if (legacyParts.length > 1) {
                    // Format is: action:eventType[:debounce]
                    const [actionMethod, legacyEventType, legacyDebounce] = legacyParts;
                    if (legacyEventType === eventType) {
                        action = actionMethod;
                        if (legacyDebounce) {
                            debounceTimeout = parseInt(legacyDebounce) || this.lively.config.debounceTimeout;
                        }
                    }
                } 
                // Check for separate event type attribute
                else {
                    const targetEventType = targetElement.getAttribute('data-lively-event') || 'click';
                    if (targetEventType === eventType) {
                        action = legacyAttr;
                        // Check for separate debounce attribute
                        const hasDebounce = targetElement.hasAttribute('data-lively-debounce');
                        if (hasDebounce) {
                            debounceTimeout = parseInt(targetElement.getAttribute('data-lively-debounce-timeout')) || 
                                             this.lively.config.debounceTimeout;
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
            valueAttr = targetElement.getAttribute('data-lively-value-attr') || 
                        targetElement.getAttribute('lively:value-attr') || 
                        'value';
        }
        
        // Create arguments for the action
        const args = {};
        if (valueAttr && (eventType === 'input' || eventType === 'change')) {
            args[valueAttr] = targetElement.value;
        }
        
        // Add parsed parameters to args
        if (actionParams.length > 0) {
            args['params'] = actionParams;
        }
        
        // Apply debounce if needed, otherwise update immediately
        if (debounceTimeout !== null) {
            const timerKey = `${componentId}-${action}`;
            
            // Clear any existing timer
            if (this.lively.debounceTimers[timerKey]) {
                clearTimeout(this.lively.debounceTimers[timerKey]);
            }
            
            // Set new timer
            this.lively.debounceTimers[timerKey] = setTimeout(() => {
                this.lively.updateComponent(componentId, action, args);
                delete this.lively.debounceTimers[timerKey];
            }, debounceTimeout);
        } else {
            this.lively.updateComponent(componentId, action, args);
        }
    }
    
    // Helper method to parse action parameters
    parseActionParams(paramsString) {
        return paramsString.split(',').map(param => {
            param = param.trim();
            // If it's a string (starts and ends with quotes)
            if ((param.startsWith("'") && param.endsWith("'")) || 
                (param.startsWith('"') && param.endsWith('"'))) {
                return param.slice(1, -1); // Remove quotes
            }
            // Try to parse as number
            const num = Number(param);
            if (!isNaN(num)) return num;
            // Try to parse as boolean
            if (param === 'true') return true;
            if (param === 'false') return false;
            // Return as is if none of the above
            return param;
        });
    }
    
    // Handle events from component elements
    handleEvent(event, componentId, method, params = {}) {
        // Prevent default behavior for links or form elements
        event.preventDefault();
        
        this.logger.log(`Handling event for component ${componentId}, method ${method}`, params);
        
        // Extract additional data from event target attributes if any
        if (event.target && event.target.hasAttribute('data-lively-params')) {
            try {
                const attributeParams = JSON.parse(event.target.getAttribute('data-lively-params'));
                params = { ...params, ...attributeParams };
            } catch (e) {
                this.logger.error('Error parsing data-lively-params attribute:', e);
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
        this.lively.updateComponent(componentId, method, params);
        
        return false; // Prevent default and stop propagation
    }
} 