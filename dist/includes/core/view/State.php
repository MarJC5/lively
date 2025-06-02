<?php

namespace Lively\Core\View;

use Lively\Core\Utils\Logger;

class State {
    protected static $instance;
    protected $globalState = [];
    protected $listeners = [];
    protected $dependencies = [];
    protected $namespaces = [];
    protected $batchNotificationMode = false;
    protected $pendingNotifications = [];
    protected $dependencyCache = [];
    protected $listenersDisabled = false;
    
    /**
     * Get singleton instance
     * 
     * @return State
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set a state value
     * 
     * @param string $key State key
     * @param mixed $value State value
     * @param string|null $namespace Optional namespace
     * @return $this
     */
    public function set($key, $value, $namespace = null) {
        // Get the actual storage key (with namespace if provided)
        $storageKey = $this->getNamespacedKey($key, $namespace);
        
        // Get old value for comparison
        $oldValue = $this->globalState[$storageKey] ?? null;
        
        // Skip if value hasn't changed (reduces unnecessary notifications)
        if ($oldValue === $value) {
            return $this;
        }
        
        // Update state
        $this->globalState[$storageKey] = $value;
        
        // Track in namespace if needed
        if ($namespace !== null) {
            if (!isset($this->namespaces[$namespace])) {
                $this->namespaces[$namespace] = [];
            }
            $this->namespaces[$namespace][$key] = $storageKey;
        }
        
        // Only notify if listeners aren't disabled
        if (!$this->listenersDisabled) {
            if ($this->batchNotificationMode) {
                // In batch mode, queue the notification
                $this->pendingNotifications[$storageKey] = [
                    'new' => $value,
                    'old' => $oldValue
                ];
            } else {
                // Otherwise notify immediately
                $this->notifyListeners($storageKey, $value, $oldValue);
                
                // Also notify dependent keys
                $this->notifyDependencies($storageKey);
            }
        }
        
        // Invalidate any cached values for dependent keys
        $this->invalidateDependencyCache($storageKey);
        
        return $this;
    }
    
    /**
     * Get a state value
     * 
     * @param string $key State key
     * @param mixed $default Default value if key not found
     * @param string|null $namespace Optional namespace
     * @return mixed
     */
    public function get($key, $default = null, $namespace = null) {
        $storageKey = $this->getNamespacedKey($key, $namespace);
        return $this->globalState[$storageKey] ?? $default;
    }
    
    /**
     * Get all state for a namespace
     * 
     * @param string $namespace Namespace
     * @return array
     */
    public function getNamespace($namespace) {
        if (!isset($this->namespaces[$namespace])) {
            return [];
        }
        
        $result = [];
        foreach ($this->namespaces[$namespace] as $key => $storageKey) {
            $result[$key] = $this->globalState[$storageKey] ?? null;
        }
        
        return $result;
    }
    
    /**
     * Set multiple state values at once
     * 
     * @param array $values State values
     * @param string|null $namespace Optional namespace
     * @return $this
     */
    public function setMultiple(array $values, $namespace = null) {
        // Batch notifications for better performance
        $this->startBatchNotifications();
        
        foreach ($values as $key => $value) {
            $this->set($key, $value, $namespace);
        }
        
        // End batching and send all notifications at once
        $this->endBatchNotifications();
        
        return $this;
    }
    
    /**
     * Listen for state changes
     * 
     * @param string $key State key
     * @param callable $callback Callback function
     * @param string|null $namespace Optional namespace
     * @return $this
     */
    public function listen($key, $callback, $namespace = null) {
        $storageKey = $this->getNamespacedKey($key, $namespace);
        
        if (!isset($this->listeners[$storageKey])) {
            $this->listeners[$storageKey] = [];
        }
        
        $this->listeners[$storageKey][] = $callback;
        return $this;
    }
    
    /**
     * Add a dependency between state keys
     * 
     * @param string $sourceKey Source key
     * @param string $dependentKey Dependent key
     * @param string|null $sourceNamespace Source namespace
     * @param string|null $dependentNamespace Dependent namespace
     * @return $this
     */
    public function addDependency($sourceKey, $dependentKey, $sourceNamespace = null, $dependentNamespace = null) {
        $sourceStorageKey = $this->getNamespacedKey($sourceKey, $sourceNamespace);
        $dependentStorageKey = $this->getNamespacedKey($dependentKey, $dependentNamespace);
        
        if (!isset($this->dependencies[$sourceStorageKey])) {
            $this->dependencies[$sourceStorageKey] = [];
        }
        
        if (!in_array($dependentStorageKey, $this->dependencies[$sourceStorageKey])) {
            $this->dependencies[$sourceStorageKey][] = $dependentStorageKey;
        }
        
        return $this;
    }
    
    /**
     * Clear state for a namespace
     * 
     * @param string $namespace Namespace
     * @return $this
     */
    public function clearNamespace($namespace) {
        if (!isset($this->namespaces[$namespace])) {
            return $this;
        }
        
        $changedKeys = [];
        $oldValues = [];
        
        // First pass: collect old values and remove from global state
        foreach ($this->namespaces[$namespace] as $key => $storageKey) {
            if (isset($this->globalState[$storageKey])) {
                $oldValues[$storageKey] = $this->globalState[$storageKey];
                unset($this->globalState[$storageKey]);
                $changedKeys[] = $storageKey;
            }
        }
        
        // Clear namespace tracking
        unset($this->namespaces[$namespace]);
        
        // Notify listeners
        foreach ($changedKeys as $storageKey) {
            $this->notifyListeners($storageKey, null, $oldValues[$storageKey]);
            $this->notifyDependencies($storageKey);
        }
        
        return $this;
    }
    
    /**
     * Notify listeners of a state change
     * 
     * @param string $key State key
     * @param mixed $newValue New value
     * @param mixed $oldValue Old value
     */
    protected function notifyListeners($key, $newValue, $oldValue) {
        if (!isset($this->listeners[$key])) {
            return;
        }
        
        foreach ($this->listeners[$key] as $callback) {
            try {
                call_user_func($callback, $newValue, $oldValue);
            } catch (\Exception $e) {
                Logger::error("Error in state listener for key $key", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
    
    /* Old notifyDependencies method removed - replaced with optimized version */
    
    /**
     * Get the storage key with namespace
     * 
     * @param string $key Key
     * @param string|null $namespace Namespace
     * @return string
     */
    protected function getNamespacedKey($key, $namespace = null) {
        if ($namespace === null) {
            return $key;
        }
        return $namespace . '.' . $key;
    }
    
    /**
     * Unregister all listeners associated with a component
     * Used to prevent memory leaks when components are destroyed
     * 
     * @param string $componentId The ID of the component to unregister
     * @return $this
     */
    public function unregisterComponentListeners($componentId) {
        // Go through all listeners and remove any associated with this component
        foreach ($this->listeners as $key => $listeners) {
            foreach ($listeners as $index => $callback) {
                // Check if this callback is associated with the component
                // If it's a closure or array with object, we need to check for the component
                if (is_array($callback) && isset($callback[0]) && is_object($callback[0])) {
                    // Check if the object has an ID method and if it matches
                    if (method_exists($callback[0], 'getId') && $callback[0]->getId() === $componentId) {
                        unset($this->listeners[$key][$index]);
                        Logger::debug("Unregistered component listener", [
                            'component_id' => $componentId,
                            'key' => $key
                        ]);
                    }
                }
            }
            
            // Clean up empty listener arrays
            if (empty($this->listeners[$key])) {
                unset($this->listeners[$key]);
            } else {
                // Re-index the array
                $this->listeners[$key] = array_values($this->listeners[$key]);
            }
        }
        
        // Also cleanup any dependencies
        foreach ($this->dependencies as $key => $dependentKeys) {
            // If any dependencies were watching for this component, clean them up
            // This is more difficult because we don't store component IDs with dependencies
            // For now, we'll just keep the dependencies intact as they don't cause memory leaks directly
        }
        
        return $this;
    }
    
    /**
     * Start batching notifications
     * This improves performance when setting multiple related values
     * 
     * @return $this
     */
    public function startBatchNotifications() {
        $this->batchNotificationMode = true;
        $this->pendingNotifications = [];
        return $this;
    }
    
    /**
     * End batching notifications and send all pending notifications
     * 
     * @return $this
     */
    public function endBatchNotifications() {
        // Exit batch mode
        $this->batchNotificationMode = false;
        
        // Process all pending notifications
        if (!empty($this->pendingNotifications)) {
            // First pass: notify all direct listeners
            foreach ($this->pendingNotifications as $key => $values) {
                $this->notifyListeners($key, $values['new'], $values['old']);
            }
            
            // Second pass: handle dependencies efficiently
            $notifiedDependencies = [];
            foreach ($this->pendingNotifications as $key => $values) {
                $this->notifyDependencies($key, $notifiedDependencies);
            }
            
            // Clear pending notifications
            $this->pendingNotifications = [];
        }
        
        return $this;
    }
    
    /**
     * Temporarily disable all listeners
     * Useful for bulk operations where notifications would be inefficient
     * 
     * @return $this
     */
    public function disableListeners() {
        $this->listenersDisabled = true;
        return $this;
    }
    
    /**
     * Re-enable listeners
     * 
     * @param bool $triggerUpdates Whether to trigger updates for changed values while disabled
     * @return $this
     */
    public function enableListeners($triggerUpdates = false) {
        $this->listenersDisabled = false;
        
        // Optionally trigger updates for all changed values
        if ($triggerUpdates && !empty($this->pendingNotifications)) {
            $this->endBatchNotifications();
        }
        
        return $this;
    }
    
    /**
     * Invalidate dependency cache for a given key
     * 
     * @param string $key The key whose dependencies should be invalidated
     */
    protected function invalidateDependencyCache($key) {
        // Clear direct cache entry
        unset($this->dependencyCache[$key]);
        
        // Also clear any entries that depend on this key
        foreach ($this->dependencyCache as $cacheKey => $deps) {
            if (in_array($key, $deps)) {
                unset($this->dependencyCache[$cacheKey]);
            }
        }
    }
    
    /**
     * Get all dependencies for a key recursively
     * Used for efficient dependency notification
     * 
     * @param string $key The source key
     * @param array $visited Already processed keys to prevent recursion
     * @return array All dependent keys
     */
    protected function getAllDependencies($key, array &$visited = []) {
        // Check if we have a cached result
        if (isset($this->dependencyCache[$key])) {
            return $this->dependencyCache[$key];
        }
        
        // Prevent circular dependency issues
        if (in_array($key, $visited)) {
            return [];
        }
        $visited[] = $key;
        
        $dependencies = $this->dependencies[$key] ?? [];
        $allDependencies = $dependencies;
        
        // Recursively gather nested dependencies
        foreach ($dependencies as $depKey) {
            $nestedDeps = $this->getAllDependencies($depKey, $visited);
            $allDependencies = array_merge($allDependencies, $nestedDeps);
        }
        
        // Remove duplicates
        $allDependencies = array_unique($allDependencies);
        
        // Cache the result for future lookups
        $this->dependencyCache[$key] = $allDependencies;
        
        return $allDependencies;
    }
    
    /**
     * Notify dependent keys of a state change
     * 
     * @param string $key State key
     * @param array $alreadyNotified Keys already notified (to prevent duplicates)
     */
    protected function notifyDependencies($key, array &$alreadyNotified = []) {
        // Get all dependent keys (direct and nested)
        $allDependencies = $this->getAllDependencies($key);
        
        foreach ($allDependencies as $dependentKey) {
            // Skip if already notified in this batch
            if (isset($alreadyNotified[$dependentKey])) {
                continue;
            }
            
            // Mark as notified
            $alreadyNotified[$dependentKey] = true;
            
            // Notify listeners of this dependency
            if (isset($this->globalState[$dependentKey])) {
                $value = $this->globalState[$dependentKey];
                $this->notifyListeners($dependentKey, $value, $value);
            }
        }
    }
}