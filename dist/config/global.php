<?php

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Lively framework helper functions
 */

 /**
  * Render a component
  * 
  * @param string $component
  * @param array $props
  * @return string HTML
  */
if (!function_exists('ly')) {
    function ly($component, $props = []) {
        echo \Lively\Core\Engine::render($component, $props);
    }
}

/**
 * Helper function to write HTML directly in component props
 * 
 * @param callable $callback Function that returns HTML content
 * @return string The HTML content
 */
if (!function_exists('ly_html')) {
    function ly_html(callable $callback) {
        ob_start();
        $callback();
        return ob_get_clean();
    }
}

/**
 * Get component states
 * @return string HTML script tag
 */
if (!function_exists('ly_states')) {
    function ly_states() {
        echo \Lively\Core\Engine::componentStates();
    }
}

/**
 * Get the CSRF token
 * @return string HTML meta tag
 */
if (!function_exists('ly_csrf')) {
    function ly_csrf() {
        echo '<meta name="csrf-token" content="' . \Lively\Core\Utils\CSRF::generate() . '">';
    }
}
