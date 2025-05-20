<?php

if (defined('LIVELY_RESOURCES_DIR') && defined('LIVELY_ASSETS_URL')) {
    /**
     * Define the root directory
     */
    if (!defined('LIVELY_ROOT_DIR')) {
        define('LIVELY_ROOT_DIR', __DIR__);
    }

    /**
     * Create resources directory if it doesn't exist
     */
    if (!is_dir(LIVELY_RESOURCES_DIR)) {
        mkdir(LIVELY_RESOURCES_DIR, 0755, true);
    }

    /**
     * Check if resources directory is empty and copy sample files if needed
     */
    if (is_dir(LIVELY_RESOURCES_DIR) && count(glob(LIVELY_RESOURCES_DIR . '/*')) === 0) {
        // Copy sample files from framework resources
        $frameworkResourcesDir = __DIR__ . '/resources';
        if (is_dir($frameworkResourcesDir)) {
            // Recursively copy directory contents
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($frameworkResourcesDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $subPath = str_replace($frameworkResourcesDir . '/', '', $item->getPathname());
                $target = LIVELY_RESOURCES_DIR . '/' . $subPath;
                
                if ($item->isDir()) {
                    if (!is_dir($target)) {
                        mkdir($target, 0755, true);
                    }
                } else {
                    copy($item, $target);
                }
            }
            
            // Rename .env.example to .env if it exists
            if (file_exists(LIVELY_RESOURCES_DIR . '/.env.example')) {
                copy(LIVELY_RESOURCES_DIR . '/.env.example', LIVELY_RESOURCES_DIR . '/.env');

                // Delete .env.example
                unlink(LIVELY_RESOURCES_DIR . '/.env.example');
            }
        }
    }

    /**
     * Include the autoloader directly first
     */
    require_once LIVELY_ROOT_DIR . '/core/utils/Autoloader.php';

    /**
     * Initialize the autoloader
     */
    $autoloader = new Lively\Core\Utils\Autoloader();
    $autoloader->register()
        ->registerFrameworkNamespaces(LIVELY_ROOT_DIR);
    $autoloader->registerNamespace('Lively\\Resources', LIVELY_RESOURCES_DIR);

    /**
     * Initialize the Lively framework
     */
    Lively\Lively::init(LIVELY_ASSETS_URL);
} else {
    error_log('Lively framework not found, please check the LIVELY_ROOT_DIR and LIVELY_RESOURCES_DIR constants');
}