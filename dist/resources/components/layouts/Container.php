<?php

namespace Lively\Resources\Components\Layouts;

use Lively\Core\View\Component;

/**
 * @view
 */
class Container extends Component {
    protected function initState() {
        $this->setState('id', $this->getProps('id'));
        $this->setState('class', $this->getProps('class'));
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