<?php
/**
 * End-to-End Payment Flow Test
 * Tests the complete payment process from session creation to webhook processing
 */

session_start();
require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/stripe_service.php';
require_once __DIR__ . '/includes/database_helper.php';

echo "<h1>🚀 End-to-End Payment Flow Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
</style>";

// Test 1: Check Stripe Configuration
echo "<div class='section'>";
echo "<h2>🔧 Stripe Configuration Test</h2>";

try {
    $stripeService = new StripeService();
    $testResult = $stripeService->testConfiguration();
    
    if ($testResult['success']) {
        echo "<div class='success'>✅ Stripe configuration valid</div>";
        echo "<div class='info'>Using: " . ($testResult['environment'] ?? 'unknown') . " environment</div>";
        
        if (isset($testResult['environment']) && $testResult['environment'] === 'test') {
            echo "<div class='success'>✅ Using TEST keys (safe for testing)</div>";
        } else {
            echo "<div class='warning'>⚠️ Using LIVE keys - be careful with real payments!</div>";
        }
    } else {
        echo "<div class='error'>❌ Stripe configuration error: " . ($testResult['error'] ?? 'Unknown error') . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Stripe service error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Session Setup
echo "<div class='section'>";
echo "<h2>👤 Session Setup</h2>";

// Create a test user session if not exists
if (!isset($_SESSION['user_id'])) {
    // Try to find or create a test user
    try {
        $pdo = getDBConnection();
        
        // Look for existing test user
        $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute(['test@cashcontrol.com']);
        $testUser = $stmt->fetch();
        
        if (!$testUser) {
            // Create test user
            $stmt = $pdo->prepare("
                INSERT INTO users (email, name, subscription_type, subscription_status) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute(['test@cashcontrol.com', 'Test User', 'free', 'active']);
            $testUserId = $pdo->lastInsertId();
            
            echo "<div class='info'>📝 Created test user with ID: $testUserId</div>";
        } else {
            $testUserId = $testUser['id'];
            echo "<div class='info'>👤 Using existing test user with ID: $testUserId</div>";
        }
        
        // Set session
        $_SESSION['user_id'] = $testUserId;
        $_SESSION['user_email'] = 'test@cashcontrol.com';
        $_SESSION['user_name'] = 'Test User';
        
        echo "<div class='success'>✅ Test user session created</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Failed to create test user: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='success'>✅ User session exists (ID: " . $_SESSION['user_id'] . ")</div>";
}
echo "</div>";

// Test 3: Payment Session Creation
echo "<div class='section'>";
echo "<h2>💳 Payment Session Creation Test</h2>";

if (isset($_SESSION['user_id'])) {
    try {
        $stripeService = new StripeService();
        
        // Test monthly plan
        $sessionData = $stripeService->createCheckoutSession(
            $_SESSION['user_id'],
            'monthly',
            'https://123cashcontrol.com/payment/success.php',
            'https://123cashcontrol.com/payment/cancel.php'
        );
        
        if ($sessionData && isset($sessionData['url'])) {
            echo "<div class='success'>✅ Payment session created successfully</div>";
            echo "<div class='info'>Session ID: " . $sessionData['id'] . "</div>";
            echo "<div class='info'>Session URL: <a href='" . $sessionData['url'] . "' target='_blank'>Open Stripe Checkout</a></div>";
            
            // Store session for webhook testing
            $_SESSION['test_session_id'] = $sessionData['id'];
            
            echo "<div class='warning'>⚠️ This is a real Stripe session - only proceed if using TEST keys!</div>";
            echo "<a href='" . $sessionData['url'] . "' class='button' target='_blank'>🔗 Test Payment Flow</a>";
            
        } else {
            echo "<div class='error'>❌ Failed to create payment session</div>";
            if (isset($sessionData['error'])) {
                echo "<div class='error'>Error: " . $sessionData['error'] . "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Payment session creation error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ No user session - cannot create payment session</div>";
}
echo "</div>";

// Test 4: Database Status
echo "<div class='section'>";
echo "<h2>🗄️ Database Status</h2>";

try {
    $stats = DatabaseHelper::getStats();
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Table</th><th>Records</th></tr>";
    foreach ($stats as $table => $count) {
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>✅ Database tables accessible</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 5: Webhook Status
echo "<div class='section'>";
echo "<h2>🔗 Webhook Status</h2>";

$webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
if (!empty($webhookSecret)) {
    echo "<div class='success'>✅ Webhook secret configured</div>";
    echo "<div class='info'>🌐 Webhook URL: https://123cashcontrol.com/webhooks/stripe.php</div>";
    echo "<div class='info'>📋 Ready to receive Stripe events</div>";
} else {
    echo "<div class='error'>❌ Webhook secret not configured</div>";
}

// Check if webhook file exists and is accessible
$webhookFile = __DIR__ . '/webhooks/stripe.php';
if (file_exists($webhookFile)) {
    echo "<div class='success'>✅ Webhook handler file exists</div>";
} else {
    echo "<div class='error'>❌ Webhook handler file missing</div>";
}
echo "</div>";

// Test 6: Instructions
echo "<div class='section'>";
echo "<h2>📋 Testing Instructions</h2>";
echo "<ol>";
echo "<li><strong>Verify TEST keys:</strong> Ensure you're using Stripe test keys (not live keys)</li>";
echo "<li><strong>Test payment:</strong> Click the payment link above to test the checkout flow</li>";
echo "<li><strong>Use test card:</strong> Use card number 4242424242424242 for successful test payment</li>";
echo "<li><strong>Check webhook:</strong> After payment, check Stripe Dashboard → Webhooks for event delivery</li>";
echo "<li><strong>Verify database:</strong> Check if user subscription was updated after payment</li>";
echo "<li><strong>Check email:</strong> Verify if confirmation email was sent</li>";
echo "</ol>";
echo "</div>";

// Test 7: Test Cards
echo "<div class='section'>";
echo "<h2>💳 Stripe Test Cards</h2>";
echo "<ul>";
echo "<li><strong>4242424242424242</strong> - Successful payment</li>";
echo "<li><strong>4000000000000002</strong> - Card declined</li>";
echo "<li><strong>4000000000009995</strong> - Insufficient funds</li>";
echo "<li><strong>4000000000000069</strong> - Expired card</li>";
echo "</ul>";
echo "<div class='info'>Use any future expiry date and any 3-digit CVC</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>✅ Next Steps</h2>";
echo "<p>If all tests pass:</p>";
echo "<ol>";
echo "<li>Test the payment flow with a test card</li>";
echo "<li>Verify webhook events are received in Stripe Dashboard</li>";
echo "<li>Check database updates after successful payment</li>";
echo "<li>Test email delivery after payment</li>";
echo "<li>Switch to live keys when ready for production</li>";
echo "</ol>";
echo "</div>";
?>
