<?php

namespace Lively\Resources\Components\Layouts;

use Lively\Core\View\Component;

/**
 * Container Component
 * 
 * A layout component for creating responsive containers with predefined size options.
 * Provides a consistent way to constrain content width and maintain responsive layouts
 * across different screen sizes.
 * 
 * @example
 * ```php
 * // Basic usage with default full width
 * new Container([
 *     'children' => '<p>Full width content</p>'
 * ]);
 * 
 * // Usage with medium size
 * new Container([
 *     'size' => 'md',
 *     'children' => '<p>Medium width content</p>'
 * ]);
 * 
 * // Usage with custom class and extra large size
 * new Container([
 *     'size' => 'xl',
 *     'class' => 'container-custom',
 *     'children' => '<p>Extra large width content</p>'
 * ]);
 * ```
 * 
 * @property string $id Optional custom ID for the container element (default: auto-generated)
 * @property string $class Optional additional CSS classes to add to the container element
 * @property string $size The size variant of the container (default: 'full')
 *              Available sizes:
 *              - xs: Extra small
 *              - sm: Small
 *              - md: Medium
 *              - lg: Large
 *              - xl: Extra large
 *              - 2xl: 2x Extra large
 *              - 3xl: 3x Extra large
 *              - 4xl: 4x Extra large
 *              - 5xl: 5x Extra large
 *              - 6xl: 6x Extra large
 *              - 7xl: 7x Extra large
 *              - full: Full width (default)
 * @property string $children The content to be rendered inside the container
 * 
 * @method string getSizeClass() Returns the appropriate CSS class based on the size property
 * 
 * @view
 */
class Container extends Component {
    protected function initState() {
        $this->setState('id', $this->getProps('id') ?? uniqid('container-'));
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('size', $this->getProps('size') ?? 'full');
        $this->setState('children', $this->getProps('children'));
    }

    /**
     * Get the size class for the container.
     *
     * @return string
     */
    protected function getSizeClass() {
        return match ($this->getState('size')) {
            'xs' => 'container--xs',
            'sm' => 'container--sm',
            'md' => 'container--md',
            'lg' => 'container--lg',
            'xl' => 'container--xl',
            '2xl' => 'container--2xl',
            '3xl' => 'container--3xl',
            '4xl' => 'container--4xl',
            '5xl' => 'container--5xl',
            '6xl' => 'container--6xl',
            '7xl' => 'container--7xl',
            default => '',
        };
    }

    /**
     * Render the container component.
     *
     * @return string
     */
    public function render() {
        return <<<HTML
        <div id="{$this->getState('id')}" class="lively-component container {$this->getSizeClass()} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Container">
            {$this->getState('children')}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Container();
}