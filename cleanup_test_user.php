<?php
require_once 'config/db_config.php';

$testEmail = 'tom@degruijterweb.nl';

try {
    $pdo = getDBConnection();
    
    echo "<h3>Cleaning up test user: $testEmail</h3>";
    
    // First, check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email, is_pro FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ Found existing user:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
        echo "<li><strong>Name:</strong> " . ($user['name'] ?: 'Not set') . "</li>";
        echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
        echo "<li><strong>Pro Status:</strong> " . ($user['is_pro'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        
        $userId = $user['id'];
        
        // Delete related records first (foreign key constraints)
        echo "<p>🧹 Cleaning up related records...</p>";
        
        // Delete user sessions
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $sessionsDeleted = $stmt->rowCount();
        echo "<p>- Deleted $sessionsDeleted user sessions</p>";
        
        // Delete subscriptions
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subscriptionsDeleted = $stmt->rowCount();
        echo "<p>- Deleted $subscriptionsDeleted subscriptions</p>";
        
        // Delete user preferences (if table exists)
        try {
            $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $preferencesDeleted = $stmt->rowCount();
            echo "<p>- Deleted $preferencesDeleted user preferences</p>";
        } catch (Exception $e) {
            echo "<p>- User preferences table not found or empty</p>";
        }
        
        // Delete payment history (if table exists)
        try {
            $stmt = $pdo->prepare("DELETE FROM payment_history WHERE user_id = ?");
            $stmt->execute([$userId]);
            $paymentsDeleted = $stmt->rowCount();
            echo "<p>- Deleted $paymentsDeleted payment records</p>";
        } catch (Exception $e) {
            echo "<p>- Payment history table not found or empty</p>";
        }
        
        // Delete checkout sessions (if table exists)
        try {
            $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $checkoutDeleted = $stmt->rowCount();
            echo "<p>- Deleted $checkoutDeleted checkout sessions</p>";
        } catch (Exception $e) {
            echo "<p>- Checkout sessions table not found or empty</p>";
        }
        
        // Finally, delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo "<p>✅ <strong>User $testEmail has been completely removed from the database</strong></p>";
        echo "<p>🎯 You can now sign up with this email to test the email flow!</p>";
        
    } else {
        echo "<p>✅ No existing user found with email: $testEmail</p>";
        echo "<p>🎯 You can proceed to sign up with this email to test the email flow!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h3 { color: #059669; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
</style>
