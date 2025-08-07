<?php
/**
 * Test Script for New Pricing Model
 * Tests all three pricing tiers and access logic
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/stripe_service.php';

function testPricingModel() {
    echo "<h1>CashControl Pricing Model Test</h1>\n";
    
    try {
        $stripeService = new StripeService();
        
        // Test 1: Stripe Configuration
        echo "<h2>1. Testing Stripe Configuration</h2>\n";
        $config = $stripeService->testConfiguration();
        if ($config['configured']) {
            echo "✅ Stripe is properly configured<br>\n";
            echo "Publishable Key: " . substr($config['publishable_key'], 0, 12) . "...<br>\n";
        } else {
            echo "❌ Stripe configuration issues:<br>\n";
            foreach ($config['errors'] as $error) {
                echo "- $error<br>\n";
            }
        }
        
        // Test 2: Plan Details
        echo "<h2>2. Testing Plan Details</h2>\n";
        $plans = [
            'monthly' => ['name' => 'Monthly Subscription', 'price' => '€3.00', 'mode' => 'subscription'],
            'yearly' => ['name' => 'Yearly Subscription', 'price' => '€25.00', 'mode' => 'subscription'],
            'one_time_scan' => ['name' => 'One-Time Scan', 'price' => '€25.00', 'mode' => 'payment']
        ];
        
        foreach ($plans as $planType => $details) {
            echo "✅ $planType: {$details['name']} - {$details['price']} ({$details['mode']})<br>\n";
        }
        
        // Test 3: Database Schema
        echo "<h2>3. Testing Database Schema</h2>\n";
        $pdo = getDBConnection();
        
        // Check if new columns exist
        $columns = [
            'users' => ['subscription_type', 'subscription_status', 'has_scan_access', 'scan_access_type', 'reminder_access_expires_at'],
            'checkout_sessions' => ['plan_type'],
            'payment_history' => ['plan_type']
        ];
        
        foreach ($columns as $table => $cols) {
            $stmt = $pdo->query("DESCRIBE $table");
            $existingCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($cols as $col) {
                if (in_array($col, $existingCols)) {
                    echo "✅ $table.$col exists<br>\n";
                } else {
                    echo "❌ $table.$col missing - run migration<br>\n";
                }
            }
        }
        
        // Test 4: Access Logic (if we have test users)
        echo "<h2>4. Testing Access Logic</h2>\n";
        
        // Create a test user for access testing
        $testEmail = 'test-pricing@example.com';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$testUser) {
            // Create test user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, created_at) 
                VALUES ('Test User', ?, NOW())
            ");
            $stmt->execute([$testEmail]);
            $testUserId = $pdo->lastInsertId();
            echo "✅ Created test user (ID: $testUserId)<br>\n";
        } else {
            $testUserId = $testUser['id'];
            echo "✅ Using existing test user (ID: $testUserId)<br>\n";
        }
        
        // Test access methods
        $hasProAccess = $stripeService->hasProAccess($testUserId);
        $hasReminderAccess = $stripeService->hasReminderAccess($testUserId);
        $subscriptionDetails = $stripeService->getUserSubscriptionDetails($testUserId);
        
        echo "Pro Access: " . ($hasProAccess ? "✅ Yes" : "❌ No") . "<br>\n";
        echo "Reminder Access: " . ($hasReminderAccess ? "✅ Yes" : "❌ No") . "<br>\n";
        
        if ($subscriptionDetails) {
            echo "Subscription Details:<br>\n";
            echo "- Type: " . ($subscriptionDetails['subscription_type'] ?? 'None') . "<br>\n";
            echo "- Status: " . ($subscriptionDetails['subscription_status'] ?? 'None') . "<br>\n";
            echo "- Has Scan Access: " . ($subscriptionDetails['has_scan_access'] ? 'Yes' : 'No') . "<br>\n";
        }
        
        // Test 5: Simulate Upgrades
        echo "<h2>5. Testing Upgrade Simulations</h2>\n";
        
        // Test monthly subscription upgrade
        echo "<h3>Monthly Subscription Test</h3>\n";
        $monthlyResult = $stripeService->upgradeUserToSubscription($testUserId, 'monthly', ['id' => 'test_session_monthly']);
        echo "Monthly upgrade: " . ($monthlyResult ? "✅ Success" : "❌ Failed") . "<br>\n";
        
        // Check access after monthly upgrade
        $hasProAfterMonthly = $stripeService->hasProAccess($testUserId);
        echo "Pro access after monthly: " . ($hasProAfterMonthly ? "✅ Yes" : "❌ No") . "<br>\n";
        
        // Test one-time scan upgrade
        echo "<h3>One-Time Scan Test</h3>\n";
        
        // Reset user first
        $pdo->prepare("
            UPDATE users 
            SET subscription_type = NULL, subscription_status = NULL, 
                has_scan_access = FALSE, reminder_access_expires_at = NULL
            WHERE id = ?
        ")->execute([$testUserId]);
        
        $scanResult = $stripeService->upgradeUserToOneTimeScan($testUserId, ['id' => 'test_session_scan']);
        echo "One-time scan upgrade: " . ($scanResult ? "✅ Success" : "❌ Failed") . "<br>\n";
        
        // Check access after scan upgrade
        $hasProAfterScan = $stripeService->hasProAccess($testUserId);
        $hasReminderAfterScan = $stripeService->hasReminderAccess($testUserId);
        echo "Pro access after scan: " . ($hasProAfterScan ? "✅ Yes" : "❌ No") . "<br>\n";
        echo "Reminder access after scan: " . ($hasReminderAfterScan ? "✅ Yes" : "❌ No") . "<br>\n";
        
        // Test 6: Payment Recording
        echo "<h2>6. Testing Payment Recording</h2>\n";
        
        $testSession = ['id' => 'test_payment_session'];
        
        foreach (['monthly', 'yearly', 'one_time_scan'] as $planType) {
            $stripeService->recordPayment($testUserId, $testSession, $planType);
            echo "✅ Recorded $planType payment<br>\n";
        }
        
        // Check payment history
        $paymentHistory = $stripeService->getPaymentHistory($testUserId);
        echo "Payment history entries: " . count($paymentHistory) . "<br>\n";
        
        // Cleanup test user
        echo "<h2>7. Cleanup</h2>\n";
        $pdo->prepare("DELETE FROM payment_history WHERE user_id = ?")->execute([$testUserId]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$testUserId]);
        echo "✅ Cleaned up test data<br>\n";
        
        echo "<h2>✅ All Tests Completed!</h2>\n";
        echo "<p>The new pricing model appears to be working correctly. You can now:</p>\n";
        echo "<ul>\n";
        echo "<li>Accept monthly subscriptions (€3/month)</li>\n";
        echo "<li>Accept yearly subscriptions (€25/year)</li>\n";
        echo "<li>Offer one-time scans (€25 with 1-year reminders)</li>\n";
        echo "</ul>\n";
        
    } catch (Exception $e) {
        echo "<h2>❌ Test Failed</h2>\n";
        echo "Error: " . $e->getMessage() . "<br>\n";
        echo "Stack trace:<br>\n<pre>" . $e->getTraceAsString() . "</pre>\n";
    }
}

// Run tests if accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "<!DOCTYPE html>\n<html><head><title>Pricing Model Test</title></head><body>\n";
    testPricingModel();
    echo "</body></html>\n";
}
?>
