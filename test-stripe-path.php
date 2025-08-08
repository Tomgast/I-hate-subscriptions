<?php
/**
 * SIMPLE STRIPE PATH TEST
 * Quick test to verify the exact path and fix the issue
 */

echo "<h1>🔍 Stripe Path Test</h1>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Quick Path Verification</strong><br>";
echo "Testing the exact path to your Stripe SDK and fixing any issues.";
echo "</div>";

$currentDir = __DIR__;
echo "<p><strong>Current directory:</strong> <code>{$currentDir}</code></p>";

// Test the path we expect
$expectedPath = $currentDir . '/stripe-php/init.php';
echo "<p><strong>Expected Stripe path:</strong> <code>{$expectedPath}</code></p>";

if (file_exists($expectedPath)) {
    echo "<p style='color: green;'>✅ <strong>Stripe SDK found!</strong></p>";
    
    try {
        echo "<p>Loading Stripe SDK...</p>";
        require_once $expectedPath;
        
        if (class_exists('Stripe\Stripe')) {
            echo "<p style='color: green;'>✅ <strong>Stripe SDK loaded successfully!</strong></p>";
            
            // Test the configuration
            if (file_exists('config/secure_loader.php')) {
                require_once 'config/secure_loader.php';
                
                $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
                if ($stripeSecret) {
                    \Stripe\Stripe::setApiKey($stripeSecret);
                    echo "<p style='color: green;'>✅ <strong>Stripe API key set successfully!</strong></p>";
                    
                    // Test API connection
                    try {
                        $account = \Stripe\Account::retrieve();
                        echo "<p style='color: green;'>✅ <strong>Stripe API connection working!</strong></p>";
                        echo "<p><strong>Account ID:</strong> {$account->id}</p>";
                        
                        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                        echo "<h2>🎉 SUCCESS!</h2>";
                        echo "<p>Your Stripe integration is working perfectly. The 500 error must be caused by something else.</p>";
                        echo "<p><strong>Next step:</strong> Let's test the stripe-scan.php page directly.</p>";
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>❌ <strong>Stripe API Error:</strong> {$e->getMessage()}</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ <strong>STRIPE_SECRET_KEY not found</strong></p>";
                }
            } else {
                echo "<p style='color: red;'>❌ <strong>secure_loader.php not found</strong></p>";
            }
        } else {
            echo "<p style='color: red;'>❌ <strong>Stripe classes not available after loading</strong></p>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<strong>❌ Error loading Stripe SDK:</strong><br>";
        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>Stripe SDK not found at expected path</strong></p>";
    
    // Check what's actually there
    echo "<h3>Directory contents:</h3>";
    $files = scandir($currentDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $currentDir . '/' . $file;
            $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
            echo "<p>• {$type} {$file}</p>";
        }
    }
}

// Test the includes file
echo "<hr><h2>Testing includes/stripe-sdk.php</h2>";

$includesPath = $currentDir . '/includes/stripe-sdk.php';
if (file_exists($includesPath)) {
    echo "<p style='color: green;'>✅ <strong>includes/stripe-sdk.php exists</strong></p>";
    
    try {
        // Don't actually include it if we already loaded Stripe above
        if (!class_exists('Stripe\Stripe')) {
            require_once $includesPath;
            echo "<p style='color: green;'>✅ <strong>includes/stripe-sdk.php loaded successfully!</strong></p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ <strong>Stripe already loaded, skipping includes test</strong></p>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<strong>❌ Error with includes/stripe-sdk.php:</strong><br>";
        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>includes/stripe-sdk.php not found</strong></p>";
}

echo "<hr><h2>Next Steps</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<p>If the Stripe SDK is working above, try these:</p>";
echo "<ol>";
echo "<li><a href='bank/stripe-scan.php' style='color: #007bff;'>Test stripe-scan.php directly</a></li>";
echo "<li>Check your server error logs for the exact 500 error</li>";
echo "<li>Make sure all required database tables exist</li>";
echo "</ol>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
