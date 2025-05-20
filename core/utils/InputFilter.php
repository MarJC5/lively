<?php

namespace Lively\Core\Utils;

/**
 * Input validation and sanitization utility
 * 
 * Provides methods for sanitizing and validating user input
 */
class InputFilter {
    /**
     * Sanitize string input
     * 
     * @param string $input The input to sanitize
     * @param bool $allowHtml Whether to allow HTML tags
     * @return string Sanitized string
     */
    public static function sanitizeString($input, $allowHtml = false) {
        if ($input === null) {
            return '';
        }
        
        $input = (string)$input;
        
        // Strip or encode HTML based on parameter
        if (!$allowHtml) {
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            // If HTML is allowed, we still filter out dangerous content
            $input = self::filterHtml($input);
        }
        
        return $input;
    }
    
    /**
     * Filter HTML to remove dangerous elements/attributes
     * 
     * @param string $html HTML content to filter
     * @return string Filtered HTML
     */
    public static function filterHtml($html) {
        // Define allowed tags and attributes
        $allowedTags = [
            'p', 'br', 'b', 'i', 'u', 'em', 'strong', 'a', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'img', 'div', 'span'
            // Form elements removed to prevent phishing and UI hijacking
        ];
        
        $allowedAttrs = [
            'href', 'src', 'alt', 'title', 'class', 'id', 'name',
            'width', 'height', 'target'
            // Style attribute removed to prevent CSS-based XSS
            // Form attributes removed
        ];
        
        // Use DOMDocument to safely parse and filter HTML
        $dom = new \DOMDocument('1.0', 'UTF-8');
        
        // Preserve UTF-8 encoding
        $dom->substituteEntities = false;
        $dom->encoding = 'UTF-8';
        
        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        
        // Add a root element to handle HTML fragments
        $html = '<div>' . $html . '</div>';
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Reset errors
        libxml_clear_errors();
        
        // Process all elements recursively
        self::filterDomElements($dom, $allowedTags, $allowedAttrs);
        
        // Get the filtered HTML
        $filteredHtml = $dom->saveHTML($dom->documentElement);
        
        // Remove the wrapper div
        $filteredHtml = preg_replace('/<div>(.*)<\/div>/s', '$1', $filteredHtml);
        
        return $filteredHtml ?? '';
    }
    
    /**
     * Filter DOM elements recursively
     * 
     * @param \DOMNode $node Current node
     * @param array $allowedTags Allowed HTML tags
     * @param array $allowedAttrs Allowed HTML attributes
     */
    private static function filterDomElements(\DOMNode $node, array $allowedTags, array $allowedAttrs) {
        // Process all child nodes
        if ($node->hasChildNodes()) {
            $childNodes = [];
            foreach ($node->childNodes as $child) {
                $childNodes[] = $child;
            }
            
            foreach ($childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    // Check if tag is allowed
                    if (!in_array(strtolower($child->nodeName), $allowedTags)) {
                        // If not allowed, replace with text content
                        $textNode = $node->ownerDocument->createTextNode($child->textContent);
                        $node->replaceChild($textNode, $child);
                        continue;
                    }
                    
                    // Extra security for specific tags
                    $tagName = strtolower($child->nodeName);
                    if ($tagName === 'a' && $child instanceof \DOMElement) {
                        // Force links to open in new window and add noopener/noreferrer
                        $child->setAttribute('target', '_blank');
                        $child->setAttribute('rel', 'noopener noreferrer');
                    }
                    
                    // Filter attributes - only for DOMElement types
                    if ($child instanceof \DOMElement && $child->hasAttributes()) {
                        $attributes = [];
                        foreach ($child->attributes as $attr) {
                            $attributes[] = $attr;
                        }
                        
                        foreach ($attributes as $attr) {
                            if (!in_array(strtolower($attr->name), $allowedAttrs)) {
                                $child->removeAttribute($attr->name);
                            } else if (strtolower($attr->name) === 'href' || strtolower($attr->name) === 'src') {
                                // Sanitize URLs
                                $url = $attr->value;
                                $sanitizedUrl = self::sanitizeUrl($url);
                                if ($sanitizedUrl !== $url) {
                                    $child->setAttribute($attr->name, $sanitizedUrl);
                                }
                            }
                        }
                    }
                    
                    // Process child's children
                    self::filterDomElements($child, $allowedTags, $allowedAttrs);
                }
            }
        }
    }
    
    /**
     * Sanitize a URL
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public static function sanitizeUrl($url) {
        if ($url === null || $url === '') {
            return '';
        }
        
        // Convert to string if not already
        $url = (string)$url;
        
        // First, filter out javascript: and other potentially dangerous protocols
        if (preg_match('/^(javascript|data|vbscript|file|about|blob):/i', $url)) {
            return '#'; // Return a harmless anchor link instead
        }
        
        // Normalize the URL and check if it's valid
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Validate the URL structure
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // If not an absolute URL, check if it's a relative URL
            if (strpos($url, '/') === 0 || strpos($url, './') === 0 || strpos($url, '#') === 0) {
                // It's a relative URL, which is generally safe
                return $url;
            } else {
                // If it's neither a valid absolute URL nor a relative URL, it might be malicious
                return '#';
            }
        }
        
        return $url;
    }
    
    /**
     * Sanitize an email address
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitizeEmail($email) {
        if ($email === null) {
            return '';
        }
        
        return filter_var((string)$email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize an integer
     * 
     * @param mixed $input Input to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Sanitized integer
     */
    public static function sanitizeInt($input, $min = null, $max = null) {
        $options = [];
        
        if ($min !== null) {
            $options['min_range'] = $min;
        }
        
        if ($max !== null) {
            $options['max_range'] = $max;
        }
        
        if (!empty($options)) {
            $filtered = filter_var($input, FILTER_VALIDATE_INT, ['options' => $options]);
            return $filtered === false ? 0 : $filtered;
        }
        
        return filter_var($input, FILTER_VALIDATE_INT) === false ? 0 : (int)$input;
    }
    
    /**
     * Sanitize a float
     * 
     * @param mixed $input Input to sanitize
     * @param float $min Minimum allowed value
     * @param float $max Maximum allowed value
     * @return float Sanitized float
     */
    public static function sanitizeFloat($input, $min = null, $max = null) {
        $filtered = filter_var($input, FILTER_VALIDATE_FLOAT);
        $filtered = $filtered === false ? 0.0 : (float)$filtered;
        
        if ($min !== null && $filtered < $min) {
            return (float)$min;
        }
        
        if ($max !== null && $filtered > $max) {
            return (float)$max;
        }
        
        return $filtered;
    }
    
    /**
     * Sanitize a boolean
     * 
     * @param mixed $input Input to sanitize
     * @return bool Sanitized boolean
     */
    public static function sanitizeBool($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Sanitize a file path to prevent path traversal
     * 
     * @param string $path Path to sanitize
     * @param string $basePath Base path that should contain the file
     * @return string|null Sanitized path or null if invalid
     */
    public static function sanitizeFilePath($path, $basePath) {
        // Normalize directory separators
        $path = str_replace('\\', '/', $path);
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        
        // Remove any null bytes (poison null byte attack prevention)
        $path = str_replace("\0", '', $path);
        
        // Reject paths with suspicious path traversal patterns
        if (preg_match('#(\.{2,}[\/\\\\]|[\/\\\\]\.{2,})#', $path)) {
            Logger::warn('Potential path traversal attempt detected', [
                'path' => $path,
                'basePath' => $basePath
            ]);
            return null;
        }
        
        // Make the path absolute
        $fullPath = $basePath . '/' . ltrim($path, '/');
        
        // Canonicalize the path (this reduces '..' and '.' references)
        $realPath = realpath($fullPath);
        
        // If realpath fails, the file doesn't exist or the path is invalid
        if ($realPath === false) {
            // For creating new files, we need to check if the directory exists
            $dirPath = dirname($fullPath);
            if (!file_exists($dirPath)) {
                return null;
            }
            
            // If the directory exists, check if it's within the base path
            $realDirPath = realpath($dirPath);
            if ($realDirPath === false || strpos($realDirPath, $basePath) !== 0) {
                return null;
            }
            
            // Return the original path since the file doesn't exist yet
            return $fullPath;
        }
        
        // Check if the real path is within the base directory
        if (strpos($realPath, $basePath) !== 0) {
            Logger::warn('Path traversal attempt: file outside allowed directory', [
                'path' => $path,
                'resolvedPath' => $realPath,
                'basePath' => $basePath
            ]);
            return null;
        }
        
        return $realPath;
    }
    
    /**
     * Validate an email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate a URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Sanitize an array recursively
     * 
     * @param array $array Array to sanitize
     * @param bool $allowHtml Whether to allow HTML
     * @return array Sanitized array
     */
    public static function sanitizeArray($array, $allowHtml = false) {
        $result = [];
        
        foreach ($array as $key => $value) {
            // For component state and form elements, we need to be careful with sanitization
            // Preserve special attribute keys that are needed for form controls
            $formAttributeKeys = ['type', 'value', 'min', 'max', 'step', 'placeholder', 'name', 'id', 
                                 'checked', 'selected', 'disabled', 'readonly', 'required', 'class'];
            
            if (is_string($key) && in_array($key, $formAttributeKeys)) {
                // For special form attribute keys, preserve the original key
                $sanitizedKey = $key;
            } else {
                // For other keys, sanitize but avoid over-sanitizing
                $sanitizedKey = is_string($key) ? self::sanitizeString($key) : $key;
            }
            
            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $result[$sanitizedKey] = self::sanitizeArray($value, $allowHtml);
            } else if (is_string($value)) {
                // For form control values, we need to be more permissive
                if (is_string($key) && in_array($key, $formAttributeKeys)) {
                    // Allow HTML in form control values but still sanitize for XSS
                    $result[$sanitizedKey] = self::sanitizeString($value, true);
                } else {
                    $result[$sanitizedKey] = self::sanitizeString($value, $allowHtml);
                }
            } else {
                // For non-string values, preserve the type
                $result[$sanitizedKey] = $value;
            }
        }
        
        return $result;
    }
} 