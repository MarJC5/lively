<?php

namespace Lively\Core\Http\Middleware;

use Lively\Core\Http\Middleware;
use Lively\Core\Http\Request;
use Lively\Core\Http\ResponseFormatter;
use Lively\Core\Utils\CSRF;

class CsrfMiddleware implements Middleware
{
    protected $responseFormatter;
    protected $excludedPaths = [];
    
    /**
     * Constructor
     * 
     * @param ResponseFormatter $responseFormatter
     * @param array $excludedPaths Paths to exclude from CSRF validation
     */
    public function __construct(ResponseFormatter $responseFormatter, array $excludedPaths = [])
    {
        $this->responseFormatter = $responseFormatter;
        $this->excludedPaths = $excludedPaths;
    }
    
    /**
     * Process the request
     * 
     * @param Request $request The request to process
     * @param callable $next The next middleware to call
     * @return mixed
     */
    public function process(Request $request, callable $next)
    {
        // Skip CSRF validation for excluded paths
        if ($this->isExcludedPath($request->getPath())) {
            return $next($request);
        }
        
        // Skip CSRF validation for GET, HEAD, OPTIONS requests
        $method = $request->getMethod();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }
        
        // Validate CSRF token
        $token = $request->getHeader('X-Csrf-Token');
        if (!$token || !CSRF::validate($token)) {
            return $this->responseFormatter->error('Invalid CSRF token', 403);
        }
        
        // Continue to the next middleware
        return $next($request);
    }
    
    /**
     * Check if the path is excluded from CSRF validation
     * 
     * @param string $path Request path
     * @return bool
     */
    protected function isExcludedPath($path)
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if ($excludedPath === $path || (substr($excludedPath, -1) === '*' && 
                strpos($path, rtrim($excludedPath, '*')) === 0)) {
                return true;
            }
        }
        
        return false;
    }
} 