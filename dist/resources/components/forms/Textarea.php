<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * Textarea Component
 * 
 * A textarea component that supports labels, error states, and various textarea-specific features.
 * Used for multi-line text input with customizable dimensions and resize behavior.
 * 
 * @example
 * ```php
 * // Basic textarea
 * new Textarea([
 *     'name' => 'description',
 *     'label' => 'Description'
 * ]);
 * 
 * // Textarea with placeholder and rows
 * new Textarea([
 *     'name' => 'bio',
 *     'label' => 'Biography',
 *     'placeholder' => 'Tell us about yourself',
 *     'rows' => 4
 * ]);
 * 
 * // Textarea with error and resize disabled
 * new Textarea([
 *     'name' => 'comment',
 *     'label' => 'Comment',
 *     'error' => 'Comment is required',
 *     'resize' => 'none'
 * ]);
 * 
 * // Textarea with max length
 * new Textarea([
 *     'name' => 'feedback',
 *     'label' => 'Feedback',
 *     'maxlength' => 500,
 *     'showCounter' => true
 * ]);
 * ```
 * 
 * @property string $name The name attribute of the textarea
 * @property string $label Optional label text to display above the textarea
 * @property string $value Optional default value for the textarea
 * @property string $placeholder Optional placeholder text
 * @property int $rows Number of visible text lines
 * @property int $cols Number of visible text columns
 * @property string $resize Resize behavior (none, vertical, horizontal, both)
 * @property int $maxlength Maximum number of characters allowed
 * @property bool $showCounter Whether to show character counter
 * @property bool $disabled Whether the textarea is disabled
 * @property bool $required Whether the textarea is required
 * @property string $error Optional error message to display
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the textarea
 * 
 * @view
 */
class Textarea extends Component {
    protected function initState() {
        $this->setState('label', $this->getProps('label') ?? '');
        $this->setState('name', $this->getProps('name') ?? '');
        $this->setState('value', $this->getProps('value') ?? '');
        $this->setState('placeholder', $this->getProps('placeholder') ?? '');
        $this->setState('rows', $this->getProps('rows') ?? 3);
        $this->setState('cols', $this->getProps('cols') ?? 50);
        $this->setState('resize', $this->getProps('resize') ?? 'vertical');
        $this->setState('maxlength', $this->getProps('maxlength') ?? null);
        $this->setState('showCounter', $this->getProps('showCounter') ?? false);
        $this->setState('disabled', $this->getProps('disabled') ?? false);
        $this->setState('required', $this->getProps('required') ?? false);
        $this->setState('error', $this->getProps('error') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
    }

    /**
     * Get the counter for the textarea
     * 
     * @return string The rendered HTML
     */
    private function getCounter() {
        if ($this->getState('showCounter') && $this->getState('maxlength')) {
            $currentLength = strlen($this->getState('value'));
            $maxLength = $this->getState('maxlength');
            return "<span class='textarea__counter'>{$currentLength}/{$maxLength}</span>";
        }
        return '';
    }

    /**
     * Update the textarea value and the error message
     * 
     * @param string $value The new value of the textarea
     * @return string The rendered textarea HTML
     */
    public function update($value) {
        if ($this->getState('maxlength') && strlen($value) > $this->getState('maxlength')) {
            $this->setState('error', 'Max length exceeded');
        } else {
            $this->setState('error', '');
        }

        $this->setState('value', $value);

        return $this->render();
    }
    
    /**
     * Render the textarea component
     * 
     * @return string The rendered textarea HTML
     */
    public function render() {
        $disabled = $this->getState('disabled') ? 'disabled' : '';
        $required = $this->getState('required') ? 'required' : '';
        $errorClass = $this->getState('error') ? 'textarea--error' : '';
        $id = $this->getState('id') ? $this->getState('id') : $this->getState('name');
        $label = $this->getState('label') ? "<label class='textarea__label' for='{$id}'>{$this->getState('label')}</label>" : '';
        $error = $this->getState('error') ? "<span class='textarea__error'>{$this->getState('error')}</span>" : '';
        $counter = $this->getCounter();
        $maxlength = $this->getState('maxlength') ? "maxlength='{$this->getState('maxlength')}'" : '';

        return <<<HTML
        <div class="lively-component textarea {$errorClass} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Textarea">
            {$label}
            <div class="textarea__field-container">
                <textarea
                    name="{$this->getState('name')}"
                    id="{$id}"
                    rows="{$this->getState('rows')}"
                    cols="{$this->getState('cols')}"
                    placeholder="{$this->getState('placeholder')}"
                    class="textarea__field"
                    {$disabled}
                    {$required}
                    {$maxlength}
                    lively:oninput="update"
                >{$this->getState('value')}</textarea>
                {$counter}
            </div>
            {$error}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Textarea();
} 