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
if (!function_exists('lively')) {
    function lively($component, $props = []) {
        echo \Lively\Core\Engine::render($component, $props);
    }
}

/**
 * Get component states
 * @return string HTML script tag
 */
if (!function_exists('lively_states')) {
    function lively_states() {
        echo \Lively\Core\Engine::componentStates();
    }
}

/**
 * Get the CSRF token
 * @return string HTML meta tag
 */
if (!function_exists('lively_csrf')) {
    function lively_csrf() {
        echo '<meta name="csrf-token" content="' . \Lively\Core\Utils\CSRF::generate() . '">';
    }
}