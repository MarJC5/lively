<?php

namespace Lively\Core\Http;

interface Middleware
{
    /**
     * Process the request
     * 
     * @param Request $request The request to process
     * @param callable $next The next middleware to call
     * @return mixed
     */
    public function process(Request $request, callable $next);
} 