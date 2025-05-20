<?php

namespace Lively;

use Lively\Core\Utils\Environment;
use Lively\Core\Utils\Assets;
use Lively\Core\Utils\Session;
use Lively\Core\Utils\MemoryManager;
use Lively\Core\Http\Router;
use Lively\Core\View\Renderer;

class Lively {
    /**
     * Initialize the Lively framework
     * 
     * @param string $assetsUrl URL to the assets directory
     * @return void
     */
    public static function init($assetsUrl = '') {
        // Use provided assets URL or fall back to constant if defined
        if (empty($assetsUrl) && defined('LIVELY_ASSETS_URL')) {
            $assetsUrl = LIVELY_ASSETS_URL;
        }
        
        // Initialize environment configuration
        Environment::init();

        // Initialize core assets
        Assets::registerCoreAssets($assetsUrl);

        // Start session before any output
        Session::start();
        
        // Initialize memory manager for component cleanup
        MemoryManager::initialize();

        // Only handle component-related requests, don't take over general routing
        $router = Router::getInstance();
        $router->handleRequest();
    }
    
    /**
     * Render a component by name or instance
     * 
     * @param string|object $component Component class name or instance
     * @param array $props Component properties
     * @return string Rendered HTML
     */
    public static function render($component, $props = []) {
        $renderer = Renderer::getInstance();
        return $renderer->render($component, $props);
    }
    
    /**
     * Get assets required for Lively components
     * 
     * @return string HTML script and style tags
     */
    public static function assets() {
        $assets = Assets::getRegisteredAssets();
        $html = '';
        
        foreach ($assets['styles'] as $style) {
            $html .= '<link rel="stylesheet" href="' . $style . '">' . PHP_EOL;
        }
        
        foreach ($assets['scripts'] as $script) {
            $html .= '<script src="' . $script . '"></script>' . PHP_EOL;
        }
        
        // Add component initialization script
        $renderer = Renderer::getInstance();
        $html .= $renderer->generateComponentsScript();
        
        return $html;
    }

    public static function componentStates() {
        $renderer = Renderer::getInstance();
        return $renderer->generateComponentStates();
    }
}