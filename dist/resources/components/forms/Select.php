<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * Select Component
 * 
 * A dropdown select component that supports labels, options, and various states.
 * Used for single-select dropdown menus with optional grouping and search functionality.
 * 
 * @example
 * ```php
 * // Basic select with options
 * new Select([
 *     'name' => 'country',
 *     'label' => 'Select Country',
 *     'options' => [
 *         ['value' => 'us', 'label' => 'United States'],
 *         ['value' => 'ca', 'label' => 'Canada'],
 *         ['value' => 'uk', 'label' => 'United Kingdom']
 *     ]
 * ]);
 * 
 * // Select with placeholder and default value
 * new Select([
 *     'name' => 'category',
 *     'label' => 'Product Category',
 *     'placeholder' => 'Choose a category',
 *     'value' => 'electronics',
 *     'options' => [
 *         ['value' => 'electronics', 'label' => 'Electronics'],
 *         ['value' => 'clothing', 'label' => 'Clothing'],
 *         ['value' => 'books', 'label' => 'Books']
 *     ]
 * ]);
 * 
 * // Disabled select with error
 * new Select([
 *     'name' => 'status',
 *     'label' => 'Status',
 *     'disabled' => true,
 *     'error' => 'This field is required',
 *     'options' => [
 *         ['value' => 'active', 'label' => 'Active'],
 *         ['value' => 'inactive', 'label' => 'Inactive']
 *     ]
 * ]);
 * ```
 * 
 * @property string $label The label text for the select
 * @property string $name The name attribute for the select
 * @property string $value The currently selected value
 * @property string $placeholder Optional placeholder text
 * @property array $options Array of options with value and label pairs
 * @property bool $disabled Whether the select is disabled
 * @property bool $required Whether the select is required
 * @property string $error Optional error message to display
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the select
 * 
 * @view
 */
class Select extends Component {
    protected function initState() {
        $this->setState('label', $this->getProps('label') ?? '');
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('value', $this->getProps('value') ?? '');
        $this->setState('placeholder', $this->getProps('placeholder') ?? '');
        $this->setState('options', $this->getProps('options') ?? []);
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('required', $this->getProps('required') ?? false);
        $this->setState('error', $this->getProps('error') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
    }

    /**
     * Render the options for the select
     * 
     * @return string The rendered HTML
     */
    private function renderOptions() {
        $options = '';
        $placeholder = $this->getState('placeholder');
        
        if ($placeholder) {
            $options .= "<option value='' disabled " . (!$this->getState('value') ? 'selected' : '') . ">{$placeholder}</option>";
        }

        foreach ($this->getState('options') as $option) {
            $value = $option['value'] ?? '';
            $label = $option['label'] ?? $value;
            $selected = $value === $this->getState('value') ? 'selected' : '';
            $options .= "<option value='{$value}' {$selected}>{$label}</option>";
        }

        return $options;
    }
    
    /**
     * Render the select component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $required = $this->getState('required') ? 'required' : '';
        $errorClass = $this->getState('error') ? 'select--error' : '';
        $id = $this->getState('id') ? $this->getState('id') : $this->getState('name');
        $label = $this->getState('label') ? "<label class='select__label' for='{$id}'>{$this->getState('label')}</label>" : '';
        $error = $this->getState('error') ? "<span class='select__error'>{$this->getState('error')}</span>" : '';

        return <<<HTML
        <div class="lively-component select {$errorClass} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Select">
            {$label}
            <div class="select__field-container">
                <select
                    name="{$this->getState('name')}"
                    id="{$id}"
                    class="select__field"
                    {$disabled}
                    {$required}
                >
                    {$this->renderOptions()}
                </select>
                <span class="select__arrow"></span>
            </div>
            {$error}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Select();
}