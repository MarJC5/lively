<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * ToggleSwitch Component
 * 
 * A toggle switch component that provides a modern alternative to checkboxes.
 * Supports labels, descriptions, and various states. Used for boolean options
 * or settings that need to be toggled on/off.
 * 
 * @example
 * ```php
 * // Basic toggle switch
 * new ToggleSwitch([
 *     'name' => 'notifications',
 *     'label' => 'Enable notifications',
 *     'description' => 'Receive push notifications for updates'
 * ]);
 * 
 * // Checked and disabled toggle switch
 * new ToggleSwitch([
 *     'name' => 'dark-mode',
 *     'label' => 'Dark Mode',
 *     'description' => 'Switch to dark theme',
 *     'checked' => true,
 *     'disabled' => true
 * ]);
 * ```
 * 
 * @property string $id Unique identifier for the toggle switch
 * @property string $name The name attribute for the toggle switch
 * @property string $description Optional description text below the label
 * @property bool $checked Whether the toggle switch is checked
 * @property bool $disabled Whether the toggle switch is disabled
 * @property string $label The label text for the toggle switch
 * 
 * @view
 */
class ToggleSwitch extends Component
{
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState()
    {
        $this->setState('id', $this->getProps('id') ?? uniqid('toggle-switch-'));
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('description', $this->getProps('description') ?? '');
        $this->setState('checked', $this->getProps('checked') ?? false);
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('label', $this->getProps('label') ?? '');
    }

    /**
     * Toggle the checked state of the switch
     */
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
        $description = $this->getState('description');

        return <<<HTML
        <div class="lively-component toggle-switch" lively:component="{$this->getId()}" role="switch" aria-checked="{$checked}" aria-label="Toggle Switch">
            <div class="toggle-switch__wrapper">
                <label for="{$id}">
                    <input type="checkbox" 
                           class="toggle-switch__input" 
                           id="{$id}"
                           name="{$name}"
                           {$checked}
                           {$disabled}
                           aria-label="{$label}"
                           lively:onclick="toggle">
                    <label class="toggle-switch__slider" for="{$id}"></label>
                </label>
            </div>
            <div class="toggle-switch__content">
                <h3 class="toggle-switch__label">{$label}</h3>
                <p class="toggle-switch__description">{$description}</p>
            </div>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new ToggleSwitch();
}
