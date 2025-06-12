<?php

namespace Lively\Resources\Components;

// Prevent direct access.
defined('ABSPATH') or exit;

use Lively\Core\View\Component;
use Lively\Media\Size;
use Lively\Models\Media;

/**
 * Modern Image Component
 * 
 * A responsive image component that automatically generates picture element with sources
 * based on provided sizes. Supports modern formats (AVIF, WebP) with automatic fallbacks,
 * responsive breakpoints, and 2x retina versions.
 * 
 * @example
 * ```php
 * // Basic usage with default sizes and modern format support
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
 * // Disable modern formats (force original format only)
 * new Image([
 *     'media' => $mediaId,
 *     'modern_formats' => false
 * ]);
 * 
 * // Custom loading and decoding behavior
 * new Image([
 *     'media' => $mediaId,
 *     'loading' => 'eager',  // or 'lazy'
 *     'decoding' => 'sync'   // or 'async'
 * ]);
 * 
 * // All options
 * new Image([
 *     'media' => $mediaId,
 *     'sizes' => ['custom-xl' => [1920, 1080]],
 *     'class' => 'my-custom-class',
 *     'modern_formats' => true,
 *     'loading' => 'lazy',
 *     'decoding' => 'async'
 * ]);
 * ```
 * 
 * @property int $media The WordPress media attachment ID
 * @property array $sizes Optional custom sizes configuration
 * @property string $class Optional additional CSS classes
 * @property bool $modern_formats Enable AVIF/WebP formats (default: true)
 * @property string $loading Loading behavior ('lazy'|'eager', default: 'lazy')
 * @property string $decoding Decoding hint ('async'|'sync', default: 'async')
 * 
 * @view
 */
class Image extends Component
{
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState()
    {
        $this->setState('media', new Media($this->getProps('media')));
        $this->setState('sizes', $this->getProps('sizes') ?? []);
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('modern_formats', $this->getProps('modern_formats') ?? true);
        $this->setState('loading', $this->getProps('loading') ?? 'lazy');
        $this->setState('decoding', $this->getProps('decoding') ?? 'async');

        // Register custom sizes if provided
        if ($this->getState('sizes')) {
            foreach ($this->getState('sizes') as $name => $size) {
                Size::add($name, $size[0], $size[1], false);
                
                // Also register 2x version
                $retina_name = $name . '-2x';
                if (!isset($this->getState('sizes')[$retina_name])) {
                    Size::add($retina_name, $size[0] * 2, $size[1] * 2, false);
                }
            }
        }
    }

    /**
     * Get available sizes sorted by width (descending) - SIMPLIFIED
     */
    private function getAvailableSizes(): array
    {
        $sizes = $this->getState('sizes');
        
        if (empty($sizes)) {
            // Use only the 4 main responsive sizes - much simpler!
            $sizes = [
                'image-xl' => [1920, Size::FULL_SIZE, false],
                'image-lg' => [1280, Size::FULL_SIZE, false], 
                'image-md' => [768, Size::FULL_SIZE, false],
                'image-sm' => [480, Size::FULL_SIZE, false],
            ];
        }

        // Sort by width descending, excluding 2x versions for breakpoint logic
        $baseSizes = array_filter($sizes, function($key) {
            return !str_ends_with($key, '-2x');
        }, ARRAY_FILTER_USE_KEY);

        uasort($baseSizes, function($a, $b) {
            return $b[0] - $a[0];
        });

        return $baseSizes;
    }

    /**
     * Generate modern format sources with automatic fallbacks
     */
    private function generateModernFormatSources(string $sizeName, string $mediaQuery = ''): string
    {
        if (!$this->getState('modern_formats')) {
            return '';
        }

        $media = $this->getState('media');
        $sources = '';
        
        // Get all available formats for this size
        $allFormats = $this->getAllImageFormats($sizeName);
        
        // Generate sources for modern formats only (AVIF, WebP)
        foreach (['avif', 'webp'] as $format) {
            if (isset($allFormats[$format])) {
                $srcset = $this->buildSrcset($sizeName, $format);
                if ($srcset) {
                    $mediaAttr = $mediaQuery ? " media=\"{$mediaQuery}\"" : '';
                    $sources .= "<source{$mediaAttr} srcset=\"{$srcset}\" type=\"image/{$format}\">\n";
                }
            }
        }

        return $sources;
    }

    /**
     * Get all available image formats for a given size
     */
    private function getAllImageFormats(string $sizeName): array
    {
        $media = $this->getState('media');
        $sizeInstance = Size::get_instance();
        
        // Use the new method to get all formats
        return $sizeInstance->get_all_image_formats($media->id(), $sizeName, null);
    }

    /**
     * Build srcset with 1x and 2x versions for a specific format
     */
    private function buildSrcset(string $sizeName, string $format = 'original'): string
    {
        $media = $this->getState('media');
        $srcset = [];

        // Get 1x version
        $image1x = Size::src_format($media->id(), $sizeName, $format);
        if (!empty($image1x['src'])) {
            $srcset[] = $image1x['src'] . ' 1x';
        }

        // Get 2x version if it exists
        $retinaSizeName = $sizeName . '-2x';
        $image2x = Size::src_format($media->id(), $retinaSizeName, $format);
        if (!empty($image2x['src'])) {
            $srcset[] = $image2x['src'] . ' 2x';
        }

        return implode(', ', $srcset);
    }

    /**
     * Generate responsive source elements
     */
    protected function generateSources(): string
    {
        $sources = '';
        $sizes = $this->getAvailableSizes();
        $prevWidth = null;

        foreach ($sizes as $sizeName => $sizeConfig) {
            $width = $sizeConfig[0];
            
            // Generate media query
            $mediaQuery = '';
            if ($prevWidth === null) {
                // Largest size - no media query needed for first source
                $mediaQuery = "(min-width: {$width}px)";
            } else {
                $mediaQuery = "(max-width: {$prevWidth}px)";
            }

            // Generate modern format sources for this breakpoint
            $sources .= $this->generateModernFormatSources($sizeName, $mediaQuery);

            $prevWidth = $width - 1;
        }

        return $sources;
    }

    /**
     * Get fallback image attributes
     */
    private function getFallbackImage(): array
    {
        $media = $this->getState('media');
        $sizes = $this->getAvailableSizes();
        
        // Use the smallest size for fallback (better for performance)
        $fallbackSizeName = array_key_last($sizes);
        
        // Always use original format for maximum compatibility
        $fallbackImage = Size::src_format($media->id(), $fallbackSizeName, 'original');
        
        if (empty($fallbackImage)) {
            // Ultimate fallback to WordPress full size
            $fallbackImage = wp_get_attachment_image_src($media->id(), 'full');
            $fallbackImage = [
                'src' => $fallbackImage[0] ?? '',
                'width' => $fallbackImage[1] ?? 0,
                'height' => $fallbackImage[2] ?? 0
            ];
        }

        return $fallbackImage;
    }

    /**
     * Generate sizes attribute for responsive images
     */
    private function generateSizesAttribute(): string
    {
        $sizes = $this->getAvailableSizes();
        $sizesAttr = [];

        $prevWidth = null;
        foreach ($sizes as $sizeName => $sizeConfig) {
            $width = $sizeConfig[0];
            
            if ($prevWidth === null) {
                // Largest size
                $sizesAttr[] = "(min-width: {$width}px) {$width}px";
            } else {
                $sizesAttr[] = "(max-width: {$prevWidth}px) {$width}px";
            }
            
            $prevWidth = $width - 1;
        }
        
        // Default size (fallback)
        $smallestSize = end($sizes)[0];
        $sizesAttr[] = "{$smallestSize}px";

        return implode(', ', $sizesAttr);
    }

    /**
     * Generate srcset for fallback img element
     */
    private function generateFallbackSrcset(): string
    {
        $sizes = $this->getAvailableSizes();
        $media = $this->getState('media');
        $srcset = [];

        foreach ($sizes as $sizeName => $sizeConfig) {
            $width = $sizeConfig[0];
            
            // Use original format for maximum compatibility
            $image = Size::src_format($media->id(), $sizeName, 'original');
            if (!empty($image['src'])) {
                $srcset[] = $image['src'] . ' ' . $width . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Render the component
     */
    public function render(): string
    {
        $media = $this->getState('media');
        
        if (!$media || !$media->id()) {
            return '<!-- Image component: Invalid media ID -->';
        }

        $sources = $this->generateSources();
        $fallbackImage = $this->getFallbackImage();
        $fallbackSrcset = $this->generateFallbackSrcset();
        $sizesAttr = $this->generateSizesAttribute();
        
        // Prepare attributes
        $class = 'lively-component ofi-parent ' . $this->getState('class');
        $loading = esc_attr($this->getState('loading'));
        $decoding = esc_attr($this->getState('decoding'));
        $alt = esc_attr($media->alt());
        $componentId = $this->getId();

        // Build the HTML
        $html = "<figure class=\"{$class}\" lively:component=\"{$componentId}\" role=\"region\" aria-label=\"Image\">\n";
        
        if ($this->getState('modern_formats') && !empty($sources)) {
            // Modern approach with picture element and format fallbacks
            $html .= "  <picture>\n";
            $html .= $sources;
            $html .= "    <img\n";
            $html .= "      class=\"ofi-image\"\n";
            $html .= "      src=\"" . esc_url($fallbackImage['src']) . "\"\n";
            if ($fallbackSrcset) {
                $html .= "      srcset=\"" . esc_attr($fallbackSrcset) . "\"\n";
                $html .= "      sizes=\"" . esc_attr($sizesAttr) . "\"\n";
            }
            $html .= "      alt=\"{$alt}\"\n";
            $html .= "      loading=\"{$loading}\"\n";
            $html .= "      decoding=\"{$decoding}\"\n";
            $html .= "      width=\"" . intval($fallbackImage['width']) . "\"\n";
            $html .= "      height=\"" . intval($fallbackImage['height']) . "\">\n";
            $html .= "  </picture>\n";
        } else {
            // Fallback to simple responsive img element
            $html .= "  <img\n";
            $html .= "    class=\"ofi-image\"\n";
            $html .= "    src=\"" . esc_url($fallbackImage['src']) . "\"\n";
            if ($fallbackSrcset) {
                $html .= "    srcset=\"" . esc_attr($fallbackSrcset) . "\"\n";
                $html .= "    sizes=\"" . esc_attr($sizesAttr) . "\"\n";
            }
            $html .= "    alt=\"{$alt}\"\n";
            $html .= "    loading=\"{$loading}\"\n";
            $html .= "    decoding=\"{$decoding}\"\n";
            $html .= "    width=\"" . intval($fallbackImage['width']) . "\"\n";
            $html .= "    height=\"" . intval($fallbackImage['height']) . "\">\n";
        }
        
        $html .= "</figure>";

        return $html;
    }

    /**
     * Get debug information about available formats
     */
    public function getDebugInfo(): array
    {
        $media = $this->getState('media');
        $sizes = $this->getAvailableSizes();
        $debug = [
            'media_id' => $media->id(),
            'available_sizes' => [],
            'modern_formats_enabled' => $this->getState('modern_formats')
        ];

        foreach ($sizes as $sizeName => $sizeConfig) {
            $formats = $this->getAllImageFormats($sizeName);
            $debug['available_sizes'][$sizeName] = [
                'dimensions' => $sizeConfig,
                'available_formats' => array_keys($formats),
                'format_urls' => array_column($formats, 'src')
            ];
        }

        return $debug;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Image();
}