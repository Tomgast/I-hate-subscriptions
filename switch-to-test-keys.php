<?php
/**
 * Stripe Environment Switcher
 * Helps switch between test and live Stripe keys for development
 */

echo "<h1>Stripe Environment Configuration</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<h2>Current Stripe Key Configuration</h2>";

// Load current configuration
require_once 'config/secure_loader.php';

$currentSecretKey = getSecureConfig('STRIPE_SECRET_KEY');
$currentPublishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');

echo "<p><strong>Current Secret Key:</strong> " . substr($currentSecretKey, 0, 12) . "...</p>";
echo "<p><strong>Current Publishable Key:</strong> " . substr($currentPublishableKey, 0, 12) . "...</p>";

// Determine environment
if (strpos($currentSecretKey, 'sk_test_') === 0) {
    echo "<p class='success'>✅ Currently using TEST keys (safe for development)</p>";
    $currentEnv = 'test';
} elseif (strpos($currentSecretKey, 'sk_live_') === 0) {
    echo "<p class='error'>❌ Currently using LIVE keys (will charge real money!)</p>";
    $currentEnv = 'live';
} else {
    echo "<p class='warning'>⚠️ Unknown key format</p>";
    $currentEnv = 'unknown';
}

echo "<h2>Secure Config File Instructions</h2>";

if ($currentEnv === 'live') {
    echo "<div class='error'>";
    echo "<h3>⚠️ URGENT: Switch to Test Keys</h3>";
    echo "<p>You're currently using live Stripe keys which will charge real money. You need to switch to test keys for development.</p>";
    echo "</div>";
}

echo "<h3>How to Configure Test vs Live Keys in secure-config.php:</h3>";

echo "<p>Your secure-config.php file should look like this for <strong>DEVELOPMENT/TESTING</strong>:</p>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("<?php
return [
    // Database Configuration
    'DB_HOST' => '45.82.188.227',
    'DB_PORT' => '3306', 
    'DB_NAME' => 'vxmjmwlj_',
    'DB_USER' => '123cashcontrol',
    'DB_PASSWORD' => 'your_db_password',
    
    // STRIPE CONFIGURATION - TEST ENVIRONMENT
    'STRIPE_SECRET_KEY' => 'sk_test_YOUR_TEST_SECRET_KEY',
    'STRIPE_PUBLISHABLE_KEY' => 'pk_test_YOUR_TEST_PUBLISHABLE_KEY',
    'STRIPE_WEBHOOK_SECRET' => 'whsec_YOUR_TEST_WEBHOOK_SECRET',
    
    // LIVE KEYS (COMMENTED OUT FOR DEVELOPMENT)
    // 'STRIPE_SECRET_KEY' => 'sk_live_YOUR_LIVE_SECRET_KEY',
    // 'STRIPE_PUBLISHABLE_KEY' => 'pk_live_YOUR_LIVE_PUBLISHABLE_KEY', 
    // 'STRIPE_WEBHOOK_SECRET' => 'whsec_YOUR_LIVE_WEBHOOK_SECRET',
    
    // Other configuration...
    'SMTP_HOST' => 'shared58.cloud86-host.nl',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => 'info@123cashcontrol.com',
    'SMTP_PASSWORD' => 'your_smtp_password',
    // ... rest of config
];");
echo "</pre>";

echo "<h3>For PRODUCTION deployment:</h3>";
echo "<p>Simply uncomment the live keys and comment out the test keys:</p>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars("    // TEST KEYS (COMMENTED OUT FOR PRODUCTION)
    // 'STRIPE_SECRET_KEY' => 'sk_test_YOUR_TEST_SECRET_KEY',
    // 'STRIPE_PUBLISHABLE_KEY' => 'pk_test_YOUR_TEST_PUBLISHABLE_KEY',
    
    // LIVE KEYS (ACTIVE FOR PRODUCTION)
    'STRIPE_SECRET_KEY' => 'sk_live_YOUR_LIVE_SECRET_KEY',
    'STRIPE_PUBLISHABLE_KEY' => 'pk_live_YOUR_LIVE_PUBLISHABLE_KEY',");
echo "</pre>";

echo "<h2>Test Your Configuration</h2>";

echo "<p>After updating your secure-config.php file:</p>";
echo "<ol>";
echo "<li>Save the changes to secure-config.php</li>";
echo "<li><a href='test-payment-session-debug.php'>Run the payment session debug script</a></li>";
echo "<li>Verify that test keys are being loaded</li>";
echo "<li>Test the payment flow - it should create a Stripe test session</li>";
echo "</ol>";

echo "<h2>Stripe Test vs Live Environment</h2>";

echo "<h3>Test Environment (Development):</h3>";
echo "<ul>";
echo "<li>Keys start with <code>sk_test_</code> and <code>pk_test_</code></li>";
echo "<li>No real money is charged</li>";
echo "<li>Use test card numbers (4242 4242 4242 4242)</li>";
echo "<li>Safe for development and testing</li>";
echo "</ul>";

echo "<h3>Live Environment (Production):</h3>";
echo "<ul>";
echo "<li>Keys start with <code>sk_live_</code> and <code>pk_live_</code></li>";
echo "<li>Real money is charged</li>";
echo "<li>Real credit cards are processed</li>";
echo "<li>Only use when ready for production</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";

if ($currentEnv === 'live') {
    echo "<div class='error'>";
    echo "<p><strong>IMMEDIATE ACTION REQUIRED:</strong></p>";
    echo "<ol>";
    echo "<li>Edit your secure-config.php file</li>";
    echo "<li>Comment out the live Stripe keys</li>";
    echo "<li>Uncomment the test Stripe keys</li>";
    echo "<li>Save the file</li>";
    echo "<li>Test the payment flow again</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ You're using test keys - safe to proceed with testing!</p>";
    echo "<p><a href='payment/checkout.php?plan=monthly'>Test the payment flow</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Remember: Always use test keys during development to avoid accidental charges!</small></p>";
?>
