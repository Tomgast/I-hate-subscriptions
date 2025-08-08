<?php
/**
 * STRIPE SDK TEST
 * Test if your uploaded Stripe SDK is working
 */

session_start();

echo "<h1>🧪 Stripe SDK Test</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "This will test if your uploaded Stripe PHP SDK is working correctly.";
echo "</div>";

echo "<h2>📁 Step 1: Locate Your Stripe Files</h2>";

// Check common locations where user might have uploaded Stripe
$possibleLocations = [
    __DIR__ . '/stripe-php/init.php',
    __DIR__ . '/lib/stripe-php/init.php',
    __DIR__ . '/vendor/stripe/stripe-php/init.php',
    __DIR__ . '/stripe/init.php',
    __DIR__ . '/includes/stripe-php/init.php'
];

$stripeFound = false;
$stripeLocation = '';

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Checking common locations:</strong><br>";

foreach ($possibleLocations as $location) {
    $exists = file_exists($location);
    $status = $exists ? "<span style='color: green;'>✅ Found</span>" : "<span style='color: red;'>❌ Not found</span>";
    echo "• " . str_replace(__DIR__, '', $location) . " - {$status}<br>";
    
    if ($exists && !$stripeFound) {
        $stripeFound = true;
        $stripeLocation = $location;
    }
}

echo "</div>";

if ($stripeFound) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Stripe SDK Found!</strong><br>";
    echo "Location: <code>" . str_replace(__DIR__, '', $stripeLocation) . "</code>";
    echo "</div>";
    
    echo "<h2>🧪 Step 2: Test Stripe SDK</h2>";
    
    try {
        // Include the Stripe SDK
        require_once $stripeLocation;
        
        echo "<span style='color: green;'>✅ Stripe SDK loaded successfully</span><br>";
        
        // Check if Stripe class exists
        if (class_exists('Stripe\Stripe')) {
            echo "<span style='color: green;'>✅ Stripe\Stripe class available</span><br>";
            
            // Load your Stripe credentials
            require_once 'config/secure_loader.php';
            $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
            
            if ($stripeSecret) {
                echo "<span style='color: green;'>✅ Stripe secret key found</span><br>";
                
                // Set API key and test connection
                \Stripe\Stripe::setApiKey($stripeSecret);
                
                try {
                    $account = \Stripe\Account::retrieve();
                    echo "<span style='color: green;'>✅ Stripe API connection successful!</span><br>";
                    echo "<span style='color: blue;'>ℹ️ Account ID: {$account->id}</span><br>";
                    echo "<span style='color: blue;'>ℹ️ Country: {$account->country}</span><br>";
                    
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                    echo "<strong>🎉 SUCCESS! Stripe SDK is working perfectly!</strong><br>";
                    echo "You can now proceed with creating the Financial Connections integration.";
                    echo "</div>";
                    
                    // Show next steps
                    echo "<h2>🚀 Next Steps</h2>";
                    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
                    echo "<h3>Now that Stripe SDK is working:</h3>";
                    echo "<ol>";
                    echo "<li><strong>Create the include file</strong> - I'll create a simple include file for you</li>";
                    echo "<li><strong>Build the Financial Connections service</strong> - Create the bank connection functionality</li>";
                    echo "<li><strong>Test Financial Connections</strong> - Test with a real bank account</li>";
                    echo "<li><strong>Update your bank scan page</strong> - Replace TrueLayer with Stripe</li>";
                    echo "</ol>";
                    echo "</div>";
                    
                    echo "<form method='POST' style='margin: 20px 0;'>";
                    echo "<input type='hidden' name='action' value='create_include_file'>";
                    echo "<input type='hidden' name='stripe_location' value='{$stripeLocation}'>";
                    echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Create Stripe Include File</button>";
                    echo "</form>";
                    
                } catch (Exception $e) {
                    echo "<span style='color: red;'>❌ Stripe API connection failed: {$e->getMessage()}</span><br>";
                    echo "<p>Check your STRIPE_SECRET_KEY in secure-config.php</p>";
                }
            } else {
                echo "<span style='color: red;'>❌ Stripe secret key not found in configuration</span><br>";
                echo "<p>Make sure STRIPE_SECRET_KEY is set in your secure-config.php file</p>";
            }
        } else {
            echo "<span style='color: red;'>❌ Stripe\Stripe class not found</span><br>";
            echo "<p>The init.php file might not be the correct one, or there might be an issue with the SDK files.</p>";
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error loading Stripe SDK: {$e->getMessage()}</span><br>";
    }
    
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>⚠️ Stripe SDK Not Found in Common Locations</strong><br>";
    echo "Please tell me where you uploaded the Stripe files so I can help you set it up correctly.";
    echo "</div>";
    
    echo "<h3>📋 Manual Setup Instructions:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>If you uploaded Stripe to a different location:</strong></p>";
    echo "<ol>";
    echo "<li>Find the <code>init.php</code> file in your uploaded Stripe folder</li>";
    echo "<li>Note the full path to this file</li>";
    echo "<li>Let me know the path and I'll create the proper include file</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔍 Help Me Find Your Stripe Files:</h3>";
    echo "<form method='POST'>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label style='display: block; font-weight: bold; margin-bottom: 5px;'>Path to your Stripe init.php file:</label>";
    echo "<input type='text' name='custom_stripe_path' placeholder='e.g., /stripe-php/init.php or /lib/stripe/init.php' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<small style='color: #666;'>Enter the path relative to your website root</small>";
    echo "</div>";
    echo "<input type='hidden' name='action' value='test_custom_path'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test This Path</button>";
    echo "</form>";
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'test_custom_path':
                $customPath = $_POST['custom_stripe_path'];
                $fullPath = __DIR__ . '/' . ltrim($customPath, '/');
                
                echo "<h2>🧪 Testing Custom Path</h2>";
                echo "<p>Testing: <code>{$customPath}</code></p>";
                
                if (file_exists($fullPath)) {
                    echo "<span style='color: green;'>✅ File found at custom path!</span><br>";
                    
                    try {
                        require_once $fullPath;
                        
                        if (class_exists('Stripe\Stripe')) {
                            echo "<span style='color: green;'>✅ Stripe SDK loaded successfully from custom path!</span><br>";
                            
                            echo "<form method='POST' style='margin: 10px 0;'>";
                            echo "<input type='hidden' name='action' value='create_include_file'>";
                            echo "<input type='hidden' name='stripe_location' value='{$fullPath}'>";
                            echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Include File for This Path</button>";
                            echo "</form>";
                        } else {
                            echo "<span style='color: red;'>❌ File found but Stripe classes not available</span><br>";
                        }
                    } catch (Exception $e) {
                        echo "<span style='color: red;'>❌ Error loading from custom path: {$e->getMessage()}</span><br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ File not found at custom path</span><br>";
                    echo "<p>Please check the path and try again.</p>";
                }
                break;
                
            case 'create_include_file':
                $stripeLocation = $_POST['stripe_location'];
                
                echo "<h2>🔄 Creating Stripe Include File</h2>";
                
                $includeCode = '<?php
/**
 * STRIPE SDK INCLUDE
 * Include this file to use Stripe functionality
 */

// Include Stripe SDK
require_once __DIR__ . \'/' . str_replace(__DIR__ . '/', '', $stripeLocation) . '\';

// Load Stripe configuration
require_once __DIR__ . \'/../config/secure_loader.php\';

// Set Stripe API key
$stripeSecretKey = getSecureConfig(\'STRIPE_SECRET_KEY\');
if ($stripeSecretKey) {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
} else {
    throw new Exception(\'STRIPE_SECRET_KEY not found in configuration\');
}

// Stripe is now ready to use!
?>';
                
                file_put_contents(__DIR__ . '/includes/stripe-sdk.php', $includeCode);
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>✅ Stripe Include File Created!</strong><br>";
                echo "File: <code>includes/stripe-sdk.php</code><br>";
                echo "Usage: <code>require_once 'includes/stripe-sdk.php';</code>";
                echo "</div>";
                
                echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>🎯 Next Steps:</h3>";
                echo "<ol>";
                echo "<li>Now I can create the Stripe Financial Connections service</li>";
                echo "<li>Build the bank connection pages</li>";
                echo "<li>Test with real bank accounts</li>";
                echo "<li>Replace TrueLayer integration</li>";
                echo "</ol>";
                echo "</div>";
                
                echo "<a href='stripe-migration-plan.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Continue with Migration Plan</a>";
                break;
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
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
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
</style>
