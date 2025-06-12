<?php

namespace Lively\Core\View;

use Lively\Core\Utils\Logger;
use Lively\Core\Utils\CSRF;
use Lively\Core\View\Traits\ChildrenTrait;
use Lively\Core\View\Traits\HydrationTrait;
use Lively\Core\View\Traits\LazyLoadingTrait;
use Lively\Core\View\Traits\LifecycleTrait;
use Lively\Core\View\Traits\PropsTrait;
use Lively\Core\View\Traits\StateTrait;
use Lively\Core\View\Traits\UtilityTrait;

abstract class Component {
    use ChildrenTrait;
    use HydrationTrait;
    use LazyLoadingTrait;
    use LifecycleTrait;
    use PropsTrait;
    use StateTrait;
    use UtilityTrait;

    /**
     * Event handlers registered for this component
     * 
     * @var array
     */
    protected $eventHandlers = [];
    
    /**
     * Parent component reference
     * 
     * @var Component|null
     */
    protected $parent = null;

    /**
     * Constructor
     */
    public function __construct($props = []) {
        // Set properties first
        $this->props = $props;
        $this->prevProps = $props;
        
        // Initialize state before generating ID
        $this->initState();
        $this->prevState = $this->state;
        
        // Generate a unique component ID
        $this->id = $this->generateId();
        
        // Register with renderer immediately
        $renderer = Renderer::getInstance();
        $renderer->registerComponent($this);

        $this->registerHooks();
        
        // Check if lazy loading is explicitly set in props
        if (isset($props['lazy']) && $props['lazy'] === true) {
            $this->lazyLoad = true;
        }
        
        Logger::debug("Component constructed", [
            'id' => $this->id,
            'class' => get_class($this),
            'state' => $this->state
        ]);
    }

    /**
     * Register hooks for the component
     * 
     * @return void
     */
    protected function registerHooks() {
        // Implement in child classes if needed
    }
    
    /**
     * Destroys the component, cleaning up resources and references
     * Called when removing a component from memory
     * 
     * @return void
     */
    public function destroy() {
        // Call unmount if component is still mounted
        if ($this->lifecycleStatus === 'mounted') {
            $this->unmount();
        }
        
        // Destroy all child components
        foreach ($this->getAllChildren() as $slot => $children) {
            foreach ($children as $child) {
                if (method_exists($child, 'destroy')) {
                    $child->destroy();
                }
            }
        }
        
        // Unregister from any global state listeners
        $this->unregisterStateListeners();
        
        // Unregister from event hooks if in WordPress environment
        if (function_exists('remove_action') && function_exists('remove_filter')) {
            $this->unregisterWordPressHooks();
        }
        
        // Unregister from the renderer to prevent memory leaks
        if (class_exists('Lively\\Core\\View\\Renderer')) {
            $renderer = Renderer::getInstance();
            $renderer->unregisterComponent($this->id);
        }
        
        // Remove any DOM-bound event handlers
        $this->eventHandlers = [];
        
        // Break circular references
        $this->props = [];
        $this->prevProps = [];
        $this->state = [];
        $this->prevState = [];
        $this->children = [];
        $this->components = [];
        $this->parent = null;
        
        // Log destruction for debugging
        Logger::debug("Component destroyed", [
            'id' => $this->id,
            'class' => get_class($this)
        ]);
    }
    
    /**
     * Unregister component from any state listeners to prevent memory leaks
     * 
     * @return void
     */
    protected function unregisterStateListeners() {
        if (class_exists('Lively\\Core\\View\\State')) {
            $state = State::getInstance();
            $state->unregisterComponentListeners($this->id);
        }
    }
    
    /**
     * Unregister from WordPress hooks
     * This helps prevent memory leaks from dangling callbacks
     * 
     * @return void
     */
    protected function unregisterWordPressHooks() {
        // Common hook patterns to check
        $hookPatterns = [
            // Actions
            "lively_component_{$this->id}_",
            "lively_before_{$this->id}_",
            "lively_after_{$this->id}_",
            
            // Filters
            "lively_filter_{$this->id}_"
        ];
        
        // WordPress stores hooks in $wp_filter global
        global $wp_filter;
        
        if (isset($wp_filter) && is_array($wp_filter)) {
            foreach ($wp_filter as $hookName => $hooks) {
                foreach ($hookPatterns as $pattern) {
                    if (strpos($hookName, $pattern) === 0) {
                        remove_all_filters($hookName);
                        remove_all_actions($hookName);
                    }
                }
            }
        }
    }
    
    /**
     * Resets a component for reuse in component pooling
     * 
     * @param array $props New properties for the component
     * @return $this
     */
    public function reset($props = []) {
        // Reset properties and state
        $this->props = $props;
        $this->prevProps = $props;
        
        // Clear children
        $this->children = [];
        
        // Re-initialize the state
        $this->initState();
        $this->prevState = $this->state;
        
        // Reset lifecycle status
        $this->lifecycleStatus = 'created';
        
        // Log reset for debugging
        Logger::debug("Component reset for reuse", [
            'id' => $this->id,
            'class' => get_class($this)
        ]);
        
        return $this;
    }
    
    /**
     * Clears component state
     * Used when recycling components in the pool
     * 
     * @return $this
     */
    public function clearState() {
        // Save previous state for comparison
        $this->prevState = $this->state;
        
        // Re-initialize the state to defaults
        $this->initState();
        
        return $this;
    }

    /**
     * Get the component's class name
     */
    public function getComponentClass() {
        // Get the actual class name without namespace
        $class = get_class($this);
        $parts = explode('\\', $class);
        $className = end($parts);
        
        return $className; // Return just the class name without namespace
    }

    /**
     * Get all registered components
     */
    public function getComponents() {
        return $this->components;
    }

    /**
     * Get the component's full class name with namespace
     */
    public function getFullComponentClass() {
        return get_class($this); // Return the full class name with namespace
    }

    /**
     * Save component metadata for reconstruction
     */
    public function getMetadata() {
        return [
            'class' => $this->getFullComponentClass(), // Store the full class name with namespace
            'id' => $this->getId(),
            'state' => $this->getState()
        ];
    }
    
    /**
     * Get the component ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Initialize component state
     * Must be implemented by child classes
     */
    abstract protected function initState();
    
    /**
     * Render the component
     * Must be implemented by child classes
     */
    abstract public function render();
    
    /**
     * Update component state
     */
    public function setState($key, $value) {
        // Save the previous state for lifecycle methods
        $this->prevState = $this->state;
        
        // Call beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Update the state
        $this->state[$key] = $value;
        
        // Call the updated lifecycle hook
        $this->updated();
        
        return $this;
    }
    
    /**
     * Bulk update state
     */
    public function setStateMultiple(array $stateChanges) {
        // Save the previous state for lifecycle methods
        $this->prevState = $this->state;
        
        // Call beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Update the state
        foreach ($stateChanges as $key => $value) {
            $this->state[$key] = $value;
        }
        
        // Call the updated lifecycle hook
        $this->updated();
        
        return $this;
    }
    
    /**
     * Replace the entire state at once
     * More efficient than setStateMultiple when setting many values
     * 
     * @param array $newState The complete new state to set
     * @return $this
     */
    public function setStates(array $newState) {
        // Save the previous state for lifecycle methods
        $this->prevState = $this->state;
        
        // Call beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Replace the entire state
        $this->state = $newState;
        
        // Call the updated lifecycle hook
        $this->updated();
        
        return $this;
    }
    
    /**
     * Get state value by key or all state
     */
    public function getState($key = null) {
        if ($key === null) {
            return $this->state;
        }
        return $this->state[$key] ?? null;
    }
    
    /**
     * Get props value by key or all props
     */
    public function getProps($key = null) {
        if ($key === null) {
            return $this->props;
        }
        return $this->props[$key] ?? null;
    }
    
    /**
     * Get the request object
     */
    public function getRequest() {
        return $this->props['request'] ?? null;
    }
    
    /**
     * Add a child component
     */
    public function addChild(Component $child, $slot = 'default') {
        if (!isset($this->children[$slot])) {
            $this->children[$slot] = [];
        }
        
        // Add the child to the slot
        $this->children[$slot][] = $child;
        
        // If this component is already mounted, mount the child too
        if ($this->lifecycleStatus === 'mounted') {
            $child->mount();
        }
        
        return $this;
    }
    
    /**
     * Get children for a specific slot
     */
    public function getChildren($slot = 'default') {
        return $this->children[$slot] ?? [];
    }
    
    /**
     * Get all children across all slots
     */
    public function getAllChildren() {
        return $this->children;
    }
    
    /**
     * Get a flat array of all children from all slots
     * Useful when you need to iterate through all children regardless of slot
     */
    public function getChildrenFlat() {
        $allChildren = [];
        foreach ($this->children as $slotChildren) {
            foreach ($slotChildren as $child) {
                $allChildren[] = $child;
            }
        }
        return $allChildren;
    }
    
    /**
     * Render all children for a specific slot
     */
    public function renderChildren($slot = 'default') {
        $output = '';
        foreach ($this->getChildren($slot) as $child) {
            $output .= $child->render();
        }
        return $output;
    }
    
    /**
     * Register a client-side event listener
     */
    protected function on($event, $method) {
        return "lively.handleEvent(event, '{$this->getId()}', '$method')";
    }
    
    /**
     * Get the component's JavaScript representation for client-side hydration
     */
    public function getClientScript() {
        // Convert state to JSON for client-side hydration
        $state = json_encode($this->state);
        $props = json_encode($this->props);
        $componentClass = $this->getFullComponentClass();
        
        // Start with the base component registration
        $script = "lively.registerComponent('{$this->getId()}', $state, $props, '{$componentClass}');\n";
        
        // Add client scripts for all children recursively
        foreach ($this->children as $slotName => $slotChildren) {
            foreach ($slotChildren as $child) {
                $script .= $child->getClientScript();
            }
        }
        
        return $script;
    }
    
    /**
     * Mount the component and trigger appropriate lifecycle method
     * This should be called when the component is first added to the DOM
     *
     * @return $this
     */
    public function mount() {
        // Only mount if not already mounted
        if ($this->lifecycleStatus !== 'mounted' && $this->lifecycleStatus !== 'mounting') {
            // Call beforeMount lifecycle hook
            $this->beforeMount();
            
            // Render the component (this happens in the actual DOM insertion)
            
            // Call mounted lifecycle hook
            $this->mounted();
        }
        
        return $this;
    }
    
    /**
     * Unmount the component and trigger appropriate lifecycle methods
     * This should be called when the component is removed from the DOM
     *
     * @return $this
     */
    public function unmount() {
        // Only unmount if not already unmounted
        if ($this->lifecycleStatus !== 'unmounted' && $this->lifecycleStatus !== 'unmounting') {
            // Call beforeUnmount lifecycle hook
            $this->beforeUnmount();
            
            // Actually remove from DOM (handled elsewhere)
            
            // Call unmounted lifecycle hook
            $this->unmounted();
        }
        
        return $this;
    }
    
    /**
     * Force a component update regardless of shouldComponentUpdate result
     * Will trigger the appropriate lifecycle methods
     *
     * @return $this
     */
    public function forceUpdate() {
        // Call beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Re-render happens elsewhere
        
        // Call updated lifecycle hook
        $this->updated();
        
        return $this;
    }
    
    /**
     * Check if state or props have changed since last render
     *
     * @return bool True if component has pending changes
     */
    public function hasChanges() {
        // Simple deep comparison of state and props
        return $this->state !== $this->prevState || $this->props !== $this->prevProps;
    }

    /**
     * Handle a client-side update request
     */
    public function handleUpdate($method, $args = []) {
        // Save previous props and state
        $this->prevProps = $this->props;
        $this->prevState = $this->state;
        
        // Call the beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Call the method
        $result = $this->callMethod($method, $args);
        
        // Re-render the component
        $html = $this->render();
        
        // Call the updated lifecycle hook
        $this->updated();
        
        // Return the updated component data
        return [
            'id' => $this->getId(),
            'html' => $html,
            'state' => $this->state,
            'result' => $result
        ];
    }
    
    /**
     * Convert the component to a string (renders the component)
     */
    public function __toString() {
        try {
            if ($this->lazyLoad) {
                return $this->renderLazy();
            }
            
            return $this->render();
        } catch (\Exception $e) {
            // Log the error and return fallback content
            Logger::error('Component rendering error: ' . $e->getMessage(), [
                'componentId' => $this->id,
                'componentClass' => get_class($this),
                'exception' => $e
            ]);
            
            return '<div class="component-error">Component rendering error</div>';
        }
    }

    /**
     * Set custom layout for this component
     */
    public function setLayout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Get the layout for this component
     */
    public function getLayout() {
        return $this->layout;
    }

    /**
     * Prune component to reduce memory footprint
     * Called on less frequently used components to free memory
     * without completely destroying the component
     * 
     * @return $this
     */
    public function prune() {
        // Clear non-essential cached data
        $this->clearCaches();
        
        // Use reflection to safely access and clear properties
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();
        
        // Properties that can be safely cleared or reduced
        $pruneableProperties = [
            'compiledTemplates' => [], 
            'cachedQueries' => [],
            'cachedResults' => [],
            'results' => [],
            'renderCache' => [],
            'calculatedValues' => []
        ];
        
        // Prune specific properties if they exist
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            
            if (array_key_exists($propertyName, $pruneableProperties)) {
                $property->setValue($this, $pruneableProperties[$propertyName]);
                Logger::debug("Pruned property: $propertyName");
            }
            
            // Handle items property specially - keep first few items only
            if ($propertyName === 'items') {
                $value = $property->getValue($this);
                if (is_array($value) && count($value) > 10) {
                    $property->setValue($this, array_slice($value, 0, 10));
                    Logger::debug("Pruned items array from " . count($value) . " to 10 items");
                }
            }
        }
        
        // Prune child components too - but don't destroy them
        foreach ($this->getAllChildren() as $slot => $children) {
            foreach ($children as $child) {
                if (method_exists($child, 'prune')) {
                    $child->prune();
                }
            }
        }
        
        // Log pruning for debugging
        Logger::debug("Component pruned to reduce memory", [
            'id' => $this->id,
            'class' => get_class($this)
        ]);
        
        return $this;
    }
    
    /**
     * Clear any internal caches
     * 
     * @return $this
     */
    protected function clearCaches() {
        // This method can be overridden by child classes
        // to clear specific caches
        return $this;
    }

    /**
     * Include a component with props
     * 
     * @param string|object $component Component class name or instance
     * @param array $props Component properties
     * @param string $slot Slot to render the component in
     * @return string Rendered component HTML
     */
    public function include($component, $props = [], $slot = 'default') {
        // If component is a string (class name), instantiate it
        if (is_string($component)) {
            // Check if it's a full class name or just a component name
            if (strpos($component, '\\') === false) {
                // Try to find the component in common namespaces
                $namespaces = [
                    'Lively\\Resources\\Components\\',
                ];
                
                $foundClass = false;
                foreach ($namespaces as $namespace) {
                    // First try the direct namespace
                    $className = $namespace . $component;
                   Logger::debug("Trying to load component class: $className");
                    if (class_exists($className)) {
                        $component = $className;
                        Logger::debug("Found component class: $className");
                        $foundClass = true;
                        break;
                    }
                    
                    // Then try with subdirectories
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
                                    $relativePath = str_replace($dir . '/', '', $file->getPathname());
                                    $namespacePath = str_replace('/', '\\', dirname($relativePath));
                                    $fileClassName = basename($file->getPathname(), '.php');
                                    
                                    // If the component name matches the file name
                                    if ($fileClassName === $component) {
                                        $fullNamespace = $namespace;
                                        if ($namespacePath !== '.') {
                                            $fullNamespace .= $namespacePath . '\\';
                                        }
                                        $className = $fullNamespace . $fileClassName;
                                        
                                        if (class_exists($className)) {
                                            $component = $className;
                                            Logger::debug("Found component class in subdirectory: $className");
                                            $foundClass = true;
                                            break 3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (!$foundClass) {
                    Logger::warn("Component class not found: $component");
                }
            }
            
            // If the component class exists, instantiate it
            if (class_exists($component)) {
                Logger::debug("Instantiating component: $component");
                $component = new $component($props);
                
                // Mount the newly created component if this component is mounted
                if ($this->lifecycleStatus === 'mounted') {
                    $component->mount();
                }
            } else {
                Logger::error("Component class does not exist: $component");
                return "<!-- Component '{$component}' not found -->";
            }
        } elseif (is_object($component) && $component instanceof Component) {
            // If it's already a component instance, update its props
            $component->updateProps($props);
        } else {
            Logger::error("Invalid component type provided to include()");
            return "<!-- Invalid component -->";
        }
        
        // Add the component as a child
        $this->addChild($component, $slot);
        
        // Return the rendered component
        return $component->render();
    }
    
    /**
     * Static method to create a component instance with props
     * 
     * @param string $component Component class name or instance
     * @param array $props Component properties
     * @return Component Component instance
     */
    public static function create($component = null, $props = []) {
        if (is_string($component)) {
            // Check if it's a full class name or just a component name
            if (strpos($component, '\\') === false) {
                // Try to find the component in common namespaces
                $namespaces = [
                    'Lively\\Resources\\Components\\',
                ];
                
                $foundClass = false;
                foreach ($namespaces as $namespace) {
                    // First try the direct namespace
                    $className = $namespace . $component;
                    Logger::debug("Trying to load component class: $className");
                    if (class_exists($className)) {
                        $component = $className;
                        Logger::debug("Found component class: $className");
                        $foundClass = true;
                        break;
                    }
                    
                    // Then try with subdirectories
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
                                    $relativePath = str_replace($dir . '/', '', $file->getPathname());
                                    $namespacePath = str_replace('/', '\\', dirname($relativePath));
                                    $fileClassName = basename($file->getPathname(), '.php');
                                    
                                    // If the component name matches the file name
                                    if ($fileClassName === $component) {
                                        $fullNamespace = $namespace;
                                        if ($namespacePath !== '.') {
                                            $fullNamespace .= $namespacePath . '\\';
                                        }
                                        $className = $fullNamespace . $fileClassName;
                                        
                                        if (class_exists($className)) {
                                            $component = $className;
                                            Logger::debug("Found component class in subdirectory: $className");
                                            $foundClass = true;
                                            break 3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (!$foundClass) {
                    Logger::warn("Component class not found: $component");
                }
            }
            
            // If the component class exists, instantiate it
            if (class_exists($component)) {
                Logger::debug("Instantiating component: $component");
                $componentInstance = new $component($props);
                
                // Get the renderer instance
                $renderer = Renderer::getInstance();
                
                // Render the component
                return $renderer->render($componentInstance);
            } else {
                Logger::error("Component class does not exist: $component");
                return "<!-- Component '{$component}' not found -->";
            }
        }
        
        // If component is already an instance, just render it
        if ($component instanceof self) {
            $renderer = Renderer::getInstance();
            return $renderer->render($component);
        }
        
        return "<!-- Invalid component -->";
    }

    /**
     * Recursively mount this component and all its children
     * 
     * @return $this
     */
    public function mountRecursive() {
        // Mount this component first
        $this->mount();
        
        // Then mount all children in all slots
        foreach ($this->children as $slotChildren) {
            foreach ($slotChildren as $child) {
                $child->mountRecursive();
            }
        }
        
        return $this;
    }
    
    /**
     * Recursively unmount this component and all its children
     * 
     * @return $this
     */
    public function unmountRecursive() {
        // Unmount all children first (bottom-up approach)
        foreach ($this->children as $slotChildren) {
            foreach ($slotChildren as $child) {
                $child->unmountRecursive();
            }
        }
        
        // Then unmount this component
        $this->unmount();
        
        return $this;
    }
    
    /**
     * Remove a specific child component and unmount it
     * 
     * @param Component $child The child component to remove
     * @param string|null $slot Optional slot to look in, or all slots if null
     * @return bool True if child was found and removed
     */
    public function removeChild(Component $child, $slot = null) {
        // If slot is specified, only look in that slot
        if ($slot !== null) {
            if (!isset($this->children[$slot])) {
                return false;
            }
            
            foreach ($this->children[$slot] as $index => $existingChild) {
                if ($existingChild->getId() === $child->getId()) {
                    // Unmount the child before removing
                    $existingChild->unmount();
                    
                    // Remove the child
                    array_splice($this->children[$slot], $index, 1);
                    return true;
                }
            }
            
            return false;
        }
        
        // If no slot specified, look in all slots
        foreach ($this->children as $slotName => $slotChildren) {
            foreach ($slotChildren as $index => $existingChild) {
                if ($existingChild->getId() === $child->getId()) {
                    // Unmount the child before removing
                    $existingChild->unmount();
                    
                    // Remove the child
                    array_splice($this->children[$slotName], $index, 1);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Replace all children in a slot with new children
     * 
     * @param array $children Array of Component instances
     * @param string $slot Slot name
     * @return $this
     */
    public function setChildren(array $children, $slot = 'default') {
        // Unmount all existing children in the slot
        if (isset($this->children[$slot])) {
            foreach ($this->children[$slot] as $child) {
                $child->unmount();
            }
        }
        
        // Set the new children
        $this->children[$slot] = [];
        foreach ($children as $child) {
            if ($child instanceof Component) {
                $this->addChild($child, $slot);
            }
        }
        
        return $this;
    }

    /**
     * Get the server-side metadata needed for hydration
     * 
     * @return array Component metadata for serialization
     */
    public function getHydrationData() {
        $data = [
            'id' => $this->getId(),
            'class' => $this->getFullComponentClass(),
            'state' => $this->state,
            'props' => $this->props,
            'children' => []
        ];
        
        // Add metadata for all children recursively
        foreach ($this->children as $slotName => $slotChildren) {
            $data['children'][$slotName] = [];
            
            foreach ($slotChildren as $child) {
                $data['children'][$slotName][] = $child->getHydrationData();
            }
        }
        
        return $data;
    }
    
    /**
     * Render component with data attributes for hydration
     * 
     * @return string HTML with hydration attributes
     */
    public function renderWithHydration() {
        // Get the component's standard rendered output
        $html = $this->render();
        
        // Find the first HTML tag to add the data attributes
        if (preg_match('/^(\s*<[^>]+)>/', $html, $matches)) {
            // The opening tag without the closing '>'
            $openingTag = $matches[1];
            
            // Add lively attributes for hydration - use only shorthand format
            $hydratedOpeningTag = $openingTag . ' lively:component="' . $this->getId() . '"';
            $hydratedOpeningTag .= ' lively:class="' . htmlspecialchars($this->getFullComponentClass()) . '">';
            
            // Replace the opening tag with our hydrated version
            $hydratedHtml = str_replace($matches[0], $hydratedOpeningTag, $html);
            return $hydratedHtml;
        }
        
        // If no HTML tag is found, wrap the output in a div with hydration attributes
        return '<div lively:component="' . $this->getId() . '" ' .
               'lively:class="' . htmlspecialchars($this->getFullComponentClass()) . '">' .
               $html .
               '</div>';
    }
    
    /**
     * Get a flattened list of all components in the tree
     * Useful for collecting all components that need hydration
     * 
     * @return array Array of Component objects
     */
    public function getComponentTree() {
        $components = [$this];
        
        // Collect all child components recursively
        foreach ($this->children as $slotName => $slotChildren) {
            foreach ($slotChildren as $child) {
                $components = array_merge($components, $child->getComponentTree());
            }
        }
        
        return $components;
    }

    /**
     * Helper to create a form with CSRF protection
     * 
     * @param string $action Form action URL
     * @param string $method Form method (POST, GET, etc.)
     * @param array $attributes Additional form attributes
     * @param string $formId Optional form identifier for the CSRF token
     * @return string Form opening tag with CSRF token
     */
    protected function csrfForm($action = '', $method = 'POST', $attributes = [], $formId = 'default') {
        $attrStr = '';
        foreach ($attributes as $key => $value) {
            $attrStr .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $html = '<form action="' . htmlspecialchars($action) . '" method="' . htmlspecialchars($method) . '"' . $attrStr . '>';
        
        // Add CSRF token if the CSRF utility exists
        if (class_exists('\\Lively\\Core\\Utils\\CSRF')) {
            $html .= CSRF::field($formId);
            $html .= '<input type="hidden" name="csrf_form_id" value="' . htmlspecialchars($formId) . '">';
        }
        
        return $html;
    }
    
    /**
     * Close a form
     * 
     * @return string Form closing tag
     */
    protected function endForm() {
        return '</form>';
    }
}