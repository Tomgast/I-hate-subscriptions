<?php
/**
 * Debug User Plan Detection Issue
 * Investigates why user shows as monthly but appears as basic
 */

session_start();

// Suppress HTTP_HOST warnings for CLI
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "=== User Plan Detection Debug ===\n\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    // Get user ID from session or parameter
    $userId = $_SESSION['user_id'] ?? 5; // Default to user 5 from the debug info
    
    echo "1. 🔍 Session Analysis:\n";
    echo "   User ID: $userId\n";
    echo "   Session Data:\n";
    foreach ($_SESSION as $key => $value) {
        echo "     $key => " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
    
    echo "\n2. 🗄️  Database User Record:\n";
    
    // Get complete user record from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   Database Record:\n";
        foreach ($user as $key => $value) {
            echo "     $key => " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "   ❌ User not found in database!\n";
        exit;
    }
    
    echo "\n3. 📊 Plan Status Analysis:\n";
    
    // Analyze subscription type
    $subscriptionType = $user['subscription_type'] ?? 'free';
    echo "   Subscription Type: '$subscriptionType'\n";
    
    // Analyze subscription status
    $subscriptionStatus = $user['subscription_status'] ?? 'inactive';
    echo "   Subscription Status: '$subscriptionStatus'\n";
    
    // Analyze expiration
    $expiresAt = $user['subscription_expires_at'] ?? null;
    echo "   Expires At: " . ($expiresAt ?? 'NULL') . "\n";
    
    if ($expiresAt) {
        $expiryTime = strtotime($expiresAt);
        $currentTime = time();
        $isExpired = $expiryTime < $currentTime;
        echo "   Is Expired: " . ($isExpired ? 'YES' : 'NO') . "\n";
        
        if (!$isExpired) {
            $timeLeft = $expiryTime - $currentTime;
            $daysLeft = floor($timeLeft / (24 * 60 * 60));
            echo "   Days Remaining: $daysLeft\n";
        }
    }
    
    echo "\n4. 🔄 Plan Logic Check:\n";
    
    // Check what the plan logic should determine
    $shouldBePaid = false;
    $planReason = '';
    
    if ($subscriptionType === 'free' || empty($subscriptionType)) {
        $planReason = 'Free user (subscription_type is free or empty)';
    } elseif ($subscriptionStatus !== 'active') {
        $planReason = "Inactive subscription (status: $subscriptionStatus)";
    } elseif ($expiresAt && strtotime($expiresAt) < time()) {
        $planReason = 'Subscription expired';
    } elseif (in_array($subscriptionType, ['monthly', 'yearly'])) {
        $shouldBePaid = true;
        $planReason = 'Valid ongoing subscription';
    } elseif ($subscriptionType === 'one_time') {
        $shouldBePaid = true;
        $planReason = 'One-time plan (check specific expiry rules)';
    } else {
        $planReason = "Unknown subscription type: $subscriptionType";
    }
    
    echo "   Expected Plan Status: " . ($shouldBePaid ? 'PAID' : 'FREE') . "\n";
    echo "   Reason: $planReason\n";
    
    echo "\n5. 💳 Payment History Check:\n";
    
    // Check payment history
    $stmt = $pdo->prepare("SELECT * FROM payment_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($payments) {
        echo "   Recent Payments:\n";
        foreach ($payments as $payment) {
            echo "     - " . $payment['created_at'] . ": " . $payment['amount'] . " " . $payment['currency'] . 
                 " (" . $payment['plan_type'] . ") - " . $payment['status'] . "\n";
        }
        
        // Check for successful payments
        $successfulPayments = array_filter($payments, function($p) {
            return $p['status'] === 'completed';
        });
        
        if ($successfulPayments) {
            echo "   ✅ Has successful payments: " . count($successfulPayments) . "\n";
        } else {
            echo "   ❌ No successful payments found\n";
        }
    } else {
        echo "   ❌ No payment history found\n";
    }
    
    echo "\n6. 🔧 Recommended Fix:\n";
    
    // Determine what needs to be fixed
    if ($subscriptionType === 'monthly' && $subscriptionStatus === 'active') {
        if (!$expiresAt) {
            echo "   ❌ ISSUE: Monthly subscription missing expiration date\n";
            echo "   🔧 FIX: Set subscription_expires_at to current date + 1 month\n";
            
            // Suggest fix
            $nextMonth = date('Y-m-d H:i:s', strtotime('+1 month'));
            echo "   💡 SQL Fix: UPDATE users SET subscription_expires_at = '$nextMonth' WHERE id = $userId;\n";
        } elseif (strtotime($expiresAt) < time()) {
            echo "   ❌ ISSUE: Monthly subscription has expired\n";
            echo "   🔧 FIX: Either extend expiration or change status to expired\n";
        } else {
            echo "   ✅ Monthly subscription appears valid\n";
            echo "   🔧 CHECK: Verify dashboard logic is reading plan correctly\n";
        }
    } elseif ($subscriptionType === 'free') {
        echo "   ✅ User correctly shows as free\n";
    } else {
        echo "   ⚠️  Unusual subscription configuration\n";
        echo "   🔧 REVIEW: Check if this is the intended state\n";
    }
    
    echo "\n7. 📋 Session vs Database Mismatch:\n";
    
    // Compare session to database
    $sessionType = $_SESSION['subscription_type'] ?? 'not_set';
    $sessionPaid = $_SESSION['is_paid'] ?? 'not_set';
    
    echo "   Session subscription_type: '$sessionType'\n";
    echo "   Database subscription_type: '$subscriptionType'\n";
    echo "   Session is_paid: '$sessionPaid'\n";
    echo "   Expected is_paid: " . ($shouldBePaid ? 'true' : 'false') . "\n";
    
    if ($sessionType !== $subscriptionType) {
        echo "   ❌ MISMATCH: Session and database subscription types don't match\n";
        echo "   🔧 FIX: Session needs to be refreshed from database\n";
    }
    
    if (($sessionPaid ? true : false) !== $shouldBePaid) {
        echo "   ❌ MISMATCH: Session is_paid doesn't match expected value\n";
        echo "   🔧 FIX: Update session is_paid based on current plan status\n";
    }
    
} catch (Exception $e) {
    echo "❌ Debug Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
