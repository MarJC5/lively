<?php

namespace Lively\Core\Utils;

use Lively\Core\Utils\Environment;

/**
 * Logger class responsible for handling application logging
 */
class Logger {
    /** @var string Log file path */
    protected $logFile;
    
    /** @var Logger Singleton instance */
    protected static $instance = null;
    
    /** @var bool Whether to format the output for better readability */
    protected $formatOutput = true;
    
    /** @var string Minimum log level to record */
    protected $minLogLevel = 'debug';
    
    /** @var bool Whether logging is enabled */
    protected $enabled = true;
    
    /** @var int Maximum log file size in bytes before rotation (0 = no limit) */
    protected $maxFileSize = 0;
    
    /** @var int Number of backup files to keep when rotating logs */
    protected $maxFiles = 5;
    
    /** @var array Log levels with their respective labels, colors and priority */
    protected $logLevels = [
        'debug' => ['label' => 'DEBUG', 'color' => '36m', 'priority' => 0], // Cyan
        'info'  => ['label' => 'INFO', 'color' => '32m', 'priority' => 1],  // Green
        'warn'  => ['label' => 'WARN', 'color' => '33m', 'priority' => 2],  // Yellow
        'error' => ['label' => 'ERROR', 'color' => '31m', 'priority' => 3], // Red
    ];
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->loadConfig();
    }
    
    /**
     * Load logger configuration from app.config.php
     */
    protected function loadConfig() {
        $configFile = defined('LIVELY_THEME_DIR') ? LIVELY_THEME_DIR . '/app.config.php' : '';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            if (isset($config['logging'])) {
                $loggingConfig = $config['logging'];
                
                // Set enabled status
                if (isset($loggingConfig['enabled'])) {
                    $this->enabled = (bool)$loggingConfig['enabled'];
                }
                
                // Set minimum log level
                if (isset($loggingConfig['level']) && isset($this->logLevels[$loggingConfig['level']])) {
                    $this->minLogLevel = $loggingConfig['level'];
                }
                
                // Set log file path
                if (isset($loggingConfig['file_path']) && $loggingConfig['file_path'] !== null) {
                    $this->logFile = $loggingConfig['file_path'];
                } else {
                    // Use logs directory instead of root directory
                    $logsDir = defined('LIVELY_THEME_DIR') ? LIVELY_THEME_DIR . '/logs' : '';
                    
                    // Create logs directory if it doesn't exist
                    if (!is_dir($logsDir)) {
                        mkdir($logsDir, 0755, true);
                    }
                    
                    $this->logFile = $logsDir . '/app.log';
                }
                
                // Set format output
                if (isset($loggingConfig['format_output'])) {
                    $this->formatOutput = (bool)$loggingConfig['format_output'];
                }
                
                // Set max file size (convert MB to bytes)
                if (isset($loggingConfig['max_file_size'])) {
                    $this->maxFileSize = (int)$loggingConfig['max_file_size'] * 1024 * 1024;
                }
                
                // Set max files
                if (isset($loggingConfig['max_files'])) {
                    $this->maxFiles = (int)$loggingConfig['max_files'];
                }
            }
        } else {
            error_log('No logging configuration found');
        }
    }
    
    /**
     * Get singleton instance
     * 
     * @return Logger
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Process data for safe logging
     * 
     * @param mixed $data Data to be processed
     * @return mixed Processed data safe for logging
     */
    public function processDataForLogging($data) {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->processDataForLogging($value);
            }
            return $result;
        } elseif (is_object($data)) {
            if ($data instanceof \Closure) {
                return "[Closure]";
            } elseif (method_exists($data, '__toString')) {
                return (string)$data;
            } else {
                // Create a simpler representation of the object
                $reflection = new \ReflectionObject($data);
                $properties = [];
                
                // Get public properties
                foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    $propName = $property->getName();
                    $properties[$propName] = $this->processDataForLogging($property->getValue($data));
                }
                
                return [
                    '__class' => get_class($data),
                    'properties' => $properties
                ];
            }
        } else {
            return $data;
        }
    }
    
    /**
     * Format data for log output with improved readability
     * 
     * @param mixed $data The data to format
     * @param string $indent Current indentation level
     * @return string Formatted string for logging
     */
    protected function formatData($data, $indent = '') {
        if (!$this->formatOutput) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        
        if (is_array($data)) {
            if (empty($data)) {
                return "[]";
            }
            
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            $result = $isAssoc ? "{\n" : "[\n";
            $nextIndent = $indent . '    ';
            
            foreach ($data as $key => $value) {
                if ($isAssoc) {
                    $result .= $nextIndent . $this->colorize('"' . $key . '":', '36m') . ' ';
                } else {
                    $result .= $nextIndent;
                }
                
                $result .= $this->formatData($value, $nextIndent) . ",\n";
            }
            
            $result = rtrim($result, ",\n") . "\n" . $indent;
            $result .= $isAssoc ? "}" : "]";
            return $result;
        } elseif (is_string($data)) {
            return $this->colorize('"' . $data . '"', '32m'); // Green for strings
        } elseif (is_numeric($data)) {
            return $this->colorize($data, '35m'); // Purple for numbers
        } elseif (is_bool($data)) {
            return $this->colorize($data ? 'true' : 'false', '33m'); // Yellow for booleans
        } elseif (is_null($data)) {
            return $this->colorize('null', '90m'); // Gray for null
        } else {
            return (string)$data;
        }
    }
    
    /**
     * Apply ANSI color to text for terminal output
     * 
     * @param string $text Text to colorize
     * @param string $color ANSI color code
     * @return string Colorized text
     */
    protected function colorize($text, $color) {
        // Only colorize if output is to be formatted and we're in CLI mode
        if ($this->formatOutput && php_sapi_name() === 'cli') {
            return "\033[" . $color . $text . "\033[0m";
        }
        return $text;
    }
    
    /**
     * Log a message with optional data
     * 
     * @param string $message Message to log
     * @param mixed|null $data Additional data to log
     * @param string $level Log level (debug, info, warn, error)
     * @return void
     */
    public function log(string $message, $data = null, string $level = 'debug'): void {
        // Skip logging if disabled
        if (!$this->enabled) {
            return;
        }
        
        // Check if the log level meets the minimum requirement
        if (!isset($this->logLevels[$level]) || 
            $this->logLevels[$level]['priority'] < $this->logLevels[$this->minLogLevel]['priority']) {
            return;
        }
        
        // Rotate log file if needed
        $this->rotateLogFileIfNeeded();
        
        $timestamp = date('Y-m-d H:i:s');
        $levelInfo = $this->logLevels[$level] ?? $this->logLevels['debug'];
        
        // Create a more readable log entry with better structure
        $logParts = [];
        $logParts[] = "[$timestamp]";
        
        if ($this->formatOutput) {
            $logParts[] = $this->colorize("[" . $levelInfo['label'] . "]", $levelInfo['color']);
        } else {
            $logParts[] = "[" . $levelInfo['label'] . "]";
        }
        
        $logParts[] = $message;
        
        $logEntry = implode(' ', $logParts);
        
        // Add formatted data if present
        if ($data !== null) {
            // Process the data for safe logging
            $processedData = $this->processDataForLogging($data);
            
            if ($this->formatOutput) {
                // Format data with better structure and indentation
                $formattedData = $this->formatData($processedData);
                $logEntry .= "\n" . $formattedData;
            } else {
                // Fall back to JSON if not formatting
                $dataStr = json_encode($processedData, JSON_PRETTY_PRINT);
                
                // Handle JSON encoding errors
                if ($dataStr === false) {
                    $dataStr = "[Error encoding data: " . json_last_error_msg() . "]";
                }
                
                $logEntry .= " " . $dataStr;
            }
        }
        
        $logEntry .= PHP_EOL . PHP_EOL; // Add extra newline for better separation between log entries
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Rotate log file if it exceeds the maximum size
     * 
     * @return void
     */
    protected function rotateLogFileIfNeeded(): void {
        // Skip if max file size is 0 (no limit) or file doesn't exist
        if ($this->maxFileSize <= 0 || !file_exists($this->logFile)) {
            return;
        }
        
        // Check current file size
        $size = filesize($this->logFile);
        
        // Rotate if file size exceeds max size
        if ($size > $this->maxFileSize) {
            // Get the logs directory path
            $logsDir = dirname($this->logFile);
            
            // Ensure the logs directory exists
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }
            
            // Remove oldest backup if max files is reached
            $oldestBackup = $this->logFile . '.' . $this->maxFiles;
            if (file_exists($oldestBackup)) {
                unlink($oldestBackup);
            }
            
            // Shift existing backups
            for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
                $oldName = $this->logFile . '.' . $i;
                $newName = $this->logFile . '.' . ($i + 1);
                if (file_exists($oldName)) {
                    rename($oldName, $newName);
                }
            }
            
            // Create new backup
            rename($this->logFile, $this->logFile . '.1');
            
            // Create empty log file
            touch($this->logFile);
            
            // Ensure proper permissions for the new log file
            chmod($this->logFile, 0644);
        }
    }
    
    /**
     * Enable or disable formatted output
     * 
     * @param bool $enabled Whether formatting should be enabled
     * @return self
     */
    public function setFormatOutput(bool $enabled): self {
        $this->formatOutput = $enabled;
        return $this;
    }
    
    /**
     * Static helper method for debug level logging
     * 
     * @param string $message Message to log
     * @param mixed|null $data Additional data to log
     * @return void
     */
    public static function debug(string $message, $data = null): void {
        self::getInstance()->log($message, $data, 'debug');
    }
    
    /**
     * Static helper method for info level logging
     * 
     * @param string $message Message to log
     * @param mixed|null $data Additional data to log
     * @return void
     */
    public static function info(string $message, $data = null): void {
        self::getInstance()->log($message, $data, 'info');
    }
    
    /**
     * Static helper method for warning level logging
     * 
     * @param string $message Message to log
     * @param mixed|null $data Additional data to log
     * @return void
     */
    public static function warn(string $message, $data = null): void {
        self::getInstance()->log($message, $data, 'warn');
    }
    
    /**
     * Static helper method for error level logging
     * 
     * @param string $message Message to log
     * @param mixed|null $data Additional data to log
     * @return void
     */
    public static function error(string $message, $data = null): void {
        self::getInstance()->log($message, $data, 'error');
    }
    
    /**
     * Set custom log file path
     * 
     * @param string $path Custom log file path
     * @return self
     */
    public function setLogFile(string $path): self {
        $this->logFile = $path;
        return $this;
    }
    
    /**
     * Enhance context information for error logging
     * 
     * @param array $context The original context data
     * @return array The enhanced context data
     */
    public static function enhanceErrorContext(array $context = []): array {
        // Add backtrace if not provided and we're not in production
        if (!isset($context['trace']) && Environment::isDevelopment()) {
            $context['trace'] = (new \Exception())->getTraceAsString();
        }
        
        // Add request information if available
        if (!isset($context['request']) && isset($_SERVER['REQUEST_URI'])) {
            $context['request'] = [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'client_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
        }
        
        return $context;
    }
    
    /**
     * Log an application error with consistent formatting
     * 
     * @param string $message Error message
     * @param array $context Additional context information
     * @param string $level Log level (error, warning, info, debug)
     * @return void
     */
    public static function logError(string $message, array $context = [], string $level = 'error'): void {
        // Enhance the context with additional information
        $enhancedContext = self::enhanceErrorContext($context);
        
        // Log with the appropriate level
        switch ($level) {
            case 'warn':
            case 'warning':
                self::warn($message, $enhancedContext);
                break;
            case 'info':
                self::info($message, $enhancedContext);
                break;
            case 'debug':
                self::debug($message, $enhancedContext);
                break;
            case 'error':
            default:
                self::error($message, $enhancedContext);
                break;
        }
    }
} 