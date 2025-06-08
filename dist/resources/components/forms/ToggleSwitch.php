<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * @view
 */
class ToggleSwitch extends Component
{
    protected function initState()
    {
        $this->setState('id', $this->getProps('id') ?? uniqid('toggle-switch-'));
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('checked', $this->getProps('checked') ?? false);
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('label', $this->getProps('label') ?? '');
    }

    public function toggle()
    {
        $this->setState('checked', !$this->getState('checked'));
    }

    /**
     * Render the toggle switch component
     * 
     * @return string The rendered HTML
     */
    public function render()
    {
        $id = $this->getState('id');
        $name = $this->getState('name');
        $checked = $this->getState('checked') ? 'checked' : '';
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $label = $this->getState('label');

        return <<<HTML
        <div  class="lively-component toggle-switch" lively:component="{$this->getId()}" role="switch" aria-checked="{$checked}" aria-label="Toggle Switch">
            <input type="checkbox" 
                   class="toggle-switch__input" 
                   id="{$id}"
                   name="{$name}"
                   {$checked}
                   {$disabled}
                   aria-label="{$label}"
                   lively:onclick="toggle">
            <label class="toggle-switch__slider" for="{$id}"></label>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new ToggleSwitch();
}
