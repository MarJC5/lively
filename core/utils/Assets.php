<?php

namespace Lively\Core\Utils;

/**
 * Assets Manager for handling JavaScript and CSS files
 */
class Assets
{
    /**
     * @var array Registered JavaScript files
     */
    private static $jsFiles = [];
    
    /**
     * @var array Registered CSS files
     */
    private static $cssFiles = [];
    
    /**
     * @var bool Core JS already registered
     */
    private static $coreJsRegistered = false;
    
    /**
     * @var bool Core CSS already registered
     */
    private static $coreCssRegistered = false;
    
    /**
     * @var bool Assets directories initialized
     */
    private static $directoriesInitialized = false;
    
    /**
     * Initialize asset directories
     *
     * @return void
     */
    public static function initAssetDirectories($rootDir = LIVELY_ROOT_DIR): void
    {
        if (self::$directoriesInitialized) {
            return;
        }
        
        // Define the core asset directories
        $coreAssetDirs = [
            $rootDir . '/assets/js',
            $rootDir . '/assets/css',
        ];
        
        // Ensure each directory exists
        foreach ($coreAssetDirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        self::$directoriesInitialized = true;
    }
    
    /**
     * Register a JavaScript file
     *
     * @param string $path Path to the JavaScript file
     * @param bool $isCore Whether this is a core file
     * @return void
     */
    public static function registerJs(string $path, bool $isCore = false): void
    {
        if (!in_array($path, self::$jsFiles)) {
            self::$jsFiles[] = $path;
        }
        
        if ($isCore) {
            self::$coreJsRegistered = true;
        }
    }
    
    /**
     * Register a CSS file
     *
     * @param string $path Path to the CSS file
     * @param bool $isCore Whether this is a core file
     * @return void
     */
    public static function registerCss(string $path, bool $isCore = false): void
    {
        if (!in_array($path, self::$cssFiles)) {
            self::$cssFiles[] = $path;
        }
        
        if ($isCore) {
            self::$coreCssRegistered = true;
        }
    }
    
    /**
     * Register core assets
     *
     * @return void
     */
    public static function registerCoreAssets($rootDir = LIVELY_ASSETS_URL): void
    {
        // Ensure asset directories exist before registering assets
        self::initAssetDirectories($rootDir);
        
        if (!self::$coreJsRegistered) {
            self::registerJs($rootDir . '/assets/js/lively.js', true);
        }
        
        if (!self::$coreCssRegistered) {
            self::registerCss($rootDir . '/assets/css/lively.css', true);
        }
    }
    
    /**
     * Get all registered assets
     * 
     * @return array Array with 'scripts' and 'styles' arrays
     */
    public static function getRegisteredAssets(): array
    {
        return [
            'scripts' => self::$jsFiles,
            'styles' => self::$cssFiles
        ];
    }
    
    /**
     * Get HTML for all registered JavaScript files
     *
     * @return string HTML script tags
     */
    public static function getJsHtml(): string
    {
        $html = '';
        foreach (self::$jsFiles as $file) {
            $html .= '<script src="' . htmlspecialchars($file) . '"></script>' . PHP_EOL;
        }
        return $html;
    }
    
    /**
     * Get HTML for all registered CSS files
     *
     * @return string HTML link tags
     */
    public static function getCssHtml(): string
    {
        $html = '';
        foreach (self::$cssFiles as $file) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($file) . '">' . PHP_EOL;
        }
        return $html;
    }
    
    /**
     * Get HTML for all registered assets
     *
     * @return string HTML for all registered assets
     */
    public static function getAssetsHtml(): string
    {
        return self::getCssHtml() . self::getJsHtml();
    }
} 