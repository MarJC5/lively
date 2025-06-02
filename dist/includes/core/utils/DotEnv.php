<?php

namespace Lively\Core\Utils;

/**
 * Simple DotEnv implementation for loading environment variables from .env files
 */
class DotEnv {
    /**
     * Load environment variables from a .env file
     * 
     * @param string $path Path to the .env file
     * @return bool True if file was loaded successfully
     */
    public static function load($path) {
        if (!is_readable($path)) {
            return false;
        }
        
        // Read the .env file line by line
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variable assignment
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                if (self::hasQuotes($value)) {
                    $value = self::removeQuotes($value);
                }
                
                // Set environment variable
                self::setEnvVar($name, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Check if a string has surrounding quotes
     * 
     * @param string $value String to check
     * @return bool True if surrounded by quotes
     */
    private static function hasQuotes($value) {
        return (
            (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
        );
    }
    
    /**
     * Remove surrounding quotes from a string
     * 
     * @param string $value String with quotes
     * @return string String without quotes
     */
    private static function removeQuotes($value) {
        return substr($value, 1, -1);
    }
    
    /**
     * Set an environment variable in all relevant places
     * 
     * @param string $name Variable name
     * @param string $value Variable value
     * @return void
     */
    private static function setEnvVar($name, $value) {
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
    
    /**
     * Get an environment variable with type conversion
     * 
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        return self::convertValueType($value);
    }
    
    /**
     * Convert string values to appropriate types
     * 
     * @param mixed $value Value to convert
     * @return mixed Converted value
     */
    private static function convertValueType($value) {
        if (!is_string($value)) {
            return $value;
        }
        
        if ($value === 'true') {
            return true;
        } elseif ($value === 'false') {
            return false;
        } elseif (is_numeric($value)) {
            return (strpos($value, '.') !== false) ? (float)$value : (int)$value;
        }
        
        return $value;
    }
} 