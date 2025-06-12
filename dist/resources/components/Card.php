<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * Card Component
 * 
 * A component for displaying content in a card layout with optional header and footer sections.
 * Can be used for displaying articles, product information, or any content that needs to be
 * visually contained in a card format.
 * 
 * @example
 * ```php
 * // Basic usage with content
 * new Card([
 *     'children' => '<p>Card content goes here</p>'
 * ]);
 * 
 * // Usage with header and footer
 * new Card([
 *     'header' => '<h2>Card Title</h2>',
 *     'children' => '<p>Main content here</p>',
 *     'footer' => '<button>Action</button>'
 * ]);
 * 
 * // Usage with custom class
 * new Card([
 *     'class' => 'card-highlight',
 *     'header' => '<h3>Featured</h3>',
 *     'children' => '<p>Featured content</p>'
 * ]);
 * ```
 * 
 * @property string $id Optional custom ID for the card element (default: auto-generated)
 * @property string $class Optional additional CSS classes to add to the card element
 * @property string $header Optional content to display in the card header
 * @property string $children The main content of the card
 * @property string $footer Optional content to display in the card footer
 * 
 * @view
 */
class Card extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('id', $this->getProps('id') ?? uniqid('card-'));
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('header', $this->getProps('header'));
        $this->setState('children', $this->getProps('children'));
        $this->setState('footer', $this->getProps('footer'));
    }
    
    /**
     * Render the card component
     * 
     * @return string The rendered HTML
     */
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