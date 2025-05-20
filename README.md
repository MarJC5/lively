# Lively Framework

A modern, React-like PHP framework that can be integrated with WordPress, Laravel, and other PHP applications. Lively Framework provides a robust foundation for building interactive web applications with a focus on component-based architecture, security, performance, and maintainability.

## Table of Contents
- [Features](#features)
- [Core Modules](#core-modules)
- [Component System](#component-system)
- [Client-Side Integration](#client-side-integration)
- [CLI Tools](#cli-tools)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## Features

- **React-like Architecture**
  - Component-based UI development
  - Virtual DOM-like updates
  - State and Props system
  - Event handling
  - Lifecycle hooks
  - Clean HTML Output

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
  - Optimized State Management

- **Developer Experience**
  - Intuitive API
  - Comprehensive Documentation
  - Debug Tools
  - Error Handling
  - Clean HTML Structure

## Core Modules

### View Module
The View module provides a React-like component system for building dynamic user interfaces:

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

### Component Update Module
Handles component state updates and client-server communication:

```php
use Lively\Core\View\Renderer;

$renderer = Renderer::getInstance();

// Handle component update
$renderer->handleComponentUpdate(
    'component-id',
    'methodName',
    ['arg1', 'arg2']
);
```

### Client-Side Module
Provides seamless integration between server and client:

```javascript
// Update component state
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
- **Clean HTML**: State stored in separate script tags

### State Management
The framework uses a React-like approach to state management:

1. Component HTML is rendered with minimal attributes
2. Component state is stored in separate script tags at the end of the body
3. JavaScript automatically loads and manages component state
4. Updates are handled seamlessly through the framework

Example output:
```html
<!-- Component HTML -->
<div class="counter-component" lively:component="counter-123">
    <h3>Count: 5</h3>
    <button lively:onclick="increment">Increment</button>
</div>

<!-- State (at end of body) -->
<script id="counter-123" type="application/json">
{
    "value": 5,
    "json-class": "Lively\\Resources\\Components\\Counter"
}
</script>
```

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

## CLI Tools

Lively Framework includes a simple CLI tool for development tasks.

### Available Commands

```bash
# Create a new component
php lively make:component ComponentName

# Clear component memory cache
php lively clear:memory
```

### Command Structure
The CLI tool provides a basic command structure with:
- Command registration
- Argument handling
- Colored output for errors, success, and info messages

### Creating Custom Commands
You can create custom commands by extending the `Command` class:

```php
namespace Lively\Core\Cli\Commands;

use Lively\Core\Cli\Command;

class MyCommand extends Command {
    protected string $name = 'my:command';
    protected string $description = 'Description of my command';
    
    public function handle(array $args = []): void {
        // Your command logic here
        $this->success('Command executed successfully');
    }
}
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

4. Add component states output to your theme's `footer.php`:
```php
<?php 
// Output component states
echo \Lively\Lively::componentStates();

wp_footer(); 
?>
</body>
</html>
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
    
    <?php 
    // Output component states
    echo \Lively\Lively::componentStates();
    ?>
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
- [Component System Documentation](docs/view.md)
- [Client-Side Documentation](docs/lively.md)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details. 