<?php
require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "=== CLEANUP BROKEN SUBSCRIPTIONS ===\n";
    
    // Delete the broken subscription(s)
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = 2 AND (name = '' OR name IS NULL OR amount < 0)");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo "Deleted $deletedCount broken subscription(s)\n";
    
    // Check remaining subscriptions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = 2");
    $stmt->execute();
    $remaining = $stmt->fetch()['count'];
    
    echo "Remaining subscriptions: $remaining\n\n";
    
    echo "=== READY FOR FRESH SCAN ===\n";
    echo "The broken subscriptions have been cleaned up.\n";
    echo "The column mapping has been fixed in the code.\n";
    echo "You can now run a fresh bank scan and it should work correctly.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
