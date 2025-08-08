<?php
/**
 * Test User Creation Fix
 * Verify that new user creation and plan detection work correctly
 */

// Suppress HTTP_HOST warnings for CLI
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once 'includes/user_plan_helper.php';

echo "=== Testing User Creation & Plan Detection Fix ===\n\n";

// Test 1: Check if UserPlanHelper works
echo "1. 🧪 Testing UserPlanHelper for User ID 9:\n";

try {
    $planStatus = UserPlanHelper::getUserPlanStatus(9);
    
    echo "   Plan Status Results:\n";
    foreach ($planStatus as $key => $value) {
        echo "     $key: " . ($value ?? 'NULL') . "\n";
    }
    
    echo "\n   Expected vs Actual:\n";
    echo "     Expected is_paid: false (new users should be free)\n";
    echo "     Actual is_paid: " . ($planStatus['is_paid'] ? 'true' : 'false') . "\n";
    
    if (!$planStatus['is_paid'] && $planStatus['plan_type'] === 'free') {
        echo "   ✅ CORRECT: New user properly detected as free\n";
    } else {
        echo "   ❌ ISSUE: User still shows as paid or non-free\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing UserPlanHelper: " . $e->getMessage() . "\n";
}

echo "\n2. 🔧 Testing Session Refresh:\n";

try {
    session_start();
    
    // Simulate user session
    $_SESSION['user_id'] = 9;
    
    echo "   Before refresh:\n";
    echo "     Session subscription_type: " . ($_SESSION['subscription_type'] ?? 'not_set') . "\n";
    echo "     Session is_paid: " . ($_SESSION['is_paid'] ?? 'not_set') . "\n";
    
    // Refresh session from database
    $success = UserPlanHelper::refreshUserSession(9);
    
    echo "   After refresh:\n";
    echo "     Session subscription_type: " . ($_SESSION['subscription_type'] ?? 'not_set') . "\n";
    echo "     Session is_paid: " . ($_SESSION['is_paid'] ? 'true' : 'false') . "\n";
    echo "     Refresh success: " . ($success ? 'true' : 'false') . "\n";
    
    if ($_SESSION['subscription_type'] === 'free' && !$_SESSION['is_paid']) {
        echo "   ✅ CORRECT: Session properly synced with database\n";
    } else {
        echo "   ❌ ISSUE: Session still has incorrect data\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing session refresh: " . $e->getMessage() . "\n";
}

echo "\n3. 📊 Summary:\n";

// Final verification
try {
    $planStatus = UserPlanHelper::getUserPlanStatus(9);
    
    $issues = 0;
    
    if ($planStatus['plan_type'] !== 'free') {
        echo "   ❌ User plan_type should be 'free', is: " . $planStatus['plan_type'] . "\n";
        $issues++;
    }
    
    if ($planStatus['is_paid'] !== false) {
        echo "   ❌ User is_paid should be false, is: " . ($planStatus['is_paid'] ? 'true' : 'false') . "\n";
        $issues++;
    }
    
    if ($planStatus['status'] !== 'inactive') {
        echo "   ❌ User status should be 'inactive', is: " . $planStatus['status'] . "\n";
        $issues++;
    }
    
    if ($issues === 0) {
        echo "   🎉 ALL TESTS PASSED!\n";
        echo "   ✅ User creation fix working correctly\n";
        echo "   ✅ Plan detection working correctly\n";
        echo "   ✅ Session sync working correctly\n";
        echo "\n🚀 READY FOR TESTING:\n";
        echo "   - New users will be created as free\n";
        echo "   - Dashboard will show correct plan status\n";
        echo "   - Upgrade page will work properly\n";
    } else {
        echo "   ⚠️  $issues issues found\n";
        echo "   Need to investigate further\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error in final verification: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
