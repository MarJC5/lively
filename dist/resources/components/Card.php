<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * @view
 */
class Card extends Component {
    protected function initState() {
        $this->setState('id', $this->getProps('id'));
        $this->setState('class', $this->getProps('class'));
        $this->setState('header', $this->getProps('header'));
        $this->setState('children', $this->getProps('children'));
        $this->setState('footer', $this->getProps('footer'));
    }
    
    public function render() {
        $header = $this->getState('header') ? '<div class="card__header">' . $this->getState('header') . '</div>' : '';
        $footer = $this->getState('footer') ? '<div class="card__footer">' . $this->getState('footer') . '</div>' : '';

        return <<<HTML
        <article id="{$this->getState('id')}" class="lively-component card {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Card">
            {$header}
            {$this->getState('children')}
            {$footer}
        </article>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Card();
}