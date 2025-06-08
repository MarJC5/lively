<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * Radio Component
 * 
 * A radio button component that supports labels, descriptions, and various states.
 * Used for single-select options within a group of radio buttons.
 * 
 * @example
 * ```php
 * // Basic radio button
 * new Radio([
 *     'name' => 'gender',
 *     'value' => 'male',
 *     'label' => 'Male'
 * ]);
 * 
 * // Radio with description
 * new Radio([
 *     'name' => 'subscription',
 *     'value' => 'premium',
 *     'label' => 'Premium Plan',
 *     'description' => 'Includes all features and priority support'
 * ]);
 * 
 * // Checked and disabled radio
 * new Radio([
 *     'name' => 'status',
 *     'value' => 'active',
 *     'label' => 'Active',
 *     'checked' => true,
 *     'disabled' => true
 * ]);
 * ```
 * 
 * @property string $label The label text for the radio button
 * @property string $description Optional description text below the label
 * @property bool $checked Whether the radio button is checked
 * @property bool $disabled Whether the radio button is disabled
 * @property string $name The name attribute for the radio button group
 * @property string $value The value attribute for the radio button
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the radio button
 * 
 * @view
 */
class Radio extends Component {
    protected function initState() {
        $this->setState('label', $this->getProps('label') ?? '');
        $this->setState('description', $this->getProps('description') ?? '');
        $this->setState('checked', $this->getProps('checked') ?? false);
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('value', $this->getProps('value') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
    }
    
    /**
     * Render the radio component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $checked = $this->getState('checked') ? 'checked' : '';
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $description = $this->getState('description') ? "<span class='radio__label-description'>{$this->getState('description')}</span>" : '';
        
        return <<<HTML
        <label class="lively-component radio {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Radio">
            <input type="radio" name="{$this->getState('name')}" value="{$this->getState('value')}" {$checked} {$disabled} id="{$this->getState('id')}" />
            <span class="radio__checkmark"></span>
            <div class="radio__label">
                <span class="radio__label-text">
                    {$this->getState('label')}
                </span>
                {$description}
            </div>
        </label>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Radio();
}