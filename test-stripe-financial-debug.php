<?php
/**
 * DEBUG STRIPE FINANCIAL CONNECTIONS ISSUE
 * Test Stripe credentials and Financial Connections API
 */

session_start();
require_once 'config/secure_loader.php';

echo "<h2>Stripe Financial Connections Debug</h2>";

// Test 1: Check Stripe credentials
echo "<h3>1. Stripe Credentials Test</h3>";
$stripeSecretKey = getSecureConfig('STRIPE_SECRET_KEY');
$stripePublishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');

if ($stripeSecretKey) {
    echo "✅ STRIPE_SECRET_KEY found: " . substr($stripeSecretKey, 0, 10) . "..." . "<br>";
    
    // Check if it's a test or live key
    if (strpos($stripeSecretKey, 'sk_test_') === 0) {
        echo "📝 Using TEST environment<br>";
    } elseif (strpos($stripeSecretKey, 'sk_live_') === 0) {
        echo "🔴 Using LIVE environment<br>";
    } else {
        echo "⚠️ Invalid key format<br>";
    }
} else {
    echo "❌ STRIPE_SECRET_KEY not found<br>";
}

if ($stripePublishableKey) {
    echo "✅ STRIPE_PUBLISHABLE_KEY found: " . substr($stripePublishableKey, 0, 10) . "..." . "<br>";
} else {
    echo "❌ STRIPE_PUBLISHABLE_KEY not found<br>";
}

// Test 2: Initialize Stripe SDK
echo "<h3>2. Stripe SDK Initialization</h3>";
try {
    require_once 'includes/stripe-sdk.php';
    echo "✅ Stripe SDK loaded successfully<br>";
    echo "📝 Stripe API Version: " . \Stripe\Stripe::getApiVersion() . "<br>";
} catch (Exception $e) {
    echo "❌ Stripe SDK error: " . $e->getMessage() . "<br>";
}

// Test 3: Test basic Stripe API call
echo "<h3>3. Basic Stripe API Test</h3>";
try {
    $customer = \Stripe\Customer::create([
        'email' => 'test@example.com',
        'metadata' => ['test' => 'debug']
    ]);
    echo "✅ Basic Stripe API working - Created test customer: " . $customer->id . "<br>";
    
    // Clean up - delete the test customer
    $customer->delete();
    echo "✅ Test customer deleted<br>";
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "❌ Stripe API error: " . $e->getMessage() . "<br>";
    echo "📝 Error type: " . get_class($e) . "<br>";
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
}

// Test 4: Test Financial Connections Session Creation
echo "<h3>4. Financial Connections Session Test</h3>";
try {
    // First create a test customer
    $testCustomer = \Stripe\Customer::create([
        'email' => 'financial-test@example.com',
        'metadata' => ['test' => 'financial_connections']
    ]);
    
    echo "✅ Test customer created: " . $testCustomer->id . "<br>";
    
    // Try to create Financial Connections session
    $session = \Stripe\FinancialConnections\Session::create([
        'account_holder' => [
            'type' => 'customer',
            'customer' => $testCustomer->id,
        ],
        'permissions' => [
            'payment_method',
            'balances',
            'transactions'
        ],
        'filters' => [
            'countries' => ['US']
        ],
        'return_url' => 'https://123cashcontrol.com/bank/stripe-callback.php'
    ]);
    
    echo "✅ Financial Connections session created successfully!<br>";
    echo "📝 Session ID: " . $session->id . "<br>";
    echo "📝 Auth URL: " . $session->hosted_auth_url . "<br>";
    
    // Clean up
    $testCustomer->delete();
    echo "✅ Test customer deleted<br>";
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "❌ Financial Connections error: " . $e->getMessage() . "<br>";
    echo "📝 Error type: " . get_class($e) . "<br>";
    echo "📝 Error code: " . $e->getStripeCode() . "<br>";
    
    if (isset($testCustomer)) {
        try {
            $testCustomer->delete();
            echo "✅ Test customer cleaned up<br>";
        } catch (Exception $cleanup) {
            echo "⚠️ Cleanup error: " . $cleanup->getMessage() . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
    
    if (isset($testCustomer)) {
        try {
            $testCustomer->delete();
            echo "✅ Test customer cleaned up<br>";
        } catch (Exception $cleanup) {
            echo "⚠️ Cleanup error: " . $cleanup->getMessage() . "<br>";
        }
    }
}

// Test 5: Database connection test
echo "<h3>5. Database Connection Test</h3>";
try {
    require_once 'config/db_config.php';
    $pdo = getDBConnection();
    
    // Check if bank_connection_sessions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bank_connection_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ bank_connection_sessions table exists<br>";
    } else {
        echo "⚠️ bank_connection_sessions table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Debug Complete!</strong> Check the results above to identify the issue.</p>";
?>
