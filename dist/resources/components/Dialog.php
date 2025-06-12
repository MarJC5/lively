<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * @view
 */
class Dialog extends Component
{
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState()
    {
        $this->setState('isOpen', $this->getProps('isOpen', false));
        $this->setState('title', $this->getProps('title') ?? 'Dialog');
        $this->setState('content', $this->getProps('content') ?? '');
        $this->setState('footer', $this->getProps('footer') ?? '');
        $this->setState('trigger', $this->getProps('trigger') ?? 'Open');
    }

    /**
     * Toggle the open state of the dialog
     * 
     * @return void
     */
    public function toggleOpen()
    {
        $this->setState('isOpen', !$this->getState('isOpen'));
    }

    /**
     * Render the component
     * 
     * @return string The rendered HTML
     */
    public function render()
    {
        $show = $this->getState('isOpen') ? '' : 'hidden';
        $showTrigger = $this->getState('isOpen') ? 'hidden' : '';
        $closeIcon = new Icon(['name' => 'cancel', 'width' => 16]);

        return <<<HTML
        <div class="lively-component dialog" lively:component="{$this->getId()}" role="region" aria-label="Dialog">
            <button class="btn btn--trigger {$showTrigger}" lively:onclick="toggleOpen">
                {$this->getState('trigger')}
            </button>
            <div class="dialog__wrapper {$show}" lively:onclick="toggleOpen">
                <div class="dialog__modal">
                    <div class="dialog__header">
                        <h2 class="dialog__title">{$this->getState('title')}</h2>
                        <button class="dialog__close" lively:onclick="toggleOpen">
                            {$closeIcon}
                        </button>
                    </div>
                    <div class="dialog__content">
                        {$this->getState('content')}
                    </div>
                    <div class="dialog__footer">
                        {$this->getState('footer')}
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Dialog();
}
