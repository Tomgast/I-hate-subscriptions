<?php
/**
 * Delete Test User Script
 * Removes tom@degruijterweb.nl and all associated data for fresh testing
 */

// Suppress HTTP_HOST warnings in CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
}

require_once 'config/db_config.php';

echo "=== Delete Test User Script ===\n\n";

$testEmail = 'tom@degruijterweb.nl';

try {
    $pdo = getDBConnection();
    
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    // Find the user
    $stmt = $pdo->prepare("SELECT id, email, name, subscription_type FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✅ User '$testEmail' not found in database - already clean for testing.\n";
        $pdo->rollback();
        exit(0);
    }
    
    $userId = $user['id'];
    echo "Found user: {$user['name']} ({$user['email']}) - ID: $userId\n";
    echo "Current subscription: {$user['subscription_type']}\n\n";
    
    // Delete associated data in correct order (foreign key constraints)
    
    // 1. Delete reminder logs
    $stmt = $pdo->prepare("DELETE FROM reminder_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $reminderCount = $stmt->rowCount();
    echo "Deleted $reminderCount reminder log entries\n";
    
    // 2. Delete user preferences
    $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $prefCount = $stmt->rowCount();
    echo "Deleted $prefCount user preference entries\n";
    
    // 3. Delete subscriptions
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subCount = $stmt->rowCount();
    echo "Deleted $subCount subscription entries\n";
    
    // 4. Delete bank scans
    $stmt = $pdo->prepare("DELETE FROM bank_scans WHERE user_id = ?");
    $stmt->execute([$userId]);
    $scanCount = $stmt->rowCount();
    echo "Deleted $scanCount bank scan entries\n";
    
    // 5. Delete bank connections
    $stmt = $pdo->prepare("DELETE FROM bank_connections WHERE user_id = ?");
    $stmt->execute([$userId]);
    $bankCount = $stmt->rowCount();
    echo "Deleted $bankCount bank connection entries\n";
    
    // 6. Delete checkout sessions
    $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $checkoutCount = $stmt->rowCount();
    echo "Deleted $checkoutCount checkout session entries\n";
    
    // 7. Finally delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userDeleted = $stmt->rowCount();
    
    if ($userDeleted === 1) {
        echo "Deleted user account\n";
    } else {
        throw new Exception("Failed to delete user account");
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n✅ SUCCESS: User '$testEmail' and all associated data deleted successfully!\n";
    echo "\nThe system is now clean for fresh testing with this email address.\n";
    echo "\nYou can now:\n";
    echo "1. Go to https://123cashcontrol.com\n";
    echo "2. Sign up with tom@degruijterweb.nl\n";
    echo "3. Test the complete user journey from scratch\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTransaction rolled back - no data was deleted.\n";
    exit(1);
}

echo "\n=== End of Script ===\n";
?>
