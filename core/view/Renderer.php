<?php

namespace Lively\Core\View;

use Lively\Core\Utils\Logger;
use Lively\Core\Utils\InputFilter;

class Renderer {
    protected static $instance;
    protected $components = [];
    protected $componentFactory;
    protected $componentLastAccess = []; // Track when components were last accessed
    protected $componentPooling = []; // For component pooling/reuse
    protected $componentPoolLastUsed = []; // Track when components in the pool were last used
    protected $maxPoolSizePerType = 10; // Default maximum pool size per component type
    protected $maxTotalPoolSize = 100; // Default maximum total pool size across all types
    protected $componentPoolMaxAge = 1800; // Default maximum age for pooled components (30 minutes)
    protected $componentUsageCount = []; // Track usage frequency
    protected $componentTier = []; // Track component tier (hot, warm, cold)
    protected $tierThresholds = [
        'hot' => 10,   // Used more than 10 times
        'warm' => 3    // Used more than 3 times (but less than 10)
        // Anything else is 'cold'
    ];
    protected $tierPoolSizes = [
        'hot' => 15,   // Keep more hot components in pool
        'warm' => 8,   // Keep medium number of warm components
        'cold' => 3    // Keep fewer cold components
    ];
    protected $componentStates = []; // Store component states for later output
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - initialize component factory
     */
    public function __construct() {
        $this->componentFactory = new ComponentFactory();
    }
    
    /**
     * Render a component
     */
    public function render($component, $props = []) {
        // If component is a string, instantiate it
        if (is_string($component)) {
            $component = $this->componentFactory->create($component, $props);
            if (!$component) {
                Logger::error("Failed to create component", [
                    'class' => $component,
                    'props' => $props
                ]);
                return '';
            }
        } elseif (is_callable($component)) {
            $component = $component($props);
        }
        
        // Get component ID
        $id = $component->getId();
        
        // Register the component BEFORE executing lifecycle hooks
        $this->registerComponent($component);
        
        // Execute lifecycle hooks
        $component->beforeMount();
        
        // Render the component and all its children
        $html = $component->render();
        
        // Also render and register any child components that were added
        // during the render process using addChild() or include()
        foreach ($component->getAllChildren() as $slot => $children) {
            foreach ($children as $child) {
                // Register each child component
                $this->registerComponent($child);
            }
        }
        
        // Wrap the component HTML with component metadata
        $wrappedHtml = $this->wrapComponentHtml($component, $html);
        
        // Execute mounted hook
        $component->mounted();
        
        Logger::info("Component rendered and registered", [
            'id' => $id,
            'class' => get_class($component)
        ]);
        
        return $wrappedHtml;
    }
    
    /**
     * Register a component (in-memory only, not in session)
     */
    public function registerComponent($component) {
        $id = $component->getId();
        $this->components[$id] = $component;
        $this->componentLastAccess[$id] = time(); // Track last access time

        Logger::info("Registered component: $id");
        
        return $this;
    }
    
    /**
     * Unregister a component from the renderer
     * 
     * @param string $id Component ID to unregister
     * @return $this
     */
    public function unregisterComponent($id) {
        if (isset($this->components[$id])) {
            unset($this->components[$id]);
            Logger::debug("Unregistered component: $id");
        }
        
        if (isset($this->componentLastAccess[$id])) {
            unset($this->componentLastAccess[$id]);
        }
        
        return $this;
    }
    
    /**
     * Get a registered component by ID
     */
    public function getComponent($id) {
        // Only check if component exists in the current request
        if (isset($this->components[$id])) {
            // Update last access time
            $this->componentLastAccess[$id] = time();
            
            // Increment usage count for tiered caching
            if (!isset($this->componentUsageCount[$id])) {
                $this->componentUsageCount[$id] = 0;
            }
            $this->componentUsageCount[$id]++;
            
            // Update component tier based on usage count
            $this->updateComponentTier($id);
            
            Logger::info("Component found: $id");
            return $this->components[$id];
        }
        
        return null;
    }
    
    /**
     * Get all registered components
     * 
     * @return array All currently active components
     */
    public function getAllComponents() {
        return $this->components;
    }
    
    /**
     * Create a component from client-side state
     */
    public function createComponentFromClientState($componentId, $className, $state = [], $props = []) {
        return $this->componentFactory->createFromClientState($componentId, $className, $state, $props);
    }
    
    /**
     * Wrap component HTML with metadata
     */
    protected function wrapComponentHtml($component, $html) {
        $id = $component->getId();
        $class = $component->getFullComponentClass();
        $state = $component->getState();
        $props = $component->getProps();
        
        // Check if the HTML already contains a component attribute
        if (strpos($html, 'lively:component') !== false) {
            // It already has lively:component attribute, just add the component ID
            $html = str_replace(
                'lively:component="' . $id . '"',
                'lively:component="' . $id . '"',
                $html
            );
        } else {
            // Wrap the component HTML with a div that includes component metadata
            $html = "<div class=\"lively-component\" 
                        id=\"{$id}\" 
                        data-component=\"{$class}\" 
                        lively:component=\"{$id}\">
                        {$html}
                    </div>";
        }
        
        // Store the state data for later output
        $this->componentStates[$id] = [
            'value' => $state,
            'json-class' => $class
        ];
        
        return $html;
    }
    
    /**
     * Generate all component state script tags
     */
    public function generateComponentStates() {
        if (empty($this->componentStates)) {
            return '';
        }
        
        $html = '<!-- Lively Component States START -->';
        
        foreach ($this->componentStates as $id => $state) {
            $html .= "<script id=\"{$id}\" type=\"application/json\">" . 
                     json_encode($state) . 
                     "</script>\n";
        }

        $html .= '<!-- Lively Component States END -->';

        return $html;
    }
    
    /**
     * Generate JavaScript to initialize components
     */
    public function generateComponentsScript() {
        $script = "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                lively.init();
            });
        </script>
        ";
        
        return $script;
    }
    
    /**
     * Returns a list of allowed component classes that can be instantiated
     * from client-side state
     * 
     * @return array List of allowed class names
     */
    public function getAllowedComponentClasses()
    {
        static $allowedClasses = null;
        
        if ($allowedClasses === null) {
            // Start with an empty list
            $allowedClasses = [];
            
            // Scan component directories for valid classes
            $componentDirs = [
                LIVELY_RESOURCES_DIR . '/components',
            ];
            
            foreach ($componentDirs as $dir) {
                if (is_dir($dir)) {
                    // Recursively scan for PHP files
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dir)
                    );
                    
                    foreach ($files as $file) {
                        if ($file->isFile() && $file->getExtension() === 'php') {
                            // Get relative path from components directory
                            $relativePath = str_replace($dir . '/', '', $file->getPathname());
                            // Convert path to namespace format
                            $namespacePath = str_replace('/', '\\', dirname($relativePath));
                            $className = basename($file->getPathname(), '.php');
                            
                            // Build full namespace
                            $namespace = 'Lively\\Resources\\Components\\';
                            if ($namespacePath !== '.') {
                                $namespace .= $namespacePath . '\\';
                            }
                            
                            $allowedClasses[] = $namespace . $className;
                        }
                    }
                }
            }
            
            // Add base component classes
            $allowedClasses[] = 'Lively\\Core\\View\\Component';
            
            // Add any explicitly allowed component classes here
            $explicitlyAllowed = [
                // Add your trusted component classes here
            ];
            
            $allowedClasses = array_merge($allowedClasses, $explicitlyAllowed);
            $allowedClasses = array_unique($allowedClasses);
            
            Logger::debug("Allowed component classes: " . implode(", ", $allowedClasses));
        }
        
        return $allowedClasses;
    }
    
    /**
     * Infer class name from component ID
     * Useful when class name is not provided but we have a component ID
     * 
     * @param string $componentId The component ID
     * @return string|null The inferred class name or null if could not infer
     */
    private function inferClassNameFromComponentId($componentId) {
        if (preg_match('/^([a-z0-9_]+)-[a-f0-9]+$/', $componentId, $matches)) {
            $componentType = $matches[1];
            $pascalCase = ucfirst($componentType);
            
            $appClass = "Lively\\Resources\\Components\\$pascalCase";
            
            Logger::debug("Inferred class name from component ID: $appClass");
            
            if (class_exists($appClass)) {
                return $appClass;
            }
        }
        
        return null;
    }
    
    /**
     * Normalize component class name to ensure it's properly formatted
     * 
     * @param string $className The original class name
     * @param string $componentId The component ID for fallback inference
     * @return string|null Normalized class name or null if can't be normalized
     */
    private function normalizeComponentClassName($className, $componentId) {
        // Clean up the class name - remove quotes or escaped quotes that might be present
        $className = trim($className, '"\'');
        $className = str_replace('\"', '', $className);
        
        // If empty after cleanup, try to extract from component ID
        if (empty($className)) {
            Logger::debug("Trying to extract class name from component ID: $componentId");
            // Try to extract component type from ID
            if (preg_match('/^([a-z0-9_]+)-[a-f0-9]+$/', $componentId, $matches)) {
                $componentType = $matches[1];
                $pascalCase = ucfirst($componentType);
                
                $appClass = "Lively\\Resources\\Components\\$pascalCase";
                
                Logger::debug("Extracted potential class from component ID: $appClass");
                
                if (class_exists($appClass)) {
                    return $appClass;
                }
            }
            return null;
        }
        
        // If the class already exists, it's valid
        if (class_exists($className, false)) {
            return $className;
        }
        
        // If it's a short name without namespace, try to add namespace
        if (strpos($className, '\\') === false) {
            $appComponentClass = 'Lively\\Resources\\Components\\' . $className;
            
            if (class_exists($appComponentClass)) {
                return $appComponentClass;
            }
        }
        
        // Try to add escape backslashes if needed
        $escapedClassName = str_replace('\\', '\\\\', $className);
        if (class_exists($escapedClassName, false)) {
            return $escapedClassName;
        }
        
        // Try to remove escape backslashes if too many
        $unescapedClassName = str_replace('\\\\', '\\', $className);
        if (class_exists($unescapedClassName, false)) {
            return $unescapedClassName;
        }
        
        // If we've reached here, we couldn't normalize the class name
        return null;
    }
    
    /**
     * Handle a component update request
     */
    public function handleComponentUpdate($componentId, $method, $args = [], $clientState = []) {
        $component = $this->getComponent($componentId);
        
        // Sanitize all inputs to prevent XSS
        $componentId = htmlspecialchars($componentId, ENT_QUOTES, 'UTF-8');
        $method = InputFilter::sanitizeString($method);
        
        // Validate method name - alphanumeric and underscore only
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $method)) {
            Logger::warn('Invalid method name in component update request', [
                'method' => $method,
                'component_id' => $componentId
            ]);
            return [
                'success' => false,
                'error' => 'Invalid method name format'
            ];
        }
        
        // Check for dangerous method names
        $dangerousMethods = ['__destruct', '__wakeup', '__sleep', '__clone', 'eval', 'exec', 'shell_exec', 'system'];
        if (in_array(strtolower($method), $dangerousMethods) || strpos($method, '__') === 0) {
            Logger::warn('Attempted to call potentially dangerous method', [
                'method' => $method,
                'component_id' => $componentId
            ]);
            return [
                'success' => false,
                'error' => 'Unauthorized method call'
            ];
        }
        
        // Sanitize arguments recursively
        $args = is_array($args) ? InputFilter::sanitizeArray($args) : [];
        
        // If component not in memory, try to create from client state with class validation
        if (!$component && isset($clientState['class'])) {
            Logger::debug("Creating component from client state", [
                "component_id" => $componentId,
                "class" => $clientState['class']
            ]);
            
            // Validate class name format before attempting to create
            $class = $clientState['class'];
            if (!preg_match('/^[a-zA-Z0-9_\\\\]+$/', $class)) {
                Logger::warn('Invalid class name format in component update request', [
                    'class' => $class,
                    'component_id' => $componentId
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid class name format'
                ];
            }
            
            // Create component from client state with state sanitization
            $sanitizedState = is_array($clientState['state'] ?? []) ? 
                InputFilter::sanitizeArray($clientState['state'] ?? []) : [];
            
            // Preserve props from client state if available
            $props = is_array($clientState['props'] ?? []) ? 
                InputFilter::sanitizeArray($clientState['props'] ?? []) : [];
            
            $component = $this->createComponentFromClientState(
                $componentId, 
                $class, 
                $sanitizedState,
                $props
            );
        }
        
        if (!$component) {
            return [
                'success' => false,
                'error' => 'Component not found: ' . $componentId . ' (class: ' . ($clientState['class'] ?? 'unknown') . ')'
            ];
        }
        
        try {
            // Check if method exists
            if (!method_exists($component, $method)) {
                return [
                    'success' => false,
                    'error' => 'Method not found: ' . $method . ' on component ' . get_class($component)
                ];
            }
            
            // Ensure the method is public
            $reflectionMethod = new \ReflectionMethod($component, $method);
            if (!$reflectionMethod->isPublic()) {
                Logger::warn('Attempted to call non-public method', [
                    'method' => $method,
                    'class' => get_class($component),
                    'component_id' => $componentId
                ]);
                return [
                    'success' => false,
                    'error' => 'Method not accessible'
                ];
            }
            
            // Call the method
            $result = call_user_func_array([$component, $method], $args);
            
            // Re-render the component
            $html = $component->render();
            
            // Return the updated component data
            return [
                'success' => true,
                'component' => [
                    'id' => $componentId,
                    'html' => $html,
                    'state' => $component->getState(),
                    'props' => $component->getProps(),
                    'class' => get_class($component)
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('Error in component update: ' . $e->getMessage(), [
                'method' => $method,
                'component_id' => $componentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up unused components
     * 
     * @param int $maxAge Maximum age in seconds before a component is considered stale
     * @return int Number of components removed
     */
    public function cleanupComponents($maxAge = 3600) {
        $count = 0;
        $currentTime = time();
        
        foreach ($this->componentLastAccess as $id => $lastAccess) {
            // If the component hasn't been accessed in $maxAge seconds
            if (($currentTime - $lastAccess) > $maxAge) {
                if (isset($this->components[$id])) {
                    // Call destroy method if it exists
                    if (method_exists($this->components[$id], 'destroy')) {
                        $this->components[$id]->destroy();
                    }
                    
                    // Remove the component
                    unset($this->components[$id]);
                    Logger::debug("Removed stale component: $id");
                    $count++;
                }
                
                // Remove from last access tracking
                unset($this->componentLastAccess[$id]);
            }
        }
        
        if ($count > 0) {
            Logger::info("Memory cleanup: removed $count stale components");
        }
        
        return $count;
    }
    
    /**
     * Get or create a component from pool
     * 
     * @param string $className The component class name
     * @param array $props Component props
     * @return Component The component instance (either new or from pool)
     */
    public function getPooledComponent($className, $props = []) {
        $poolKey = $className;
        $now = time();
        
        // Track pool sizes for logging
        $initialPoolSize = isset($this->componentPooling[$poolKey]) ? count($this->componentPooling[$poolKey]) : 0;
        
        // Check if we have any components of this type in the pool
        if (!empty($this->componentPooling[$poolKey])) {
            // First, clean up any stale pooled components
            $this->cleanupPooledComponents($poolKey);
            
            // If we still have components after cleanup, use one
            if (!empty($this->componentPooling[$poolKey])) {
                // Get a component from the pool
                $component = array_pop($this->componentPooling[$poolKey]);
                $componentId = $component->getId();
                
                // Restore usage metrics if they exist
                if (isset($this->componentUsageCount[$componentId])) {
                    // Increment usage count for the reused component
                    $this->componentUsageCount[$componentId]++;
                    $this->updateComponentTier($componentId);
                } else {
                    // Initialize usage metrics for new component
                    $this->componentUsageCount[$componentId] = 1;
                    $this->componentTier[$componentId] = 'cold';
                }
                
                // Reset the component with new props
                if (method_exists($component, 'reset')) {
                    $component->reset($props);
                }
                
                // Update pool stats
                $poolSizeAfter = count($this->componentPooling[$poolKey]);
                $tier = $this->componentTier[$componentId] ?? 'cold';
                
                Logger::debug("Reused component from pool", [
                    'class' => get_class($component),
                    'tier' => $tier,
                    'usage_count' => $this->componentUsageCount[$componentId],
                    'initial_pool_size' => $initialPoolSize,
                    'pool_size_after' => $poolSizeAfter
                ]);
                
                return $component;
            }
        }
        
        // No pooled component available, create a new one
        $component = $this->componentFactory->create($className, $props);
        
        // Initialize usage metrics for new component
        $componentId = $component->getId();
        $this->componentUsageCount[$componentId] = 1;
        $this->componentTier[$componentId] = 'cold';
        
        return $component;
    }
    
    /**
     * Return a component to the pool for reuse
     * 
     * @param Component $component The component to return to the pool
     * @param int|null $maxPoolSize Maximum number of components to keep in the pool per type
     * @return bool Whether the component was added to the pool
     */
    public function returnToPool($component, $maxPoolSize = null) {
        // Get component ID and class
        $id = $component->getId();
        $className = get_class($component);
        $poolKey = $className;
        $now = time();
        
        // Determine component tier
        $tier = $this->componentTier[$id] ?? 'cold';
        
        // Use tier-based pool size if no specific size provided
        if ($maxPoolSize === null) {
            $maxPoolSize = $this->tierPoolSizes[$tier] ?? $this->maxPoolSizePerType;
        }
        
        // Calculate total pool size across all types
        $totalPoolSize = 0;
        foreach ($this->componentPooling as $type => $components) {
            $totalPoolSize += count($components);
        }
        
        // Check if the total pool size is already at or above the maximum
        if ($totalPoolSize >= $this->maxTotalPoolSize) {
            // Pool is full across all types
            // For hot/warm components, try to make room by removing cold components first
            if ($tier === 'hot' || $tier === 'warm') {
                $this->cleanupTieredPooledComponents('cold');
                
                // If still not enough room for hot components, try to remove warm ones too
                if ($tier === 'hot' && $totalPoolSize >= $this->maxTotalPoolSize) {
                    $this->cleanupTieredPooledComponents('warm');
                }
                
                // Recalculate total pool size after cleanup
                $totalPoolSize = 0;
                foreach ($this->componentPooling as $type => $components) {
                    $totalPoolSize += count($components);
                }
            } else {
                // For cold components, just remove oldest components
                $this->cleanupOldestPooledComponents();
                
                // Recalculate total pool size after cleanup
                $totalPoolSize = 0;
                foreach ($this->componentPooling as $type => $components) {
                    $totalPoolSize += count($components);
                }
            }
            
            // If we're still at max capacity, destroy this component
            if ($totalPoolSize >= $this->maxTotalPoolSize) {
                // Pool is still full, destroy the component
                if (method_exists($component, 'destroy')) {
                    $component->destroy();
                }
                
                Logger::debug("Component not returned to pool (global pool limit reached)", [
                    'class' => $className,
                    'tier' => $tier,
                    'total_pool_size' => $totalPoolSize
                ]);
                
                return false;
            }
        }
        
        // Initialize pool for this component type if it doesn't exist
        if (!isset($this->componentPooling[$poolKey])) {
            $this->componentPooling[$poolKey] = [];
            $this->componentPoolLastUsed[$poolKey] = [];
        }
        
        // Only add to pool if we haven't reached the max size for this type
        if (count($this->componentPooling[$poolKey]) < $maxPoolSize) {
            // First clean up any stale components in this pool
            $this->cleanupPooledComponents($poolKey);
            
            // Check again after cleanup
            if (count($this->componentPooling[$poolKey]) < $maxPoolSize) {
                // Clear the component state
                if (method_exists($component, 'clearState')) {
                    $component->clearState();
                }
                
                // Add to pool and track last use time
                $this->componentPooling[$poolKey][] = $component;
                $this->componentPoolLastUsed[$poolKey][$id] = $now;
                
                Logger::debug("Component returned to pool", [
                    'class' => $className,
                    'tier' => $tier,
                    'pool_size' => count($this->componentPooling[$poolKey]),
                    'total_pool_size' => $totalPoolSize + 1
                ]);
                
                // Remove from active components but preserve usage metrics
                unset($this->components[$id]);
                unset($this->componentLastAccess[$id]);
                
                return true;
            }
        }
        
        // Pool is full for this type, destroy the component
        if (method_exists($component, 'destroy')) {
            $component->destroy();
        }
        
        Logger::debug("Component not returned to pool (type limit reached)", [
            'class' => $className,
            'tier' => $tier,
            'pool_size' => count($this->componentPooling[$poolKey] ?? [])
        ]);
        
        return false;
    }
    
    /**
     * Clean up stale components in a specific pool
     * 
     * @param string $poolKey The pool key to clean
     * @return int Number of components removed
     */
    protected function cleanupPooledComponents($poolKey) {
        $now = time();
        $count = 0;
        
        // Skip if pool doesn't exist
        if (!isset($this->componentPooling[$poolKey]) || empty($this->componentPooling[$poolKey])) {
            return 0;
        }
        
        // Loop through components in the pool
        foreach ($this->componentPooling[$poolKey] as $index => $component) {
            $componentId = $component->getId();
            
            // Check if component is too old
            if (!isset($this->componentPoolLastUsed[$poolKey][$componentId]) || 
                ($now - $this->componentPoolLastUsed[$poolKey][$componentId]) > $this->componentPoolMaxAge) {
                
                // Destroy the component
                if (method_exists($component, 'destroy')) {
                    $component->destroy();
                }
                
                // Remove from pool
                unset($this->componentPooling[$poolKey][$index]);
                if (isset($this->componentPoolLastUsed[$poolKey][$componentId])) {
                    unset($this->componentPoolLastUsed[$poolKey][$componentId]);
                }
                
                $count++;
            }
        }
        
        // Re-index arrays
        if ($count > 0) {
            $this->componentPooling[$poolKey] = array_values($this->componentPooling[$poolKey]);
            
            Logger::debug("Cleaned up stale components from pool", [
                'pool_key' => $poolKey,
                'removed' => $count,
                'remaining' => count($this->componentPooling[$poolKey])
            ]);
        }
        
        return $count;
    }
    
    /**
     * Clean up the oldest components from all pools to make room
     * 
     * @param int $count Number of components to remove
     * @return int Number of components actually removed
     */
    protected function cleanupOldestPooledComponents($count = 5) {
        // Build a list of all components with their last use time
        $allPooledComponents = [];
        
        foreach ($this->componentPooling as $poolKey => $components) {
            foreach ($components as $index => $component) {
                $componentId = $component->getId();
                $lastUsed = $this->componentPoolLastUsed[$poolKey][$componentId] ?? 0;
                
                $allPooledComponents[] = [
                    'pool_key' => $poolKey,
                    'index' => $index,
                    'component' => $component,
                    'component_id' => $componentId,
                    'last_used' => $lastUsed
                ];
            }
        }
        
        // Sort by last used time (oldest first)
        usort($allPooledComponents, function($a, $b) {
            return $a['last_used'] - $b['last_used'];
        });
        
        // Take the oldest ones up to $count
        $toRemove = array_slice($allPooledComponents, 0, $count);
        $removed = 0;
        
        // Remove them from their pools
        foreach ($toRemove as $item) {
            $poolKey = $item['pool_key'];
            $index = $item['index'];
            $component = $item['component'];
            $componentId = $item['component_id'];
            
            // Destroy the component
            if (method_exists($component, 'destroy')) {
                $component->destroy();
            }
            
            // Remove from pool
            if (isset($this->componentPooling[$poolKey][$index])) {
                unset($this->componentPooling[$poolKey][$index]);
                $removed++;
            }
            
            if (isset($this->componentPoolLastUsed[$poolKey][$componentId])) {
                unset($this->componentPoolLastUsed[$poolKey][$componentId]);
            }
        }
        
        // Re-index the arrays
        foreach ($this->componentPooling as $poolKey => $components) {
            if (empty($components)) {
                unset($this->componentPooling[$poolKey]);
                unset($this->componentPoolLastUsed[$poolKey]);
            } else {
                $this->componentPooling[$poolKey] = array_values($components);
            }
        }
        
        if ($removed > 0) {
            Logger::debug("Cleaned up oldest components from pools", [
                'removed' => $removed,
                'requested' => $count
            ]);
        }
        
        return $removed;
    }
    
    /**
     * Configure pool sizes and aging
     * 
     * @param int $maxPerType Maximum components per type
     * @param int $maxTotal Maximum total components across all types
     * @param int $maxAge Maximum age in seconds for pooled components
     * @return $this
     */
    public function configureComponentPooling($maxPerType = 10, $maxTotal = 100, $maxAge = 1800) {
        $this->maxPoolSizePerType = max(1, $maxPerType);
        $this->maxTotalPoolSize = max($this->maxPoolSizePerType, $maxTotal);
        $this->componentPoolMaxAge = max(60, $maxAge); // Minimum 1 minute
        
        return $this;
    }
    
    /**
     * Monitor memory usage and trigger cleanup if necessary
     * 
     * @param float $threshold Percentage of memory limit that triggers cleanup (0.8 = 80%)
     * @return bool Whether cleanup was performed
     */
    public function monitorMemory($threshold = 0.8) {
        $memoryLimit = $this->getMemoryLimitBytes();
        $currentUsage = memory_get_usage(true);
        $usageRatio = $currentUsage / $memoryLimit;
        
        if ($usageRatio > $threshold) {
            Logger::warn("Memory usage high ($usageRatio), triggering cleanup", [
                'current_usage' => $this->formatBytes($currentUsage),
                'memory_limit' => $this->formatBytes($memoryLimit),
                'usage_percent' => round($usageRatio * 100, 2) . '%'
            ]);
            
            // Perform cleanup
            $removed = $this->cleanupComponents(1800); // Use a shorter timeout when memory is high
            
            // Return whether cleanup was performed
            return $removed > 0;
        }
        
        return false;
    }
    
    /**
     * Get memory limit in bytes
     * 
     * @return int Memory limit in bytes
     */
    private function getMemoryLimitBytes() {
        $limit = ini_get('memory_limit');
        
        // Convert to bytes
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Update component tier based on usage pattern
     * 
     * @param string $id Component ID
     * @return string The component tier ('hot', 'warm', or 'cold')
     */
    protected function updateComponentTier($id) {
        $usageCount = $this->componentUsageCount[$id] ?? 0;
        
        if ($usageCount >= $this->tierThresholds['hot']) {
            $tier = 'hot';
        } elseif ($usageCount >= $this->tierThresholds['warm']) {
            $tier = 'warm';
        } else {
            $tier = 'cold';
        }
        
        // Only log if the tier has changed
        if (!isset($this->componentTier[$id]) || $this->componentTier[$id] !== $tier) {
            Logger::debug("Component $id moved to $tier tier (usage count: $usageCount)");
        }
        
        $this->componentTier[$id] = $tier;
        return $tier;
    }
    
    /**
     * Cleanup components of a specific tier from pools
     * 
     * @param string $targetTier Tier to clean up ('hot', 'warm', or 'cold')
     * @param int $count Maximum number of components to remove
     * @return int Number of components removed
     */
    protected function cleanupTieredPooledComponents($targetTier, $count = 5) {
        // Build a list of components of the target tier
        $tieredComponents = [];
        
        foreach ($this->componentPooling as $poolKey => $components) {
            foreach ($components as $index => $component) {
                $componentId = $component->getId();
                $tier = $this->componentTier[$componentId] ?? 'cold';
                $lastUsed = $this->componentPoolLastUsed[$poolKey][$componentId] ?? 0;
                
                if ($tier === $targetTier) {
                    $tieredComponents[] = [
                        'pool_key' => $poolKey,
                        'index' => $index,
                        'component' => $component,
                        'component_id' => $componentId,
                        'last_used' => $lastUsed
                    ];
                }
            }
        }
        
        // If no components of this tier found, return
        if (empty($tieredComponents)) {
            return 0;
        }
        
        // Sort by last used time (oldest first)
        usort($tieredComponents, function($a, $b) {
            return $a['last_used'] - $b['last_used'];
        });
        
        // Take the oldest ones up to $count
        $toRemove = array_slice($tieredComponents, 0, $count);
        $removed = 0;
        
        // Remove them from their pools
        foreach ($toRemove as $item) {
            $poolKey = $item['pool_key'];
            $index = $item['index'];
            $component = $item['component'];
            $componentId = $item['component_id'];
            
            // Destroy the component
            if (method_exists($component, 'destroy')) {
                $component->destroy();
            }
            
            // Remove from pool
            if (isset($this->componentPooling[$poolKey][$index])) {
                unset($this->componentPooling[$poolKey][$index]);
                $removed++;
            }
            
            if (isset($this->componentPoolLastUsed[$poolKey][$componentId])) {
                unset($this->componentPoolLastUsed[$poolKey][$componentId]);
            }
        }
        
        // Re-index the arrays
        foreach ($this->componentPooling as $poolKey => $components) {
            if (empty($components)) {
                unset($this->componentPooling[$poolKey]);
                unset($this->componentPoolLastUsed[$poolKey]);
            } else {
                $this->componentPooling[$poolKey] = array_values($components);
            }
        }
        
        if ($removed > 0) {
            Logger::debug("Cleaned up $targetTier tier components from pools", [
                'removed' => $removed,
                'requested' => $count
            ]);
        }
        
        return $removed;
    }
    
    /**
     * Configure tiered caching thresholds and pool sizes
     * 
     * @param array $thresholds Thresholds for tier classification
     * @param array $poolSizes Maximum pool sizes per tier
     * @return $this
     */
    public function configureTieredCaching($thresholds = null, $poolSizes = null) {
        if ($thresholds !== null) {
            $this->tierThresholds = array_merge($this->tierThresholds, $thresholds);
        }
        
        if ($poolSizes !== null) {
            $this->tierPoolSizes = array_merge($this->tierPoolSizes, $poolSizes);
        }
        
        return $this;
    }
}