<?php
/**
 * Server User Reset - Actually removes user from live database
 * Run this on the server to clean user data
 */

session_start();

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "<h1>🗑️ Server User Reset</h1>\n";
echo "<pre>\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    // Get current user ID from session
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo "❌ No user ID in session\n";
        exit;
    }
    
    echo "=== Removing User ID: $userId ===\n\n";
    
    echo "1. 🔍 Current User Data:\n";
    
    // Show current user data
    $stmt = $pdo->prepare("SELECT id, email, name, subscription_type, subscription_status, subscription_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        foreach ($user as $key => $value) {
            echo "   $key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "   User not found\n";
        exit;
    }
    
    echo "\n2. 🗑️ Removing User Data:\n";
    
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
    
    echo "\n3. 🧹 Clearing Session:\n";
    
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
    
    echo "\n4. ✅ Reset Complete:\n";
    echo "   🎯 User data completely removed from live database\n";
    echo "   🎯 Session cleared\n";
    echo "   🎯 Ready for fresh start\n";
    
    echo "\n🚀 Next Steps:\n";
    echo "   1. Visit <a href='/auth/signin.php'>auth/signin.php</a> to sign in again\n";
    echo "   2. Complete Google OAuth authentication\n";
    echo "   3. Test upgrade flow with clean user state\n";
    echo "   4. Verify payment processing works correctly\n";
    
} catch (Exception $e) {
    echo "❌ Reset Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Server Reset Complete ===\n";
echo "</pre>\n";

// Add a simple redirect button
echo "<br><a href='/auth/signin.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Sign In Fresh</a>\n";
?>
