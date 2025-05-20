# Lively Framework View Module Documentation

The View module provides a robust component-based system for building dynamic user interfaces in the Lively Framework. This documentation covers the main components and their usage.

## Table of Contents
- [Component](#component)
- [ComponentFactory](#componentfactory)
- [Renderer](#renderer)
- [State](#state)

## Component

The `Component` class is the base class for all UI components in the framework.

### Key Features
- Component lifecycle management
- Props and state management
- Child component handling
- Event handling
- CSRF protection
- Hydration support
- Lazy loading

### Usage Example
```php
use Lively\Core\View\Component;

class MyComponent extends Component
{
    protected function initState()
    {
        return [
            'count' => 0
        ];
    }

    public function render()
    {
        return "
            <div>
                <h1>Count: {$this->getState('count')}</h1>
                <button onclick='lively.update(\"{$this->getId()}\", \"increment\")'>
                    Increment
                </button>
            </div>
        ";
    }

    public function increment()
    {
        $this->setState('count', $this->getState('count') + 1);
    }
}
```

## ComponentFactory

The `ComponentFactory` class handles component instantiation and validation.

### Key Features
- Component creation with validation
- Namespace management
- Client state handling
- Security checks

### Usage Example
```php
use Lively\Core\View\ComponentFactory;

$factory = new ComponentFactory();

// Create a component
$component = $factory->create('MyComponent', [
    'prop1' => 'value1'
]);

// Create from client state
$component = $factory->createFromClientState(
    'component-id',
    'MyComponent',
    ['state' => 'value']
);
```

## Renderer

The `Renderer` class manages component rendering and lifecycle.

### Key Features
- Component rendering
- Component registration
- Component pooling
- Memory management
- Tiered caching
- Component updates

### Usage Example
```php
use Lively\Core\View\Renderer;

$renderer = Renderer::getInstance();

// Render a component
$html = $renderer->render('MyComponent', [
    'prop1' => 'value1'
]);

// Get a component by ID
$component = $renderer->getComponent('component-id');

// Handle component updates
$renderer->handleComponentUpdate(
    'component-id',
    'methodName',
    ['arg1', 'arg2']
);
```

## State

The `State` class manages global state and state dependencies.

### Key Features
- Global state management
- Namespaced state
- State dependencies
- Batch notifications
- State listeners

### Usage Example
```php
use Lively\Core\View\State;

$state = State::getInstance();

// Set state
$state->set('key', 'value', 'namespace');

// Get state
$value = $state->get('key', 'default', 'namespace');

// Listen for changes
$state->listen('key', function($newValue, $oldValue) {
    // Handle state change
}, 'namespace');

// Set multiple values
$state->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2'
], 'namespace');
```

## Best Practices

1. **Component Design**
   - Keep components focused and single-purpose
   - Use props for configuration
   - Use state for internal data
   - Implement proper lifecycle methods

2. **State Management**
   - Use namespaces to organize state
   - Implement proper state dependencies
   - Use batch notifications for multiple updates
   - Clean up listeners when components are destroyed

3. **Performance**
   - Use component pooling for frequently used components
   - Implement lazy loading for heavy components
   - Use tiered caching for optimal memory usage
   - Clean up unused components

4. **Security**
   - Always validate component inputs
   - Use CSRF protection for component updates
   - Sanitize component output
   - Validate component class names

## Component Lifecycle

1. **Creation**
   - Component is instantiated
   - Props are set
   - State is initialized
   - Component is registered

2. **Mounting**
   - `beforeMount()` is called
   - Component is rendered
   - `mounted()` is called

3. **Updates**
   - State changes trigger re-renders
   - Props changes trigger re-renders
   - Child components are updated

4. **Unmounting**
   - `unmount()` is called
   - Resources are cleaned up
   - Component is unregistered

## Error Handling

The framework provides standardized error handling for components:

```php
try {
    $component = $factory->create('MyComponent');
    $html = $renderer->render($component);
} catch (\Exception $e) {
    Logger::error("Component error", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    // Handle error appropriately
}
``` 