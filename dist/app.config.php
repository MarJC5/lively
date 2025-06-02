<?php

namespace Lively;

defined('ABSPATH') || exit;

/**
 * Application Configuration
 * 
 * This file contains configuration settings for the Lively framework.
 */

use Lively\Core\Utils\Environment;

/**
 * Logging Configuration
 */
return [
    'logging' => [
        // Whether logging is enabled - always on in production, configurable in other environments
        'enabled' => Environment::isProduction() ? false : Environment::get('debug_mode', true),
        
        // Minimum log level to record (debug, info, warn, error)
        'level' => Environment::isProduction() ? 'info' : 'debug',
        
        // Log file path (null = use default path in logs directory)
        'file_path' => null, // Will default to ROOT_DIR/logs/app.log
        
        // Format output for better readability
        'format_output' => true,
        
        // Maximum log file size in MB before rotation (0 = no limit)
        'max_file_size' => 10,
        
        // Number of backup files to keep when rotating logs
        'max_files' => 5,
    ],
];
