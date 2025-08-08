<?php
session_start();
require_once 'includes/stripe_service.php';
require_once 'includes/database_helper.php';

// Test payment success flow without actual Stripe
echo "<h1>Payment Success Flow Test</h1>";

// Check if StripeService methods exist
$stripeService = new StripeService();
$reflection = new ReflectionClass($stripeService);

echo "<h2>Method Availability Check:</h2>";
$requiredMethods = ['handleSuccessfulPayment', 'upgradeUserToOneTimeScan', 'upgradeUserToSubscription'];

foreach ($requiredMethods as $method) {
    if ($reflection->hasMethod($method)) {
        echo "<div style='color: green;'>✅ Method '$method' exists</div>";
    } else {
        echo "<div style='color: red;'>❌ Method '$method' is missing</div>";
    }
}

// Test database connection
echo "<h2>Database Connection Test:</h2>";
try {
    $pdo = DatabaseHelper::getConnection();
    echo "<div style='color: green;'>✅ Database connection successful</div>";
    
    // Check if required tables exist
    $requiredTables = ['users', 'payment_history', 'checkout_sessions'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green;'>✅ Table '$table' exists</div>";
        } else {
            echo "<div style='color: red;'>❌ Table '$table' is missing</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Test user session simulation
echo "<h2>User Session Test:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div style='color: green;'>✅ User session active (ID: " . $_SESSION['user_id'] . ")</div>";
} else {
    echo "<div style='color: orange;'>⚠️ No user session - this would redirect to signin</div>";
}

echo "<h2>Next Steps:</h2>";
echo "<p>1. The missing methods have been added to StripeService</p>";
echo "<p>2. Test the actual payment flow by:</p>";
echo "<ul>";
echo "<li>Login with your account (tom@degruijterweb.nl)</li>";
echo "<li>Go to upgrade.php and start a new payment</li>";
echo "<li>Complete the Stripe checkout</li>";
echo "<li>Verify the success page loads without 500 error</li>";
echo "</ul>";

echo "<p><a href='auth/signin.php'>Go to Sign In</a> | <a href='upgrade.php'>Go to Upgrade</a></p>";
?>
