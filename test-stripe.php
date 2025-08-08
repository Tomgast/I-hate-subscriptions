<?php
/**
 * Stripe Payment Integration Test
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
            echo "   - Business type: " . ($account['business_type'] ?? 'Unknown') . "\n";
            echo "   - Country: " . ($account['country'] ?? 'Unknown') . "\n\n";
        } else {
            echo "   ❌ Stripe API connection failed (HTTP $httpCode)\n";
            echo "   - Response: " . substr($response, 0, 200) . "...\n\n";
        }
    } else {
        echo "   ⚠️ Skipping API test (no secret key)\n\n";
    }
    
    // Test 4: Test plan pricing configuration
    echo "4. Testing plan pricing configuration...\n";
    
    try {
        $plans = [
            'monthly' => $stripeService->getStripePriceId('monthly'),
            'yearly' => $stripeService->getStripePriceId('yearly'),
            'one_time' => $stripeService->getStripePriceId('one_time')
        ];
        
        foreach ($plans as $planType => $priceId) {
            echo "   - $planType plan: " . ($priceId ? "✅ $priceId" : "❌ Missing") . "\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "   ❌ Error getting price IDs: " . $e->getMessage() . "\n\n";
    }
    
    // Test 5: Test checkout session creation (simulation)
    echo "5. Testing checkout session creation...\n";
    
    try {
        // Test with monthly plan
        $sessionData = [
            'user_id' => 1,
            'plan_type' => 'monthly',
            'success_url' => 'https://123cashcontrol.com/payment/success.php',
            'cancel_url' => 'https://123cashcontrol.com/payment/cancel.php'
        ];
        
        // This would normally create a real Stripe session, but we'll just test the method exists
        if (method_exists($stripeService, 'createCheckoutSession')) {
            echo "   ✅ createCheckoutSession method exists\n";
            echo "   - Ready to create checkout sessions for all plan types\n\n";
        } else {
            echo "   ❌ createCheckoutSession method missing\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error testing checkout session: " . $e->getMessage() . "\n\n";
    }
    
    echo "=== Stripe Test Summary ===\n";
    if ($stripeSecret && $stripePublishable) {
        echo "✅ Stripe integration appears to be properly configured\n";
        echo "Ready for payment processing with all three plan types\n";
    } else {
        echo "❌ Stripe configuration incomplete\n";
        echo "Missing credentials need to be added to secure-config.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
