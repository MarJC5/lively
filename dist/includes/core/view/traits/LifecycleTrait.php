<?php

namespace Lively\Core\View\Traits;

use Lively\Core\WordPress\Hooks;

/**
 * Trait for component lifecycle methods
 */
trait LifecycleTrait
{
    protected $lifecycleStatus = 'initialized'; // Track component lifecycle state
    protected $prevState = []; // Store previous state for change detection
    protected $prevProps = []; // Store previous props for change detection
    
    /**
     * Lifecycle method: before component is mounted
     * Called before the component is first rendered in the DOM
     * 
     * @return void
     */
    public function beforeMount() {
        // Update lifecycle status
        $this->lifecycleStatus = 'mounting';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_before_mount', $this);
            Hooks::doAction("lively_component_{$this->id}_before_mount", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Lifecycle method: after component is mounted
     * Called after the component has been rendered in the DOM
     * 
     * @return void
     */
    public function mounted() {
        // Update lifecycle status
        $this->lifecycleStatus = 'mounted';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_mounted', $this);
            Hooks::doAction("lively_component_{$this->id}_mounted", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Lifecycle method: before component is updated
     * Called before the component re-renders due to state or prop changes
     * 
     * @return void
     */
    public function beforeUpdate() {
        // Update lifecycle status
        $this->lifecycleStatus = 'updating';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_before_update', $this);
            Hooks::doAction("lively_component_{$this->id}_before_update", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Lifecycle method: after component is updated
     * Called after the component has re-rendered due to state or prop changes
     * 
     * @return void
     */
    public function updated() {
        // Update lifecycle status
        $this->lifecycleStatus = 'updated';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_updated', $this);
            Hooks::doAction("lively_component_{$this->id}_updated", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Lifecycle method: before component is unmounted
     * Called before the component is removed from the DOM
     * 
     * @return void
     */
    public function beforeUnmount() {
        // Update lifecycle status
        $this->lifecycleStatus = 'unmounting';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_before_unmount', $this);
            Hooks::doAction("lively_component_{$this->id}_before_unmount", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Lifecycle method: after component is unmounted
     * Called after the component has been removed from the DOM
     * 
     * @return void
     */
    public function unmounted() {
        // Update lifecycle status
        $this->lifecycleStatus = 'unmounted';
        
        // Add WordPress integration
        if (defined('ABSPATH')) {
            Hooks::doAction('lively_component_unmounted', $this);
            Hooks::doAction("lively_component_{$this->id}_unmounted", $this);
        }
        
        // Implement in child classes if needed
    }
    
    /**
     * Get the current lifecycle status of the component
     * 
     * @return string Current lifecycle status
     */
    public function getLifecycleStatus() {
        return $this->lifecycleStatus;
    }
    
    /**
     * Check if component should update based on state/props changes
     * Can be overridden in child classes to optimize rendering
     * 
     * @param array $nextProps Next props
     * @param array $nextState Next state
     * @return bool Whether component should update
     */
    public function shouldComponentUpdate($nextProps = null, $nextState = null) {
        // By default, always update when setState is called
        return true;
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
            // Ensure we're in a WordPress context
            if (defined('ABSPATH') && !Hooks::didAction('wp_loaded')) {
                // Queue the mount for after WordPress is fully loaded
                Hooks::addAction('wp_loaded', function() {
                    $this->beforeMount();
                    $this->mounted();
                });
                return $this;
            }
            
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
} 