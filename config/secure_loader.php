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
    
    // Make configData global so it can be accessed by the function
    global $configData;
    $configData = [];
    
    foreach ($possiblePaths as $path) {
        $debugInfo[] = "Checking: $path - " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . (file_exists($path) && is_readable($path) ? ' & READABLE' : '');
        
        if (file_exists($path) && is_readable($path)) {
            try {
                // Try to load config as return array (user's format)
                $loadedConfig = include $path;
                
                if (is_array($loadedConfig)) {
                    $configData = $loadedConfig;
                    $configLoaded = true;
                    error_log("CashControl: SUCCESS - Loaded secure config array from: $path");
                    error_log("CashControl: Config contains " . count($configData) . " keys");
                    break;
                } else {
                    // Fallback: try require_once in case it defines functions
                    require_once $path;
                    if (function_exists('getSecureConfig')) {
                        $configLoaded = true;
                        error_log("CashControl: SUCCESS - Loaded secure config function from: $path");
                        break;
                    }
                }
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
    
    // Create getSecureConfig function based on what we loaded
    if (!function_exists('getSecureConfig')) {
        if ($configLoaded && !empty($configData)) {
            // Create function that uses the loaded config array
            function getSecureConfig($key = null, $default = null) {
                global $configData;
                // If no key specified, return entire config array
                if ($key === null) {
                    return $configData;
                }
                // If key specified, return that specific value
                return $configData[$key] ?? $default;
            }
            error_log("CashControl: SUCCESS - Created getSecureConfig function with loaded config data");
        } else {
            // Create fallback function if config not found
            error_log("CashControl: WARNING - secure-config.php not found in any expected location");
            
            function getSecureConfig($key = null, $default = null) {
                error_log("CashControl: getSecureConfig called for '$key' but secure-config.php not loaded");
                
                // Emergency fallback values
                $emergencyFallbacks = [
                    'DB_PASSWORD' => 'Super-mannetje45',
                    'SMTP_PASSWORD' => 'Super-mannetje45',
                    'GOOGLE_CLIENT_SECRET' => '',
                    'TRUELAYER_CLIENT_SECRET' => '',
                    'STRIPE_SECRET_KEY' => '',
                    'STRIPE_WEBHOOK_SECRET' => ''
                ];
                
                // If no key specified, return entire fallback array
                if ($key === null) {
                    return $emergencyFallbacks;
                }
                
                return $emergencyFallbacks[$key] ?? $default;
            }
        }
    }
}

// Add loadSecureConfig function that returns the full config array
if (!function_exists('loadSecureConfig')) {
    function loadSecureConfig() {
        global $configData;
        
        // Return the loaded config data or emergency fallbacks
        if (!empty($configData)) {
            return $configData;
        }
        
        // Emergency fallback config
        return [
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => 'vxmjmwlj_',
            'db_user' => '123cashcontrol',
            'db_password' => 'Super-mannetje45',
            'google_client_id' => '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com',
            'google_client_secret' => '',
            'google_redirect_uri' => 'https://123cashcontrol.com/auth/google-callback.php',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'smtp_host' => 'shared58.cloud86-host.nl',
            'smtp_port' => '587',
            'smtp_user' => 'info@123cashcontrol.com',
            'smtp_password' => 'Super-mannetje45'
        ];
    }
}

// Verify the functions exist
if (!function_exists('getSecureConfig')) {
    error_log("CashControl: CRITICAL ERROR - getSecureConfig function still not available");
    die('Configuration system error. Please check server logs.');
}

if (!function_exists('loadSecureConfig')) {
    error_log("CashControl: CRITICAL ERROR - loadSecureConfig function still not available");
    die('Configuration system error. Please check server logs.');
}
?>
