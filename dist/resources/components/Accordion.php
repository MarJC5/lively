<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * Accordion Component
 * 
 * A component for displaying collapsible content sections that can be expanded and collapsed.
 * Supports both single-item and multiple-item expansion modes. Each item has a title and content
 * section that can be toggled independently.
 * 
 * @example
 * ```php
 * // Basic usage with single item open at a time
 * new Accordion([
 *     'items' => [
 *         [
 *             'title' => 'Section 1',
 *             'content' => '<p>Content for section 1</p>'
 *         ],
 *         [
 *             'title' => 'Section 2',
 *             'content' => '<p>Content for section 2</p>'
 *         ]
 *     ]
 * ]);
 * 
 * // Usage with multiple items allowed to be open
 * new Accordion([
 *     'allowMultiple' => true,
 *     'items' => [
 *         [
 *             'title' => 'FAQ 1',
 *             'content' => '<p>Answer to question 1</p>'
 *         ],
 *         [
 *             'title' => 'FAQ 2',
 *             'content' => '<p>Answer to question 2</p>'
 *         ]
 *     ]
 * ]);
 * 
 * // Usage with custom class
 * new Accordion([
 *     'class' => 'faq-accordion',
 *     'items' => [
 *         [
 *             'title' => 'Custom Section',
 *             'content' => '<p>Custom styled content</p>'
 *         ]
 *     ]
 * ]);
 * ```
 * 
 * @property string $id Optional custom ID for the accordion element (default: auto-generated)
 * @property string $class Optional additional CSS classes to add to the accordion element
 * @property array $items Array of items to display in the accordion. Each item should have:
 *                      - title: string - The title of the accordion section
 *                      - content: string - The HTML content to display when expanded
 * @property bool $allowMultiple Whether multiple items can be open at once (default: false)
 * 
 * @method void toggleOpenItem(string $itemTitle) Toggles the open state of an accordion item
 * 
 * @view
 */
class Accordion extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('openItems', []);
        $this->setState('id', $this->getProps('id') ?? uniqid('accordion-'));
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('items', $this->getProps('items') ?? []);
        $this->setState('allowMultiple', $this->getProps('allowMultiple') ?? false);
    }

    /**
     * Toggle the open state of an accordion item
     * 
     * @param string $itemTitle The title of the item to toggle
     */
    public function toggleOpenItem($itemTitle) {
        $openItems = $this->getState('openItems');
        
        if ($this->getState('allowMultiple')) {
            // Toggle the item in the array
            if (in_array($itemTitle, $openItems)) {
                $openItems = array_diff($openItems, [$itemTitle]);
            } else {
                $openItems[] = $itemTitle;
            }
        } else {
            // Single item open at a time
            $openItems = in_array($itemTitle, $openItems) ? [] : [$itemTitle];
        }
        
        $this->setState('openItems', $openItems);
    }

    /**
     * Get the items for the accordion
     * 
     * @return string The rendered HTML
     */
    protected function getItems() {
        $items = array_map(function($item, $index) {
            $isOpen = in_array($item['title'], $this->getState('openItems'));
            $itemId = "accordion-item-{$index}";
            $escapedTitle = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
            
            return <<<HTML
            <div class="accordion__item" data-open="{$isOpen}">
                <h3 class="accordion__title" id="{$itemId}-title" lively:onclick="toggleOpenItem('{$escapedTitle}')">
                    {$escapedTitle}
                    <span class="accordion__title__arrow"></span>
                </h3>
                <div class="accordion__content" id="{$itemId}-content" role="region" aria-labelledby="{$itemId}-title">
                    {$item['content']}
                </div>
            </div>
            HTML;
        }, $this->getState('items'), array_keys($this->getState('items')));

        return implode('', $items);
    }
    
    /**
     * Render the accordion component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        return <<<HTML
        <div class="lively-component accordion {$this->getState('class')}" 
             lively:component="{$this->getId()}" 
             role="region" 
             aria-label="Accordion"
             data-allow-multiple="{$this->getState('allowMultiple')}">
            {$this->getItems()}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Accordion();
}