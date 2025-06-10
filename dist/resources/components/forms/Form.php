<?php

namespace Lively\Resources\Components\Forms;

use Lively\Core\View\Component;
use Lively\Resources\Components\Icon;

/**
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