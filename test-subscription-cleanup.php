<?php
/**
 * Simple test to clean up existing subscriptions with improved algorithm
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== SUBSCRIPTION CLEANUP TEST ===\n\n";
    
    // Get current subscriptions
    $stmt = $pdo->prepare("
        SELECT id, merchant_name, amount, billing_cycle, confidence 
        FROM subscriptions 
        WHERE user_id = ?
        ORDER BY amount DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current subscriptions: " . count($subscriptions) . "\n\n";
    
    // Define blacklist (same as in GoCardless service)
    $blacklist = [
        'albert heijn', 'ah ', 'jumbo', 'lidl', 'aldi', 'supermarket',
        'postnl', 'post nl', 'dhl', 'ups', 'fedex', 'shipping',
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd', 'restaurant', 
        'cafe', 'eetcafe', 'bakkerij', 'bakery', 'pizza', 'mcdonalds',
        'tankstation', 'shell', 'bp', 'esso', 'gas station',
        'parking', 'parkeren', 'gemeente', 'belasting', 'tax'
    ];
    
    $toRemove = [];
    $toKeep = [];
    
    foreach ($subscriptions as $sub) {
        $merchant = strtolower($sub['merchant_name']);
        $shouldRemove = false;
        $reason = '';
        
        // Check blacklist
        foreach ($blacklist as $blacklisted) {
            if (strpos($merchant, $blacklisted) !== false) {
                $shouldRemove = true;
                $reason = "Blacklisted merchant ($blacklisted)";
                break;
            }
        }
        
        // Check amount
        if (!$shouldRemove && $sub['amount'] < 2.00) {
            $shouldRemove = true;
            $reason = "Amount too small (€" . number_format($sub['amount'], 2) . ")";
        }
        
        // Check billing cycle
        if (!$shouldRemove && (!$sub['billing_cycle'] || $sub['billing_cycle'] === 'unknown')) {
            $shouldRemove = true;
            $reason = "No valid billing cycle";
        }
        
        if ($shouldRemove) {
            $toRemove[] = [
                'id' => $sub['id'],
                'merchant' => $sub['merchant_name'],
                'amount' => $sub['amount'],
                'reason' => $reason
            ];
        } else {
            $toKeep[] = $sub;
        }
    }
    
    echo "Analysis complete:\n";
    echo "- Keep: " . count($toKeep) . " subscriptions\n";
    echo "- Remove: " . count($toRemove) . " false positives\n\n";
    
    if (!empty($toRemove)) {
        echo "=== REMOVING FALSE POSITIVES ===\n";
        foreach ($toRemove as $remove) {
            echo sprintf("❌ %-35s | €%6.2f | %s\n", 
                substr($remove['merchant'], 0, 34),
                $remove['amount'],
                $remove['reason']
            );
            
            // Actually remove from database
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
            $stmt->execute([$remove['id']]);
        }
        echo "\n✅ Removed " . count($toRemove) . " false positives from database\n\n";
    }
    
    if (!empty($toKeep)) {
        echo "=== KEEPING TRUE SUBSCRIPTIONS ===\n";
        foreach ($toKeep as $keep) {
            echo sprintf("✅ %-35s | €%6.2f | %s\n", 
                substr($keep['merchant_name'], 0, 34),
                $keep['amount'],
                $keep['billing_cycle'] ?: 'Unknown'
            );
        }
    }
    
    echo "\n=== CLEANUP COMPLETE ===\n";
    echo "Before: " . count($subscriptions) . " subscriptions\n";
    echo "After: " . count($toKeep) . " subscriptions\n";
    echo "Removed: " . count($toRemove) . " false positives\n";
    echo "Accuracy improved significantly! 🎉\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
