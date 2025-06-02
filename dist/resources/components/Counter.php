<?php

namespace Lively\Resources\Components;

defined('ABSPATH') || exit;

use Lively\Core\View\Component;

/**
 * @view
 */
class Counter extends Component {
    protected function initState() {
        $this->state['value'] = $this->getProps('initialValue') ?? 0;
    }
    
    public function increment() {
        $this->setState('value', $this->getState('value') + 1);
    }
    
    public function decrement() {
        $this->setState('value', $this->getState('value') - 1);
    }

    public function setValue($value = null) {
        // For data-lively-action calls, the value comes from the event data
        if ($value === null) {
            $value = $this->getState('value');
        }
        
        if ($value !== null) {
            $this->setState('value', intval($value));
        }
    }
    
    public function render() {
        $value = $this->getState('value');
        $label = $this->getProps('label') ?? 'Counter: ' . $value;
        
        return <<<HTML
        <div class="lively-component" lively:component="{$this->getId()}" role="region" aria-label="Counter">
            <h3>{$label}</h3>
            <div class="counter-controls" role="group" aria-label="Counter controls">
                <button lively:onclick="decrement" aria-label="Decrease value">-</button>
                <input 
                    type="number" 
                    lively:onchange="setValue" 
                    lively:value-attr="value" 
                    value="{$value}" 
                    min="0" 
                    max="100"
                    aria-label="Counter value"
                />
                <button lively:onclick="increment" aria-label="Increase value">+</button>
            </div>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Counter();
} 