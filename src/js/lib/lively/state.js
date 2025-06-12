import { Logger } from './utils.js';

export class StateManager {
    constructor(lively) {
        this.lively = lively;
        this.logger = new Logger(lively.config.debug);
    }
    
    init() {
        // Nothing to initialize for state manager
    }
    
    // Get component state
    getState(componentId) {
        return this.lively.components[componentId]?.state || {};
    }
    
    // Set component state
    setState(componentId, state) {
        if (this.lively.components[componentId]) {
            this.lively.components[componentId].state = state;
            this.logger.log(`Updated state for component ${componentId}:`, state);
        } else {
            this.logger.warn(`Attempted to set state for non-existent component: ${componentId}`);
        }
    }
    
    // Update component state
    updateState(componentId, stateUpdate) {
        if (this.lively.components[componentId]) {
            this.lively.components[componentId].state = {
                ...this.lively.components[componentId].state,
                ...stateUpdate
            };
            this.logger.log(`Updated state for component ${componentId}:`, stateUpdate);
        } else {
            this.logger.warn(`Attempted to update state for non-existent component: ${componentId}`);
        }
    }
} 