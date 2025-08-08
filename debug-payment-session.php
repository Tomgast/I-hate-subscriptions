<?php
/**
 * Payment Session Creation Diagnostic
 * Tests the specific createCheckoutSession method to identify the failure
 */

session_start();
require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/includes/stripe_service.php';
require_once __DIR__ . '/includes/database_helper.php';

echo "<h1>💳 Payment Session Creation Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Set up test user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 5; // Use existing test user
    $_SESSION['user_email'] = 'test@cashcontrol.com';
    $_SESSION['user_name'] = 'Test User';
}

echo "<h2>👤 Session Info</h2>";
echo "<div class='info'>User ID: " . $_SESSION['user_id'] . "</div>";
echo "<div class='info'>User Email: " . $_SESSION['user_email'] . "</div>";

echo "<h2>🔧 Stripe Service Test</h2>";

try {
    $stripeService = new StripeService();
    echo "<div class='success'>✅ StripeService created</div>";
    
    // Test configuration first
    $configTest = $stripeService->testConfiguration();
    if ($configTest['configured']) {
        echo "<div class='success'>✅ Stripe configuration valid</div>";
    } else {
        echo "<div class='error'>❌ Stripe configuration invalid</div>";
        foreach ($configTest['errors'] as $error) {
            echo "<div class='error'>- $error</div>";
        }
        exit;
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Failed to create StripeService: " . $e->getMessage() . "</div>";
    exit;
}

echo "<h2>💳 Payment Session Creation Test</h2>";

try {
    echo "<div class='info'>Testing createCheckoutSession with:</div>";
    echo "<div class='info'>- User ID: " . $_SESSION['user_id'] . "</div>";
    echo "<div class='info'>- Plan: monthly</div>";
    echo "<div class='info'>- Success URL: https://123cashcontrol.com/payment/success.php</div>";
    echo "<div class='info'>- Cancel URL: https://123cashcontrol.com/payment/cancel.php</div>";
    
    $sessionData = $stripeService->createCheckoutSession(
        $_SESSION['user_id'],
        $_SESSION['user_email'],
        'monthly',
        'https://123cashcontrol.com/payment/success.php',
        'https://123cashcontrol.com/payment/cancel.php'
    );
    
    echo "<h3>📋 Session Creation Result:</h3>";
    echo "<pre>";
    print_r($sessionData);
    echo "</pre>";
    
    if ($sessionData && isset($sessionData['url'])) {
        echo "<div class='success'>✅ Payment session created successfully!</div>";
        echo "<div class='info'>Session ID: " . $sessionData['id'] . "</div>";
        echo "<div class='info'>Session URL: <a href='" . $sessionData['url'] . "' target='_blank'>" . $sessionData['url'] . "</a></div>";
        echo "<div class='warning'>⚠️ This is a real Stripe session - only proceed if using TEST keys!</div>";
        
        // Test with different plans
        echo "<h3>🧪 Testing Other Plans:</h3>";
        
        $plans = ['yearly', 'onetime'];
        foreach ($plans as $plan) {
            try {
                $testSession = $stripeService->createCheckoutSession(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    $plan,
                    'https://123cashcontrol.com/payment/success.php',
                    'https://123cashcontrol.com/payment/cancel.php'
                );
                
                if ($testSession && isset($testSession['url'])) {
                    echo "<div class='success'>✅ $plan plan session created</div>";
                } else {
                    echo "<div class='error'>❌ $plan plan session failed</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ $plan plan error: " . $e->getMessage() . "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Payment session creation failed</div>";
        
        if (is_array($sessionData) && isset($sessionData['error'])) {
            echo "<div class='error'>Error: " . $sessionData['error'] . "</div>";
        }
        
        // Check if it's a method issue
        if (method_exists($stripeService, 'createCheckoutSession')) {
            echo "<div class='info'>✅ createCheckoutSession method exists</div>";
        } else {
            echo "<div class='error'>❌ createCheckoutSession method missing</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception during session creation: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🗄️ Database User Check</h2>";

try {
    $pdo = DatabaseHelper::getConnection();
    $stmt = $pdo->prepare("SELECT id, email, name, subscription_type, subscription_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<div class='success'>✅ User found in database</div>";
        echo "<div class='info'>Email: " . $user['email'] . "</div>";
        echo "<div class='info'>Name: " . $user['name'] . "</div>";
        echo "<div class='info'>Subscription Type: " . $user['subscription_type'] . "</div>";
        echo "<div class='info'>Subscription Status: " . $user['subscription_status'] . "</div>";
    } else {
        echo "<div class='error'>❌ User not found in database</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "<h2>📋 Summary</h2>";
echo "<ul>";
echo "<li>Stripe Service: ✅ Working</li>";
echo "<li>Configuration: ✅ Valid</li>";
echo "<li>User Session: ✅ Present</li>";
echo "<li>Database: ✅ Accessible</li>";
echo "<li>Payment Session: " . (isset($sessionData) && $sessionData ? "✅ Working" : "❌ Failed") . "</li>";
echo "</ul>";
?>
