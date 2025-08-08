<?php
/**
 * DEBUG BANK SCAN ISSUE
 * Quick diagnostic tool to check why bank scan isn't working for pro users
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';
require_once 'includes/user_plan_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];

echo "<h1>Bank Scan Debug for User ID: {$userId}</h1>";

// Get plan information from both systems
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);
$userPlanHelper = UserPlanHelper::getUserPlanStatus($userId);

echo "<h2>Plan Manager Data:</h2>";
echo "<pre>";
print_r($userPlan);
echo "</pre>";

echo "<h2>User Plan Helper Data:</h2>";
echo "<pre>";
print_r($userPlanHelper);
echo "</pre>";

// Check specific conditions
echo "<h2>Bank Scan Checks:</h2>";

echo "<p><strong>Can Access Bank Scan Feature:</strong> ";
if ($planManager->canAccessFeature($userId, 'bank_scan')) {
    echo "✅ YES";
} else {
    echo "❌ NO";
}
echo "</p>";

echo "<p><strong>Has Scans Remaining:</strong> ";
if ($planManager->hasScansRemaining($userId)) {
    echo "✅ YES";
} else {
    echo "❌ NO";
}
echo "</p>";

echo "<p><strong>Plan Active:</strong> ";
if ($userPlan && $userPlan['is_active']) {
    echo "✅ YES";
} else {
    echo "❌ NO";
}
echo "</p>";

echo "<p><strong>Plan Type:</strong> " . ($userPlan['plan_type'] ?? 'NONE') . "</p>";

// Check database directly
echo "<h2>Direct Database Check:</h2>";
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            id, email, name, 
            plan_type, plan_expires_at, plan_purchased_at,
            scan_count, max_scans,
            subscription_type, subscription_status, subscription_expires_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $dbUser = $stmt->fetch();
    
    echo "<pre>";
    print_r($dbUser);
    echo "</pre>";
    
    // Check expiration dates
    if ($dbUser['plan_expires_at']) {
        $expiresAt = strtotime($dbUser['plan_expires_at']);
        $now = time();
        echo "<p><strong>Plan Expires At:</strong> " . date('Y-m-d H:i:s', $expiresAt) . "</p>";
        echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s', $now) . "</p>";
        echo "<p><strong>Plan Expired:</strong> " . ($expiresAt <= $now ? "❌ YES" : "✅ NO") . "</p>";
    } else {
        echo "<p><strong>Plan Expires At:</strong> ❌ NULL (This is the problem!)</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Suggest fix
echo "<h2>Suggested Fix:</h2>";
if ($userPlan && in_array($userPlan['plan_type'], ['monthly', 'yearly']) && !$userPlan['is_active']) {
    echo "<p>Your subscription plan is not showing as active. This is likely because:</p>";
    echo "<ul>";
    echo "<li>The <code>plan_expires_at</code> field is NULL or expired</li>";
    echo "<li>The subscription status needs to be updated</li>";
    echo "</ul>";
    
    echo "<p><strong>Quick Fix:</strong></p>";
    echo "<form method='POST' style='background: #f0f0f0; padding: 20px; margin: 20px 0;'>";
    echo "<p>Set plan expiration to 1 year from now:</p>";
    echo "<input type='hidden' name='action' value='fix_plan_expiration'>";
    echo "<button type='submit' style='background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Fix Plan Expiration</button>";
    echo "</form>";
}

// Handle fix action
if ($_POST && $_POST['action'] === 'fix_plan_expiration') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE users SET 
                plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                subscription_status = 'active'
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        echo "<div style='background: #d1fae5; border: 1px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<strong>✅ Fixed!</strong> Plan expiration updated. <a href='bank/scan.php'>Try bank scan now</a>";
        echo "</div>";
        
        // Refresh the page to show updated data
        echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #fef2f2; border: 1px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
