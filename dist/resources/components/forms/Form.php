<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;
use Lively\Resources\Components\Icon;

/**
 * Form Component
 * 
 * A component for creating HTML forms with built-in CSRF protection and flexible submission options.
 * Integrates with WordPress functions for security and URL handling. Requires WordPress environment
 * for full functionality (home_url() and wp_create_nonce()).
 * 
 * @example
 * ```php
 * // Basic usage with default settings
 * new Form([
 *     'children' => '<input type="text" name="search" placeholder="Search...">'
 * ]);
 * 
 * // Usage with custom method and action
 * new Form([
 *     'method' => 'get',
 *     'action' => '/search',
 *     'children' => '<input type="text" name="query">'
 * ]);
 * 
 * // Usage with CSRF protection and custom submit button
 * new Form([
 *     'csrf_token_name' => 'my_form_nonce',
 *     'submit' => 'Submit Form',
 *     'children' => '<input type="text" name="data">'
 * ]);
 * ```
 * 
 * @property string $id The ID of the form element (default: 'form')
 * @property string $class Optional additional CSS classes to add to the form element
 * @property string $method The HTTP method to use (default: 'post', accepts: 'get' or 'post')
 * @property string $action The form submission URL (default: WordPress home_url())
 * @property string|Icon $submit Custom submit button content or Icon component (default: search icon)
 * @property string $csrf_token_name Optional name for the CSRF token field
 * 
 * @method string getCsrfToken() Generates a WordPress nonce for CSRF protection
 * 
 * @requires WordPress Functions:
 * - home_url() - For default form action URL
 * - wp_create_nonce() - For CSRF token generation
 * 
 * @view
 */
class Form extends Component {
    protected function initState() {
        $this->setState('id', $this->getProps('id') ?? 'form');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('method', $this->getProps('method') ?? 'post');
        $this->setState('action', $this->getProps('action') ?? home_url());
        $this->setState('submit', $this->getProps('submit') ?? '');
        $this->setState('csrf_token_name', $this->getProps('csrf_token_name') ?? '');
    }

    /**
     * Get the CSRF token
     * 
     * @return string The CSRF token
     */
    public function getCsrfToken() {
        return wp_create_nonce($this->getState('csrf_token_name'));
    }
    
    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $method = in_array($this->getState('method'), ['get', 'post']) ? $this->getState('method') : 'post';
        $csrf = $this->getState('csrf_token_name') ? '<input type="hidden" name="'.$this->getState('csrf_token_name').'" value="'.$this->getCsrfToken().'">' : '';
        $button = $this->getState('submit') ? $this->getState('submit') : new Icon([ 'name' => 'search', 'width' => 20, 'height' => 20 ]);
        
        return <<<HTML
        <form id="{$this->getState('id')}" class="lively-component form {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Form" method="{$method}" action="{$this->getState('action')}">
            {$csrf}
            {$this->getProps('children')}
            <button type="submit">{$button}</button>
        </form>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Form();
}