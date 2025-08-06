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
        
        // Absolute server paths
        '/secure-config.php',
        '/home/secure-config.php'
    ];
    
    $configLoaded = false;
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            require_once $path;
            $configLoaded = true;
            error_log("CashControl: Loaded secure config from: $path");
            break;
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
