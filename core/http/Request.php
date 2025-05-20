<?php

namespace Lively\Core\Http;

use Lively\Core\Utils\CSRF;
use Lively\Core\Utils\InputFilter;

class Request
{
    protected $method;
    protected $path;
    protected $params = [];
    protected $body = [];
    protected $headers = [];
    protected $validated = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->params = $_GET;
        $this->parseBody();
        $this->parseHeaders();
    }
    
    /**
     * Parse the request body
     */
    protected function parseBody()
    {
        switch ($this->method) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
                // Check for JSON content type
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($contentType, 'application/json') !== false) {
                    $json = file_get_contents('php://input');
                    $this->body = json_decode($json, true) ?: [];
                } else {
                    $this->body = $_POST;
                }
                break;
            default:
                $this->body = [];
        }
    }
    
    /**
     * Parse request headers
     */
    protected function parseHeaders()
    {
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $this->headers[$name] = $value;
            }
        }
    }
    
    /**
     * Get the request method
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Get the request path
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Get a query parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    /**
     * Get a body parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed
     */
    public function getBody($key = null, $default = null)
    {
        if ($key === null) {
            return $this->body;
        }
        
        return $this->body[$key] ?? $default;
    }
    
    /**
     * Get a request header
     * 
     * @param string $name Header name
     * @param mixed $default Default value if header not found
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }
    
    /**
     * Validate CSRF token
     * 
     * @return bool
     */
    public function validateCsrf()
    {
        $token = $this->getHeader('X-Csrf-Token');
        return CSRF::validate($token);
    }
    
    /**
     * Validate request data and return sanitized values
     * 
     * @param array $rules Validation rules
     * @return array Validated data
     */
    public function validate(array $rules)
    {
        $validated = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->getBody($field);
            
            // Apply sanitization if needed
            if (is_callable($rule)) {
                $value = $rule($value);
            } elseif (is_string($rule)) {
                // Apply simple sanitization based on rule
                switch ($rule) {
                    case 'string':
                        $value = InputFilter::sanitizeString($value);
                        break;
                    case 'int':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'bool':
                        $value = (bool) $value;
                        break;
                    case 'email':
                        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        break;
                }
            }
            
            $validated[$field] = $value;
        }
        
        $this->validated = $validated;
        return $validated;
    }
    
    /**
     * Get validated data
     * 
     * @param string|null $key Specific field to get or null for all
     * @param mixed $default Default value if field not found
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        if ($key === null) {
            return $this->validated;
        }
        
        return $this->validated[$key] ?? $default;
    }
} 