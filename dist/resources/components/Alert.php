<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;
use Lively\Resources\Components\Icon;
use Lively\Resources\Components\Avatar;

/**
 * Alert Component
 * 
 * A component for displaying alert messages with optional icon or avatar, and titles.
 * Can be used for notifications, warnings, success messages, or any type of alert.
 * Note: Use either an icon or an avatar, but not both.
 * 
 * @example
 * ```php
 * // Basic usage with message
 * new Alert(['message' => 'Your changes have been saved']);
 * 
 * // Usage with title and type
 * new Alert([
 *     'title' => 'Success',
 *     'message' => 'Your profile has been updated',
 *     'type' => 'success'
 * ]);
 * 
 * // Usage with icon
 * new Alert([
 *     'title' => 'New Message',
 *     'message' => 'You have received a new message',
 *     'icon' => 'message'
 * ]);
 * 
 * // Usage with avatar
 * new Alert([
 *     'title' => 'New Message',
 *     'message' => 'You have received a new message',
 *     'avatar' => $userId
 * ]);
 * 
 * // Usage with custom class
 * new Alert([
 *     'title' => 'Warning',
 *     'message' => 'Please review your changes',
 *     'type' => 'warning',
 *     'class' => 'my-custom-alert'
 * ]);
 * ```
 * 
 * @property string $type The type of alert to display (info, success, warning, danger)
 * @property string $message The main message text to display
 * @property string $title Optional title text to display above the message
 * @property string $class Optional additional CSS classes to add to the alert element
 * @property string $id Optional ID attribute for the alert element
 * @property string $icon Optional icon name to display (without .svg extension). Note: Use either icon or avatar, not both.
 * @property int $avatar Optional user ID to display an avatar. Note: Use either icon or avatar, not both.
 * @property bool $isOpen Optional boolean to control if the alert is open or closed.
 * 
 * @view
 */
class Alert extends Component
{
    protected function initState()
    {
        $this->setState('type', $this->getProps('type') ?? 'info');
        $this->setState('message', $this->getProps('message') ?? '');
        $this->setState('title', $this->getProps('title') ?? '');
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? '');
        $this->setState('icon', $this->getProps('icon') ?? '');
        $this->setState('avatar', $this->getProps('avatar') ?? '');
        $this->setState('isOpen', $this->getProps('isOpen') ?? true);
    }

    /**
     * Get the icon
     * 
     * @return string The rendered HTML
     */
    private function getIcon()
    {
        if ($this->getState('icon')) {
            return new Icon(array_merge($this->getState('icon'), [ 'width' => 24, 'height' => 24 ]));
        }
    }

    /**
     * Get the avatar
     * 
     * @return string The rendered HTML
     */
    private function getAvatar()
    {
        if ($this->getState('avatar')) {
            return new Avatar(array_merge($this->getState('avatar'), [ 'size' => 50 ]));
        }
    }

    /**
     * Get the title
     * 
     * @return string The rendered HTML
     */
    private function getTitle()
    {
        if ($this->getState('title')) {
            return <<<HTML
                <h3 class="alert__title">
                    {$this->getState('title')}
                </h3>
            HTML;
        }
    }

    /**
     * Get the message
     * 
     * @return string The rendered HTML
     */
    private function getMessage()
    {
        if ($this->getState('message')) {
            return <<<HTML
                <p class="alert__message">{$this->getState('message')}</p>
            HTML;
        }
    }

    /**
     * Close the alert
     */
    public function close()
    {
        $this->setState('isOpen', false);
    }

    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render()
    {
        if (!$this->getState('isOpen')) {
            return '';
        }

        $closeHtml = new Icon([ 'name' => 'cancel' ]);
    
        return <<<HTML
            <div id="{$this->getState('id')}" class="lively-component alert alert-{$this->getState('type')} {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Alert">
                <button class="alert__close" lively:onclick="close" aria-label="Close" type="button">
                    {$closeHtml}
                </button>
                <div class="alert__header">
                    {$this->getAvatar()}
                    {$this->getIcon()}
                    {$this->getTitle()}
                </div>
                <div class="alert__content">
                    {$this->getMessage()}
                </div>
            </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Alert();
}
