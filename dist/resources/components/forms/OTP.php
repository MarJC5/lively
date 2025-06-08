<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;

/**
 * OTP Component
 * 
 * A One-Time Password input component that supports auto-focus and secure input.
 * Used for verification codes, PINs, or any numeric sequence input.
 * 
 * @example
 * ```php
 * // Basic OTP with 6 digits
 * new OTP([
 *     'length' => 6,
 *     'value' => ['1', '2', '3', '4', '5', '6']
 * ]);
 * 
 * // Secure OTP with custom length
 * new OTP([
 *     'length' => 4,
 *     'secure' => true,
 *     'value' => ['', '', '', '']
 * ]);
 * 
 * // OTP with error state
 * new OTP([
 *     'length' => 6,
 *     'error' => 'Invalid verification code',
 *     'class' => 'otp--error'
 * ]);
 * ```
 * 
 * @property int $length The number of input fields (default: 6)
 * @property array $value Array of values for each input field
 * @property bool $secure Whether to use password type for secure input
 * @property string $error Optional error message
 * @property string $class Optional additional CSS classes
 * @property string $id Optional ID attribute for the OTP container
 * 
 * @view
 */
class OTP extends Component {
    protected function initState() {
        $this->setState('length', $this->getProps('length') ?? 6);
        $this->setState('value', $this->getProps('value') ?? array_fill(0, 6, ''));
        $this->setState('secure', $this->getProps('secure') ?? false);
        $this->setState('error', $this->getProps('error') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
    }
    
    /**
     * Render the OTP component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $length = $this->getState('length');
        $value = $this->getState('value');
        $secure = $this->getState('secure') ? 'type="password"' : 'type="text"';
        $inputs = '';
        for ($i = 0; $i < $length; $i++) {
            $inputs .= <<<HTML
            <input 
                {$secure}
                maxlength="1" 
                class="otp__input" 
                data-index="{$i}"
                value="{$value[$i]}"
                oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length === 1) { this.nextElementSibling?.focus(); }"
                onkeydown="if(event.key === 'Backspace' && !this.value) { this.previousElementSibling?.focus(); }"
            >
            HTML;
        }
        
        return <<<HTML
        <div id="{$this->getState('id')}" class="lively-component otp {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="OTP">
            <div class="otp__container">
                {$inputs}
            </div>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new OTP();
}