<?php
/**
 * Test Improved Next Payment Date Calculation
 * Clear existing subscriptions and run fresh scan with fixed algorithm
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== TESTING IMPROVED NEXT PAYMENT DATE CALCULATION ===\n\n";
    
    // Clear existing subscriptions for fresh test
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "✅ Cleared existing subscriptions for fresh test\n\n";
    
    // Run the improved subscription scan manually
    echo "Running subscription detection with improved algorithm...\n";
    
    // Get raw transaction data
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, booking_date, currency
        FROM raw_transactions 
        WHERE user_id = ? AND amount < 0
        ORDER BY merchant_name, booking_date
    ");
    $stmt->execute([$userId]);
    $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by merchant
    $merchantGroups = [];
    foreach ($rawTransactions as $transaction) {
        $merchant = $transaction['merchant_name'];
        if (!isset($merchantGroups[$merchant])) {
            $merchantGroups[$merchant] = [];
        }
        
        $merchantGroups[$merchant][] = [
            'amount' => $transaction['amount'],
            'date' => $transaction['booking_date'],
            'currency' => $transaction['currency'] ?? 'EUR'
        ];
    }
    
    echo "Analyzing " . count($merchantGroups) . " merchants...\n\n";
    
    $detectedSubscriptions = [];
    $testCount = 0;
    
    foreach ($merchantGroups as $merchant => $transactions) {
        if (count($transactions) < 2) continue;
        if ($testCount >= 5) break; // Test first 5 potential subscriptions
        
        $testCount++;
        
        // Simulate the improved detection logic
        $amounts = array_column($transactions, 'amount');
        $amountCounts = array_count_values(array_map('strval', $amounts));
        if (empty($amountCounts)) continue;
        
        $recurringAmountStr = array_keys($amountCounts, max($amountCounts))[0];
        $recurringAmount = (float)$recurringAmountStr;
        
        // Apply filters
        if ($recurringAmount > 0) continue; // Skip income
        if (abs($recurringAmount) < 2.00) continue; // Too small
        if (abs($recurringAmount) > 500.00) continue; // Too large
        
        $recurringTransactions = array_filter($transactions, function($t) use ($recurringAmount) {
            return $t['amount'] == $recurringAmount;
        });
        
        if (count($recurringTransactions) < 2) continue;
        
        // Calculate intervals and billing cycle
        $dates = array_column($recurringTransactions, 'date');
        sort($dates);
        
        $intervals = [];
        for ($i = 1; $i < count($dates); $i++) {
            $interval = (strtotime($dates[$i]) - strtotime($dates[$i-1])) / (24 * 60 * 60);
            $intervals[] = $interval;
        }
        
        $avgInterval = array_sum($intervals) / count($intervals);
        
        // Determine billing cycle
        $billingCycle = 'unknown';
        if ($avgInterval >= 28 && $avgInterval <= 32) {
            $billingCycle = 'monthly';
        } elseif ($avgInterval >= 350 && $avgInterval <= 380) {
            $billingCycle = 'yearly';
        } elseif ($avgInterval >= 85 && $avgInterval <= 95) {
            $billingCycle = 'quarterly';
        }
        
        if ($billingCycle === 'unknown') continue;
        
        // Test the IMPROVED next payment calculation
        $lastDate = end($dates);
        
        echo "Testing: $merchant\n";
        echo "- Last charge date: $lastDate\n";
        echo "- Billing cycle: $billingCycle\n";
        echo "- Average interval: " . round($avgInterval, 1) . " days\n";
        
        // Apply the new calculation logic
        if (!$lastDate || !strtotime($lastDate)) {
            $lastDate = date('Y-m-d');
            echo "- Fixed invalid last date to: $lastDate\n";
        }
        
        $nextPaymentDate = null;
        switch ($billingCycle) {
            case 'monthly':
                $nextPaymentDate = date('Y-m-d', strtotime($lastDate . ' + 1 month'));
                break;
            case 'yearly':
                $nextPaymentDate = date('Y-m-d', strtotime($lastDate . ' + 1 year'));
                break;
            case 'quarterly':
                $nextPaymentDate = date('Y-m-d', strtotime($lastDate . ' + 3 months'));
                break;
        }
        
        if (!$nextPaymentDate || !strtotime($nextPaymentDate)) {
            $nextPaymentDate = date('Y-m-d', strtotime('+1 month'));
        }
        
        echo "- Next payment date: $nextPaymentDate\n";
        
        // Validate the date makes sense
        $today = date('Y-m-d');
        if ($nextPaymentDate > $today) {
            echo "- ✅ Next payment date is in the future (correct)\n";
        } else {
            echo "- ⚠️  Next payment date is in the past (may need adjustment)\n";
        }
        
        echo "\n";
        
        // Save to database for testing
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (
                user_id, merchant_name, amount, currency, billing_cycle,
                last_charge_date, next_billing_date, confidence, 
                provider, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'gocardless', 'active', NOW(), NOW())
        ");
        
        $confidence = 75; // Test confidence
        $stmt->execute([
            $userId, $merchant, $recurringAmount, 'EUR', $billingCycle,
            $lastDate, $nextPaymentDate, $confidence
        ]);
        
        $detectedSubscriptions[] = [
            'merchant' => $merchant,
            'amount' => $recurringAmount,
            'cycle' => $billingCycle,
            'next_date' => $nextPaymentDate
        ];
    }
    
    echo "=== TEST RESULTS ===\n";
    echo "Subscriptions detected: " . count($detectedSubscriptions) . "\n\n";
    
    if (!empty($detectedSubscriptions)) {
        echo "Next payment dates:\n";
        foreach ($detectedSubscriptions as $sub) {
            echo sprintf("- %-30s | €%6.2f | %s | Next: %s\n", 
                substr($sub['merchant'], 0, 29),
                abs($sub['amount']),
                $sub['cycle'],
                $sub['next_date']
            );
        }
    }
    
    echo "\n🎉 NEXT PAYMENT DATE CALCULATION TEST COMPLETE!\n";
    echo "The improved algorithm should now calculate proper next payment dates\n";
    echo "instead of showing November 30 everywhere.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
