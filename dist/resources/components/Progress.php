<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * @view
 */
class Progress extends Component {
    protected function initState() {
        $this->setState('value', $this->getProps('value') ?? 0);
        $this->setState('min', $this->getProps('min') ?? 0);
        $this->setState('max', $this->getProps('max') ?? 100);
        $this->setState('step', $this->getProps('step') ?? 1);
        $this->setState('label', $this->getProps('label') ?? 'Progress');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
        $this->setState('name', $this->getProps('name') ?? '');   
    }
    
    /**
    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        return <<<HTML
        <input 
            type="range"
            class="lively-component progress-bar {$this->getState('class')}" 
            lively:component="{$this->getId()}" 
            role="region" 
            aria-label="{$this->getState('label')}" 
            value="{$this->getState('value')}" 
            min="{$this->getState('min')}" 
            max="{$this->getState('max')}" 
            step="{$this->getState('step')}" 
            id="{$this->getState('id')}" 
            name="{$this->getState('name')}"
            class="{$this->getState('class')}"/>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Progress();
}