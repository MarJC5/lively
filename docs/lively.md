# Lively.js Client-Side Framework Documentation

The Lively.js framework provides client-side functionality for the Lively Framework, enabling dynamic component updates and interactions without full page reloads.

## Table of Contents
- [Initialization](#initialization)
- [Component Management](#component-management)
- [Event Handling](#event-handling)
- [Component Updates](#component-updates)
- [State Management](#state-management)
- [Error Handling](#error-handling)
- [Security](#security)

## Initialization

The framework automatically initializes when the DOM is ready. You can also configure it manually:

```javascript
// Configure with custom options
const lively = new Lively({
    debug: true,
    debounceTimeout: 300
});

// Initialize manually
lively.init();
```

### Configuration Options
- `debug`: Enable/disable debug logging (default: false)
- `debounceTimeout`: Default debounce timeout in milliseconds (default: 300)

## Component Management

### Component Registration
Components are automatically registered when found in the DOM. Each component must have:
- A unique ID (`lively:component` attribute)
- State data (`lively:state` attribute)
- Class name (`lively:class` or `lively:json-class` attribute)

```html
<div lively:component="counter-123"
     lively:state='{"count": 0}'
     lively:class="Lively\Resources\Components\Counter">
    <!-- Component content -->
</div>
```

### Component Scanning
The framework automatically scans for components on initialization:
```javascript
// Manual component scan
lively.scanForComponents();
```

## Event Handling

### Event Attributes
Components can handle events using two formats:

1. Shorthand format:
```html
<button lively:onclick="increment">Increment</button>
<input lively:onchange:300="updateValue"> <!-- With 300ms debounce -->
```

2. Legacy format:
```html
<button data-lively-action="increment" data-lively-event="click">Increment</button>
<input data-lively-action="updateValue" 
       data-lively-event="change" 
       data-lively-debounce="true"
       data-lively-debounce-timeout="300">
```

### Supported Events
- `click`: Mouse click events
- `input`: Input field changes
- `change`: Form element changes

### Debouncing
Events can be debounced to prevent rapid-fire updates:
```html
<!-- 300ms debounce -->
<input lively:onchange:300="updateValue">

<!-- Custom debounce timeout -->
<input data-lively-action="updateValue" 
       data-lively-event="change" 
       data-lively-debounce-timeout="500">
```

## Component Updates

### Update Methods
1. Using the global function:
```javascript
updateState('component-id', 'methodName', { arg1: 'value1' });
```

2. Using the Lively instance:
```javascript
lively.updateComponent('component-id', 'methodName', { arg1: 'value1' });
```

### Update Process
1. Component is marked as loading
2. Request is sent to server
3. Server processes update
4. Component is updated in DOM
5. Loading state is removed

### Loading States
Components automatically show loading states during updates:
```css
.lively-loading {
    /* Your loading styles */
}
```

## State Management

### Component State
Each component maintains its own state:
```javascript
// Get component state
const state = lively.components['component-id'].state;

// Update component state (server-side)
lively.updateComponent('component-id', 'setState', { key: 'value' });
```

### CSRF Protection
The framework automatically handles CSRF tokens:
```javascript
// Token is automatically included in requests
lively.updateComponent('component-id', 'method', {});
```

## Error Handling

### Error Display
Components automatically show errors:
```css
.lively-error {
    /* Your error styles */
}
.lively-error-message {
    /* Your error message styles */
}
```

### Error Types
1. Server Errors:
```javascript
lively.showComponentError('component-id', 'Server error message');
```

2. Network Errors:
```javascript
// Automatically handled by the framework
```

## Security

### CSRF Protection
The framework automatically:
1. Finds CSRF tokens in:
   - Meta tags: `<meta name="csrf-token" content="token">`
   - Input fields: `<input name="csrf_token" value="token">`
2. Includes tokens in all requests
3. Validates responses

### Component Validation
1. Class name validation
2. State validation
3. Method validation

## Best Practices

1. **Component Structure**
   - Use unique component IDs
   - Include all required attributes
   - Properly encode state data

2. **Event Handling**
   - Use debouncing for frequent events
   - Keep event handlers simple
   - Use appropriate event types

3. **Error Handling**
   - Implement proper error displays
   - Handle all error cases
   - Provide user feedback

4. **Performance**
   - Use debouncing for input events
   - Minimize state updates
   - Clean up unused components

## Debugging

Enable debug mode for detailed logging:
```javascript
lively.setDebug(true);
```

Debug information includes:
- Component registration
- Event handling
- State updates
- Error details
- Network requests

## Example Usage

```html
<!-- Component Definition -->
<div lively:component="counter-123"
     lively:state='{"count": 0}'
     lively:class="Lively\Resources\Components\Counter">
    <h1>Count: <span id="count">0</span></h1>
    <button lively:onclick="increment">Increment</button>
    <input lively:onchange:300="updateValue" 
           lively:value-attr="value">
</div>

<!-- JavaScript Usage -->
<script>
// Update component
updateState('counter-123', 'increment');

// With parameters
updateState('counter-123', 'updateValue', { value: 42 });

// Handle errors
lively.showComponentError('counter-123', 'Custom error message');
</script>
``` 