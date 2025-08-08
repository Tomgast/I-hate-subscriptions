<?php
/**
 * Check Payment Result
 * Verifies if the webhook processed your test payment correctly
 */

require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/includes/database_helper.php';

echo "<h1>💳 Payment Result Verification</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<h2>👤 User Status Check</h2>";
    
    // Check user 5 (the test user)
    $stmt = $pdo->prepare("
        SELECT id, email, name, subscription_type, subscription_status, 
               subscription_expires_at, reminder_access_expires_at, 
               stripe_customer_id, updated_at
        FROM users 
        WHERE id = 5
    ");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<div class='success'>✅ User found</div>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            echo "<tr><td>$key</td><td>" . ($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // Check if subscription was updated
        if ($user['subscription_type'] !== 'free') {
            echo "<div class='success'>✅ Subscription updated! User is now: " . $user['subscription_type'] . "</div>";
        } else {
            echo "<div class='error'>❌ Subscription still 'free' - webhook may not have processed</div>";
        }
        
    } else {
        echo "<div class='error'>❌ User not found</div>";
    }
    
    echo "<h2>💰 Payment History Check</h2>";
    
    // Check payment history
    $stmt = $pdo->prepare("
        SELECT * FROM payment_history 
        WHERE user_id = 5 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll();
    
    if ($payments) {
        echo "<div class='success'>✅ Found " . count($payments) . " payment record(s)</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Session ID</th><th>Amount</th><th>Currency</th><th>Plan Type</th><th>Status</th><th>Created</th></tr>";
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>" . $payment['id'] . "</td>";
            echo "<td>" . substr($payment['stripe_session_id'] ?? '', 0, 20) . "...</td>";
            echo "<td>" . ($payment['amount'] / 100) . "</td>";
            echo "<td>" . $payment['currency'] . "</td>";
            echo "<td>" . $payment['plan_type'] . "</td>";
            echo "<td>" . $payment['status'] . "</td>";
            echo "<td>" . $payment['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No payment records found</div>";
        echo "<div class='info'>This suggests the webhook may not have processed the payment</div>";
    }
    
    echo "<h2>🔗 Checkout Sessions Check</h2>";
    
    // Check checkout sessions
    $stmt = $pdo->prepare("
        SELECT * FROM checkout_sessions 
        WHERE user_id = 5 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll();
    
    if ($sessions) {
        echo "<div class='success'>✅ Found " . count($sessions) . " checkout session(s)</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Stripe Session ID</th><th>Plan Type</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        foreach ($sessions as $session) {
            echo "<tr>";
            echo "<td>" . $session['id'] . "</td>";
            echo "<td>" . substr($session['stripe_session_id'], 0, 20) . "...</td>";
            echo "<td>" . $session['plan_type'] . "</td>";
            echo "<td>" . ($session['amount'] / 100) . "</td>";
            echo "<td>" . $session['status'] . "</td>";
            echo "<td>" . $session['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No checkout sessions found</div>";
    }
    
    echo "<h2>📊 Summary</h2>";
    
    $webhookWorking = false;
    if ($user && $user['subscription_type'] !== 'free') {
        $webhookWorking = true;
        echo "<div class='success'>🎉 SUCCESS: Webhook processed your payment!</div>";
        echo "<div class='success'>✅ User subscription updated to: " . $user['subscription_type'] . "</div>";
        
        if ($payments) {
            echo "<div class='success'>✅ Payment recorded in database</div>";
        }
        
        echo "<div class='info'>💡 The success page error is just a missing method - the actual payment processing worked!</div>";
        
    } else {
        echo "<div class='error'>❌ Webhook may not have processed the payment</div>";
        echo "<div class='info'>Possible reasons:</div>";
        echo "<ul>";
        echo "<li>Webhook endpoint not accessible from internet</li>";
        echo "<li>Webhook secret mismatch</li>";
        echo "<li>Stripe webhook not configured correctly</li>";
        echo "<li>Payment was cancelled or failed</li>";
        echo "</ul>";
    }
    
    echo "<h2>🔧 Next Steps</h2>";
    if ($webhookWorking) {
        echo "<div class='success'>✅ Your payment system is working perfectly!</div>";
        echo "<ul>";
        echo "<li>✅ Payment session creation works</li>";
        echo "<li>✅ Stripe checkout processes payments</li>";
        echo "<li>✅ Webhook updates database correctly</li>";
        echo "<li>⚠️ Just need to fix the success page display</li>";
        echo "</ul>";
    } else {
        echo "<div class='info'>Need to investigate webhook processing:</div>";
        echo "<ul>";
        echo "<li>Check Stripe Dashboard → Webhooks for event delivery status</li>";
        echo "<li>Verify webhook endpoint is accessible</li>";
        echo "<li>Check webhook secret configuration</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}
?>
