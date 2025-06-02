<?php

namespace Lively\Core\Utils;

use Lively\Core\Utils\Logger;
use Lively\Core\Utils\Session;

class CSRF {
    /**
     * The session key for storing CSRF tokens
     */
    const TOKEN_KEY = 'lively_csrf_tokens';
    
    /**
     * Default token expiration time in seconds (1 hour)
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Ensure session is active before accessing session data
     * 
     * @return bool Whether the session is active
     */
    private static function ensureSessionActive(): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        
        return session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Generate a new CSRF token
     * 
     * @param string $formId Optional form identifier
     * @param int $expiration Token expiration time in seconds
     * @return string The generated token
     */
    public static function generate($formId = 'default', $expiration = self::DEFAULT_EXPIRATION) {
        // Ensure secure session is active
        if (!self::ensureSessionActive()) {
            Logger::error('Failed to start session for CSRF token generation');
            return '';
        }
        
        // Initialize tokens array if not exists
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = [];
        }
        
        // Generate random token
        $token = bin2hex(random_bytes(32));
        
        // Store token with expiration time
        $_SESSION[self::TOKEN_KEY][$formId] = [
            'token' => $token,
            'expires' => time() + $expiration
        ];
        
        return $token;
    }
    
    /**
     * Validate a CSRF token
     * 
     * @param string $token The token to validate
     * @param string $formId Optional form identifier
     * @param bool $removeAfterValidation Whether to remove the token after validation (default: false)
     * @return bool True if token is valid
     */
    public static function validate($token, $formId = 'default', $removeAfterValidation = false) {
        // Ensure secure session is active
        if (!self::ensureSessionActive()) {
            return false;
        }
        
        // Check if token exists and is valid
        if (isset($_SESSION[self::TOKEN_KEY][$formId])) {
            $storedToken = $_SESSION[self::TOKEN_KEY][$formId];
            
            // Check if token is still valid
            if (time() <= $storedToken['expires'] && hash_equals($storedToken['token'], $token)) {
                // Remove used token only if requested (for form submissions)
                if ($removeAfterValidation) {
                    self::remove($formId);
                }
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remove a CSRF token
     * 
     * @param string $formId Optional form identifier
     */
    public static function remove($formId = 'default') {
        // Ensure secure session is active
        if (!self::ensureSessionActive()) {
            return;
        }
        
        if (isset($_SESSION[self::TOKEN_KEY][$formId])) {
            unset($_SESSION[self::TOKEN_KEY][$formId]);
        }
    }
    
    /**
     * Clean expired tokens
     */
    public static function cleanExpired() {
        // Ensure secure session is active
        if (!self::ensureSessionActive()) {
            return;
        }
        
        if (isset($_SESSION[self::TOKEN_KEY]) && is_array($_SESSION[self::TOKEN_KEY])) {
            foreach ($_SESSION[self::TOKEN_KEY] as $formId => $tokenData) {
                if (time() > $tokenData['expires']) {
                    unset($_SESSION[self::TOKEN_KEY][$formId]);
                }
            }
        }
    }
    
    /**
     * Generate HTML for a CSRF token input field
     * 
     * @param string $formId Optional form identifier
     * @return string HTML input field
     */
    public static function field($formId = 'default') {
        $token = self::generate($formId);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Check if a form submission has a valid CSRF token
     * 
     * @return bool True if request has valid CSRF token
     */
    public static function checkRequest() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        $formId = $_POST['csrf_form_id'] ?? $_GET['csrf_form_id'] ?? 'default';
        
        if ($token === null) {
            return false;
        }
        
        // For form submissions, we want to remove the token after validation
        return self::validate($token, $formId, true);
    }
} 