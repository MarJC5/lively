# Lively Framework

A modern, flexible PHP framework that can be integrated with WordPress, Laravel, and other PHP applications. Lively Framework provides a robust foundation for building web applications with a focus on security, performance, and maintainability.

## Table of Contents
- [Features](#features)
- [Core Modules](#core-modules)
- [Component System](#component-system)
- [Client-Side Integration](#client-side-integration)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Modern Architecture**
  - React-like Component System
  - Modular Design
  - Framework Agnostic
  - Modern PHP Practices

- **Security**
  - CSRF Protection
  - Input Validation
  - Secure Component Updates
  - XSS Prevention

- **Performance**
  - Component Pooling
  - Lazy Loading
  - Tiered Caching
  - Memory Management

- **Developer Experience**
  - Intuitive API
  - Comprehensive Documentation
  - Debug Tools
  - Error Handling

## Core Modules

### View Module
The View module provides a robust component-based system for building dynamic user interfaces:

```php
use Lively\Core\View\Component;

class MyComponent extends Component {
    protected function initState() {
        return ['count' => 0];
    }
    
    public function render() {
        return "<div>Count: {$this->getState('count')}</div>";
    }
}
```

### HTTP Module
Handles routing, middleware, and request/response management:

```php
use Lively\Core\Http\Router;

$router = Router::getInstance();
$router->get('/users', function() {
    // Handle request
});
```

### Client-Side Module
Provides seamless integration between server and client:

```javascript
// Update component
updateState('component-id', 'methodName', { arg1: 'value1' });
```

## Component System

### Component Structure
```php
namespace Lively\Resources\Components;

use Lively\Core\View\Component;

class Counter extends Component {
    protected function initState() {
        $this->state['value'] = $this->getProps('initialValue') ?? 0;
    }
    
    public function increment() {
        $this->setState('value', $this->getState('value') + 1);
    }

    public function render() {
        return <<<HTML
        <div class="counter-component" lively:component="{$this->getId()}">
            <h3>Count: {$this->getState('value')}</h3>
            <button lively:onclick="increment">Increment</button>
        </div>
        HTML;
    }
}
```

### Component Features
- **State Management**: Components maintain their own state
- **Props System**: Pass data to components
- **Event Handling**: Bind events to component methods
- **Lifecycle Hooks**: Control component behavior
- **Child Components**: Build complex UIs
- **CSRF Protection**: Secure component updates

## Client-Side Integration

### Event Handling
```html
<!-- Shorthand format -->
<button lively:onclick="increment">Increment</button>
<input lively:onchange:300="updateValue">

<!-- Legacy format -->
<button data-lively-action="increment" data-lively-event="click">
    Increment
</button>
```

### Component Updates
```javascript
// Update component state
updateState('component-id', 'methodName', { arg1: 'value1' });

// Handle errors
lively.showComponentError('component-id', 'Error message');
```

## Requirements

- PHP 7.4 or higher
- Composer (for dependency management)
- Modern web browser (for client-side features)

## Installation

### WordPress Installation

1. Copy the `lively` directory to your WordPress theme:
```bash
cp -r lively /path/to/your/wordpress/wp-content/themes/your-theme/
```

2. Add the following to your theme's `functions.php`:
```php
// Define Lively Framework paths
define('LIVELY_RESOURCES_DIR', __DIR__ . '/resources');
define('LIVELY_ASSETS_URL', get_template_directory_uri() . '/lively');

// Initialize Lively Framework
require_once __DIR__ . '/lively/init.php';
```

3. Add CSRF token and assets to your theme's `header.php`:
```php
<?php if (class_exists('\\Lively\\Core\\Utils\\CSRF')): ?>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo \Lively\Core\Utils\CSRF::generate(); ?>">
<?php endif; ?>

<?php echo \Lively\Lively::assets(); ?>
```

### Other PHP Projects

1. Copy the `lively` directory to your project:
```bash
cp -r lively /path/to/your/project/
```

2. Create an initialization file (e.g., `init.php`):
```php
// Define Lively Framework paths
define('LIVELY_RESOURCES_DIR', __DIR__ . '/resources');
define('LIVELY_ASSETS_URL', '/lively'); // Adjust based on your URL structure

// Initialize Lively Framework
require_once __DIR__ . '/lively/init.php';
```

3. Add CSRF token and assets to your HTML template:
```php
<!DOCTYPE html>
<html>
<head>
    <?php if (class_exists('\\Lively\\Core\\Utils\\CSRF')): ?>
        <!-- CSRF Token -->
        <meta name="csrf-token" content="<?php echo \Lively\Core\Utils\CSRF::generate(); ?>">
    <?php endif; ?>

    <?php echo \Lively\Lively::assets(); ?>
</head>
<body>
    <!-- Your content here -->
</body>
</html>
```

4. Include the initialization file in your application's entry point:
```php
require_once __DIR__ . '/init.php';
```

## Quick Start

1. Create a component:
```php
namespace Lively\Resources\Components;

use Lively\Core\View\Component;

class HelloWorld extends Component {
    public function render() {
        return "<h1>Hello, {$this->getProps('name')}!</h1>";
    }
}
```

2. Use the component:
```php
use Lively\Lively;

// Render with props
echo Lively::render('HelloWorld', ['name' => 'World']);
```

3. Add interactivity:
```html
<button lively:onclick="updateName">Update Name</button>
```

## Documentation

For detailed documentation, see:
- [View Module Documentation](docs/view.md)
- [HTTP Module Documentation](docs/router.md)
- [Client-Side Documentation](docs/lively.md)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details. 