<?php
/**
 * Comprehensive System Alignment Audit
 * Checks consistency between Stripe integration, database schema, and application logic
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 CashControl System Alignment Audit</h1>";
echo "<p>Checking consistency between Stripe, Database, and Application Logic</p>";

$issues = [];
$warnings = [];
$successes = [];

try {
    // 1. DATABASE SCHEMA AUDIT
    echo "<h2>1. 📊 Database Schema Audit</h2>";
    
    require_once 'includes/database_helper.php';
    $pdo = DatabaseHelper::getConnection();
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredUserColumns = [
        'subscription_type' => "ENUM('free', 'monthly', 'yearly', 'one_time')",
        'subscription_status' => "ENUM('active', 'cancelled', 'expired')",
        'subscription_expires_at' => 'DATETIME',
        'reminder_access_expires_at' => 'DATETIME',
        'stripe_customer_id' => 'VARCHAR'
    ];
    
    $existingUserColumns = [];
    foreach ($userColumns as $col) {
        $existingUserColumns[$col['Field']] = $col['Type'];
    }
    
    foreach ($requiredUserColumns as $colName => $expectedType) {
        if (isset($existingUserColumns[$colName])) {
            echo "<div style='color: green;'>✅ $colName: {$existingUserColumns[$colName]}</div>";
            $successes[] = "Users table has $colName column";
        } else {
            echo "<div style='color: red;'>❌ Missing: $colName ($expectedType)</div>";
            $issues[] = "Users table missing $colName column";
        }
    }
    
    // Check payment_history table
    echo "<h3>Payment History Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE payment_history");
    $paymentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredPaymentColumns = [
        'user_id' => 'INT',
        'stripe_session_id' => 'VARCHAR',
        'amount' => 'INT',
        'currency' => 'VARCHAR',
        'plan_type' => 'VARCHAR',
        'status' => 'ENUM'
    ];
    
    $existingPaymentColumns = [];
    foreach ($paymentColumns as $col) {
        $existingPaymentColumns[$col['Field']] = $col['Type'];
    }
    
    foreach ($requiredPaymentColumns as $colName => $expectedType) {
        if (isset($existingPaymentColumns[$colName])) {
            echo "<div style='color: green;'>✅ $colName: {$existingPaymentColumns[$colName]}</div>";
            $successes[] = "Payment history has $colName column";
        } else {
            echo "<div style='color: red;'>❌ Missing: $colName ($expectedType)</div>";
            $issues[] = "Payment history missing $colName column";
        }
    }
    
    // Check checkout_sessions table
    echo "<h3>Checkout Sessions Table:</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'checkout_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green;'>✅ checkout_sessions table exists</div>";
        $successes[] = "Checkout sessions table exists";
        
        $stmt = $pdo->query("DESCRIBE checkout_sessions");
        $checkoutColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($checkoutColumns as $col) {
            echo "<div style='color: gray;'>  - {$col['Field']}: {$col['Type']}</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ checkout_sessions table missing</div>";
        $issues[] = "Checkout sessions table missing";
    }
    
    // 2. STRIPE INTEGRATION AUDIT
    echo "<h2>2. 💳 Stripe Integration Audit</h2>";
    
    require_once 'includes/stripe_service.php';
    $stripeService = new StripeService();
    
    // Test Stripe configuration
    $configTest = $stripeService->testConfiguration();
    if ($configTest['configured']) {
        echo "<div style='color: green;'>✅ Stripe credentials valid</div>";
        $successes[] = "Stripe credentials configured";
    } else {
        echo "<div style='color: red;'>❌ Stripe configuration issues:</div>";
        foreach ($configTest['errors'] as $error) {
            echo "<div style='color: red;'>  - $error</div>";
            $issues[] = "Stripe: $error";
        }
    }
    
    // Check StripeService methods
    $reflection = new ReflectionClass($stripeService);
    $requiredMethods = [
        'createCheckoutSession',
        'handleSuccessfulPayment',
        'upgradeUserToOneTimeScan',
        'upgradeUserToSubscription',
        'recordPayment'
    ];
    
    echo "<h3>StripeService Methods:</h3>";
    foreach ($requiredMethods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "<div style='color: green;'>✅ $method() exists</div>";
            $successes[] = "StripeService has $method method";
        } else {
            echo "<div style='color: red;'>❌ $method() missing</div>";
            $issues[] = "StripeService missing $method method";
        }
    }
    
    // 3. PLAN TYPE CONSISTENCY AUDIT
    echo "<h2>3. 📋 Plan Type Consistency Audit</h2>";
    
    // Check plan types across different files
    $planTypeMapping = [
        'Database ENUM' => ['free', 'monthly', 'yearly', 'one_time'],
        'StripeService' => ['monthly', 'yearly', 'onetime'], // From createCheckoutSession
        'Checkout.php' => ['monthly', 'yearly', 'onetime'], // From validPlans array
        'Dashboard.php' => ['monthly', 'yearly', 'one_time'] // From routing logic
    ];
    
    echo "<h3>Plan Type Mapping Across Components:</h3>";
    foreach ($planTypeMapping as $component => $planTypes) {
        echo "<div><strong>$component:</strong> " . implode(', ', $planTypes) . "</div>";
    }
    
    // Check for inconsistencies
    $allPlanTypes = array_merge(...array_values($planTypeMapping));
    $uniquePlanTypes = array_unique($allPlanTypes);
    
    if (in_array('onetime', $uniquePlanTypes) && in_array('one_time', $uniquePlanTypes)) {
        echo "<div style='color: red;'>❌ Inconsistency: Both 'onetime' and 'one_time' used</div>";
        $issues[] = "Plan type inconsistency: onetime vs one_time";
    }
    
    // 4. APPLICATION FLOW AUDIT
    echo "<h2>4. 🔄 Application Flow Audit</h2>";
    
    // Check upgrade flow
    echo "<h3>Upgrade Flow Check:</h3>";
    
    // Check if upgrade.php exists and has correct JavaScript
    if (file_exists('upgrade.php')) {
        $upgradeContent = file_get_contents('upgrade.php');
        if (strpos($upgradeContent, 'startUpgrade') !== false) {
            echo "<div style='color: green;'>✅ upgrade.php has startUpgrade function</div>";
            $successes[] = "Upgrade page has payment initiation";
        } else {
            echo "<div style='color: red;'>❌ upgrade.php missing startUpgrade function</div>";
            $issues[] = "Upgrade page missing payment initiation";
        }
        
        if (strpos($upgradeContent, '/payment/checkout.php') !== false) {
            echo "<div style='color: green;'>✅ upgrade.php redirects to checkout</div>";
            $successes[] = "Upgrade page redirects correctly";
        } else {
            echo "<div style='color: red;'>❌ upgrade.php incorrect redirect</div>";
            $issues[] = "Upgrade page incorrect redirect";
        }
    }
    
    // Check if checkout.php exists
    if (file_exists('payment/checkout.php')) {
        echo "<div style='color: green;'>✅ payment/checkout.php exists</div>";
        $successes[] = "Checkout page exists";
    } else {
        echo "<div style='color: red;'>❌ payment/checkout.php missing</div>";
        $issues[] = "Checkout page missing";
    }
    
    // Check if success.php exists
    if (file_exists('payment/success.php')) {
        echo "<div style='color: green;'>✅ payment/success.php exists</div>";
        $successes[] = "Success page exists";
    } else {
        echo "<div style='color: red;'>❌ payment/success.php missing</div>";
        $issues[] = "Success page missing";
    }
    
    // 5. USER DATA AUDIT
    echo "<h2>5. 👤 User Data Audit</h2>";
    
    // Check current user's data
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "<h3>Current User Data:</h3>";
            echo "<div>User ID: {$userData['id']}</div>";
            echo "<div>Email: {$userData['email']}</div>";
            echo "<div>Subscription Type: " . ($userData['subscription_type'] ?? 'NULL') . "</div>";
            echo "<div>Subscription Status: " . ($userData['subscription_status'] ?? 'NULL') . "</div>";
            echo "<div>Expires At: " . ($userData['subscription_expires_at'] ?? 'NULL') . "</div>";
            echo "<div>Stripe Customer ID: " . ($userData['stripe_customer_id'] ?? 'NULL') . "</div>";
            
            if (empty($userData['subscription_type']) || $userData['subscription_type'] === 'free') {
                $warnings[] = "Current user has no paid subscription";
            }
        }
    } else {
        echo "<div style='color: orange;'>⚠️ No user session active</div>";
        $warnings[] = "No user session for testing";
    }
    
    // 6. SUMMARY AND RECOMMENDATIONS
    echo "<h2>6. 📋 Summary and Recommendations</h2>";
    
    echo "<h3 style='color: green;'>✅ Successes (" . count($successes) . "):</h3>";
    foreach ($successes as $success) {
        echo "<div style='color: green;'>• $success</div>";
    }
    
    if (!empty($warnings)) {
        echo "<h3 style='color: orange;'>⚠️ Warnings (" . count($warnings) . "):</h3>";
        foreach ($warnings as $warning) {
            echo "<div style='color: orange;'>• $warning</div>";
        }
    }
    
    if (!empty($issues)) {
        echo "<h3 style='color: red;'>❌ Critical Issues (" . count($issues) . "):</h3>";
        foreach ($issues as $issue) {
            echo "<div style='color: red;'>• $issue</div>";
        }
        
        echo "<h3>🔧 Recommended Fixes:</h3>";
        echo "<ol>";
        echo "<li>Run database migration to ensure all required columns exist</li>";
        echo "<li>Standardize plan type naming (use 'one_time' everywhere)</li>";
        echo "<li>Update StripeService to use consistent plan types</li>";
        echo "<li>Test complete payment flow end-to-end</li>";
        echo "</ol>";
    } else {
        echo "<h3 style='color: green;'>🎉 System Alignment Status: GOOD</h3>";
        echo "<p>All critical components are properly aligned. Ready for payment testing.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Audit Error: " . $e->getMessage() . "</div>";
    echo "<div>Stack trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Fix any critical issues identified above</li>";
echo "<li>Run this audit again to verify fixes</li>";
echo "<li>Test payment flow with aligned system</li>";
echo "</ul>";
?>
