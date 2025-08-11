<?php
/**
 * Check Current Subscriptions and Next Payment Dates
 * See what was actually detected and diagnose the November 30 issue
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== CURRENT SUBSCRIPTION STATUS ===\n\n";
    
    // Get current subscriptions
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, currency, billing_cycle, 
               last_charge_date, next_billing_date, confidence, created_at
        FROM subscriptions 
        WHERE user_id = ?
        ORDER BY merchant_name
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "No subscriptions found in database.\n";
        echo "This means either:\n";
        echo "1. The improved algorithm filtered everything out (working correctly)\n";
        echo "2. The scan hasn't run yet\n";
        echo "3. There's an issue with the detection\n\n";
        
        // Check raw transaction data to see what we have
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT merchant_name) as merchants,
                   COUNT(*) as transactions
            FROM raw_transactions 
            WHERE user_id = ? AND amount < 0
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Raw transaction data available:\n";
        echo "- Unique expense merchants: " . $stats['merchants'] . "\n";
        echo "- Total expense transactions: " . $stats['transactions'] . "\n\n";
        
        // Show some sample expense merchants
        $stmt = $pdo->prepare("
            SELECT merchant_name, COUNT(*) as count, 
                   MIN(amount) as min_amount, MAX(amount) as max_amount
            FROM raw_transactions 
            WHERE user_id = ? AND amount < 0
            GROUP BY merchant_name
            HAVING COUNT(*) >= 2
            ORDER BY COUNT(*) DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Top expense merchants with multiple transactions:\n";
        foreach ($merchants as $merchant) {
            echo sprintf("- %-30s | %2d transactions | €%6.2f to €%6.2f\n", 
                substr($merchant['merchant_name'], 0, 29),
                $merchant['count'],
                abs($merchant['max_amount']),
                abs($merchant['min_amount'])
            );
        }
        
    } else {
        echo "Found " . count($subscriptions) . " subscriptions:\n\n";
        
        echo "Merchant                         | Amount   | Cycle    | Last Charge | Next Payment | Confidence\n";
        echo "---------------------------------|----------|----------|-------------|--------------|----------\n";
        
        $nov30Count = 0;
        
        foreach ($subscriptions as $sub) {
            $nextDate = $sub['next_billing_date'];
            if (strpos($nextDate, '-11-30') !== false) {
                $nov30Count++;
            }
            
            echo sprintf("%-30s | €%7.2f | %-8s | %-11s | %-12s | %3d%%\n", 
                substr($sub['merchant_name'], 0, 29),
                abs($sub['amount']),
                $sub['billing_cycle'],
                $sub['last_charge_date'] ?: 'NULL',
                $nextDate ?: 'NULL',
                $sub['confidence']
            );
        }
        
        echo "\n=== NEXT PAYMENT DATE ANALYSIS ===\n";
        if ($nov30Count > 0) {
            echo "❌ ISSUE FOUND: $nov30Count subscriptions show November 30 as next payment\n";
            echo "This suggests a bug in the date calculation logic.\n\n";
            
            // Analyze one subscription in detail
            $problemSub = null;
            foreach ($subscriptions as $sub) {
                if (strpos($sub['next_billing_date'], '-11-30') !== false) {
                    $problemSub = $sub;
                    break;
                }
            }
            
            if ($problemSub) {
                echo "Analyzing problematic subscription: " . $problemSub['merchant_name'] . "\n";
                echo "- Last charge date: " . $problemSub['last_charge_date'] . "\n";
                echo "- Next payment date: " . $problemSub['next_billing_date'] . "\n";
                echo "- Billing cycle: " . $problemSub['billing_cycle'] . "\n";
                echo "- Created: " . $problemSub['created_at'] . "\n\n";
                
                echo "🔧 LIKELY CAUSES:\n";
                echo "1. Last charge date is incorrect or null\n";
                echo "2. Interval calculation is wrong\n";
                echo "3. Date calculation logic has a bug\n";
                echo "4. All subscriptions created on same day with same interval\n";
            }
        } else {
            echo "✅ No November 30 issues found - next payment dates look correct\n";
        }
    }
    
    echo "\n=== NEXT STEPS ===\n";
    echo "1. If no subscriptions: Run fresh scan with improved algorithm\n";
    echo "2. If Nov 30 issue: Fix the next payment date calculation logic\n";
    echo "3. Test with real transaction data to verify dates\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
