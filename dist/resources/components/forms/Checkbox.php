<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * Checkbox Component
 * 
 * A checkbox component that supports labels, descriptions, and various states.
 * Used for boolean options or multiple selections.
 * 
 * @example
 * ```php
 * // Basic checkbox
 * new Checkbox([
 *     'name' => 'terms',
 *     'value' => 'accepted',
 *     'label' => 'I agree to the terms and conditions'
 * ]);
 * 
 * // Checkbox with description
 * new Checkbox([
 *     'name' => 'newsletter',
 *     'value' => 'subscribe',
 *     'label' => 'Subscribe to newsletter',
 *     'description' => 'Receive weekly updates and special offers'
 * ]);
 * 
 * // Checked and disabled checkbox
 * new Checkbox([
 *     'name' => 'feature',
 *     'value' => 'enabled',
 *     'label' => 'Enable feature',
 *     'checked' => true,
 *     'disabled' => true
 * ]);
 * ```
 * 
 * @property string $label The label text for the checkbox
 * @property string $description Optional description text below the label
 * @property bool $checked Whether the checkbox is checked
 * @property bool $disabled Whether the checkbox is disabled
 * @property string $name The name attribute for the checkbox
 * @property string $value The value attribute for the checkbox
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the checkbox
 * 
 * @view
 */
class Checkbox extends Component {
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
     * Render the checkbox component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $checked = $this->getState('checked') ? 'checked' : '';
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $description = $this->getState('description') ? "<span class='checkbox__label-description'>{$this->getState('description')}</span>" : '';

        return <<<HTML
        <label class="lively-component checkbox {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Checkbox">
            <input type="checkbox" name="{$this->getState('name')}" value="{$this->getState('value')}" {$checked} {$disabled} id="{$this->getState('id')}" />
            <span class="checkbox__checkmark"></span>
            <div class="checkbox__label">
                <span class="checkbox__label-text">
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
    return new Checkbox();
}