<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;
use Lively\Resources\Components\Image;

/**
 * Avatar Component
 * 
 * A specialized image component for displaying user avatars or profile pictures.
 * Extends the Image component with avatar-specific features like circular shape and default sizes.
 * 
 * @example
 * ```php
 * // Basic usage with media ID
 * new Avatar(['media' => $mediaId]);
 * 
 * // Usage with custom size
 * new Avatar([
 *     'media' => $mediaId,
 *     'size' => 100 // size in pixels
 * ]);
 * 
 * // Usage with custom class
 * new Avatar([
 *     'media' => $mediaId,
 *     'class' => 'my-custom-class'
 * ]);
 * ```
 * 
 * @property int $media The WordPress media attachment ID
 * @property int $size Optional custom size in pixels (default: 64)
 * @property string $class Optional additional CSS classes to add to the figure element
 * 
 * @view
 */
class Avatar extends Component {
    protected function initState() {
        $this->setState('media', $this->getProps('media'));
        $this->setState('size', $this->getProps('size') ?? 64);
        $this->setState('class', $this->getProps('class') ?? '');
    }
    
    public function render() {
        $size = $this->getState('size');
        $customClass = $this->getState('class');
        
        // Create custom sizes for the avatar
        $sizes = [
            'avatar' => [$size, $size],
            'avatar-2x' => [$size * 2, $size * 2]
        ];
        
        // Create Image component with avatar-specific configuration
        $image = new Image([
            'media' => $this->getState('media'),
            'sizes' => $sizes,
            'class' => "avatar {$customClass}"
        ]);
        
        return <<<HTML
        <div class="lively-component avatar {$customClass}" lively:component="{$this->getId()}" role="region" aria-label="Avatar" style="width: {$size}px; height: {$size}px;">
            {$image->render()}
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Avatar();
}