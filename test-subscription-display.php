<?php
require_once 'config/db_config.php';

echo "=== TESTING SUBSCRIPTION DISPLAY FIELDS ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Get subscriptions for support@origens.nl (User ID: 2)
    $userId = 2;
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    echo "Testing display fields for " . count($subscriptions) . " subscriptions:\n\n";
    
    foreach ($subscriptions as $i => $subscription) {
        echo "--- Subscription #" . ($i+1) . " ---\n";
        
        // Test display name logic
        $displayName = $subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown';
        echo "Display Name: '$displayName'\n";
        
        // Test amount logic (what the dashboard now uses)
        $displayAmount = $subscription['cost'] ?? $subscription['amount'] ?? 0;
        echo "Display Amount: €" . number_format($displayAmount, 2) . "\n";
        
        // Test active status logic
        $isActive = ($subscription['is_active'] ?? ($subscription['status'] === 'active'));
        echo "Is Active: " . ($isActive ? 'YES' : 'NO') . "\n";
        
        // Test provider info
        $provider = $subscription['provider'] ?? 'none';
        echo "Provider: $provider\n";
        
        // Test billing cycle
        $billingCycle = $subscription['billing_cycle'] ?? 'monthly';
        echo "Billing Cycle: $billingCycle\n";
        
        echo "\n";
        
        if ($i >= 2) break; // Test first 3 only
    }
    
    echo "=== FIELD VERIFICATION ===\n";
    if (count($subscriptions) > 0) {
        $first = $subscriptions[0];
        echo "Available fields in subscription record:\n";
        foreach ($first as $key => $value) {
            if (!is_numeric($key)) {
                echo "  $key: " . ($value ?? 'NULL') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
