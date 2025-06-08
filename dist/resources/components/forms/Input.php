<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * Input Component
 * 
 * A versatile form input component that supports various input types, labels, prefixes, suffixes,
 * and error states. Can be used for text, email, password, number, tel, and url inputs.
 * 
 * @example
 * ```php
 * // Basic text input
 * new Input(['name' => 'username', 'label' => 'Username']);
 * 
 * // Email input with placeholder
 * new Input([
 *     'type' => 'email',
 *     'name' => 'email',
 *     'label' => 'Email Address',
 *     'placeholder' => 'Enter your email'
 * ]);
 * 
 * // Password input with error
 * new Input([
 *     'type' => 'password',
 *     'name' => 'password',
 *     'label' => 'Password',
 *     'error' => 'Password is required'
 * ]);
 * 
 * // Input with prefix and suffix
 * new Input([
 *     'name' => 'price',
 *     'label' => 'Price',
 *     'prefix' => '$',
 *     'suffix' => '.00'
 * ]);
 * ```
 * 
 * @property string $type The type of input (text, email, password, number, tel, url)
 * @property string $name The name attribute of the input field
 * @property string $label Optional label text to display above the input
 * @property string $value Optional default value for the input
 * @property string $placeholder Optional placeholder text
 * @property bool $disabled Whether the input is disabled
 * @property bool $required Whether the input is required
 * @property string $autocomplete The autocomplete attribute value
 * @property bool $autofocus Whether the input should be focused on page load
 * @property string $error Optional error message to display
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the input
 * @property string $prefix Optional prefix text to display before the input
 * @property string $suffix Optional suffix text to display after the input
 * 
 * @view
 */
class Input extends Component {
    protected function initState() {
        $this->setState('label', $this->getProps('label') ?? '');
        $this->setState('type', $this->getProps('type') ?? 'text');
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('value', $this->getProps('value') ?? '');
        $this->setState('placeholder', $this->getProps('placeholder') ?? '');
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('required', $this->getProps('required') ?? false);
        $this->setState('autocomplete', $this->getProps('autocomplete') ?? 'off');
        $this->setState('autofocus', $this->getProps('autofocus') ?? false);
        $this->setState('error', $this->getProps('error') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
        $this->setState('prefix', $this->getProps('prefix') ?? '');
        $this->setState('suffix', $this->getProps('suffix') ?? '');
    }

    /**
     * Check the type of the input
     * 
     * @return string The type of the input
     */
    private function checkType() {
        $types = [
            'email',
            'password',
            'text',
            'number',
            'tel',
            'url',
        ];
        if (in_array($this->getState('type'), $types)) {
            return $this->getState('type');
        }
        return 'text';
    }
    
    /**
     * Render the input component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $required = $this->getState('required') ? 'required' : '';
        $errorClass = $this->getState('error') ? 'input--error' : '';
        $id = $this->getState('id') ? $this->getState('id') : $this->getState('name');
        $label = $this->getState('label') ? "<label class='input__label' for='{$id}'>{$this->getState('label')}</label>" : '';
        $error = $this->getState('error') ? "<span class='input__error'>{$this->getState('error')}</span>" : '';
        $prefix = $this->getState('prefix') ? "<span class='input__prefix'>{$this->getState('prefix')}</span>" : '';
        $suffix = $this->getState('suffix') ? "<span class='input__suffix'>{$this->getState('suffix')}</span>" : '';

        return <<<HTML
        <div class="lively-component input {$errorClass} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Input">
            {$label}
            <div class="input__field-container">
                {$prefix}
                <input
                    type="{$this->checkType()}"
                    name="{$this->getState('name')}"
                    id="{$id}"
                    value="{$this->getState('value')}"
                    placeholder="{$this->getState('placeholder')}"
                    class="input__field"
                    {$disabled}
                    {$required}
                    autocomplete="{$this->getState('autocomplete')}"
                    autofocus="{$this->getState('autofocus')}"
                />
                {$suffix}
            </div>
            {$error}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Input();
}