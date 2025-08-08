<?php
session_start();
require_once 'includes/stripe_service.php';
require_once 'includes/database_helper.php';

echo "<h1>Detailed Payment Debug</h1>";

$sessionId = $_GET['session_id'] ?? 'cs_test_a1IABbo9my7WrsdKj0Sgv7ZXSjsyuwJu4vyU6IvG5Lbp7JY6aXNIIyZymX';

echo "<h2>Step-by-Step Payment Processing Debug</h2>";
echo "<p>Session ID: $sessionId</p>";

try {
    $stripeService = new StripeService();
    
    // Step 1: Retrieve session (we know this works)
    echo "<h3>Step 1: Retrieve Stripe Session</h3>";
    $session = $stripeService->getCheckoutSession($sessionId);
    
    if (!$session) {
        echo "<div style='color: red;'>❌ Failed to retrieve session</div>";
        exit;
    }
    
    echo "<div style='color: green;'>✅ Session retrieved</div>";
    echo "<p>Payment Status: " . ($session['payment_status'] ?? 'unknown') . "</p>";
    echo "<p>Plan Type: " . ($session['metadata']['plan_type'] ?? 'unknown') . "</p>";
    echo "<p>User ID: " . ($session['metadata']['user_id'] ?? 'unknown') . "</p>";
    
    // Step 2: Check user ID resolution
    echo "<h3>Step 2: User ID Resolution</h3>";
    $userId = null;
    if (isset($session['metadata']['user_id'])) {
        $userId = $session['metadata']['user_id'];
        echo "<div style='color: green;'>✅ User ID from metadata: $userId</div>";
    } else {
        echo "<div style='color: orange;'>⚠️ No user ID in metadata, trying email lookup</div>";
        $customerEmail = $session['customer_details']['email'] ?? null;
        if ($customerEmail) {
            $pdo = DatabaseHelper::getConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$customerEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $user['id'] ?? null;
            
            if ($userId) {
                echo "<div style='color: green;'>✅ User ID from email lookup: $userId</div>";
            } else {
                echo "<div style='color: red;'>❌ User not found by email: $customerEmail</div>";
            }
        }
    }
    
    if (!$userId) {
        echo "<div style='color: red;'>❌ Cannot proceed without user ID</div>";
        exit;
    }
    
    // Step 3: Test recordPayment method
    echo "<h3>Step 3: Test Payment Recording</h3>";
    try {
        // Get the StripeService instance and test recordPayment
        $reflection = new ReflectionClass($stripeService);
        $recordPaymentMethod = $reflection->getMethod('recordPayment');
        $recordPaymentMethod->setAccessible(true);
        
        $planType = $session['metadata']['plan_type'] ?? 'one_time_scan';
        $recordPaymentMethod->invoke($stripeService, $userId, $session, $planType);
        
        echo "<div style='color: green;'>✅ Payment recording completed</div>";
        
        // Check if payment was actually recorded
        $pdo = DatabaseHelper::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM payment_history WHERE stripe_session_id = ?");
        $stmt->execute([$sessionId]);
        $paymentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paymentRecord) {
            echo "<div style='color: green;'>✅ Payment record found in database</div>";
        } else {
            echo "<div style='color: red;'>❌ Payment record NOT found in database</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Payment recording failed: " . $e->getMessage() . "</div>";
        echo "<p>Error details: " . $e->getFile() . " line " . $e->getLine() . "</p>";
    }
    
    // Step 4: Test user upgrade method
    echo "<h3>Step 4: Test User Upgrade</h3>";
    try {
        $planType = $session['metadata']['plan_type'] ?? 'one_time_scan';
        
        if ($planType === 'one_time_scan') {
            echo "<p>Testing upgradeUserToOneTimeScan...</p>";
            $upgradeMethod = $reflection->getMethod('upgradeUserToOneTimeScan');
            $upgradeMethod->setAccessible(true);
            $upgradeMethod->invoke($stripeService, $userId, $session);
        } else {
            echo "<p>Testing upgradeUserToSubscription for plan: $planType...</p>";
            $upgradeMethod = $reflection->getMethod('upgradeUserToSubscription');
            $upgradeMethod->setAccessible(true);
            $upgradeMethod->invoke($stripeService, $userId, $planType, $session);
        }
        
        echo "<div style='color: green;'>✅ User upgrade completed</div>";
        
        // Check if user was actually updated
        $pdo = DatabaseHelper::getConnection();
        $stmt = $pdo->prepare("SELECT subscription_type, subscription_status, subscription_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div style='color: green;'>✅ User record found</div>";
            echo "<p>Subscription Type: " . ($user['subscription_type'] ?? 'null') . "</p>";
            echo "<p>Subscription Status: " . ($user['subscription_status'] ?? 'null') . "</p>";
            echo "<p>Expires At: " . ($user['subscription_expires_at'] ?? 'null') . "</p>";
        } else {
            echo "<div style='color: red;'>❌ User record not found</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ User upgrade failed: " . $e->getMessage() . "</div>";
        echo "<p>Error details: " . $e->getFile() . " line " . $e->getLine() . "</p>";
    }
    
    // Step 5: Test checkout session update
    echo "<h3>Step 5: Test Checkout Session Update</h3>";
    try {
        $pdo = DatabaseHelper::getConnection();
        $stmt = $pdo->prepare("
            UPDATE checkout_sessions 
            SET status = 'completed', updated_at = NOW() 
            WHERE stripe_session_id = ?
        ");
        $result = $stmt->execute([$sessionId]);
        
        if ($result) {
            echo "<div style='color: green;'>✅ Checkout session update completed</div>";
            echo "<p>Rows affected: " . $stmt->rowCount() . "</p>";
        } else {
            echo "<div style='color: red;'>❌ Checkout session update failed</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Checkout session update error: " . $e->getMessage() . "</div>";
    }
    
    echo "<h3>Summary</h3>";
    echo "<p>This detailed debug shows exactly which step is failing in the payment processing.</p>";
    echo "<p>If all steps pass individually but handleSuccessfulPayment still fails, there may be a database transaction or connection issue.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Fatal error: " . $e->getMessage() . "</div>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
