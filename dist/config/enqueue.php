<?php

// Prevent direct access.
defined('ABSPATH') or exit;

if (!function_exists('lively_enqueue_scripts')) {
    /**
     * Enqueue scripts
     * 
     * @param string $hook
     * @return void
     */
    function lively_enqueue_scripts($hook) {
        $manifest_path = LIVELY_THEME_DIR . '/manifest.json';
        if (!file_exists($manifest_path)) {
            error_log('Manifest file not found');
            return;
        }
        
        $manifest = json_decode(file_get_contents($manifest_path), true);
        
        // Main entry point
        $main_entry = 'src/js/main.js';
        if (isset($manifest[$main_entry])) {
            // Only enqueue the main script - let ES6 modules handle imports
            wp_enqueue_script(
                'sigeasy-admin-scripts',
                LIVELY_THEME_URL . '/' . $manifest[$main_entry]['file'],
                [], // No dependencies - ES6 modules handle this
                LIVELY_THEME_VERSION,
                true
            );
            
            // Add module type for main script
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'sigeasy-admin-scripts') {
                    return str_replace('<script ', '<script type="module" ', $tag);
                }
                return $tag;
            }, 10, 2);
            
            // Enqueue CSS files
            if (isset($manifest[$main_entry]['css'])) {
                foreach ($manifest[$main_entry]['css'] as $index => $css) {
                    wp_enqueue_style(
                        'sigeasy-admin-styles-' . $index,
                        LIVELY_THEME_URL . '/' . $css,
                        [],
                        LIVELY_THEME_VERSION
                    );
                }
            }
        }
    }
}

/**
 * Enqueue scripts
 * 
 * @param string $hook
 * @return void
 */
add_action('wp_enqueue_scripts', 'lively_enqueue_scripts');