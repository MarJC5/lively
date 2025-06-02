<?php

namespace Lively\Core\Utils;

use Lively\Core\Utils\Environment;

class Session {
    /**
     * Default session lifetime in seconds (30 minutes)
     */
    const DEFAULT_LIFETIME = 1800;
    
    /**
     * Start a secure session with proper configuration
     * 
     * @param int|null $lifetime Session lifetime in seconds (null to use environment config)
     * @return void
     */
    public static function start($lifetime = null) {
        if (session_status() === PHP_SESSION_NONE) {
            // Get configuration values from Environment
            $securityConfig = [
                'lifetime' => Environment::get('security.session.lifetime', self::DEFAULT_LIFETIME),
                'secure' => Environment::get('security.session.secure', Environment::isProduction()),
                'samesite' => Environment::get('security.session.samesite', 'Lax')
            ];
            
            // Get session lifetime from config or use default
            $lifetime = $lifetime ?? $securityConfig['lifetime'];
            
            // Get secure flag from config - default to true in production
            $secure = $securityConfig['secure'];
            
            // Get SameSite attribute from config - default to Lax
            $sameSite = $securityConfig['samesite'];
            
            // Validate SameSite value to prevent security issues
            $validSameSiteValues = ['Lax', 'Strict', 'None'];
            if (!in_array($sameSite, $validSameSiteValues)) {
                Logger::warn('Invalid SameSite value in session config, defaulting to Lax', [
                    'provided_value' => $sameSite,
                    'valid_values' => $validSameSiteValues
                ]);
                $sameSite = 'Lax';
            }
            
            // If SameSite is None, secure must be true per spec
            if ($sameSite === 'None' && !$secure) {
                Logger::warn('SameSite=None requires secure flag to be true, enabling secure flag');
                $secure = true;
            }
            
            // Check if headers have already been sent
            if (!headers_sent()) {
                // Force session settings to be secure by setting them directly
                // These override any php.ini settings to ensure proper security
                ini_set('session.cookie_secure', $secure ? '1' : '0');
                ini_set('session.cookie_httponly', '1'); // Always force httpOnly
                ini_set('session.cookie_samesite', $sameSite); // Set SameSite from config
                ini_set('session.use_strict_mode', '1'); // Prevent session fixation
                ini_set('session.use_only_cookies', '1'); // Prevent URL-based sessions
                ini_set('session.use_trans_sid', '0'); // Disable transparent SID
                ini_set('session.gc_maxlifetime', (string)$lifetime); // Set garbage collection
                
                // Set secure session parameters
                session_set_cookie_params([
                    'lifetime' => $lifetime,
                    'path' => '/',
                    'domain' => '', // Use site domain automatically
                    'secure' => $secure, // Only transmit over HTTPS
                    'httponly' => true, // Prevent JavaScript access
                    'samesite' => $sameSite // Set from config
                ]);
                
                // Start the session
                session_start();
                
                // Check if we need to regenerate the session ID (if it's old)
                if (!isset($_SESSION['last_activity']) || 
                    (time() - $_SESSION['last_activity']) > ($lifetime / 4)) {
                    self::regenerate();
                }
                
                // Update last activity time
                $_SESSION['last_activity'] = time();
            } else {
                // Headers already sent, log a warning and proceed with minimal function
                Logger::warn('Attempted to start session after headers were sent. For optimal security, call Session::start() before any output.');
                
                // Try to start session with minimal impact
                @session_start();
            }
        }
    }
    
    /**
     * Regenerate the session ID to prevent session fixation
     * 
     * @param bool $deleteOldSession Whether to delete the old session
     * @return void
     */
    public static function regenerate($deleteOldSession = true) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }
    
    /**
     * Regenerate session ID after privilege level changes
     * 
     * @return void
     */
    public static function regenerateOnPrivilegeChange() {
        self::regenerate();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_privilege_change'] = time();
        }
    }
    
    /**
     * Check if session has expired
     * 
     * @param int|null $lifetime Session lifetime in seconds (null to use environment config)
     * @return bool Whether the session has expired
     */
    public static function isExpired($lifetime = null) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Get session lifetime from config or use default
        $lifetime = $lifetime ?? Environment::get('security.session.lifetime', self::DEFAULT_LIFETIME);
        
        return isset($_SESSION['last_activity']) && 
               (time() - $_SESSION['last_activity']) > $lifetime;
    }
    
    /**
     * Destroy the current session
     * 
     * @return void
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session data
            $_SESSION = [];
            
            // Delete the session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Destroy the session
            session_destroy();
        }
    }
} 