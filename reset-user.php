<?php
/**
 * Reset User - Clean slate for testing
 * Removes user record and clears session
 */

session_start();

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "=== User Reset Script ===\n\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    $userId = 5; // User ID from debug info
    
    echo "1. 🗑️  Removing User Data:\n";
    
    // Delete from payment_history first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM payment_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    $deletedPayments = $stmt->rowCount();
    echo "   Deleted $deletedPayments payment records\n";
    
    // Delete from checkout_sessions
    $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $deletedSessions = $stmt->rowCount();
    echo "   Deleted $deletedSessions checkout sessions\n";
    
    // Delete user record
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $deletedUsers = $stmt->rowCount();
    echo "   Deleted $deletedUsers user record\n";
    
    echo "\n2. 🧹 Clearing Session:\n";
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    echo "   ✅ Session cleared\n";
    
    echo "\n3. ✅ Reset Complete:\n";
    echo "   🎯 User data completely removed\n";
    echo "   🎯 Session cleared\n";
    echo "   🎯 Ready for fresh start\n";
    
    echo "\n🚀 Next Steps:\n";
    echo "   1. Visit auth/signin.php to sign in again\n";
    echo "   2. Complete Google OAuth authentication\n";
    echo "   3. Test upgrade flow with clean user state\n";
    echo "   4. Verify payment processing works correctly\n";
    
} catch (Exception $e) {
    echo "❌ Reset Error: " . $e->getMessage() . "\n";
}

echo "\n=== Reset Complete ===\n";
?>
