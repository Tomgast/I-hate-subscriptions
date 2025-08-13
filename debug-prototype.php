<?php
/**
 * Debug script to test prototype dependencies
 */

session_start();
echo "<h2>Dashboard Prototype Debug</h2>";

// Check session
echo "<h3>1. Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user_id'] . " (" . ($_SESSION['user_name'] ?? 'No name') . ")<br>";
} else {
    echo "❌ User not logged in<br>";
}

// Check file includes
echo "<h3>2. File Dependencies</h3>";

$files = [
    'config/db_config.php',
    'includes/subscription_manager.php', 
    'includes/multi_bank_service.php',
    'includes/plan_manager.php',
    'includes/bank_provider_router.php',
    'improved-subscription-detection.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ Found: $file<br>";
        try {
            require_once $file;
            echo "✅ Loaded: $file<br>";
        } catch (Exception $e) {
            echo "❌ Error loading $file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Missing: $file<br>";
    }
}

// Check database connection
echo "<h3>3. Database Connection</h3>";
try {
    $pdo = getDBConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Check classes
echo "<h3>4. Class Availability</h3>";
$classes = [
    'SubscriptionManager',
    'MultiBankService', 
    'BankProviderRouter',
    'ImprovedSubscriptionDetector'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✅ Class exists: $class<br>";
        try {
            if ($class === 'SubscriptionManager') {
                $obj = new SubscriptionManager();
            } elseif ($class === 'MultiBankService') {
                $obj = new MultiBankService();
            } elseif ($class === 'BankProviderRouter') {
                $obj = new BankProviderRouter($pdo);
            } else {
                echo "✅ Static class: $class<br>";
                continue;
            }
            echo "✅ Can instantiate: $class<br>";
        } catch (Exception $e) {
            echo "❌ Error instantiating $class: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Class missing: $class<br>";
    }
}

// Check if user has valid plan
if (isset($_SESSION['user_id'])) {
    echo "<h3>5. User Plan Check</h3>";
    try {
        $planManager = getPlanManager();
        $userPlan = $planManager->getUserPlan($_SESSION['user_id']);
        echo "✅ Plan manager works<br>";
        if ($userPlan) {
            echo "✅ User has plan: " . print_r($userPlan, true) . "<br>";
        } else {
            echo "❌ No user plan found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Plan manager error: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>6. PHP Error Log</h3>";
echo "Check your server error logs for detailed PHP errors.<br>";
echo "Common locations:<br>";
echo "- /var/log/apache2/error.log<br>";
echo "- /var/log/nginx/error.log<br>";
echo "- cPanel Error Logs<br>";
?>
