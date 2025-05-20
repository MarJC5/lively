<?php

namespace Lively\Core\View\Traits;

use Lively\Core\Utils\CSRF;

/**
 * Trait for component utility methods
 */
trait UtilityTrait
{
    protected $id;
    protected $layout = null;
    
    /**
     * Generate a unique component ID
     */
    protected function generateId() {
        // Get the class name without namespace
        $className = (new \ReflectionClass($this))->getShortName();
        
        // Convert PascalCase to kebab-case
        $kebabCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
        
        // Get the unique key for this component instance
        $uniqueKey = spl_object_hash($this);
        
        // Create a stable ID based on the kebab-case class name and a hash of the unique key
        return $kebabCase . '-' . substr(md5($uniqueKey), 0, 8);
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
     * Call a method on the component
     */
    public function callMethod($method, $args = []) {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }
        return null;
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
     * Static method to create a component instance with props
     * 
     * @param string $className Component class name
     * @param array $props Component properties
     * @return Component Component instance
     */
    public static function create($className, $props = []) {
        // If class doesn't have namespace, assume it's in one of the component directories
        if (strpos($className, '\\') === false) {
            // Try application resources first, then fall back to core resources
            $applicationClass = 'Lively\\Resources\\Components\\' . $className;
            $coreClass = 'Lively\\Core\\Resources\\Components\\' . $className;
            
            if (class_exists($applicationClass)) {
                return new $applicationClass($props);
            } else if (class_exists($coreClass)) {
                return new $coreClass($props);
            }
        } else if (class_exists($className)) {
            return new $className($props);
        }
        
        throw new \Exception("Component class '{$className}' not found");
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
            \Lively\Core\Utils\Logger::error('Component rendering error: ' . $e->getMessage(), [
                'componentId' => $this->id,
                'componentClass' => get_class($this),
                'exception' => $e
            ]);
            
            return '<div class="component-error">Component rendering error</div>';
        }
    }
} 