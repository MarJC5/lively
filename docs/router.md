# Lively Framework HTTP Module Documentation

The HTTP module provides a robust set of classes for handling HTTP requests, routing, middleware, and responses in the Lively Framework. This documentation covers the main components and their usage.

## Table of Contents
- [Request](#request)
- [Router](#router)
- [Middleware](#middleware)
- [ResponseFormatter](#responseformatter)
- [Component Updates](#component-updates)

## Request

The `Request` class handles incoming HTTP requests and provides methods to access request data.

### Key Features
- Request method and path handling
- Query parameter access
- Request body parsing (supports JSON and form data)
- Header management
- CSRF validation
- Input validation and sanitization

### Usage Example
```php
use Lively\Core\Http\Request;

$request = new Request();

// Get request method
$method = $request->getMethod();

// Get query parameter
$id = $request->getParam('id');

// Get request body
$data = $request->getBody();

// Validate input
$rules = [
    'name' => 'string',
    'email' => 'email',
    'age' => 'int'
];
$validated = $request->validate($rules);
```

## Router

The `Router` class manages route registration and request handling.

### Key Features
- Route registration for different HTTP methods
- Middleware support
- Component update handling
- Default route registration

### Usage Example
```php
use Lively\Core\Http\Router;

$router = Router::getInstance();

// Register routes
$router->get('/users', function() {
    // Handle GET request
});

$router->post('/users', function() {
    // Handle POST request
});

// Handle request
$router->handleRequest();
```

## Middleware

The middleware system allows for request processing through a chain of middleware components.

### Key Features
- Middleware chain processing
- Fallback handler support
- CSRF middleware included by default

### Usage Example
```php
use Lively\Core\Http\MiddlewareHandler;
use Lively\Core\Http\Middleware;

class CustomMiddleware implements Middleware
{
    public function process(Request $request, callable $next)
    {
        // Process request
        return $next($request);
    }
}

$handler = new MiddlewareHandler();
$handler->add(new CustomMiddleware());
```

## ResponseFormatter

The `ResponseFormatter` class provides methods for formatting and sending HTTP responses.

### Key Features
- JSON response formatting
- Success and error response helpers
- Status code management
- Header management

### Usage Example
```php
use Lively\Core\Http\ResponseFormatter;

$formatter = new ResponseFormatter();

// Send success response
$formatter->success(['data' => 'value']);

// Send error response
$formatter->error('Invalid input', 400);

// Send custom JSON response
$formatter->sendJsonResponse([
    'custom' => 'data'
], 200);
```

## Component Updates

The HTTP module includes built-in support for handling component updates through AJAX requests. This feature is particularly useful for dynamic component updates without full page reloads.

### Key Features
- Automatic component update handling
- CSRF protection for component updates
- JSON response formatting
- Component-specific route handling

### Usage Example
```php
use Lively\Core\Http\Router;

$router = Router::getInstance();

// The component update route is automatically registered
// You can access it via: ?lively-action=update-component

// Example of making a component update request from JavaScript
fetch('/?lively-action=update-component', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Csrf-Token': 'your-csrf-token'
    },
    body: JSON.stringify({
        component: 'your-component-name',
        data: {
            // Your component update data
        }
    })
});
```

### Component Update Flow
1. Client makes a POST request to `/?lively-action=update-component`
2. Request is validated for CSRF token
3. Component controller processes the update
4. Response is formatted and returned as JSON

### Security Considerations
- All component update requests must include a valid CSRF token
- The token should be included in the `X-Csrf-Token` header
- Component updates are only processed for POST requests
- The component update endpoint is protected by default middleware

### Response Format
```json
{
    "success": true,
    "data": {
        "component": "updated-component-html",
        "status": "success"
    }
}
```

## Best Practices

1. **Request Validation**
   - Always validate and sanitize input data
   - Use the built-in validation methods for common data types
   - Implement custom validation rules when needed

2. **Middleware Usage**
   - Keep middleware focused on specific tasks
   - Use middleware for cross-cutting concerns
   - Implement proper error handling in middleware

3. **Response Formatting**
   - Use consistent response formats
   - Include appropriate status codes
   - Handle errors gracefully with proper messages

4. **Security**
   - Always validate CSRF tokens for POST requests
   - Sanitize all input data
   - Use appropriate HTTP methods for operations

## Error Handling

The framework provides standardized error responses through the `ResponseFormatter`. Always use these methods to ensure consistent error handling:

```php
// For validation errors
$formatter->error('Validation failed', 400, $errors);

// For server errors
$formatter->error('Internal server error', 500);

// For not found errors
$formatter->error('Resource not found', 404);
``` 