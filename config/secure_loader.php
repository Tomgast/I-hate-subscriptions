<?php
/**
 * Universal Secure Configuration Loader for CashControl
 * This file ensures secure-config.php is loaded correctly from any location
 */

// Prevent multiple inclusions
if (defined('SECURE_CONFIG_LOADED')) {
    return;
}
define('SECURE_CONFIG_LOADED', true);

// Only load if getSecureConfig function doesn't exist yet
if (!function_exists('getSecureConfig')) {
    
    // Define possible paths for secure-config.php (user has it outside web directory)
    $possiblePaths = [
        // Outside document root (most likely location for user's setup)
        dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/../secure-config.php',
        
        // Project root variations
        __DIR__ . '/../secure-config.php',
        __DIR__ . '/../../secure-config.php',
        
        // Common Plesk hosting paths
        dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/secure-config.php',
        '/var/www/vhosts/' . $_SERVER['HTTP_HOST'] . '/secure-config.php',
        '/home/' . get_current_user() . '/secure-config.php',
        
        // Absolute server paths
        '/secure-config.php',
        '/home/secure-config.php'
    ];
    
    $configLoaded = false;
    $debugInfo = [];
    
    foreach ($possiblePaths as $path) {
        $debugInfo[] = "Checking: $path - " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . (file_exists($path) && is_readable($path) ? ' & READABLE' : '');
        
        if (file_exists($path) && is_readable($path)) {
            try {
                require_once $path;
                $configLoaded = true;
                error_log("CashControl: SUCCESS - Loaded secure config from: $path");
                
                // Verify that getSecureConfig function is now available
                if (function_exists('getSecureConfig')) {
                    error_log("CashControl: SUCCESS - getSecureConfig function is available");
                } else {
                    error_log("CashControl: WARNING - secure-config.php loaded but getSecureConfig function not found");
                }
                break;
            } catch (Exception $e) {
                error_log("CashControl: ERROR loading secure config from $path: " . $e->getMessage());
                continue;
            }
        }
    }
    
    // Log debug information if config not found
    if (!$configLoaded) {
        error_log("CashControl: DEBUG - Document root: " . $_SERVER['DOCUMENT_ROOT']);
        error_log("CashControl: DEBUG - Current user: " . get_current_user());
        error_log("CashControl: DEBUG - HTTP host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown'));
        foreach ($debugInfo as $info) {
            error_log("CashControl: DEBUG - $info");
        }
    }
    
    // If secure config not found, create a fallback that logs the issue
    if (!$configLoaded) {
        error_log("CashControl: WARNING - secure-config.php not found in any expected location");
        
        // Create minimal fallback function
        function getSecureConfig($key, $default = null) {
            error_log("CashControl: getSecureConfig called for '$key' but secure-config.php not loaded");
            
            // Emergency fallback values (these should match your secure-config.php)
            $emergencyFallbacks = [
                'DB_PASSWORD' => 'Super-mannetje45',
                'SMTP_PASSWORD' => 'Super-mannetje45',
                'GOOGLE_CLIENT_SECRET' => '',
                'TRUELAYER_CLIENT_SECRET' => '',
                'STRIPE_SECRET_KEY' => '',
                'STRIPE_WEBHOOK_SECRET' => ''
            ];
            
            return $emergencyFallbacks[$key] ?? $default;
        }
    }
}

// Verify the function exists
if (!function_exists('getSecureConfig')) {
    error_log("CashControl: CRITICAL ERROR - getSecureConfig function still not available");
    die('Configuration system error. Please check server logs.');
}
?>
