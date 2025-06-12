import { Lively } from './core.js';

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

// Export for module usage
export { Lively, lively, updateState }; 