<?php

namespace Lively\Core\View;

use Lively\Core\Utils\Logger;

class ComponentFactory
{
    /**
     * List of allowed component namespaces
     * @var array
     */
    protected $allowedNamespaces = [
        'Lively\\Resources\\Components\\',
        'Lively\\Core\\View\\'
    ];
    
    /**
     * Create a component instance
     * 
     * @param string $className Component class name
     * @param array $props Component props
     * @return Component|null
     */
    public function create($className, $props = [])
    {
        try {
            // First normalize the class name
            $normalizedClassName = $this->normalizeComponentClassName($className);
            
            if (!$normalizedClassName) {
                Logger::warn("Invalid component class name: $className");
                return null;
            }
            
            // Now check if the normalized class is allowed
            if (!$this->isClassAllowed($normalizedClassName)) {
                Logger::warn("Attempted to instantiate non-allowed class: $normalizedClassName");
                return null;
            }
            
            // Check if the class exists
            if (!class_exists($normalizedClassName, true)) {
                Logger::warn("Component class does not exist: $normalizedClassName");
                return null;
            }
            
            // Create the component
            $component = new $normalizedClassName($props);
            
            if (!($component instanceof Component)) {
                Logger::warn("Created object is not a Component: $normalizedClassName");
                return null;
            }
            
            Logger::info("Component created successfully", [
                'id' => $component->getId(),
                'class' => get_class($component)
            ]);
            
            return $component;
        } catch (\Exception $e) {
            Logger::error("Error creating component", [
                'class' => $className,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Create a component from client-side state
     */
    public function createFromClientState($componentId, $className, $state = [], $props = []) {
        try {
            // Validate class name
            if (empty($className)) {
                Logger::warn("Empty class name provided for component: $componentId");
                return null;
            }
            
            Logger::debug("Attempting to create component from class: $className, ID: $componentId");
            
            // Normalize class name (handle different formats)
            $normalizedClassName = $this->normalizeComponentClassName($className, $componentId);
            if ($normalizedClassName) {
                $className = $normalizedClassName;
                Logger::debug("Normalized class name to: $className");
            }
            
            // Check against the allowed component classes - SECURITY IMPROVEMENT
            $allowedComponentClasses = $this->getAllowedComponentClasses();
            if (!in_array($className, $allowedComponentClasses)) {
                Logger::warn("Attempted to instantiate non-allowed class: $className", [
                    'component_id' => $componentId,
                    'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
                ]);
                return null;
            }
            
            // Check if the class exists or can be loaded
            if (!class_exists($className, true)) {
                Logger::warn("Component class does not exist and cannot be loaded: $className");
                return null;
            }
            
            // Create new component instance with props
            $component = new $className($props);
            
            // Set component ID to match the client ID
            $reflectionClass = new \ReflectionClass($component);
            $idProperty = $reflectionClass->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($component, $componentId);
            
            // Set component state if provided
            if (!empty($state) && method_exists($component, 'setStateMultiple')) {
                $component->setStateMultiple($state);
            }
            
            // Register the component
            $renderer = Renderer::getInstance();
            $renderer->registerComponent($component);
            
            Logger::info("Successfully created component from client state", [
                'id' => $componentId,
                'class' => $className,
                'props' => $props
            ]);
            
            return $component;
        } catch (\Exception $e) {
            Logger::error("Error creating component from client state", [
                'component_id' => $componentId,
                'class' => $className,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Check if a class is allowed
     * 
     * @param string $className Component class name
     * @return bool
     */
    public function isClassAllowed($className)
    {
        // Always allow core component class
        if ($className === 'Lively\\Core\\View\\Component') {
            return true;
        }

        // Check allowed namespaces
        foreach ($this->allowedNamespaces as $namespace) {
            if (strpos($className, $namespace) === 0) {
                return true;
            }
        }
        
        // Check additional allowed component classes
        $allowedComponentClasses = $this->getAllowedComponentClasses();
        
        // Log for debugging
        Logger::debug("Checking if class is allowed", [
            'class' => $className,
            'allowed_classes' => $allowedComponentClasses
        ]);
        
        return in_array($className, $allowedComponentClasses);
    }
    
    /**
     * Get list of explicitly allowed component classes
     * 
     * @return array
     */
    protected function getAllowedComponentClasses()
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
                            
                            $fullClassName = $namespace . $className;
                            
                            // Only add if the class exists and extends Component
                            if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Lively\\Core\\View\\Component')) {
                                $allowedClasses[] = $fullClassName;
                                Logger::debug("Added allowed component class: $fullClassName");
                            }
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
     * 
     * @param string $componentId Component ID
     * @return string|null
     */
    protected function inferClassNameFromComponentId($componentId)
    {
        // Format is usually something like "my-component-f7e8a9b1"
        $parts = explode('-', $componentId);
        if (count($parts) >= 1) {
            // Convert kebab-case to PascalCase
            $pascalCase = str_replace(' ', '', ucwords(str_replace('-', ' ', $parts[0])));
            
            // Try with different namespaces
            foreach ($this->allowedNamespaces as $namespace) {
                // First try direct namespace
                $className = $namespace . $pascalCase;
                if (class_exists($className)) {
                    return $className;
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
                                if ($fileClassName === $pascalCase) {
                                    $fullNamespace = $namespace;
                                    if ($namespacePath !== '.') {
                                        $fullNamespace .= $namespacePath . '\\';
                                    }
                                    $className = $fullNamespace . $fileClassName;
                                    
                                    if (class_exists($className)) {
                                        return $className;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Normalize component class name
     * 
     * @param string $className Component class name
     * @return string|null
     */
    protected function normalizeComponentClassName($className)
    {
        // Already a valid class name with namespace
        if (strpos($className, '\\') !== false) {
            // If it's a partial namespace (like Layouts\Header), try to resolve it
            if (strpos($className, 'Lively\\') !== 0) {
                $parts = explode('\\', $className);
                $baseName = array_pop($parts);
                $namespace = 'Lively\\Resources\\Components\\';
                
                if (!empty($parts)) {
                    $namespace .= implode('\\', $parts) . '\\';
                }
                
                $fullClassName = $namespace . $baseName;
                if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Lively\\Core\\View\\Component')) {
                    return $fullClassName;
                }
            }
            return $className;
        }

        // Try to add namespace
        foreach ($this->allowedNamespaces as $namespace) {
            $fullClassName = $namespace . $className;
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Lively\\Core\\View\\Component')) {
                return $fullClassName;
            }
        }

        // Try with PascalCase
        $pascalCaseName = ucfirst($className);
        foreach ($this->allowedNamespaces as $namespace) {
            $fullClassName = $namespace . $pascalCaseName;
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Lively\\Core\\View\\Component')) {
                return $fullClassName;
            }
        }

        // Try to find in component directories
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
                        if ($fileClassName === $className || $fileClassName === $pascalCaseName) {
                            $fullNamespace = 'Lively\\Resources\\Components\\';
                            if ($namespacePath !== '.') {
                                $fullNamespace .= $namespacePath . '\\';
                            }
                            
                            $fullClassName = $fullNamespace . $fileClassName;
                            if (class_exists($fullClassName) && is_subclass_of($fullClassName, 'Lively\\Core\\View\\Component')) {
                                return $fullClassName;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }
} 