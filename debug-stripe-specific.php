<?php
/**
 * Specific Stripe Configuration Diagnostic
 * Tests Stripe service initialization and API connectivity
 */

require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/includes/stripe_service.php';

echo "<h1>🔧 Stripe Service Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

echo "<h2>🔑 Stripe Keys Check</h2>";

$secretKey = getSecureConfig('STRIPE_SECRET_KEY');
$publishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
$webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');

echo "<div class='info'>Secret Key Length: " . strlen($secretKey) . " characters</div>";
echo "<div class='info'>Publishable Key Length: " . strlen($publishableKey) . " characters</div>";
echo "<div class='info'>Webhook Secret Length: " . strlen($webhookSecret) . " characters</div>";

// Check key types
if (strpos($secretKey, 'sk_test_') === 0) {
    echo "<div class='success'>✅ Using TEST secret key (safe for testing)</div>";
} elseif (strpos($secretKey, 'sk_live_') === 0) {
    echo "<div class='warning'>⚠️ Using LIVE secret key (real payments!)</div>";
} else {
    echo "<div class='error'>❌ Invalid secret key format</div>";
}

if (strpos($publishableKey, 'pk_test_') === 0) {
    echo "<div class='success'>✅ Using TEST publishable key</div>";
} elseif (strpos($publishableKey, 'pk_live_') === 0) {
    echo "<div class='warning'>⚠️ Using LIVE publishable key</div>";
} else {
    echo "<div class='error'>❌ Invalid publishable key format</div>";
}

if (strpos($webhookSecret, 'whsec_') === 0) {
    echo "<div class='success'>✅ Webhook secret format correct</div>";
} else {
    echo "<div class='error'>❌ Invalid webhook secret format</div>";
}

echo "<h2>🏗️ Stripe Service Initialization</h2>";

try {
    $stripeService = new StripeService();
    echo "<div class='success'>✅ StripeService created successfully</div>";
    
    // Test configuration method
    echo "<h3>Testing configuration...</h3>";
    $testResult = $stripeService->testConfiguration();
    
    echo "<pre>";
    print_r($testResult);
    echo "</pre>";
    
    if ($testResult['configured']) {
        echo "<div class='success'>✅ Stripe configuration test passed</div>";
        echo "<div class='success'>✅ All Stripe keys valid and API connection working</div>";
        if (empty($testResult['errors'])) {
            echo "<div class='success'>✅ No configuration errors found</div>";
        }
    } else {
        echo "<div class='error'>❌ Stripe configuration test failed</div>";
        if (!empty($testResult['errors'])) {
            foreach ($testResult['errors'] as $error) {
                echo "<div class='error'>Error: " . $error . "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Failed to create StripeService: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🧪 Direct Stripe API Test</h2>";

try {
    // Test direct Stripe API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "<div class='error'>❌ cURL Error: $curlError</div>";
    } else {
        echo "<div class='info'>HTTP Response Code: $httpCode</div>";
        
        if ($httpCode === 200) {
            echo "<div class='success'>✅ Direct Stripe API connection successful</div>";
            $accountData = json_decode($response, true);
            if (isset($accountData['id'])) {
                echo "<div class='info'>Account ID: " . $accountData['id'] . "</div>";
                echo "<div class='info'>Account Type: " . ($accountData['type'] ?? 'unknown') . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Stripe API returned HTTP $httpCode</div>";
            echo "<div class='error'>Response: " . substr($response, 0, 500) . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Direct API test failed: " . $e->getMessage() . "</div>";
}

echo "<h2>📋 Summary</h2>";
echo "<ul>";
echo "<li>Configuration loading: ✅ Working</li>";
echo "<li>Stripe keys: " . ((!empty($secretKey) && !empty($publishableKey)) ? "✅ Present" : "❌ Missing") . "</li>";
echo "<li>Key format: " . ((strpos($secretKey, 'sk_') === 0 && strpos($publishableKey, 'pk_') === 0) ? "✅ Valid" : "❌ Invalid") . "</li>";
echo "</ul>";
?>
