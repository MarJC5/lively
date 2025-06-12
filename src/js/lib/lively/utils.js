export class Logger {
    constructor(debug = false) {
        this.debug = debug;
    }
    
    setDebug(enabled) {
        this.debug = enabled;
    }
    
    // Logging helper that respects debug configuration
    log(...args) {
        if (this.debug) {
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
        if (this.debug) {
            console.debug(...args);
        }
    }
} 