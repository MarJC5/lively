<?php

namespace Lively\Core;

use Lively\Core\Utils\Environment;
use Lively\Core\Utils\Session;
use Lively\Core\Utils\MemoryManager;
use Lively\Core\Http\Router;
use Lively\Core\View\Renderer;

class Engine {
    /**
     * Initialize the Lively framework
     * 
     * @return void
     */
    public static function init() {
        // Include global functions
        require_once LIVELY_THEME_DIR . '/config/global.php';

        // Initialize environment configuration
        Environment::init();

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
     * Get the component states
     * 
     * @return string Rendered HTML
     */
    public static function componentStates() {
        $renderer = Renderer::getInstance();
        return $renderer->generateComponentStates();
    }
}