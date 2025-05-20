<?php

namespace Lively\Core\View\Traits;

use Lively\Core\View\Component;

/**
 * Trait for managing component children
 */
trait ChildrenTrait
{
    protected $children = [];
    protected static $components = [];
    
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
     * Get all registered components
     */
    public function getComponents() {
        return $this->components;
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
                    'Lively\\Resources\\Components\\'
                ];
                
                $foundClass = false;
                foreach ($namespaces as $namespace) {
                    // First try the direct namespace
                    $className = $namespace . $component;
                    \Lively\Core\Utils\Logger::debug("Trying to load component class: $className");
                    if (class_exists($className)) {
                        $component = $className;
                        \Lively\Core\Utils\Logger::debug("Found component class: $className");
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
                                            \Lively\Core\Utils\Logger::debug("Found component class in subdirectory: $className");
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
                    \Lively\Core\Utils\Logger::warn("Component class not found: $component");
                }
            }
            
            // If the component class exists, instantiate it
            if (class_exists($component)) {
                \Lively\Core\Utils\Logger::debug("Instantiating component: $component");
                $component = new $component($props);
                
                // Mount the newly created component if this component is mounted
                if ($this->lifecycleStatus === 'mounted') {
                    $component->mount();
                }
            } else {
                \Lively\Core\Utils\Logger::error("Component class does not exist: $component");
                return "<!-- Component '{$component}' not found -->";
            }
        } elseif (is_object($component) && $component instanceof Component) {
            // If it's already a component instance, update its props
            $component->updateProps($props);
        } else {
            \Lively\Core\Utils\Logger::error("Invalid component type provided to include()");
            return "<!-- Invalid component -->";
        }
        
        // Add the component as a child
        $this->addChild($component, $slot);
        
        // Return the rendered component
        return $component->render();
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
} 