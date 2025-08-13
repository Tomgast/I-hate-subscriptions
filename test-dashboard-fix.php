<?php
require_once 'config/db_config.php';

echo "=== TESTING DASHBOARD FIX ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Simulate the exact same logic as dashboard.php
    $userId = 2; // support@origens.nl user ID
    
    echo "1. TESTING SUBSCRIPTION QUERY:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    echo "Found " . count($subscriptions) . " subscriptions\n\n";
    
    echo "2. TESTING STATS CALCULATION:\n";
    $stats = [
        'total_active' => 0,
        'monthly_total' => 0,
        'yearly_total' => 0,
        'next_payment' => null
    ];
    
    foreach ($subscriptions as $subscription) {
        // Check if subscription is active (using actual database fields)
        $isActive = (bool)($subscription['is_active'] ?? ($subscription['status'] === 'active'));
        
        echo "Subscription: " . ($subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown') . "\n";
        echo "  - Active: " . ($isActive ? 'YES' : 'NO') . "\n";
        echo "  - Cost: €" . ($subscription['cost'] ?? $subscription['amount'] ?? 0) . "\n";
        echo "  - Cycle: " . ($subscription['billing_cycle'] ?? 'monthly') . "\n";
        
        if ($isActive) {
            $stats['total_active']++;
            
            // Get amount (using actual database fields)
            $amount = (float)($subscription['cost'] ?? $subscription['amount'] ?? 0);
            
            // Calculate monthly cost
            $monthlyCost = 0;
            $billingCycle = $subscription['billing_cycle'] ?? 'monthly';
            
            switch ($billingCycle) {
                case 'monthly':
                    $monthlyCost = $amount;
                    break;
                case 'yearly':
                    $monthlyCost = $amount / 12;
                    break;
                case 'weekly':
                    $monthlyCost = $amount * 4.33;
                    break;
                case 'daily':
                    $monthlyCost = $amount * 30;
                    break;
                default:
                    $monthlyCost = $amount;
                    break;
            }
            
            $stats['monthly_total'] += $monthlyCost;
            $stats['yearly_total'] += $monthlyCost * 12;
            
            echo "  - Monthly cost: €" . number_format($monthlyCost, 2) . "\n";
        }
        echo "\n";
    }
    
    echo "3. FINAL STATS:\n";
    echo "Total Active: " . $stats['total_active'] . "\n";
    echo "Monthly Total: €" . number_format($stats['monthly_total'], 2) . "\n";
    echo "Yearly Total: €" . number_format($stats['yearly_total'], 2) . "\n";
    
    echo "\n4. TESTING DISPLAY FIELDS:\n";
    foreach ($subscriptions as $i => $subscription) {
        if ($i >= 3) break; // Just test first 3
        
        $displayName = $subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown';
        $displayAmount = $subscription['cost'] ?? $subscription['amount'] ?? 0;
        $isActive = $subscription['is_active'] ? 1 : 0;
        
        echo "Subscription #" . ($i+1) . ":\n";
        echo "  - Display Name: " . $displayName . "\n";
        echo "  - Display Amount: €" . $displayAmount . "\n";
        echo "  - Is Active: " . $isActive . "\n";
        echo "  - Provider: " . ($subscription['provider'] ?? 'none') . "\n";
        echo "  - Confidence: " . ($subscription['confidence'] ?? 'none') . "%\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
