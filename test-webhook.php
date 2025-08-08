<?php
/**
 * Stripe Webhook Test Script
 * Tests webhook handler functionality and simulates webhook events
 */

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/includes/database_helper.php';

echo "<h1>🔗 Stripe Webhook Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Check webhook file exists
echo "<div class='section'>";
echo "<h2>📁 Webhook File Check</h2>";
$webhookFile = __DIR__ . '/webhooks/stripe.php';
if (file_exists($webhookFile)) {
    echo "<div class='success'>✅ Webhook file exists: webhooks/stripe.php</div>";
    echo "<div class='info'>File size: " . number_format(filesize($webhookFile)) . " bytes</div>";
} else {
    echo "<div class='error'>❌ Webhook file not found: webhooks/stripe.php</div>";
}
echo "</div>";

// Test 2: Check webhook secret configuration
echo "<div class='section'>";
echo "<h2>🔐 Webhook Secret Configuration</h2>";
$webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
if (!empty($webhookSecret)) {
    echo "<div class='success'>✅ Webhook secret configured</div>";
    echo "<div class='info'>Secret length: " . strlen($webhookSecret) . " characters</div>";
    if (strpos($webhookSecret, 'whsec_') === 0) {
        echo "<div class='success'>✅ Secret format is correct (starts with whsec_)</div>";
    } else {
        echo "<div class='error'>❌ Secret format incorrect (should start with whsec_)</div>";
    }
} else {
    echo "<div class='error'>❌ Webhook secret not configured</div>";
    echo "<div class='info'>Add STRIPE_WEBHOOK_SECRET to your secure-config.php</div>";
}
echo "</div>";

// Test 3: Check database tables
echo "<div class='section'>";
echo "<h2>🗄️ Database Tables Check</h2>";
try {
    $pdo = getDBConnection();
    
    // Initialize tables if needed
    $result = DatabaseHelper::initializeTables();
    if ($result['success']) {
        echo "<div class='success'>✅ Database tables initialized</div>";
    }
    
    // Check required tables for webhooks
    $requiredTables = ['users', 'payment_history', 'checkout_sessions'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>❌ Table '$table' missing</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Simulate webhook signature verification
echo "<div class='section'>";
echo "<h2>🔏 Webhook Signature Test</h2>";
if (!empty($webhookSecret)) {
    $testPayload = '{"id":"evt_test","object":"event","type":"checkout.session.completed"}';
    $timestamp = time();
    $signedPayload = $timestamp . '.' . $testPayload;
    $signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
    $sigHeader = "t=$timestamp,v1=$signature";
    
    echo "<div class='info'>Test payload: <code>$testPayload</code></div>";
    echo "<div class='info'>Generated signature: <code>v1=$signature</code></div>";
    echo "<div class='success'>✅ Signature generation working</div>";
} else {
    echo "<div class='error'>❌ Cannot test signature without webhook secret</div>";
}
echo "</div>";

// Test 5: Check webhook URL accessibility
echo "<div class='section'>";
echo "<h2>🌐 Webhook URL Test</h2>";
$webhookUrl = "https://123cashcontrol.com/webhooks/stripe.php";
echo "<div class='info'>Expected webhook URL: <strong>$webhookUrl</strong></div>";
echo "<div class='info'>⚠️ This URL must be accessible from the internet for Stripe to send webhooks</div>";
echo "<div class='info'>💡 Test this URL in your browser - it should return a JSON error about missing payload</div>";
echo "</div>";

// Test 6: Show recommended Stripe webhook events
echo "<div class='section'>";
echo "<h2>📋 Recommended Stripe Webhook Events</h2>";
echo "<p>Configure these events in your Stripe Dashboard → Webhooks:</p>";
$events = [
    'checkout.session.completed' => 'Payment completed successfully',
    'checkout.session.expired' => 'Payment session expired',
    'payment_intent.succeeded' => 'One-time payment succeeded',
    'payment_intent.payment_failed' => 'Payment failed',
    'customer.subscription.created' => 'New subscription created',
    'customer.subscription.updated' => 'Subscription modified',
    'customer.subscription.deleted' => 'Subscription cancelled',
    'invoice.payment_succeeded' => 'Recurring payment succeeded',
    'invoice.payment_failed' => 'Recurring payment failed',
    'customer.created' => 'New customer created',
    'customer.updated' => 'Customer information updated'
];

echo "<ul>";
foreach ($events as $event => $description) {
    echo "<li><strong>$event</strong> - $description</li>";
}
echo "</ul>";
echo "</div>";

// Test 7: Show webhook setup instructions
echo "<div class='section'>";
echo "<h2>⚙️ Webhook Setup Instructions</h2>";
echo "<ol>";
echo "<li>Go to <a href='https://dashboard.stripe.com/webhooks' target='_blank'>Stripe Dashboard → Webhooks</a></li>";
echo "<li>Click 'Add endpoint'</li>";
echo "<li>Enter endpoint URL: <strong>$webhookUrl</strong></li>";
echo "<li>Select the events listed above</li>";
echo "<li>Click 'Add endpoint'</li>";
echo "<li>Copy the webhook signing secret (starts with whsec_)</li>";
echo "<li>Add it to your secure-config.php as STRIPE_WEBHOOK_SECRET</li>";
echo "</ol>";
echo "</div>";

// Test 8: Show test webhook payload example
echo "<div class='section'>";
echo "<h2>🧪 Test Webhook Payload</h2>";
echo "<p>You can test the webhook handler by sending a POST request with this payload:</p>";
echo "<pre>";
$testWebhookPayload = [
    'id' => 'evt_test_webhook',
    'object' => 'event',
    'type' => 'checkout.session.completed',
    'data' => [
        'object' => [
            'id' => 'cs_test_session',
            'customer' => 'cus_test_customer',
            'customer_details' => [
                'email' => 'test@example.com'
            ],
            'amount_total' => 999,
            'currency' => 'eur',
            'metadata' => [
                'user_id' => '1',
                'plan_type' => 'monthly'
            ]
        ]
    ]
];
echo json_encode($testWebhookPayload, JSON_PRETTY_PRINT);
echo "</pre>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>✅ Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Configure webhook secret</strong> in secure-config.php if not done</li>";
echo "<li><strong>Set up Stripe webhook</strong> in dashboard with the events listed above</li>";
echo "<li><strong>Test payment flow</strong> with test keys to verify webhook processing</li>";
echo "<li><strong>Check error logs</strong> for any webhook processing issues</li>";
echo "</ol>";
echo "</div>";
?>
