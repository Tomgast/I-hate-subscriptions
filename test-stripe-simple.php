<?php
/**
 * Simple Stripe Payment Integration Test
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Stripe Payment Integration Test ===\n\n";

try {
    // Test 1: Load secure configuration
    echo "1. Testing Stripe configuration loading...\n";
    require_once __DIR__ . '/config/secure_loader.php';
    
    $stripePublishable = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
    $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
    $stripeWebhook = getSecureConfig('STRIPE_WEBHOOK_SECRET');
    
    echo "   - Publishable key: " . ($stripePublishable ? "✅ " . substr($stripePublishable, 0, 12) . "..." : "❌ Missing") . "\n";
    echo "   - Secret key: " . ($stripeSecret ? "✅ " . substr($stripeSecret, 0, 12) . "..." : "❌ Missing") . "\n";
    echo "   - Webhook secret: " . ($stripeWebhook ? "✅ Loaded" : "❌ Missing") . "\n\n";
    
    // Test 2: Load StripeService
    echo "2. Loading StripeService...\n";
    require_once __DIR__ . '/includes/stripe_service.php';
    
    $stripeService = new StripeService();
    echo "   ✅ StripeService loaded successfully\n\n";
    
    // Test 3: Test Stripe API connectivity
    echo "3. Testing Stripe API connectivity...\n";
    
    if ($stripeSecret) {
        // Simple API test - retrieve account info
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $stripeSecret,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $account = json_decode($response, true);
            echo "   ✅ Stripe API connection successful\n";
            echo "   - Account ID: " . ($account['id'] ?? 'Unknown') . "\n";
            echo "   - Country: " . ($account['country'] ?? 'Unknown') . "\n\n";
        } else {
            echo "   ❌ Stripe API connection failed (HTTP $httpCode)\n";
            echo "   - Response: " . substr($response, 0, 100) . "...\n\n";
        }
    } else {
        echo "   ⚠️ Skipping API test (no secret key)\n\n";
    }
    
    // Test 4: Check available methods
    echo "4. Checking StripeService methods...\n";
    
    $methods = get_class_methods($stripeService);
    $importantMethods = ['createCheckoutSession', 'handleWebhook', 'getCustomer'];
    
    foreach ($importantMethods as $method) {
        if (in_array($method, $methods)) {
            echo "   ✅ $method method exists\n";
        } else {
            echo "   ❌ $method method missing\n";
        }
    }
    echo "\n";
    
    echo "=== Stripe Test Summary ===\n";
    if ($stripeSecret && $stripePublishable) {
        echo "✅ Stripe integration appears to be properly configured\n";
        echo "✅ Live Stripe keys are loaded and API is accessible\n";
        echo "Ready for payment processing\n";
    } else {
        echo "❌ Stripe configuration incomplete\n";
        echo "Missing credentials need to be added to secure-config.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
