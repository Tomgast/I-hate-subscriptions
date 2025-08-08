<?php
/**
 * Simple verification script to check alignment fixes
 */

// Suppress HTTP_HOST warnings for CLI
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "=== CashControl Alignment Verification ===\n\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    // 1. Check database columns
    echo "1. Database Schema Check:\n";
    
    // Check users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'subscription_expires_at'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ users.subscription_expires_at exists\n";
    } else {
        echo "   ❌ users.subscription_expires_at missing\n";
    }
    
    // Check payment_history table
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_history WHERE Field = 'plan_type'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ payment_history.plan_type exists\n";
    } else {
        echo "   ❌ payment_history.plan_type missing\n";
    }
    
    // 2. Check plan type consistency
    echo "\n2. Plan Type Consistency Check:\n";
    
    // Check StripeService plan types
    require_once 'includes/stripe_service.php';
    $reflection = new ReflectionClass('StripeService');
    $method = $reflection->getMethod('createCheckoutSession');
    $source = file_get_contents('includes/stripe_service.php');
    
    if (strpos($source, "'one_time' =>") !== false) {
        echo "   ✅ StripeService uses 'one_time'\n";
    } else {
        echo "   ❌ StripeService still uses 'onetime'\n";
    }
    
    // Check checkout.php
    $checkoutSource = file_get_contents('payment/checkout.php');
    if (strpos($checkoutSource, "'one_time'") !== false) {
        echo "   ✅ checkout.php uses 'one_time'\n";
    } else {
        echo "   ❌ checkout.php still uses 'onetime'\n";
    }
    
    // Check upgrade.php
    $upgradeSource = file_get_contents('upgrade.php');
    if (strpos($upgradeSource, "startUpgrade('one_time')") !== false) {
        echo "   ✅ upgrade.php uses 'one_time'\n";
    } else {
        echo "   ❌ upgrade.php still uses 'onetime'\n";
    }
    
    echo "\n3. System Status:\n";
    
    // Count issues
    $issues = 0;
    
    // Recheck everything
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'subscription_expires_at'");
    if ($stmt->rowCount() == 0) $issues++;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_history WHERE Field = 'plan_type'");
    if ($stmt->rowCount() == 0) $issues++;
    
    if (strpos($source, "'one_time' =>") === false) $issues++;
    if (strpos($checkoutSource, "'one_time'") === false) $issues++;
    if (strpos($upgradeSource, "startUpgrade('one_time')") === false) $issues++;
    
    if ($issues == 0) {
        echo "   🎉 ALL ALIGNMENT ISSUES FIXED!\n";
        echo "   ✅ Database schema complete\n";
        echo "   ✅ Plan types consistent\n";
        echo "   ✅ Ready for payment testing\n";
    } else {
        echo "   ⚠️  $issues issues remaining\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ===\n";
?>
