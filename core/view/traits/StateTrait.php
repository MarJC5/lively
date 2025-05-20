<?php

namespace Lively\Core\View\Traits;

/**
 * Trait for managing component state
 */
trait StateTrait
{
    protected $state = [];
    
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
     * Get state value by key or all state
     */
    public function getState($key = null) {
        if ($key === null) {
            return $this->state;
        }
        return $this->state[$key] ?? null;
    }
    
    /**
     * Get the previous state before the last update
     * 
     * @param string|null $key Specific state key to retrieve
     * @return mixed Previous state value(s)
     */
    public function getPrevState($key = null) {
        if ($key === null) {
            return $this->prevState;
        }
        return $this->prevState[$key] ?? null;
    }
} 