<?php

namespace Lively\Core\WordPress;

/**
 * WordPress Hooks Integration
 * Handles WordPress hook integration for components
 */
class Hooks {
    /**
     * Execute a WordPress action
     * 
     * @param string $hook The hook name
     * @param mixed ...$args Arguments to pass to the hook
     * @return void
     */
    public static function doAction($hook, ...$args) {
        if (function_exists('do_action')) {
            do_action($hook, ...$args);
        }
    }

    /**
     * Add a WordPress action
     * 
     * @param string $hook The hook name
     * @param callable $callback The callback function
     * @param int $priority The priority
     * @param int $acceptedArgs Number of arguments to accept
     * @return void
     */
    public static function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1) {
        if (function_exists('add_action')) {
            add_action($hook, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * Check if a WordPress action has been executed
     * 
     * @param string $hook The hook name
     * @return bool
     */
    public static function didAction($hook) {
        if (function_exists('did_action')) {
            return did_action($hook);
        }
        return false;
    }

    /**
     * Remove a WordPress action
     * 
     * @param string $hook The hook name
     * @param callable $callback The callback function
     * @param int $priority The priority
     * @return bool
     */
    public static function removeAction($hook, $callback, $priority = 10) {
        if (function_exists('remove_action')) {
            return remove_action($hook, $callback, $priority);
        }
        return false;
    }

    /**
     * Add a WordPress action before mount
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function beforeMount($id, $callback) {
        self::addAction("lively_component_{$id}_before_mount", $callback);
    }

    /**
     * Add a WordPress action mounted
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function mounted($id, $callback) {
        self::addAction("lively_component_{$id}_mounted", $callback);
    }

    /**
     * Add a WordPress action before update
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function beforeUpdate($id, $callback) {
        self::addAction("lively_component_{$id}_before_update", $callback);
    }

    /**
     * Add a WordPress action updated
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function updated($id, $callback) {
        self::addAction("lively_component_{$id}_updated", $callback);
    }

    /**
     * Add a WordPress action before unmount
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function beforeUnmount($id, $callback) {
        self::addAction("lively_component_{$id}_before_unmount", $callback);
    }

    /**
     * Add a WordPress action unmounted
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function unmounted($id, $callback) {
        self::addAction("lively_component_{$id}_unmounted", $callback);
    }

    /**
     * Add a WordPress action mount
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function mount($id, $callback) {
        self::addAction("lively_component_{$id}_mount", $callback);
    }

    /**
     * Add a WordPress action unmount
     * 
     * @param string $id The component ID
     * @param callable $callback The callback function
     * @return void
     */
    public static function unmount($id, $callback) {
        self::addAction("lively_component_{$id}_unmount", $callback);
    }
    
} 