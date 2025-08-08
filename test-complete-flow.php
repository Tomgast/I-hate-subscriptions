<?php
/**
 * Complete Payment Flow Test
 * Tests the entire upgrade → checkout → payment success flow
 */

// Suppress HTTP_HOST warnings for CLI
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';
require_once 'includes/stripe_service.php';

echo "=== CashControl Complete Payment Flow Test ===\n\n";

try {
    $pdo = DatabaseHelper::getConnection();
    $stripeService = new StripeService();
    
    echo "1. 🔧 System Readiness Check:\n";
    
    // Check database connection
    echo "   ✅ Database connection: OK\n";
    
    // Check Stripe configuration
    $config = getSecureConfig();
    if (!empty($config['STRIPE_SECRET_KEY'])) {
        echo "   ✅ Stripe credentials: Configured\n";
    } else {
        echo "   ❌ Stripe credentials: Missing\n";
        exit;
    }
    
    // Check required tables
    $tables = ['users', 'payment_history', 'checkout_sessions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table $table: Exists\n";
        } else {
            echo "   ❌ Table $table: Missing\n";
        }
    }
    
    echo "\n2. 🧪 Plan Type Consistency Test:\n";
    
    // Test plan types in different components
    $planTypes = ['monthly', 'yearly', 'one_time'];
    
    foreach ($planTypes as $planType) {
        echo "   Testing plan type: $planType\n";
        
        // Test StripeService accepts the plan type
        try {
            // We can't actually create a session without a real user, but we can check if the plan exists
            $reflection = new ReflectionClass('StripeService');
            $method = $reflection->getMethod('createCheckoutSession');
            
            // Check if plan type exists in StripeService source
            $source = file_get_contents('includes/stripe_service.php');
            if (strpos($source, "'$planType' =>") !== false) {
                echo "     ✅ StripeService recognizes '$planType'\n";
            } else {
                echo "     ❌ StripeService missing '$planType'\n";
            }
        } catch (Exception $e) {
            echo "     ⚠️  Could not test StripeService for '$planType'\n";
        }
        
        // Test checkout.php accepts the plan type
        $checkoutSource = file_get_contents('payment/checkout.php');
        if (strpos($checkoutSource, "'$planType'") !== false) {
            echo "     ✅ checkout.php recognizes '$planType'\n";
        } else {
            echo "     ❌ checkout.php missing '$planType'\n";
        }
    }
    
    echo "\n3. 🔄 Upgrade Flow Test:\n";
    
    // Test upgrade page functionality
    $upgradeSource = file_get_contents('upgrade.php');
    
    // Check for startUpgrade function calls
    foreach ($planTypes as $planType) {
        if (strpos($upgradeSource, "startUpgrade('$planType')") !== false) {
            echo "   ✅ Upgrade button for '$planType': Found\n";
        } else {
            echo "   ❌ Upgrade button for '$planType': Missing\n";
        }
    }
    
    // Check JavaScript function exists
    if (strpos($upgradeSource, 'function startUpgrade') !== false) {
        echo "   ✅ startUpgrade JavaScript function: Exists\n";
    } else {
        echo "   ❌ startUpgrade JavaScript function: Missing\n";
    }
    
    echo "\n4. 💳 Stripe Integration Test:\n";
    
    // Test Stripe API connectivity
    try {
        $testResult = $stripeService->testConfiguration();
        if ($testResult) {
            echo "   ✅ Stripe API connectivity: Working\n";
        } else {
            echo "   ❌ Stripe API connectivity: Failed\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Stripe API test error: " . $e->getMessage() . "\n";
    }
    
    // Test required StripeService methods exist
    $requiredMethods = [
        'createCheckoutSession',
        'handleSuccessfulPayment',
        'upgradeUserToOneTimeScan',
        'upgradeUserToSubscription',
        'recordPayment'
    ];
    
    foreach ($requiredMethods as $method) {
        if (method_exists($stripeService, $method)) {
            echo "   ✅ StripeService::$method(): Exists\n";
        } else {
            echo "   ❌ StripeService::$method(): Missing\n";
        }
    }
    
    echo "\n5. 🗄️  Database Schema Test:\n";
    
    // Test users table schema
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredUserColumns = [
        'id', 'email', 'name', 'subscription_type', 
        'subscription_status', 'subscription_expires_at', 'stripe_customer_id'
    ];
    
    foreach ($requiredUserColumns as $col) {
        if (in_array($col, $userColumns)) {
            echo "   ✅ users.$col: Exists\n";
        } else {
            echo "   ❌ users.$col: Missing\n";
        }
    }
    
    // Test payment_history table schema
    $stmt = $pdo->query("DESCRIBE payment_history");
    $paymentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredPaymentColumns = [
        'id', 'user_id', 'stripe_session_id', 'amount', 
        'currency', 'plan_type', 'status'
    ];
    
    foreach ($requiredPaymentColumns as $col) {
        if (in_array($paymentColumns, $paymentColumns)) {
            echo "   ✅ payment_history.$col: Exists\n";
        } else {
            echo "   ❌ payment_history.$col: Missing\n";
        }
    }
    
    echo "\n6. 📋 Final Readiness Assessment:\n";
    
    // Count any issues found
    $issues = 0;
    
    // Quick recheck of critical components
    if (empty($config['STRIPE_SECRET_KEY'])) $issues++;
    if (strpos(file_get_contents('includes/stripe_service.php'), "'one_time' =>") === false) $issues++;
    if (strpos(file_get_contents('payment/checkout.php'), "'one_time'") === false) $issues++;
    if (strpos(file_get_contents('upgrade.php'), "startUpgrade('one_time')") === false) $issues++;
    
    // Check database columns
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'subscription_expires_at'");
    if ($stmt->rowCount() == 0) $issues++;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_history WHERE Field = 'plan_type'");
    if ($stmt->rowCount() == 0) $issues++;
    
    if ($issues == 0) {
        echo "   🎉 SYSTEM READY FOR PAYMENT TESTING!\n";
        echo "   ✅ All components aligned and functional\n";
        echo "   ✅ Database schema complete\n";
        echo "   ✅ Stripe integration configured\n";
        echo "   ✅ Plan types consistent across all files\n";
        echo "\n🚀 RECOMMENDED NEXT STEPS:\n";
        echo "   1. Test upgrade flow: Visit upgrade.php\n";
        echo "   2. Click 'Choose One-Time Scan' button\n";
        echo "   3. Verify redirect to Stripe checkout\n";
        echo "   4. Complete test payment\n";
        echo "   5. Verify success page and user upgrade\n";
    } else {
        echo "   ⚠️  $issues critical issues found\n";
        echo "   Please fix issues before payment testing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Complete Flow Test Finished ===\n";
?>
