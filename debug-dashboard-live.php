<?php
// Simulate the exact dashboard.php execution flow
session_start();
require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';

echo "=== LIVE DASHBOARD EXECUTION DEBUG ===\n\n";

// 1. Check session state
echo "1. SESSION STATE:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Email in session: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "User Name in session: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";

// If no session, simulate login for support@origens.nl
if (!isset($_SESSION['user_id'])) {
    echo "No session found - simulating login for support@origens.nl\n";
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
        $stmt->execute(['support@origens.nl']);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            echo "Simulated login successful - User ID: {$user['id']}\n";
        } else {
            echo "ERROR: Could not find support@origens.nl user\n";
            exit;
        }
    } catch (Exception $e) {
        echo "ERROR during simulated login: " . $e->getMessage() . "\n";
        exit;
    }
}
echo "\n";

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

echo "2. WORKING WITH USER:\n";
echo "User ID: $userId\n";
echo "User Name: $userName\n";
echo "User Email: $userEmail\n\n";

// 2. Check user plan status (this might affect dashboard access)
echo "3. USER PLAN STATUS CHECK:\n";
try {
    require_once 'includes/user_plan_helper.php';
    $userPlan = UserPlanHelper::getUserPlanStatus($userId);
    
    echo "Plan Type: " . ($userPlan['plan_type'] ?? 'UNKNOWN') . "\n";
    echo "Is Paid: " . ($userPlan['is_paid'] ? 'YES' : 'NO') . "\n";
    echo "Plan Details: " . print_r($userPlan, true) . "\n";
    
    // Check if user would be redirected
    if ($userPlan['plan_type'] === 'one_time' && $userPlan['is_paid']) {
        echo "WARNING: User would be redirected to dashboard-onetime.php\n";
    } elseif (in_array($userPlan['plan_type'], ['monthly', 'yearly']) && $userPlan['is_paid']) {
        echo "OK: User has access to full dashboard\n";
    } else {
        echo "WARNING: User would be redirected to upgrade.php\n";
    }
} catch (Exception $e) {
    echo "ERROR checking user plan: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Test the exact subscription query from dashboard.php
echo "4. SUBSCRIPTION QUERY TEST:\n";
try {
    $pdo = getDBConnection();
    
    echo "Executing: SELECT * FROM subscriptions WHERE user_id = $userId ORDER BY created_at DESC\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    echo "Query returned: " . count($subscriptions) . " subscriptions\n";
    
    if (count($subscriptions) > 0) {
        echo "First subscription sample:\n";
        $first = $subscriptions[0];
        echo "  ID: {$first['id']}\n";
        echo "  Name: " . ($first['name'] ?: 'EMPTY') . "\n";
        echo "  Merchant Name: " . ($first['merchant_name'] ?: 'EMPTY') . "\n";
        echo "  Cost: {$first['cost']}\n";
        echo "  Is Active: {$first['is_active']}\n";
        echo "  Status: {$first['status']}\n";
        echo "  Provider: {$first['provider']}\n";
    }
} catch (Exception $e) {
    echo "ERROR in subscription query: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Test the stats calculation logic
echo "5. STATS CALCULATION TEST:\n";
if (isset($subscriptions) && count($subscriptions) > 0) {
    $stats = [
        'total_active' => 0,
        'monthly_total' => 0,
        'yearly_total' => 0,
        'next_payment' => null
    ];
    
    foreach ($subscriptions as $subscription) {
        echo "Processing subscription: " . ($subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown') . "\n";
        
        // Check if subscription is active (using actual database fields)
        $isActive = (bool)($subscription['is_active'] ?? ($subscription['status'] === 'active'));
        echo "  Is Active Check: is_active={$subscription['is_active']}, status={$subscription['status']} -> Result: " . ($isActive ? 'TRUE' : 'FALSE') . "\n";
        
        if ($isActive) {
            $stats['total_active']++;
            
            // Get amount (using actual database fields)
            $amount = (float)($subscription['cost'] ?? $subscription['amount'] ?? 0);
            echo "  Amount Check: cost={$subscription['cost']}, amount={$subscription['amount']} -> Result: $amount\n";
            
            // Calculate monthly cost
            $billingCycle = $subscription['billing_cycle'] ?? 'monthly';
            $monthlyCost = 0;
            
            switch ($billingCycle) {
                case 'monthly':
                    $monthlyCost = $amount;
                    break;
                case 'yearly':
                    $monthlyCost = $amount / 12;
                    break;
                case 'weekly':
                    $monthlyCost = $amount * 4.33;
                    break;
                case 'daily':
                    $monthlyCost = $amount * 30;
                    break;
                default:
                    $monthlyCost = $amount;
                    break;
            }
            
            $stats['monthly_total'] += $monthlyCost;
            $stats['yearly_total'] += $monthlyCost * 12;
            
            echo "  Monthly Cost: €" . number_format($monthlyCost, 2) . "\n";
        }
        echo "\n";
    }
    
    echo "FINAL STATS:\n";
    echo "  Total Active: {$stats['total_active']}\n";
    echo "  Monthly Total: €" . number_format($stats['monthly_total'], 2) . "\n";
    echo "  Yearly Total: €" . number_format($stats['yearly_total'], 2) . "\n";
} else {
    echo "No subscriptions to process for stats\n";
}
echo "\n";

// 5. Test display name logic
echo "6. DISPLAY NAME LOGIC TEST:\n";
if (isset($subscriptions) && count($subscriptions) > 0) {
    foreach ($subscriptions as $i => $subscription) {
        if ($i >= 3) break; // Test first 3
        
        $displayName = $subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown';
        echo "Subscription #" . ($i+1) . ":\n";
        echo "  Raw name: '" . ($subscription['name'] ?? 'NULL') . "'\n";
        echo "  Raw merchant_name: '" . ($subscription['merchant_name'] ?? 'NULL') . "'\n";
        echo "  Final display name: '$displayName'\n\n";
    }
}

// 6. Check for any PHP errors or warnings
echo "7. ERROR LOG CHECK:\n";
$errorLog = error_get_last();
if ($errorLog) {
    echo "Last PHP error: " . print_r($errorLog, true) . "\n";
} else {
    echo "No recent PHP errors\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
