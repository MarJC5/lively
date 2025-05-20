<?php

namespace Lively\Core\View\Traits;

/**
 * Trait for managing component properties
 */
trait PropsTrait
{
    protected $props = [];
    
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
     * Get the previous props before the last update
     * 
     * @param string|null $key Specific prop key to retrieve
     * @return mixed Previous prop value(s)
     */
    public function getPrevProps($key = null) {
        if ($key === null) {
            return $this->prevProps;
        }
        return $this->prevProps[$key] ?? null;
    }
    
    /**
     * Update props and trigger appropriate lifecycle methods
     *
     * @param array $newProps New properties
     * @return $this
     */
    public function updateProps($newProps) {
        // Store previous props
        $this->prevProps = $this->props;
        
        // Check if component should update
        if (!$this->shouldComponentUpdate($newProps, $this->state)) {
            return $this;
        }
        
        // Call beforeUpdate lifecycle hook
        $this->beforeUpdate();
        
        // Update props
        foreach ($newProps as $key => $value) {
            $this->props[$key] = $value;
        }
        
        // Call updated lifecycle hook
        $this->updated();
        
        return $this;
    }
} 