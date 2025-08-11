<?php
require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "=== DATABASE CHECK ===\n";
    
    // Check subscriptions for user ID 2
    $stmt = $pdo->prepare("SELECT id, name, amount, status, billing_cycle FROM subscriptions WHERE user_id = 2");
    $stmt->execute();
    $subs = $stmt->fetchAll();
    
    echo "Subscriptions in database: " . count($subs) . "\n\n";
    
    if (count($subs) > 0) {
        foreach($subs as $sub) {
            echo "ID: " . $sub['id'] . "\n";
            echo "Name: " . $sub['name'] . "\n";
            echo "Amount: " . $sub['amount'] . "\n";
            echo "Status: " . $sub['status'] . "\n";
            echo "Billing Cycle: " . $sub['billing_cycle'] . "\n";
            echo "---\n";
        }
        
        // Calculate totals
        $activeCount = 0;
        $monthlyTotal = 0;
        
        foreach($subs as $sub) {
            if ($sub['status'] == 'active') {
                $activeCount++;
                $amount = floatval($sub['amount']);
                
                if ($sub['billing_cycle'] == 'monthly') {
                    $monthlyTotal += $amount;
                } elseif ($sub['billing_cycle'] == 'yearly') {
                    $monthlyTotal += ($amount / 12);
                }
            }
        }
        
        echo "TOTALS:\n";
        echo "Active subscriptions: " . $activeCount . "\n";
        echo "Monthly total: €" . number_format($monthlyTotal, 2) . "\n";
        echo "Yearly total: €" . number_format($monthlyTotal * 12, 2) . "\n";
        
    } else {
        echo "No subscriptions found in database!\n";
        
        // Check if there are any bank scan results
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_scan_results WHERE user_id = 2");
        $stmt->execute();
        $scanCount = $stmt->fetch()['count'];
        echo "Bank scan results: " . $scanCount . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
