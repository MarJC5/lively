<?php

namespace Lively\Resources\Components;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Core\View\Component;
use Lively\Media\Size;
use Lively\Models\Media;

/**
 * Image Component
 * 
 * A responsive image component that automatically generates picture element with sources
 * based on provided sizes or default sizes. Supports both 1x and 2x image versions.
 * 
 * @example
 * ```php
 * // Basic usage with default sizes
 * new Image(['media' => $mediaId]);
 * 
 * // Usage with custom sizes
 * new Image([
 *     'media' => $mediaId,
 *     'sizes' => [
 *         'custom-xl' => [1920, 1080],
 *         'custom-l' => [1280, 720],
 *         'custom-m' => [860, 480],
 *         'custom-s' => [400, 225]
 *     ]
 * ]);
 * 
 * // Usage with custom class
 * new Image([
 *     'media' => $mediaId,
 *     'class' => 'my-custom-class'
 * ]);
 * 
 * // Usage with all options
 * new Image([
 *     'media' => $mediaId,
 *     'sizes' => [
 *         'custom-xl' => [1920, 1080],
 *         'custom-l' => [1280, 720]
 *     ],
 *     'class' => 'my-custom-class'
 * ]);
 * ```
 * 
 * @property int $media The WordPress media attachment ID
 * @property array $sizes Optional custom sizes configuration. If not provided, uses default sizes from Size::SIZES
 * @property string $class Optional additional CSS classes to add to the figure element
 * 
 * Default sizes (if not provided):
 * - image-xl-2x: 3840px width
 * - image-xl: 1920px width
 * - image-l-2x: 2560px width
 * - image-l: 1280px width
 * - image-m-2x: 1720px width
 * - image-m: 860px width
 * - image-s-2x: 800px width
 * - image-s: 400px width
 * 
 * @view
 */
class Image extends Component
{
    /**
     * Initialize the component state
     * 
     * Sets up the media object and registers any custom sizes
     */
    protected function initState()
    {
        // Get props
        $this->setState('media', new Media($this->getProps('media')));
        $this->setState('sizes', $this->getProps('sizes') ?? []);
        $this->setState('class', $this->getProps('class') ?? '');

        // Add sizes
        if ($this->getState('sizes')) {
            foreach ($this->getState('sizes') as $name => $size) {
                Size::add($name, $size[0], $size[1], false);
            }
        }
    }

    /**
     * Generate source elements for the picture element
     * 
     * Creates source elements with appropriate media queries and srcset attributes
     * based on the provided sizes or default sizes. Automatically handles 2x versions
     * if they exist.
     * 
     * @return string HTML string containing source elements
     */
    protected function generateSources()
    {
        $sources = '';
        $media = $this->getState('media');
        $sizes = $this->getState('sizes');

        // If no custom sizes provided, use default sizes
        if (empty($sizes)) {
            $sizes = Size::SIZES;
        }

        // Sort sizes by width in descending order
        uasort($sizes, function($a, $b) {
            return $b[0] - $a[0];
        });

        $prevWidth = null;
        foreach ($sizes as $name => $size) {
            $width = $size[0];
            $mediaQuery = '';
            
            if ($prevWidth === null) {
                $mediaQuery = "(min-width: " . ($width + 1) . "px)";
            } else {
                $mediaQuery = "(max-width: " . $prevWidth . "px)";
            }

            $srcset = $media->src($name) . " 1x";
            if (isset($sizes[$name . "-2x"])) {
                $srcset .= ", " . $media->src($name . "-2x") . " 2x";
            }

            $sources .= <<<HTML
                <source
                    media="{$mediaQuery}"
                    srcset="{$srcset}">
            HTML;

            $prevWidth = $width;
        }

        return $sources;
    }

    /**
     * Render the component
     * 
     * Generates the HTML output for the image component, including:
     * - Picture element with source elements for different viewport sizes
     * - Fallback img element with srcset for browsers that don't support picture
     * - Proper alt text from the media object
     * - Custom CSS classes if provided
     * 
     * @return string HTML string containing the complete image component
     */
    public function render()
    {
        $media = $this->getState('media');
        $sources = $this->generateSources();
        
        // Get the largest size for default src
        $sizes = $this->getState('sizes') ?: Size::SIZES;
        $largestSize = array_reduce(array_keys($sizes), function($carry, $key) use ($sizes) {
            if (!$carry || $sizes[$key][0] > $sizes[$carry][0]) {
                return $key;
            }
            return $carry;
        });
        
        $defaultSrc = $media->src($largestSize) ?? $media->src("full");
        $defaultSrcset = $media->src("image-l") . " 1280w, " . $defaultSrc . " " . $sizes[$largestSize][0] . "w";

        return <<<HTML
        <figure class="lively-component ofi-parent {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="Image">
            <picture>
                {$sources}
                <img
                    class="ofi-image"
                    srcset="{$defaultSrcset}"
                    src="{$defaultSrc}"
                    alt="{$media->alt()}">
            </picture>
        </figure>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Image();
}
