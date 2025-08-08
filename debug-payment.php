<?php
session_start();
require_once 'includes/stripe_service.php';
require_once 'includes/database_helper.php';

echo "<h1>Payment Debug Tool</h1>";

// Check if we have a session ID to debug
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    echo "<p>Please provide a session_id parameter in the URL</p>";
    echo "<p>Example: debug-payment.php?session_id=cs_test_...</p>";
    exit;
}

echo "<h2>Debugging Session: $sessionId</h2>";

try {
    $stripeService = new StripeService();
    
    // Test Stripe configuration first
    echo "<h3>1. Stripe Configuration Test:</h3>";
    $configTest = $stripeService->testConfiguration();
    if ($configTest['configured']) {
        echo "<div style='color: green;'>✅ Stripe configuration is valid</div>";
        echo "<div>Publishable Key: " . substr($configTest['publishable_key'], 0, 20) . "...</div>";
    } else {
        echo "<div style='color: red;'>❌ Stripe configuration issues:</div>";
        foreach ($configTest['errors'] as $error) {
            echo "<div style='color: red;'>- $error</div>";
        }
        exit;
    }
    
    // Try to retrieve the session from Stripe
    echo "<h3>2. Stripe Session Retrieval:</h3>";
    $session = $stripeService->getCheckoutSession($sessionId);
    
    if ($session) {
        echo "<div style='color: green;'>✅ Session retrieved successfully</div>";
        echo "<h4>Session Details:</h4>";
        echo "<pre>";
        echo "Session ID: " . ($session['id'] ?? 'N/A') . "\n";
        echo "Payment Status: " . ($session['payment_status'] ?? 'N/A') . "\n";
        echo "Status: " . ($session['status'] ?? 'N/A') . "\n";
        echo "Mode: " . ($session['mode'] ?? 'N/A') . "\n";
        echo "Amount Total: " . ($session['amount_total'] ?? 'N/A') . "\n";
        echo "Currency: " . ($session['currency'] ?? 'N/A') . "\n";
        echo "Customer Email: " . ($session['customer_details']['email'] ?? 'N/A') . "\n";
        echo "Payment Intent: " . ($session['payment_intent'] ?? 'N/A') . "\n";
        
        if (isset($session['metadata'])) {
            echo "Metadata:\n";
            foreach ($session['metadata'] as $key => $value) {
                echo "  $key: $value\n";
            }
        }
        echo "</pre>";
        
        // Check payment status specifically
        echo "<h4>Payment Status Analysis:</h4>";
        if (isset($session['payment_status'])) {
            switch ($session['payment_status']) {
                case 'paid':
                    echo "<div style='color: green;'>✅ Payment is marked as PAID</div>";
                    break;
                case 'unpaid':
                    echo "<div style='color: orange;'>⚠️ Payment is marked as UNPAID</div>";
                    break;
                case 'no_payment_required':
                    echo "<div style='color: blue;'>ℹ️ No payment required</div>";
                    break;
                default:
                    echo "<div style='color: red;'>❌ Unknown payment status: " . $session['payment_status'] . "</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ No payment_status field found</div>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ Failed to retrieve session from Stripe</div>";
        echo "<div>This could mean:</div>";
        echo "<ul>";
        echo "<li>Session ID is invalid or expired</li>";
        echo "<li>Stripe API credentials are incorrect</li>";
        echo "<li>Network connectivity issues</li>";
        echo "</ul>";
        exit;
    }
    
    // Test the handleSuccessfulPayment method
    echo "<h3>3. Payment Handling Test:</h3>";
    
    // Simulate user session for testing
    if (!isset($_SESSION['user_id'])) {
        echo "<div style='color: orange;'>⚠️ No user session - simulating with test user</div>";
        $_SESSION['user_id'] = 1; // Temporary for testing
        $_SESSION['user_email'] = 'tom@degruijterweb.nl';
    }
    
    $result = $stripeService->handleSuccessfulPayment($sessionId);
    
    if ($result) {
        echo "<div style='color: green;'>✅ Payment handling completed successfully</div>";
    } else {
        echo "<div style='color: red;'>❌ Payment handling failed</div>";
    }
    
    // Check database records
    echo "<h3>4. Database Records:</h3>";
    $pdo = DatabaseHelper::getConnection();
    
    // Check payment history
    $stmt = $pdo->prepare("SELECT * FROM payment_history WHERE stripe_session_id = ?");
    $stmt->execute([$sessionId]);
    $paymentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($paymentRecord) {
        echo "<div style='color: green;'>✅ Payment record found in database</div>";
        echo "<pre>";
        print_r($paymentRecord);
        echo "</pre>";
    } else {
        echo "<div style='color: red;'>❌ No payment record found in database</div>";
    }
    
    // Check user subscription status
    if (isset($_SESSION['user_email'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['user_email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<h4>User Subscription Status:</h4>";
            echo "<pre>";
            echo "Subscription Type: " . ($user['subscription_type'] ?? 'N/A') . "\n";
            echo "Subscription Status: " . ($user['subscription_status'] ?? 'N/A') . "\n";
            echo "Expires At: " . ($user['subscription_expires_at'] ?? 'N/A') . "\n";
            echo "Reminder Access Expires: " . ($user['reminder_access_expires_at'] ?? 'N/A') . "\n";
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error during debugging: " . $e->getMessage() . "</div>";
    echo "<div>Stack trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>5. Next Steps:</h3>";
echo "<ul>";
echo "<li>If payment_status is 'paid' but handling failed, check the error logs</li>";
echo "<li>If session retrieval failed, verify Stripe credentials</li>";
echo "<li>If database records are missing, check database permissions</li>";
echo "</ul>";
?>
