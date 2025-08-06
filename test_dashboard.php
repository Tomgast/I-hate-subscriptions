<?php
// Simple test to debug dashboard issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Dashboard Debug Test</h2>";

// Test 1: Check session
echo "<h3>1. Session Check:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
    echo "✅ User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
    echo "✅ User Email: " . ($_SESSION['user_email'] ?? 'Not set') . "<br>";
    echo "✅ Is Paid: " . ($_SESSION['is_paid'] ? 'Yes' : 'No') . "<br>";
} else {
    echo "❌ No user session found<br>";
}

// Test 2: Check database connection
echo "<h3>2. Database Connection:</h3>";
try {
    require_once 'config/db_config.php';
    $pdo = getDBConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check subscription manager
echo "<h3>3. Subscription Manager:</h3>";
try {
    require_once 'includes/subscription_manager.php';
    $subscriptionManager = new SubscriptionManager();
    echo "✅ SubscriptionManager loaded successfully<br>";
    
    if (isset($_SESSION['user_id'])) {
        $subscriptions = $subscriptionManager->getUserSubscriptions($_SESSION['user_id']);
        echo "✅ Got " . count($subscriptions) . " subscriptions<br>";
    }
} catch (Exception $e) {
    echo "❌ SubscriptionManager error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<br><a href='dashboard.php'>Try Dashboard Again</a>";
?>
