<?php

namespace Lively\Core\Http;

use Lively\Core\Utils\Logger;
use Lively\Core\Http\Middleware\CsrfMiddleware;

class Router
{
    protected static $instance;
    protected $routes = [];
    protected $middlewareHandler;
    protected $responseFormatter;
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->responseFormatter = new ResponseFormatter();
        $this->middlewareHandler = new MiddlewareHandler(function() {
            // If no route is matched, just return null
            // This allows the host application to handle routes
            return null;
        });
        
        // Set up default middleware
        $this->setupMiddleware();
        
        // Register default routes
        $this->registerDefaultRoutes();
    }
    
    /**
     * Set up middleware
     */
    protected function setupMiddleware()
    {
        // Add CSRF middleware with excluded paths
        $csrfMiddleware = new CsrfMiddleware(
            $this->responseFormatter,
            ['/?lively-action=update-component'] // Paths exempt from CSRF validation
        );
        
        $this->middlewareHandler->add($csrfMiddleware);
    }
    
    /**
     * Register default routes
     */
    protected function registerDefaultRoutes()
    {
        // Register only the component update route
        $this->post('/update-component', [$this, 'handleComponentUpdateRoute']);
    }
    
    /**
     * Add a route
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param callable $handler Route handler
     * @return $this
     */
    public function addRoute($method, $path, $handler)
    {
        $method = strtoupper($method);
        
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        
        $this->routes[$method][$path] = $handler;
        return $this;
    }
    
    /**
     * Add a GET route
     */
    public function get($path, $handler)
    {
        return $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $handler)
    {
        return $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add a PUT route
     */
    public function put($path, $handler)
    {
        return $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($path, $handler)
    {
        return $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Handle incoming requests, but only for specific Lively component endpoints
     * Returns true if the request was handled, false otherwise
     * 
     * @return bool Whether the request was handled by Lively
     */
    public function handleRequest()
    {
        $request = new Request();
        $action = isset($_GET['lively-action']) ? $_GET['lively-action'] : null;
        
        // Handle component update request
        if ($action === 'update-component' && $request->getMethod() === 'POST') {
            Logger::info("Handling component update request", [
                'action' => $action,
                'method' => $request->getMethod()
            ]);
            
            $this->handleComponentUpdateRequest($request);
            return true;
        }
        
        // If no Lively-specific routes match, return false to let the main app handle it
        return false;
    }
    
    /**
     * Match a route
     * 
     * @param Request $request
     * @return callable|null
     */
    public function matchRoute(Request $request)
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        
        // Check for exact route match
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path];
        }
        
        return null;
    }
    
    /**
     * Handle the component update request
     * 
     * @param Request $request
     * @return void
     */
    public function handleComponentUpdateRequest(Request $request)
    {
        Logger::debug("Processing component update request");
        
        // Set proper content type for AJAX response
        header('Content-Type: application/json; charset=utf-8');
        
        $componentController = new ComponentController();
        $result = $componentController->handleComponentUpdateRequest();
        
        // Send JSON response
        $this->responseFormatter->sendJsonResponse($result);
    }
}