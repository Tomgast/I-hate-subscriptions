<?php
/**
 * Debug Next Payment Date Calculation
 * Find out why all next payment dates show November 30
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== DEBUGGING NEXT PAYMENT DATE CALCULATION ===\n\n";
    
    // Check current subscriptions and their next payment dates
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, billing_cycle, last_charge_date, 
               next_billing_date, confidence, created_at
        FROM subscriptions 
        WHERE user_id = ?
        ORDER BY merchant_name
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current subscriptions and their next payment dates:\n";
    echo "Merchant                         | Last Charge | Next Payment | Cycle\n";
    echo "---------------------------------|-------------|--------------|----------\n";
    
    foreach ($subscriptions as $sub) {
        echo sprintf("%-30s | %-11s | %-12s | %s\n", 
            substr($sub['merchant_name'], 0, 29),
            $sub['last_charge_date'] ?: 'NULL',
            $sub['next_billing_date'] ?: 'NULL',
            $sub['billing_cycle']
        );
    }
    
    echo "\n=== ANALYZING RAW TRANSACTION DATA ===\n";
    
    // Let's manually test the calculation for a few merchants
    $testMerchants = ['Spotify', 'Netflix', 'Prim'];
    
    foreach ($testMerchants as $searchTerm) {
        echo "\n--- Testing: $searchTerm ---\n";
        
        $stmt = $pdo->prepare("
            SELECT merchant_name, amount, booking_date
            FROM raw_transactions 
            WHERE user_id = ? AND LOWER(merchant_name) LIKE ?
            AND amount < 0
            ORDER BY booking_date ASC
        ");
        $stmt->execute([$userId, "%".strtolower($searchTerm)."%"]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($transactions) >= 2) {
            echo "Found " . count($transactions) . " transactions:\n";
            
            $dates = array_column($transactions, 'booking_date');
            $amounts = array_column($transactions, 'amount');
            
            // Show transaction dates
            foreach ($transactions as $i => $trans) {
                echo "  " . ($i+1) . ". " . $trans['booking_date'] . " | €" . number_format(abs($trans['amount']), 2) . "\n";
            }
            
            // Calculate intervals
            $intervals = [];
            for ($i = 1; $i < count($dates); $i++) {
                $interval = (strtotime($dates[$i]) - strtotime($dates[$i-1])) / (24 * 60 * 60);
                $intervals[] = $interval;
                echo "  Interval " . $i . ": " . round($interval, 1) . " days\n";
            }
            
            if (!empty($intervals)) {
                $avgInterval = array_sum($intervals) / count($intervals);
                echo "  Average interval: " . round($avgInterval, 1) . " days\n";
                
                $lastDate = end($dates);
                echo "  Last charge date: $lastDate\n";
                
                // Test the calculation
                $nextPaymentDate = date('Y-m-d', strtotime($lastDate . ' + ' . round($avgInterval) . ' days'));
                echo "  Calculated next payment: $nextPaymentDate\n";
                
                // Check if this matches what's in database
                $stmt = $pdo->prepare("
                    SELECT next_billing_date 
                    FROM subscriptions 
                    WHERE user_id = ? AND LOWER(merchant_name) LIKE ?
                ");
                $stmt->execute([$userId, "%".strtolower($searchTerm)."%"]);
                $dbNext = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dbNext) {
                    echo "  Database next payment: " . $dbNext['next_billing_date'] . "\n";
                    if ($dbNext['next_billing_date'] === $nextPaymentDate) {
                        echo "  ✅ Calculation matches database\n";
                    } else {
                        echo "  ❌ Calculation doesn't match database!\n";
                    }
                }
            }
        } else {
            echo "Not enough transactions found for $searchTerm\n";
        }
    }
    
    echo "\n=== DIAGNOSIS ===\n";
    echo "If all next payment dates show the same date (like Nov 30), possible causes:\n";
    echo "1. All subscriptions were created/updated on the same day\n";
    echo "2. The last_charge_date is wrong or null\n";
    echo "3. The interval calculation is wrong\n";
    echo "4. There's a bug in the date calculation logic\n\n";
    
    echo "Current date: " . date('Y-m-d') . "\n";
    echo "Test calculation from today + 30 days: " . date('Y-m-d', strtotime('+30 days')) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
