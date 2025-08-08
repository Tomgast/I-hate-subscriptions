<?php
/**
 * Test Stripe Configuration and Payment Session Creation
 * This script helps diagnose Stripe payment issues
 */

require_once 'includes/stripe_service.php';

echo "<h1>Stripe Configuration Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .info { color: blue; }</style>";

try {
    // Initialize Stripe service
    $stripeService = new StripeService();
    
    echo "<h2>1. Testing Stripe Configuration</h2>";
    $configTest = $stripeService->testConfiguration();
    
    if ($configTest['configured']) {
        echo "<p class='success'>✅ Stripe configuration is valid</p>";
        echo "<p class='info'>Publishable Key: " . substr($configTest['publishable_key'], 0, 12) . "...</p>";
    } else {
        echo "<p class='error'>❌ Stripe configuration errors:</p>";
        foreach ($configTest['errors'] as $error) {
            echo "<p class='error'>- $error</p>";
        }
    }
    
    echo "<h2>2. Testing Payment Session Creation</h2>";
    
    // Test with sample user data
    $testUserId = 1;
    $testUserEmail = "test@example.com";
    $testPlanType = "monthly";
    
    echo "<p class='info'>Testing with:</p>";
    echo "<p>- User ID: $testUserId</p>";
    echo "<p>- Email: $testUserEmail</p>";
    echo "<p>- Plan: $testPlanType</p>";
    
    $session = $stripeService->createCheckoutSession(
        $testUserId,
        $testUserEmail,
        $testPlanType,
        'https://123cashcontrol.com/payment/success.php',
        'https://123cashcontrol.com/upgrade.php?cancelled=1'
    );
    
    if ($session && isset($session['url'])) {
        echo "<p class='success'>✅ Payment session created successfully!</p>";
        echo "<p class='info'>Session ID: " . $session['id'] . "</p>";
        echo "<p class='info'>Checkout URL: <a href='" . $session['url'] . "' target='_blank'>" . $session['url'] . "</a></p>";
    } else {
        echo "<p class='error'>❌ Failed to create payment session</p>";
        echo "<p class='error'>This is the same error you're experiencing in the upgrade flow.</p>";
    }
    
    echo "<h2>3. Checking Secure Configuration</h2>";
    
    // Check if secure config file exists and has Stripe keys
    $configPath = dirname(__DIR__) . '/secure-config.php';
    if (file_exists($configPath)) {
        echo "<p class='success'>✅ Secure config file exists</p>";
        
        $config = include $configPath;
        $stripeKeys = ['STRIPE_SECRET_KEY', 'STRIPE_PUBLISHABLE_KEY', 'STRIPE_WEBHOOK_SECRET'];
        
        foreach ($stripeKeys as $key) {
            if (isset($config[$key]) && !empty($config[$key])) {
                if ($key === 'STRIPE_SECRET_KEY') {
                    $value = str_starts_with($config[$key], 'sk_') ? 'Valid format' : 'Invalid format';
                } elseif ($key === 'STRIPE_PUBLISHABLE_KEY') {
                    $value = str_starts_with($config[$key], 'pk_') ? 'Valid format' : 'Invalid format';
                } else {
                    $value = 'Present';
                }
                echo "<p class='success'>✅ $key: $value</p>";
            } else {
                echo "<p class='error'>❌ $key: Missing or empty</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Secure config file not found at: $configPath</p>";
    }
    
    echo "<h2>4. Error Log Information</h2>";
    echo "<p class='info'>Check your PHP error logs for detailed Stripe API error messages.</p>";
    echo "<p class='info'>Common issues:</p>";
    echo "<ul>";
    echo "<li>Invalid or expired Stripe API keys</li>";
    echo "<li>Test keys being used in production mode</li>";
    echo "<li>Missing webhook secret</li>";
    echo "<li>Network connectivity issues</li>";
    echo "<li>Incorrect API endpoint or request format</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error during testing: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>5. Next Steps</h2>";
echo "<p>If the configuration test passes but payment session creation fails:</p>";
echo "<ol>";
echo "<li>Check PHP error logs for detailed Stripe API error messages</li>";
echo "<li>Verify Stripe keys are for the correct environment (test vs live)</li>";
echo "<li>Ensure your Stripe account has the necessary permissions</li>";
echo "<li>Test with a simpler payment session configuration</li>";
echo "</ol>";
?>
