<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * Icon Component
 * 
 * A component that renders SVG icons from the assets directory.
 *
 * @example
 * ```php
 * $icon = new Icon([
 *     'name' => 'check',  // Will load check.svg from assets/svg directory
 *     'class' => 'icon-sm'
 * ]);
 * ```
 * 
 * @property string $name The name of the SVG file to load (without .svg extension)
 * @property string $class Additional CSS classes to apply to the icon container
 * @property string $color The color of the icon
 * @property int $width The width of the icon
 * @property int $height The height of the icon
 * 
 * @view
 */
class Icon extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $props = $this->getProps();
        
        $this->setState('name', $props['name'] ?? '');
        $this->setState('class', $props['class'] ?? '');
        $this->setState('color', $props['color'] ?? 'currentColor');
        $this->setState('width', $props['width'] ?? 24);
        $this->setState('height', $props['height'] ?? 24);
        $this->setState('strokeWidth', $props['strokeWidth'] ?? 1.5);
    }
    
    /**
     * Get the SVG content from the assets directory
     * 
     * @param string $name The name of the SVG file (without extension)
     * @return string The SVG content or empty string if file not found
     */
    protected function getSvgContent($name) {
        // First try to load a PHP file
        $phpPath = __DIR__ . '/../assets/svg/' . $name . '.php';
        if (file_exists($phpPath)) {
            // Extract variables from state
            $color = $this->getState('color');
            $width = $this->getState('width');
            $height = $this->getState('height');
            $strokeWidth = $this->getState('strokeWidth');
            
            ob_start();
            include $phpPath;
            return ob_get_clean();
        }
        
        // Fall back to SVG file
        $svgPath = __DIR__ . '/../assets/svg/' . $name . '.svg';
        if (file_exists($svgPath)) {
            return file_get_contents($svgPath);
        }
        return '';
    }
    
    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        $svgContent = $this->getSvgContent($this->getState('name'));
        if (empty($svgContent)) {
            return '';
        }
        
        return <<<HTML
            {$svgContent}
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Icon();
}