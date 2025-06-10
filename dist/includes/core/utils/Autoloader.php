<?php

namespace Lively\Core\Utils;

class Autoloader {
    protected static $instance;
    protected $namespaces = [];
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register the autoloader with PHP
     */
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
        return $this;
    }
    
    /**
     * Register a namespace with a base directory
     */
    public function registerNamespace(string $namespace, string $baseDir) {
        // Normalize namespace (ensure trailing slash)
        $namespace = trim($namespace, '\\') . '\\';
        
        // Normalize base directory (ensure trailing slash)
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        $this->namespaces[$namespace] = $baseDir;
        return $this;
    }
    
    /**
     * Auto-register all framework namespaces based on directory structure
     */
    public function registerFrameworkNamespaces(string $rootDir) {
        // Register core namespace
        $this->registerNamespace('Lively\\Admin', $rootDir . '/includes/admin');
        $this->registerNamespace('Lively\\Core', $rootDir . '/includes/core');
        $this->registerNamespace('Lively\\Database', $rootDir . '/includes/database');
        $this->registerNamespace('Lively\\Media', $rootDir . '/includes/media');
        $this->registerNamespace('Lively\\Models', $rootDir . '/includes/models');
        $this->registerNamespace('Lively\\SEO', $rootDir . '/includes/seo');
        $this->registerNamespace('Lively\\Resources', $rootDir . '/resources');

        // Register base namespace
        $this->registerNamespace('Lively', $rootDir);

        return $this;
    }
    
    /**
     * Load a class based on its fully qualified name
     */
    public function loadClass(string $class) {
        // Try to find the namespace that matches the class
        foreach ($this->namespaces as $namespace => $baseDir) {
            // If the class uses this namespace
            if (strpos($class, $namespace) === 0) {
                // Get the relative class name (without namespace)
                $relativeClass = substr($class, strlen($namespace));
                
                // Convert namespace separators to directory separators
                $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
                
                // Build the file path
                $filePath = $baseDir . $relativeClass . '.php';
                
                // If the file exists, include it
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Discovers and registers classes in a directory
     */
    public function discoverClasses(string $directory, string $namespace = '') {
        $files = glob($directory . '/*.php');
        
        foreach ($files as $file) {
            require_once $file;
        }
        
        // Recursively check subdirectories
        $subdirs = glob($directory . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $dirname = basename($subdir);
            $subNamespace = $namespace ? $namespace . '\\' . $dirname : $dirname;
            $this->discoverClasses($subdir, $subNamespace);
        }
        
        return $this;
    }
}