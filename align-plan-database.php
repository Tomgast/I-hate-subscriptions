<?php
/**
 * PLAN DATABASE ALIGNMENT TOOL
 * Comprehensive tool to align all plan-related database columns and fix inconsistencies
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
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>Plan Database Alignment Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

// Get all plan-related data from database
try {
    $pdo = getDBConnection();
    
    // Get complete user record
    $stmt = $pdo->prepare("
        SELECT * FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    echo "<h2>🔍 Current Database State</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
    echo "<strong>All Plan-Related Columns:</strong><br>";
    
    $planColumns = [
        'plan_type' => $user['plan_type'] ?? 'NULL',
        'plan_expires_at' => $user['plan_expires_at'] ?? 'NULL',
        'plan_purchased_at' => $user['plan_purchased_at'] ?? 'NULL',
        'subscription_type' => $user['subscription_type'] ?? 'NULL',
        'subscription_status' => $user['subscription_status'] ?? 'NULL',
        'subscription_expires_at' => $user['subscription_expires_at'] ?? 'NULL',
        'scan_count' => $user['scan_count'] ?? 'NULL',
        'max_scans' => $user['max_scans'] ?? 'NULL',
        'stripe_customer_id' => $user['stripe_customer_id'] ?? 'NULL',
        'stripe_subscription_id' => $user['stripe_subscription_id'] ?? 'NULL'
    ];
    
    foreach ($planColumns as $column => $value) {
        $color = ($value === 'NULL' || empty($value)) ? 'red' : 'green';
        echo "<span style='color: {$color};'>{$column}:</span> {$value}<br>";
    }
    echo "</div>";
    
    // Test different plan checking systems
    echo "<h2>🧪 Plan System Tests</h2>";
    
    // Test 1: PlanManager
    echo "<h3>1. PlanManager System:</h3>";
    $planManager = getPlanManager();
    $planManagerResult = $planManager->getUserPlan($userId);
    echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px;'>";
    if ($planManagerResult) {
        echo "<strong>Plan Type:</strong> " . ($planManagerResult['plan_type'] ?? 'NULL') . "<br>";
        echo "<strong>Is Active:</strong> " . ($planManagerResult['is_active'] ? 'YES' : 'NO') . "<br>";
        echo "<strong>Can Scan:</strong> " . ($planManagerResult['can_scan'] ? 'YES' : 'NO') . "<br>";
        echo "<strong>Scans Remaining:</strong> " . ($planManagerResult['scans_remaining'] ?? 'NULL') . "<br>";
    } else {
        echo "<span style='color: red;'>❌ No plan found</span>";
    }
    echo "</div>";
    
    // Test 2: UserPlanHelper
    echo "<h3>2. UserPlanHelper System:</h3>";
    $userPlanHelper = UserPlanHelper::getUserPlanStatus($userId);
    echo "<div style='background: #f3e5f5; padding: 10px; border-radius: 5px;'>";
    if ($userPlanHelper) {
        echo "<strong>Plan Type:</strong> " . ($userPlanHelper['plan_type'] ?? 'NULL') . "<br>";
        echo "<strong>Is Paid:</strong> " . ($userPlanHelper['is_paid'] ? 'YES' : 'NO') . "<br>";
        echo "<strong>Display Name:</strong> " . ($userPlanHelper['display_name'] ?? 'NULL') . "<br>";
    } else {
        echo "<span style='color: red;'>❌ No plan found</span>";
    }
    echo "</div>";
    
    // Test 3: Header system (what shows in navigation)
    echo "<h3>3. Header System Test:</h3>";
    echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    
    // Simulate header logic
    if (function_exists('getUserPlanBadge')) {
        echo "<strong>Header Badge:</strong> " . getUserPlanBadge($userId) . "<br>";
    } else {
        echo "<strong>Header Badge Function:</strong> Not available<br>";
    }
    
    // Check session data
    echo "<strong>Session Data:</strong><br>";
    echo "- user_plan: " . ($_SESSION['user_plan'] ?? 'NULL') . "<br>";
    echo "- is_paid: " . ($_SESSION['is_paid'] ?? 'NULL') . "<br>";
    echo "</div>";
    
    // Identify inconsistencies
    echo "<h2>⚠️ Inconsistencies Found</h2>";
    $issues = [];
    $recommendations = [];
    
    // Check for plan type mismatches
    $planTypes = [
        'Database plan_type' => $user['plan_type'],
        'Database subscription_type' => $user['subscription_type'],
        'PlanManager' => $planManagerResult['plan_type'] ?? null,
        'UserPlanHelper' => $userPlanHelper['plan_type'] ?? null
    ];
    
    $uniquePlanTypes = array_unique(array_filter($planTypes));
    if (count($uniquePlanTypes) > 1) {
        $issues[] = "Plan type mismatch across systems: " . implode(', ', array_map(function($k, $v) { return "$k: $v"; }, array_keys($planTypes), $planTypes));
        $recommendations[] = "Standardize plan_type across all systems";
    }
    
    // Check for missing expiration dates
    if (in_array($user['subscription_type'], ['monthly', 'yearly']) && empty($user['plan_expires_at'])) {
        $issues[] = "Subscription user missing plan_expires_at date";
        $recommendations[] = "Set plan_expires_at for subscription users";
    }
    
    // Check for conflicting active status
    $isActiveStates = [
        'PlanManager' => $planManagerResult['is_active'] ?? false,
        'UserPlanHelper' => $userPlanHelper['is_paid'] ?? false
    ];
    
    if ($isActiveStates['PlanManager'] !== $isActiveStates['UserPlanHelper']) {
        $issues[] = "Active status mismatch: PlanManager=" . ($isActiveStates['PlanManager'] ? 'true' : 'false') . ", UserPlanHelper=" . ($isActiveStates['UserPlanHelper'] ? 'true' : 'false');
        $recommendations[] = "Align active status logic between systems";
    }
    
    if (empty($issues)) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "✅ No major inconsistencies found!";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>Issues Found:</strong><ul>";
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ul></div>";
        
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin-top: 10px;'>";
        echo "<strong>Recommendations:</strong><ul>";
        foreach ($recommendations as $rec) {
            echo "<li>{$rec}</li>";
        }
        echo "</ul></div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Alignment Actions
echo "<h2>🔧 Alignment Actions</h2>";

// Action 1: Detect and fix plan type
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Action 1: Standardize Plan Type</h3>";
echo "<p>This will set a single, consistent plan type across all database columns.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='standardize_plan_type'>";

// Determine the best plan type to use
$bestPlanType = null;
if (!empty($user['subscription_type']) && in_array($user['subscription_type'], ['monthly', 'yearly'])) {
    $bestPlanType = $user['subscription_type'];
} elseif (!empty($user['plan_type']) && in_array($user['plan_type'], ['monthly', 'yearly', 'onetime'])) {
    $bestPlanType = $user['plan_type'];
}

if ($bestPlanType) {
    echo "<p><strong>Detected plan type:</strong> {$bestPlanType}</p>";
    echo "<input type='hidden' name='plan_type' value='{$bestPlanType}'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Standardize to {$bestPlanType}</button>";
} else {
    echo "<p style='color: red;'>⚠️ Cannot auto-detect plan type. Manual selection required:</p>";
    echo "<select name='plan_type' required style='padding: 5px; margin-right: 10px;'>";
    echo "<option value=''>Select Plan Type</option>";
    echo "<option value='monthly'>Monthly (€3/month)</option>";
    echo "<option value='yearly'>Yearly (€25/year)</option>";
    echo "<option value='onetime'>One-time (€25 once)</option>";
    echo "</select>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Set Plan Type</button>";
}
echo "</form>";
echo "</div>";

// Action 2: Set expiration dates
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Action 2: Set Expiration Dates</h3>";
echo "<p>This will set proper expiration dates for subscription users.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='set_expiration_dates'>";
echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Set Expiration Dates</button>";
echo "</form>";
echo "</div>";

// Action 3: Nuclear option - Force Pro Status
echo "<div style='background: #ffebee; border: 1px solid #ffcdd2; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Action 3: Force Pro Status (Nuclear Option)</h3>";
echo "<p><strong>⚠️ WARNING:</strong> This will force set you as a yearly pro user with all features enabled.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='force_pro_status'>";
echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"Are you sure? This will override all your plan data.\")'>Force Pro Status</button>";
echo "</form>";
echo "</div>";

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        switch ($_POST['action']) {
            case 'standardize_plan_type':
                $planType = $_POST['plan_type'];
                if (in_array($planType, ['monthly', 'yearly', 'onetime'])) {
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            plan_type = ?,
                            subscription_type = ?,
                            subscription_status = 'active'
                        WHERE id = ?
                    ");
                    $stmt->execute([$planType, $planType, $userId]);
                    
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                    echo "<strong>✅ Plan Type Standardized!</strong><br>";
                    echo "Set both plan_type and subscription_type to: {$planType}";
                    echo "</div>";
                }
                break;
                
            case 'set_expiration_dates':
                // Get current user data to determine proper expiration dates
                $stmt = $pdo->prepare("SELECT plan_type, subscription_type FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch();
                
                // Set expiration based on actual plan type
                $planType = $userData['plan_type'] ?: $userData['subscription_type'];
                
                if ($planType === 'monthly') {
                    // Monthly plans expire in 1 month
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                            subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        WHERE id = ?
                    ");
                    $expirationText = "1 month from now (monthly plan)";
                } elseif ($planType === 'yearly') {
                    // Yearly plans expire in 1 year
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                            subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
                        WHERE id = ?
                    ");
                    $expirationText = "1 year from now (yearly plan)";
                } elseif ($planType === 'onetime') {
                    // One-time plans don't expire but set far future date
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            plan_expires_at = DATE_ADD(NOW(), INTERVAL 10 YEAR),
                            subscription_expires_at = DATE_ADD(NOW(), INTERVAL 10 YEAR)
                        WHERE id = ?
                    ");
                    $expirationText = "10 years from now (one-time plan - effectively never expires)";
                } else {
                    // Default to 1 year if plan type unclear
                    $stmt = $pdo->prepare("
                        UPDATE users SET 
                            plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                            subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
                        WHERE id = ?
                    ");
                    $expirationText = "1 year from now (default)";
                }
                
                $stmt->execute([$userId]);
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>✅ Expiration Dates Set!</strong><br>";
                echo "Plan expires: {$expirationText}<br>";
                echo "Plan type: {$planType}";
                echo "</div>";
                break;
                
            case 'force_pro_status':
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        plan_type = 'yearly',
                        subscription_type = 'yearly',
                        subscription_status = 'active',
                        plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                        subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                        max_scans = 999999,
                        scan_count = 0
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>🚀 Pro Status Forced!</strong><br>";
                echo "You are now set as a yearly pro user with unlimited access to all features.";
                echo "</div>";
                break;
        }
        
        // Refresh page to show updated data
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🧪 Test Links</h2>";
echo "<p>";
echo "<a href='dashboard.php' style='color: #007bff; margin-right: 15px;'>Dashboard</a>";
echo "<a href='bank/scan.php' style='color: #007bff; margin-right: 15px;'>Bank Scan</a>";
echo "<a href='debug-bank-scan.php' style='color: #007bff; margin-right: 15px;'>Bank Scan Debug</a>";
echo "<a href='fix-bank-scan-access.php' style='color: #007bff; margin-right: 15px;'>Bank Scan Fix</a>";
echo "</p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
