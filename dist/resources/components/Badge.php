<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;
use Lively\Resources\Components\Icon;

/**
 * Badge Component
 * 
 * A component for displaying small labels or status indicators with optional icons.
 * Can be used for notifications, status indicators, or small labels.
 * 
 * @example
 * ```php
 * // Basic usage with label
 * new Badge(['label' => 'New']);
 * 
 * // Usage with icon
 * new Badge([
 *     'label' => 'Success',
 *     'icon' => 'check'
 * ]);
 * 
 * // Usage with custom class
 * new Badge([
 *     'label' => 'Warning',
 *     'icon' => 'alert',
 *     'class' => 'badge-warning'
 * ]);
 * ```
 * 
 * @property string $label The text to display in the badge
 * @property string $icon Optional icon name to display before the label (without .svg extension)
 * @property string $class Optional additional CSS classes to add to the badge element
 * @property string $type The type of badge to display (default, success, warning, danger)
 * 
 * @view
 */
class Badge extends Component {
    protected function initState() {
        $this->setState('label', $this->getProps('label') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('icon', $this->getProps('icon') ?? '');
        $this->setState('type', $this->getProps('type') ?? 'default');
    }
    
    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $iconHtml = '';
        if (!empty($this->getState('icon'))) {
            $icon = new Icon($this->getState('icon'));
            $iconHtml = $icon->render();
        }
        
        return <<<HTML
        <div class="lively-component badge badge-{$this->getState('type')} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Badge">
            <span class="badge__label">{$this->getState('label')}</span>
            {$iconHtml}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Badge();
}