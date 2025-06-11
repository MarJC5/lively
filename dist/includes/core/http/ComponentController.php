<?php

namespace Lively\Core\Http;

use Lively\Core\Utils\Logger;
use Lively\Core\Utils\CSRF;
use Lively\Core\View\Renderer;

class ComponentController
{
    protected $renderer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->renderer = Renderer::getInstance();
    }

    /**
     * Handle component update requests
     * 
     * @return array Response data
     */
    public function handleComponentUpdateRequest()
    {
        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            return [
                'success' => false,
                'error' => 'Invalid request data',
                'status' => 400
            ];
        }

        // Validate CSRF token
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$csrfToken || !CSRF::validate($csrfToken)) {
            return [
                'success' => false,
                'error' => 'Invalid CSRF token',
                'status' => 403
            ];
        }

        // Handle component update requests
        if (isset($input['component_id']) && isset($input['method'])) {
            // Extract the actual class name from state if available
            $class = '';
            $state = [];
            
            if (isset($input['state'])) {
                if (isset($input['state']['class'])) {
                    // Extract the actual class name (remove quotes if present)
                    $class = trim($input['state']['class'], '"');
                }
                
                // Get the actual state if available
                if (isset($input['state']['state'])) {
                    $state = $input['state']['state'];
                }
            }
            
            return $this->handleComponentUpdate(
                $input['component_id'],
                $input['method'],
                $input['args'] ?? [],
                [
                    'class' => $class,
                    'state' => $state
                ]
            );
        }

        // Unknown AJAX request
        return [
            'success' => false,
            'error' => 'Unknown request type',
            'status' => 400
        ];
    }

    /**
     * Handle component update
     * 
     * @param string $componentId Component ID
     * @param string $method Method to call
     * @param array $args Method arguments
     * @param array $clientState Client-side state
     * @return array Response data
     */
    public function handleComponentUpdate($componentId, $method, $args = [], $clientState = [])
    {
        try {
            // Get the component from the renderer
            $component = $this->renderer->getComponent($componentId);

            // If component not found, try to recreate it from client state
            if (!$component && isset($clientState['class'])) {
                Logger::debug("Attempting to recreate component from client state", [
                    'component_id' => $componentId,
                    'class' => $clientState['class'],
                    'state' => $clientState['state'] ?? []
                ]);

                $component = $this->renderer->createComponentFromClientState(
                    $componentId,
                    $clientState['class'],
                    $clientState['state'] ?? []
                );
            }

            if (!$component) {
                return [
                    'success' => false,
                    'error' => 'Component not found: ' . $componentId . ' (class: ' . ($clientState['class'] ?? 'unknown') . ')',
                    'status' => 404
                ];
            }

            // Log the component state before method call
            Logger::debug("Component state before method call", [
                'id' => $componentId,
                'method' => $method,
                'args' => $args,
                'state' => $component->getState()
            ]);

            // Check if method exists
            if (!method_exists($component, $method)) {
                return [
                    'success' => false,
                    'error' => 'Method not found: ' . $method . ' on component ' . get_class($component),
                    'status' => 404
                ];
            }

            // Prepare method arguments
            $methodArgs = [];
            if (isset($args['params']) && is_array($args['params'])) {
                // Use the params array directly if it exists
                $methodArgs = $args['params'];
            } else {
                // Otherwise use the args array as is
                $methodArgs = $args;
            }

            // Call the method with the prepared arguments
            $result = call_user_func_array([$component, $method], $methodArgs);

            // Log the component state after method call
            Logger::debug("Component state after method call", [
                'id' => $componentId,
                'method' => $method,
                'args' => $methodArgs,
                'state' => $component->getState()
            ]);

            // Re-render the component
            $html = $component->render();

            // Return successful response
            return [
                'success' => true,
                'component' => [
                    'id' => $componentId,
                    'html' => $html,
                    'state' => $component->getState(),
                    'class' => get_class($component)
                ],
                'status' => 200
            ];

        } catch (\Exception $e) {
            Logger::error('Error handling component update', [
                'component_id' => $componentId,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
                'status' => 500
            ];
        }
    }

    /**
     * Process a component event
     */
    public function processEvent($request) {
        // Monitor memory before processing
        $renderer = Renderer::getInstance();
        $renderer->monitorMemory(0.7); // Trigger cleanup at 70% memory usage
        
        // ... TODO: Implement event processing ...
    }
} 