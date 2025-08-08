<?php
/**
 * Quick Webhook Setup Verification
 * Checks if webhook configuration is complete and ready
 */

require_once __DIR__ . '/config/secure_loader.php';
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/database_helper.php';

echo "🔗 Webhook Setup Verification\n";
echo "============================\n\n";

// Check 1: Webhook secret
echo "1. Checking webhook secret...\n";
$webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
if (!empty($webhookSecret) && strpos($webhookSecret, 'whsec_') === 0) {
    echo "   ✅ Webhook secret configured correctly\n";
    echo "   📏 Length: " . strlen($webhookSecret) . " characters\n";
} else {
    echo "   ❌ Webhook secret missing or invalid format\n";
    exit(1);
}

// Check 2: Database tables
echo "\n2. Checking database tables...\n";
try {
    $pdo = getDBConnection();
    DatabaseHelper::initializeTables();
    
    $requiredTables = ['payment_history', 'checkout_sessions'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check 3: Webhook file
echo "\n3. Checking webhook handler...\n";
$webhookFile = __DIR__ . '/webhooks/stripe.php';
if (file_exists($webhookFile)) {
    echo "   ✅ Webhook handler exists\n";
    echo "   📁 Size: " . number_format(filesize($webhookFile)) . " bytes\n";
} else {
    echo "   ❌ Webhook handler missing\n";
    exit(1);
}

// Check 4: Test webhook URL format
echo "\n4. Webhook URL info...\n";
echo "   🌐 URL: https://123cashcontrol.com/webhooks/stripe.php\n";
echo "   📋 This should be configured in Stripe Dashboard\n";

echo "\n✅ Webhook setup verification complete!\n";
echo "🚀 Ready to test payment flow with webhook processing\n\n";

echo "Next steps:\n";
echo "- Test a payment with Stripe test keys\n";
echo "- Check webhook receives events in Stripe Dashboard\n";
echo "- Verify database updates after payment\n";
?>
