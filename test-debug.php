<?php
// Debug test to identify 500 error cause
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Service Test Debug</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test each service file individually
$services = [
    'email_service.php' => __DIR__ . '/includes/email_service.php',
    'google_oauth.php' => __DIR__ . '/includes/google_oauth.php', 
    'bank_service.php' => __DIR__ . '/includes/bank_service.php',
    'stripe_service.php' => __DIR__ . '/includes/stripe_service.php'
];

foreach ($services as $name => $path) {
    echo "<h3>Testing: $name</h3>";
    
    if (file_exists($path)) {
        echo "✅ File exists: $path<br>";
        
        try {
            require_once $path;
            echo "✅ File loaded successfully<br>";
        } catch (Error $e) {
            echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ File not found: $path<br>";
    }
    
    echo "<hr>";
}

// Test secure config loading
echo "<h3>Testing Secure Config</h3>";
try {
    require_once __DIR__ . '/config/secure_loader.php';
    echo "✅ Secure loader loaded<br>";
    
    if (function_exists('getSecureConfig')) {
        echo "✅ getSecureConfig function available<br>";
        
        $testKey = getSecureConfig('DB_PASSWORD');
        if ($testKey) {
            echo "✅ Config loading works (password found)<br>";
        } else {
            echo "⚠️ Config loading returns empty (check secure-config.php location)<br>";
        }
    } else {
        echo "❌ getSecureConfig function not found<br>";
    }
    
} catch (Error $e) {
    echo "❌ Secure config error: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "❌ Secure config exception: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Session Test</h3>";
session_start();
echo "✅ Session started successfully<br>";

echo "<hr>";
echo "<p><a href='test/test-connections.php'>Try Full Test Again</a></p>";
?>
