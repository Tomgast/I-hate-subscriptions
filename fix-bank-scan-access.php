<?php
/**
 * BANK SCAN ACCESS FIX
 * Quick fix for bank scan redirection issue for subscription users
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

echo "<h1>Bank Scan Access Fix for User ID: {$userId}</h1>";
echo "<p>Email: {$userEmail}</p>";

// Get current database state
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            id, email, name, 
            plan_type, plan_expires_at, plan_purchased_at,
            scan_count, max_scans,
            subscription_type, subscription_status, subscription_expires_at,
            stripe_customer_id, stripe_subscription_id
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    echo "<h2>Current Database State:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Analyze the issue
    $issues = [];
    $fixes = [];
    
    // Check if user has subscription type but no plan_type
    if (in_array($user['subscription_type'], ['monthly', 'yearly']) && empty($user['plan_type'])) {
        $issues[] = "subscription_type is set but plan_type is empty";
        $fixes[] = "Set plan_type = subscription_type";
    }
    
    // Check if subscription is active but no expiration date
    if (in_array($user['subscription_type'], ['monthly', 'yearly']) && $user['subscription_status'] === 'active' && !$user['subscription_expires_at']) {
        $issues[] = "Active subscription but no expiration date";
        $fixes[] = "Set subscription_expires_at to 1 year from now";
    }
    
    // Check if plan_expires_at is missing for subscription users
    if (in_array($user['subscription_type'], ['monthly', 'yearly']) && !$user['plan_expires_at']) {
        $issues[] = "Subscription user but no plan_expires_at";
        $fixes[] = "Set plan_expires_at to 1 year from now";
    }
    
    echo "<h2>Issues Found:</h2>";
    if (empty($issues)) {
        echo "<p style='color: green;'>✅ No obvious issues found</p>";
    } else {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li style='color: red;'>❌ {$issue}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>Suggested Fixes:</h2>";
    if (empty($fixes)) {
        echo "<p>No automatic fixes needed</p>";
    } else {
        echo "<ul>";
        foreach ($fixes as $fix) {
            echo "<li style='color: blue;'>🔧 {$fix}</li>";
        }
        echo "</ul>";
        
        echo "<form method='POST' style='background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>Apply All Fixes:</h3>";
        echo "<input type='hidden' name='action' value='apply_fixes'>";
        echo "<button type='submit' style='background: #10b981; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>Apply Fixes Now</button>";
        echo "</form>";
    }
    
    // Manual override option
    echo "<h2>Manual Override (Force Pro Access):</h2>";
    echo "<form method='POST' style='background: #fef3c7; padding: 20px; margin: 20px 0; border-radius: 5px; border: 2px solid #f59e0b;'>";
    echo "<p><strong>Force set as Pro user:</strong></p>";
    echo "<p>This will set you as a yearly subscription user with full access.</p>";
    echo "<input type='hidden' name='action' value='force_pro'>";
    echo "<button type='submit' style='background: #f59e0b; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>Force Pro Access</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Handle fix actions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        if ($_POST['action'] === 'apply_fixes') {
            // Apply the identified fixes
            $updateFields = [];
            $params = [];
            
            // Fix plan_type
            if (in_array($user['subscription_type'], ['monthly', 'yearly']) && empty($user['plan_type'])) {
                $updateFields[] = "plan_type = ?";
                $params[] = $user['subscription_type'];
            }
            
            // Fix expiration dates
            if (in_array($user['subscription_type'], ['monthly', 'yearly'])) {
                if (!$user['subscription_expires_at']) {
                    $updateFields[] = "subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)";
                }
                if (!$user['plan_expires_at']) {
                    $updateFields[] = "plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)";
                }
                $updateFields[] = "subscription_status = 'active'";
            }
            
            if (!empty($updateFields)) {
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $params[] = $userId;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
                echo "<h3 style='color: #065f46;'>✅ Fixes Applied Successfully!</h3>";
                echo "<p>Your account has been updated. <a href='bank/scan.php' style='color: #10b981; font-weight: bold;'>Try bank scan now</a></p>";
                echo "</div>";
            }
            
        } elseif ($_POST['action'] === 'force_pro') {
            // Force set as pro user
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    subscription_type = 'yearly',
                    subscription_status = 'active',
                    subscription_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                    plan_type = 'yearly',
                    plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
                    max_scans = 999999
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            echo "<div style='background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3 style='color: #92400e;'>🚀 Pro Access Forced!</h3>";
            echo "<p>You now have yearly pro access. <a href='bank/scan.php' style='color: #f59e0b; font-weight: bold;'>Try bank scan now</a></p>";
            echo "</div>";
        }
        
        // Refresh page to show updated data
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #fef2f2; border: 2px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3 style='color: #991b1b;'>❌ Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a> | <a href='bank/scan.php'>Test Bank Scan</a></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
button {
    cursor: pointer;
}
button:hover {
    opacity: 0.9;
}
</style>
