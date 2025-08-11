<?php
/**
 * Run a fresh subscription scan using the improved detection algorithm
 * This will analyze raw transaction data and detect only true subscriptions
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== FRESH SUBSCRIPTION SCAN WITH IMPROVED ALGORITHM ===\n\n";
    
    // Get raw transaction data
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, booking_date, currency, description
        FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY merchant_name, booking_date
    ");
    $stmt->execute([$userId]);
    $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($rawTransactions) . " raw transactions\n\n";
    
    // Group by merchant
    $merchantGroups = [];
    foreach ($rawTransactions as $transaction) {
        $merchant = $transaction['merchant_name'];
        if (!isset($merchantGroups[$merchant])) {
            $merchantGroups[$merchant] = [];
        }
        
        $merchantGroups[$merchant][] = [
            'amount' => abs($transaction['amount']),
            'date' => $transaction['booking_date'],
            'currency' => $transaction['currency'] ?? 'EUR'
        ];
    }
    
    echo "Grouped into " . count($merchantGroups) . " unique merchants\n\n";
    
    // Apply improved detection logic
    $blacklist = [
        'albert heijn', 'ah ', 'jumbo', 'lidl', 'aldi', 'supermarket',
        'postnl', 'post nl', 'dhl', 'ups', 'fedex', 'shipping',
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd', 'restaurant', 
        'cafe', 'eetcafe', 'bakkerij', 'bakery', 'pizza', 'mcdonalds',
        'tankstation', 'shell', 'bp', 'esso', 'gas station',
        'parking', 'parkeren', 'gemeente', 'belasting', 'tax'
    ];
    
    $subscriptionKeywords = [
        'spotify', 'netflix', 'disney', 'amazon prime', 'adobe', 'microsoft', 
        'apple music', 'youtube premium', 'hulu', 'hbo', 'jagex', 'games studio',
        'playstation', 'xbox', 'steam', 'office 365', 'dropbox', 'google'
    ];
    
    $detectedSubscriptions = [];
    $skippedMerchants = [];
    
    foreach ($merchantGroups as $merchant => $transactions) {
        if (count($transactions) < 2) continue; // Need at least 2 transactions
        
        $merchantLower = strtolower($merchant);
        
        // Check blacklist first
        $isBlacklisted = false;
        foreach ($blacklist as $blacklisted) {
            if (strpos($merchantLower, $blacklisted) !== false) {
                $isBlacklisted = true;
                $skippedMerchants[] = [
                    'merchant' => $merchant,
                    'reason' => "Blacklisted ($blacklisted)",
                    'transactions' => count($transactions)
                ];
                break;
            }
        }
        
        if ($isBlacklisted) continue;
        
        // Look for recurring amounts
        $amounts = array_column($transactions, 'amount');
        $amountCounts = array_count_values(array_map('strval', $amounts));
        
        if (empty($amountCounts)) continue;
        
        $recurringAmountStr = array_keys($amountCounts, max($amountCounts))[0];
        $recurringAmount = (float)$recurringAmountStr;
        
        // Apply amount filters
        if ($recurringAmount < 2.00) {
            $skippedMerchants[] = [
                'merchant' => $merchant,
                'reason' => "Amount too small (€" . number_format($recurringAmount, 2) . ")",
                'transactions' => count($transactions)
            ];
            continue;
        }
        
        if ($recurringAmount > 500.00) {
            $skippedMerchants[] = [
                'merchant' => $merchant,
                'reason' => "Amount too large (€" . number_format($recurringAmount, 2) . ")",
                'transactions' => count($transactions)
            ];
            continue;
        }
        
        // Get transactions with recurring amount
        $recurringTransactions = array_filter($transactions, function($t) use ($recurringAmount) {
            return $t['amount'] == $recurringAmount;
        });
        
        if (count($recurringTransactions) < 2) continue;
        
        // Calculate intervals
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
        } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
            $billingCycle = 'weekly';
        }
        
        // Skip if no valid billing cycle
        if ($billingCycle === 'unknown') {
            $skippedMerchants[] = [
                'merchant' => $merchant,
                'reason' => "No valid billing cycle (avg " . round($avgInterval, 1) . " days)",
                'transactions' => count($transactions)
            ];
            continue;
        }
        
        // Calculate confidence score
        $confidence = min(50, count($recurringTransactions) * 15); // Base score
        
        // Bonus for known subscription merchants
        foreach ($subscriptionKeywords as $keyword) {
            if (strpos($merchantLower, $keyword) !== false) {
                $confidence += 25;
                break;
            }
        }
        
        // Bonus for billing cycle
        switch ($billingCycle) {
            case 'monthly': $confidence += 20; break;
            case 'yearly': $confidence += 15; break;
            case 'quarterly': $confidence += 10; break;
            default: $confidence += 5; break;
        }
        
        // Bonus for reasonable amounts
        if ($recurringAmount >= 5 && $recurringAmount <= 100) {
            $confidence += 10;
        } elseif ($recurringAmount >= 2 && $recurringAmount <= 200) {
            $confidence += 5;
        }
        
        $confidence = min(100, $confidence);
        
        // Only keep if confidence >= 50
        if ($confidence >= 50) {
            $detectedSubscriptions[] = [
                'merchant_name' => $merchant,
                'amount' => $recurringAmount,
                'currency' => $transactions[0]['currency'] ?? 'EUR',
                'billing_cycle' => $billingCycle,
                'last_charge_date' => end($dates),
                'confidence' => $confidence,
                'transaction_count' => count($recurringTransactions)
            ];
        } else {
            $skippedMerchants[] = [
                'merchant' => $merchant,
                'reason' => "Low confidence ($confidence%)",
                'transactions' => count($transactions)
            ];
        }
    }
    
    echo "=== DETECTION RESULTS ===\n";
    echo "Total merchants analyzed: " . count($merchantGroups) . "\n";
    echo "True subscriptions detected: " . count($detectedSubscriptions) . "\n";
    echo "Merchants filtered out: " . count($skippedMerchants) . "\n\n";
    
    if (!empty($detectedSubscriptions)) {
        echo "=== DETECTED TRUE SUBSCRIPTIONS ===\n";
        foreach ($detectedSubscriptions as $sub) {
            echo sprintf("✅ %-35s | €%6.2f | %-12s | %d%% confidence\n", 
                substr($sub['merchant_name'], 0, 34),
                $sub['amount'],
                $sub['billing_cycle'],
                $sub['confidence']
            );
        }
        echo "\n";
        
        // Save to database
        echo "Saving subscriptions to database...\n";
        foreach ($detectedSubscriptions as $sub) {
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (
                    user_id, merchant_name, amount, currency, billing_cycle,
                    last_charge_date, confidence, provider, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'gocardless', 'active', NOW(), NOW())
            ");
            
            $stmt->execute([
                $userId,
                $sub['merchant_name'],
                $sub['amount'],
                $sub['currency'],
                $sub['billing_cycle'],
                $sub['last_charge_date'],
                $sub['confidence']
            ]);
        }
        echo "✅ Saved " . count($detectedSubscriptions) . " subscriptions\n\n";
    }
    
    echo "=== FILTERED OUT EXAMPLES ===\n";
    $count = 0;
    foreach ($skippedMerchants as $skipped) {
        if ($count >= 10) break;
        echo sprintf("⏭️  %-35s | %s\n", 
            substr($skipped['merchant'], 0, 34),
            $skipped['reason']
        );
        $count++;
    }
    
    if (count($skippedMerchants) > 10) {
        echo "... and " . (count($skippedMerchants) - 10) . " more filtered out\n";
    }
    
    echo "\n🎉 IMPROVED SUBSCRIPTION DETECTION COMPLETE!\n";
    echo "Quality over quantity - only true subscriptions detected!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
