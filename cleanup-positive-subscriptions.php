<?php
/**
 * Clean up any subscriptions with positive amounts (income)
 * These should never be considered subscriptions
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== CLEANING UP POSITIVE AMOUNT SUBSCRIPTIONS ===\n\n";
    
    // Check for any subscriptions with positive amounts
    $stmt = $pdo->prepare("
        SELECT id, merchant_name, amount, billing_cycle, confidence
        FROM subscriptions 
        WHERE user_id = ? AND amount > 0
    ");
    $stmt->execute([$userId]);
    $positiveSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($positiveSubscriptions)) {
        echo "Found " . count($positiveSubscriptions) . " subscriptions with positive amounts (income):\n\n";
        
        foreach ($positiveSubscriptions as $sub) {
            echo sprintf("❌ REMOVING: %-35s | +€%6.2f | %s\n", 
                $sub['merchant_name'],
                $sub['amount'],
                $sub['billing_cycle']
            );
            
            // Remove from database
            $deleteStmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
            $deleteStmt->execute([$sub['id']]);
        }
        
        echo "\n✅ Removed " . count($positiveSubscriptions) . " positive amount subscriptions\n";
        echo "These were income transactions, not subscription expenses.\n\n";
    } else {
        echo "✅ No positive amount subscriptions found - filter is working correctly!\n\n";
    }
    
    // Show current subscriptions (should all be negative amounts = expenses)
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, billing_cycle, confidence
        FROM subscriptions 
        WHERE user_id = ?
        ORDER BY amount DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CURRENT SUBSCRIPTIONS (All should be expenses) ===\n";
    if (!empty($subscriptions)) {
        foreach ($subscriptions as $sub) {
            $amountDisplay = $sub['amount'] > 0 ? "+€" . number_format($sub['amount'], 2) : "€" . number_format(abs($sub['amount']), 2);
            echo sprintf("✅ %-35s | %8s | %s\n", 
                substr($sub['merchant_name'], 0, 34),
                $amountDisplay,
                $sub['billing_cycle']
            );
        }
        echo "\nTotal subscriptions: " . count($subscriptions) . "\n";
    } else {
        echo "No subscriptions found.\n";
    }
    
    echo "\n🎉 POSITIVE AMOUNT CLEANUP COMPLETE!\n";
    echo "Only expense subscriptions (negative amounts) are now in the system.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
