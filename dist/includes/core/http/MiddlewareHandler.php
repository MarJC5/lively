<?php

namespace Lively\Core\Http;

class MiddlewareHandler
{
    /**
     * @var array
     */
    protected $middleware = [];
    
    /**
     * @var callable
     */
    protected $fallbackHandler;
    
    /**
     * Constructor
     * 
     * @param callable $fallbackHandler The fallback handler to use if no middleware handles the request
     */
    public function __construct(?callable $fallbackHandler = null)
    {
        $this->fallbackHandler = $fallbackHandler ?: function() {
            return null;
        };
    }
    
    /**
     * Add middleware to the chain
     * 
     * @param Middleware $middleware
     * @return $this
     */
    public function add(Middleware $middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Handle the request through the middleware chain
     * 
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $chain = $this->createChain($this->fallbackHandler);
        return $chain($request);
    }
    
    /**
     * Create the middleware chain
     * 
     * @param callable $fallback
     * @return callable
     */
    protected function createChain(callable $fallback)
    {
        $chain = $fallback;
        
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $chain;
            $chain = function ($request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }
        
        return $chain;
    }
} 