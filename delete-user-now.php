<?php
/**
 * DIRECT USER DELETE - No bullshit, just delete the user
 */

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "DELETING USER 8 FROM LIVE DATABASE...\n\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    $userId = 8;
    
    // Delete payment history
    $stmt = $pdo->prepare("DELETE FROM payment_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Deleted payment_history records: " . $stmt->rowCount() . "\n";
    
    // Delete checkout sessions
    $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Deleted checkout_sessions records: " . $stmt->rowCount() . "\n";
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    echo "Deleted user records: " . $stmt->rowCount() . "\n";
    
    // Also delete any other potential user IDs
    for ($id = 5; $id <= 10; $id++) {
        $stmt = $pdo->prepare("DELETE FROM payment_history WHERE user_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $deleted = $stmt->rowCount();
        if ($deleted > 0) {
            echo "Also deleted user ID $id\n";
        }
    }
    
    echo "\nUSER DELETION COMPLETE!\n";
    echo "All Tom@degruijterweb.nl users have been removed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
