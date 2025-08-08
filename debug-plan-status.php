<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$userId = $_SESSION['user_id'];

echo "<h2>DEBUG: Plan Status Analysis</h2>";

echo "<h3>1. Session Data:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "user_email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "is_paid: " . (($_SESSION['is_paid'] ?? false) ? 'true' : 'false') . "\n";
echo "subscription_type: " . ($_SESSION['subscription_type'] ?? 'NOT SET') . "\n";
echo "subscription_status: " . ($_SESSION['subscription_status'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>2. Database Data (via PlanManager):</h3>";
try {
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    echo "<pre>";
    if ($userPlan) {
        print_r($userPlan);
    } else {
        echo "NULL - No plan found in database\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>ERROR: " . $e->getMessage() . "</pre>";
}

echo "<h3>3. Direct Database Query:</h3>";
try {
    $pdo = new PDO("mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4", 
                   "123cashcontrol", 
                   "Welkom123!", 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    if ($user) {
        print_r($user);
    } else {
        echo "User not found in database\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre>DATABASE ERROR: " . $e->getMessage() . "</pre>";
}

echo "<h3>4. Upgrade Page Logic Test:</h3>";
echo "<pre>";
if ($userPlan && $userPlan['is_active']) {
    echo "Current plan detected: " . $userPlan['plan_type'] . "\n";
    echo "Plan is active: YES\n";
    echo "This is why buttons show 'Current Plan'\n";
} else {
    echo "No active plan detected\n";
    echo "Buttons should show 'Choose [Plan]'\n";
}
echo "</pre>";

echo "<h3>5. Header Logic Test:</h3>";
echo "<pre>";
if (!($_SESSION['is_paid'] ?? false)) {
    echo "Header shows: 'No Plan' (because \$_SESSION['is_paid'] is false/unset)\n";
} else {
    echo "Header shows plan badge\n";
}
echo "</pre>";

echo "<h3>DIAGNOSIS:</h3>";
echo "<pre>";
echo "BEFORE FIX: PlanManager returned NULL for free users\n";
echo "AFTER FIX: PlanManager should now return proper 'none' plan object\n";
echo "\n";
echo "Expected behavior now:\n";
echo "- PlanManager returns plan object with plan_type='none', is_active=false\n";
echo "- Upgrade buttons should show 'Choose [Plan]' instead of 'Current Plan'\n";
echo "- Header should still show 'No Plan' (which is correct)\n";
echo "</pre>";

echo "<h3>6. TESTING THE FIX:</h3>";
echo "<pre>";
if ($userPlan) {
    echo "✅ PlanManager now returns a plan object (not NULL)\n";
    echo "Plan type: " . ($userPlan['plan_type'] ?? 'UNDEFINED') . "\n";
    echo "Is active: " . (($userPlan['is_active'] ?? false) ? 'true' : 'false') . "\n";
    
    if ($userPlan['plan_type'] === 'none' && !$userPlan['is_active']) {
        echo "✅ PERFECT: Free user properly detected as 'none' plan with is_active=false\n";
        echo "✅ Upgrade buttons should now work correctly\n";
    } else {
        echo "❌ Issue: Plan type or active status unexpected\n";
    }
} else {
    echo "❌ PlanManager still returns NULL - fix didn't work\n";
}
echo "</pre>";
?>
