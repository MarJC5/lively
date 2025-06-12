<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * Progress Component
 * 
 * A component for displaying a progress bar or slider input.
 * Can be used for showing progress, volume controls, or any range-based input.
 * 
 * @example
 * ```php
 * // Basic usage with default values
 * new Progress(['label' => 'Download Progress']);
 * 
 * // Usage with custom range
 * new Progress([
 *     'label' => 'Volume',
 *     'min' => 0,
 *     'max' => 100,
 *     'value' => 50
 * ]);
 * 
 * // Usage with custom step and class
 * new Progress([
 *     'label' => 'Rating',
 *     'min' => 1,
 *     'max' => 5,
 *     'step' => 0.5,
 *     'class' => 'rating-slider'
 * ]);
 * ```
 * 
 * @property int $value The current value of the progress bar (default: 0)
 * @property int $min The minimum value of the range (default: 0)
 * @property int $max The maximum value of the range (default: 100)
 * @property int|float $step The step increment for the range (default: 1)
 * @property string $label The label for the progress bar (default: 'Progress')
 * @property string $class Optional additional CSS classes to add to the progress element
 * @property string $id Optional custom ID for the progress element
 * @property string $name Optional name attribute for the progress element
 * 
 * @view
 */
class Progress extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('value', $this->getProps('value') ?? 0);
        $this->setState('min', $this->getProps('min') ?? 0);
        $this->setState('max', $this->getProps('max') ?? 100);
        $this->setState('step', $this->getProps('step') ?? 1);
        $this->setState('label', $this->getProps('label') ?? 'Progress');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? uniqid('progress-'));
        $this->setState('name', $this->getProps('name') ?? '');   
    }
    
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
            name="{$this->getState('name')}"/>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Progress();
}