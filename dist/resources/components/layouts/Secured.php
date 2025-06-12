<?php

namespace Lively\Resources\Components\Layouts;

use Lively\Core\View\Component;

/**
 * Secured Component
 * 
 * A layout component that conditionally renders its children only when a user is logged in.
 * This component acts as a security wrapper, ensuring content is only visible to authenticated users.
 * 
 * @example
 * ```php
 * // Basic usage - content only visible to logged in users
 * new Secured([
 *     'children' => '<div>Protected content here</div>'
 * ]);
 * 
 * // With dynamic content
 * new Secured([
 *     'children' => $someDynamicContent
 * ]);
 * ```
 * 
 * @property string $children The content to be rendered when user is logged in
 * 
 * @view
 */
class Secured extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('children', $this->getProps('children') ?? '');
    }

    /**
     * Check if the user is logged in
     * 
     * @return bool
     */
    protected function isUserLoggedIn() {
        if (is_user_logged_in()) {
            return true;
        }

        return false;
    }
    
    /**
     * Render the component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $content = $this->isUserLoggedIn() ? $this->getState('children') : '';
        
        return <<<HTML
        {$content}
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Secured();
}