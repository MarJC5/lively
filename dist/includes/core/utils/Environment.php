<?php

namespace Lively\Core\Utils;

use Lively\Core\Utils\DotEnv;
use Lively\Core\Utils\Logger;

/**
 * Environment configuration settings
 */
class Environment {
    /** Environment constants */
    const ENV_DEVELOPMENT = 'development';
    const ENV_PRODUCTION = 'production';
    const ENV_TESTING = 'testing';
    
    const VALID_ENVIRONMENTS = [
        self::ENV_DEVELOPMENT,
        self::ENV_PRODUCTION,
        self::ENV_TESTING
    ];
    
    /** @var string Current environment (default to production for security) */
    private static $currentEnvironment = self::ENV_PRODUCTION;
    
    /** @var array Environment-specific default configuration */
    private static $defaultConfig = [
        self::ENV_DEVELOPMENT => [
            'display_errors' => true,
            'error_reporting' => E_ALL,
            'session_secure' => false,
            'session_lifetime' => 3600,
            'debug_mode' => true
        ],
        self::ENV_PRODUCTION => [
            'display_errors' => false,
            'error_reporting' => E_ERROR | E_PARSE,
            'session_secure' => true,
            'session_lifetime' => 1800,
            'debug_mode' => false
        ],
        self::ENV_TESTING => [
            'display_errors' => true,
            'error_reporting' => E_ALL,
            'session_secure' => false,
            'session_lifetime' => 3600,
            'debug_mode' => true
        ]
    ];
    
    /** @var array Required environment variables for secure operation */
    private static $requiredEnvVars = [
        self::ENV_PRODUCTION => [
            'LIVELY_ENV',
            'SESSION_LIFETIME',
            'SESSION_SECURE'
        ]
    ];
    
    /** @var array Runtime configuration */
    private static $config = [];
    
    /**
     * Initialize environment based on .env file and environment variables
     * 
     * @return void
     */
    public static function init() {
        // Load environment variables from .env file if it exists
        if (file_exists(LIVELY_THEME_DIR . '/.env')) {
            DotEnv::load(LIVELY_THEME_DIR . '/.env');
        }
        
        // Determine environment
        self::determineEnvironment();
        
        // Load configuration
        self::loadConfiguration();
        
        // Validate required environment variables in production
        self::validateRequiredEnvVars();
        
        // Apply PHP configuration settings
        self::applyConfig();
    }
    
    /**
     * Determine which environment we're running in
     * 
     * @return void
     */
    private static function determineEnvironment() {
        $env = getenv('LIVELY_ENV');
        
        if ($env && in_array($env, self::VALID_ENVIRONMENTS)) {
            self::$currentEnvironment = $env;
        } else if ($env) {
            self::logError('Invalid environment value provided', ['value' => $env]);
        }
    }
    
    /**
     * Load configuration from default values and environment variables
     * 
     * @return void
     */
    private static function loadConfiguration() {
        // Start with default config for current environment
        self::$config = self::$defaultConfig[self::$currentEnvironment];
        
        // Process configuration in order of priority
        self::loadKnownMappings();
        self::loadAdditionalEnvironmentVariables();
    }
    
    /**
     * Known configuration mappings from environment variables to config keys
     * 
     * @return array Mapping of environment variable names to configuration keys
     */
    private static function getKnownMappings() {
        return [
            'SESSION_LIFETIME' => 'session_lifetime',
            'SESSION_SECURE' => 'session_secure',
            'DISPLAY_ERRORS' => 'display_errors',
            'DEBUG_MODE' => 'debug_mode'
        ];
    }
    
    /**
     * Load configuration from known environment variable mappings
     * These are high-priority settings that should override defaults
     * 
     * @return void
     */
    private static function loadKnownMappings() {
        // Process each known mapping
        foreach (self::getKnownMappings() as $envVar => $configKey) {
            self::applyEnvironmentVariable($envVar, $configKey);
        }
    }
    
    /**
     * Apply a specific environment variable to the configuration
     * 
     * @param string $envVar Environment variable name
     * @param string $configKey Configuration key
     * @return void
     */
    private static function applyEnvironmentVariable($envVar, $configKey) {
        $value = self::getEnvVar($envVar);
        if ($value !== null) {
            self::$config[$configKey] = $value;
        }
    }
    
    /**
     * Load additional configuration from all available environment variables
     * 
     * @return void
     */
    private static function loadAdditionalEnvironmentVariables() {
        // Get the known mapping keys for comparison
        $knownConfigKeys = array_values(self::getKnownMappings());
        
        // Process all environment variables from $_ENV
        foreach ($_ENV as $key => $value) {
            $configKey = strtolower($key);
            
            // Skip if already processed through known mappings
            if (!self::shouldProcessEnvironmentVariable($configKey, $knownConfigKeys)) {
                continue;
            }
            
            self::$config[$configKey] = self::convertValueType($value);
        }
    }
    
    /**
     * Determine if an environment variable should be processed
     * Variables should be processed if:
     * 1. They're not already in the config, OR
     * 2. They're in the config but not from a known mapping
     * 
     * @param string $configKey The configuration key (lowercase)
     * @param array $knownConfigKeys List of known config keys to check against
     * @return bool Whether the variable should be processed
     */
    private static function shouldProcessEnvironmentVariable($configKey, $knownConfigKeys) {
        // If the key doesn't exist in config, we should process it
        if (!array_key_exists($configKey, self::$config)) {
            return true;
        }
        
        // If it exists but isn't a known mapping, we should still process it
        if (!in_array($configKey, $knownConfigKeys)) {
            return true;
        }
        
        // Otherwise, it's already been processed as a known mapping
        return false;
    }
    
    /**
     * Get environment variable with type conversion
     * 
     * @param string $name Environment variable name
     * @return mixed|null Converted value or null if not found
     */
    private static function getEnvVar($name) {
        $value = getenv($name);
        return ($value !== false) ? self::convertValueType($value) : null;
    }
    
    /**
     * Convert string values to appropriate native PHP types
     * This method transforms environment variable strings into their proper data types:
     * - 'true'/'false' to boolean values
     * - numeric strings to int/float values
     * - leaves other strings as they are
     * 
     * @param mixed $value Value to convert
     * @return mixed Converted value with the appropriate type
     */
    private static function convertValueType($value) {
        // If it's not a string, no conversion needed
        if (!is_string($value)) {
            return $value;
        }
        
        // Check for boolean values first
        $boolValue = self::convertToBoolean($value);
        if ($boolValue !== null) {
            return $boolValue;
        }
        
        // Then check for numeric values
        $numValue = self::convertToNumber($value);
        if ($numValue !== null) {
            return $numValue;
        }
        
        // If no conversion applies, return the original string
        return $value;
    }
    
    /**
     * Convert string value to boolean if it explicitly represents a boolean
     * Handles the string values 'true' and 'false' only.
     * 
     * @param string $value Value to convert
     * @return bool|null Boolean value or null if not a boolean string
     */
    private static function convertToBoolean($value) {
        if ($value === 'true') {
            return true;
        }
        
        if ($value === 'false') {
            return false;
        }
        
        return null;
    }
    
    /**
     * Convert string value to a number if it's numeric
     * Handles integers and floating point numbers.
     * 
     * @param string $value Value to convert
     * @return int|float|null Numeric value (int or float) or null if not numeric
     */
    private static function convertToNumber($value) {
        if (!is_numeric($value)) {
            return null;
        }
        
        // Convert to float if it has a decimal point, otherwise to integer
        return (strpos($value, '.') !== false) ? (float)$value : (int)$value;
    }
    
    /**
     * Validate that required environment variables are set based on environment
     * 
     * @return void
     */
    private static function validateRequiredEnvVars() {
        // Only validate in production environment
        if (!self::isProduction()) {
            return;
        }
        
        $missing = [];
        
        foreach (self::$requiredEnvVars[self::ENV_PRODUCTION] as $varName) {
            if (getenv($varName) === false) {
                $missing[] = $varName;
            }
        }
        
        if (!empty($missing)) {
            self::logError('Missing required environment variables in production: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Log an error message using Logger if available, otherwise use error_log
     * 
     * @param string $message Error message
     * @param array $context Optional context data
     * @return void
     */
    private static function logError($message, array $context = []) {
        if (class_exists('\\Lively\\Core\\Utils\\Logger')) {
            Logger::error($message, $context);
        } else {
            error_log("ERROR: $message");
        }
    }
    
    /**
     * Apply environment-specific configuration to PHP
     * 
     * @return void
     */
    private static function applyConfig() {
        $config = self::$config;
        
        ini_set('display_errors', $config['display_errors'] ? 1 : 0);
        ini_set('display_startup_errors', $config['display_errors'] ? 1 : 0);
        error_reporting($config['error_reporting']);
    }
    
    /**
     * Get the current environment
     * 
     * @return string
     */
    public static function getEnvironment() {
        return self::$currentEnvironment;
    }
    
    /**
     * Check if we're in development environment
     * 
     * @return bool
     */
    public static function isDevelopment() {
        return self::$currentEnvironment === self::ENV_DEVELOPMENT;
    }
    
    /**
     * Check if we're in production environment
     * 
     * @return bool
     */
    public static function isProduction() {
        return self::$currentEnvironment === self::ENV_PRODUCTION;
    }
    
    /**
     * Check if we're in testing environment
     * 
     * @return bool
     */
    public static function isTesting() {
        return self::$currentEnvironment === self::ENV_TESTING;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        // Try direct match
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        // Try case-insensitive match
        $lowercaseKey = strtolower($key);
        if (isset(self::$config[$lowercaseKey])) {
            return self::$config[$lowercaseKey];
        }
        
        // Try environment variable
        $value = self::getEnvVar($key) ?? self::getEnvVar(strtoupper($key));
        if ($value !== null) {
            return $value;
        }
        
        // Nothing found, return default
        return $default;
    }
} 