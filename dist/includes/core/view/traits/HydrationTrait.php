<?php

namespace Lively\Core\View\Traits;

/**
 * Trait for component hydration for client-side interaction
 */
trait HydrationTrait
{
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
        
        // Get the full class name with namespace
        $componentClass = $this->getFullComponentClass();
        
        // Start with the base component registration
        $script = "lively.registerComponent('{$this->getId()}', $state, $props, " . json_encode($componentClass) . ");\n";
        
        // Add client scripts for all children recursively
        foreach ($this->children as $slotName => $slotChildren) {
            foreach ($slotChildren as $child) {
                $script .= $child->getClientScript();
            }
        }
        
        return $script;
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
            $hydratedOpeningTag .= ' lively:json-class="' . json_encode($this->getFullComponentClass()) . '">';
            
            // Replace the opening tag with our hydrated version
            $hydratedHtml = str_replace($matches[0], $hydratedOpeningTag, $html);
            return $hydratedHtml;
        }
        
        // If no HTML tag is found, wrap the output in a div with hydration attributes
        return '<div lively:component="' . $this->getId() . '" ' .
               'lively:json-class="' . json_encode($this->getFullComponentClass()) . '">' .
               $html .
               '</div>';
    }
} 