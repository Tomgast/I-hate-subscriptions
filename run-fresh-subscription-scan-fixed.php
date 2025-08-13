<?php
/**
 * Run a fresh subscription scan with INCOMING TRANSACTION EXCLUSION
 * This will analyze raw transaction data and detect only true OUTGOING subscriptions
 * FIXED: Excludes incoming payments (debtor_name present, positive amounts)
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== FRESH SUBSCRIPTION SCAN WITH INCOME EXCLUSION ===\n\n";
    
    // Get raw transaction data WITH direction indicators
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, booking_date, currency, description, 
               creditor_name, debtor_name, raw_data
        FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY merchant_name, booking_date
    ");
    $stmt->execute([$userId]);
    $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($rawTransactions) . " raw transactions\n";
    
    // FILTER OUT INCOMING TRANSACTIONS
    $outgoingTransactions = [];
    $incomingCount = 0;
    
    foreach ($rawTransactions as $transaction) {
        $isIncoming = false;
        
        // Method 1: Check if transaction has debtor_name (indicates incoming)
        if (!empty($transaction['debtor_name'])) {
            $isIncoming = true;
        }
        
        // Method 2: Check if amount is positive AND has debtor info in raw_data
        if ($transaction['amount'] > 0) {
            $rawData = json_decode($transaction['raw_data'], true);
            if ($rawData && isset($rawData['debtorAccount'])) {
                $isIncoming = true;
            }
        }
        
        // Method 3: Check bank transaction type for "Overschrijving" (incoming transfer)
        if ($transaction['amount'] > 0) {
            $rawData = json_decode($transaction['raw_data'], true);
            if ($rawData && isset($rawData['proprietaryBankTransactionCode']) 
                && $rawData['proprietaryBankTransactionCode'] === 'Overschrijving') {
                $isIncoming = true;
            }
        }
        
        if ($isIncoming) {
            $incomingCount++;
            echo "⬅️  EXCLUDED INCOMING: {$transaction['merchant_name']} (+€{$transaction['amount']})\n";
        } else {
            $outgoingTransactions[] = $transaction;
        }
    }
    
    echo "\nFiltered out {$incomingCount} incoming transactions\n";
    echo "Processing " . count($outgoingTransactions) . " outgoing transactions\n\n";
    
    // Group by merchant (using only outgoing transactions)
    $merchantGroups = [];
    foreach ($outgoingTransactions as $transaction) {
        $merchant = $transaction['merchant_name'];
        if (!isset($merchantGroups[$merchant])) {
            $merchantGroups[$merchant] = [];
        }
        
        $merchantGroups[$merchant][] = [
            'amount' => abs($transaction['amount']), // Now safe to use abs() since we filtered incoming
            'date' => $transaction['booking_date'],
            'currency' => $transaction['currency'] ?? 'EUR'
        ];
    }
    
    echo "Grouped into " . count($merchantGroups) . " unique merchants\n\n";
    
    // Apply improved detection logic (same as before)
    $blacklist = [
        'albert heijn', 'ah ', 'jumbo', 'lidl', 'aldi', 'supermarket',
        'postnl', 'post nl', 'dhl', 'ups', 'fedex', 'shipping',
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd', 'restaurant', 
        'cafe', 'eetcafe', 'bakkerij', 'bakery', 'pizza', 'mcdonalds',
        'kfc', 'burger king', 'subway', 'dominos', 'new york pizza',
        'gas station', 'shell', 'bp', 'esso', 'total', 'texaco',
        'parking', 'parkeren', 'gemeente', 'belasting', 'tax',
        'ziekenhuis', 'hospital', 'apotheek', 'pharmacy', 'huisarts',
        'dentist', 'tandarts', 'fysio', 'physiotherapy',
        'school', 'universiteit', 'university', 'college',
        'train', 'trein', 'ns ', 'bus', 'metro', 'tram', 'ov-chipkaart',
        'hotel', 'booking', 'airbnb', 'hostel', 'vacation',
        'clothing', 'kleding', 'fashion', 'shoes', 'schoenen',
        'electronics', 'mediamarkt', 'coolblue', 'bol.com',
        'cash', 'geldautomaat', 'atm', 'withdrawal'
    ];
    
    $detectedSubscriptions = [];
    $skippedMerchants = [];
    
    foreach ($merchantGroups as $merchant => $transactions) {
        if (count($transactions) < 2) continue;
        
        // Check blacklist
        $isBlacklisted = false;
        $merchantLower = strtolower($merchant);
        
        foreach ($blacklist as $blacklistItem) {
            if (strpos($merchantLower, strtolower($blacklistItem)) !== false) {
                $isBlacklisted = true;
                $skippedMerchants[] = [
                    'merchant' => $merchant,
                    'reason' => "Blacklisted ($blacklistItem)",
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
        
        // Determine billing cycle and confidence
        $billingCycle = null;
        $confidence = 50;
        
        if ($avgInterval >= 28 && $avgInterval <= 32) {
            $billingCycle = 'monthly';
            $confidence = count($recurringTransactions) >= 3 ? 100 : 80;
        } elseif ($avgInterval >= 360 && $avgInterval <= 370) {
            $billingCycle = 'yearly';
            $confidence = count($recurringTransactions) >= 2 ? 90 : 75;
        } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
            $billingCycle = 'weekly';
            $confidence = count($recurringTransactions) >= 4 ? 85 : 70;
        } else {
            $skippedMerchants[] = [
                'merchant' => $merchant,
                'reason' => "No valid billing cycle (avg " . round($avgInterval) . " days)",
                'transactions' => count($transactions)
            ];
            continue;
        }
        
        // Additional confidence boosts
        if (count($recurringTransactions) >= 6) $confidence += 10;
        if (strpos(strtolower($merchant), 'spotify') !== false) $confidence = 100;
        if (strpos(strtolower($merchant), 'netflix') !== false) $confidence = 100;
        if (strpos(strtolower($merchant), 'amazon') !== false) $confidence += 15;
        
        $confidence = min($confidence, 100);
        
        if ($confidence >= 75) {
            $detectedSubscriptions[] = [
                'merchant' => $merchant,
                'amount' => $recurringAmount,
                'cycle' => $billingCycle,
                'confidence' => $confidence,
                'transaction_count' => count($recurringTransactions)
            ];
        }
    }
    
    echo "=== DETECTION RESULTS ===\n";
    echo "Total merchants analyzed: " . count($merchantGroups) . "\n";
    echo "True subscriptions detected: " . count($detectedSubscriptions) . "\n";
    echo "Merchants filtered out: " . count($skippedMerchants) . "\n\n";
    
    echo "=== DETECTED TRUE SUBSCRIPTIONS ===\n";
    foreach ($detectedSubscriptions as $sub) {
        echo sprintf("✅ %-40s | €%7.2f | %-12s | %d%% confidence\n",
            substr($sub['merchant'], 0, 39),
            $sub['amount'],
            $sub['cycle'],
            $sub['confidence']
        );
    }
    
    // Save to database (clear existing first)
    echo "\nSaving subscriptions to database...\n";
    
    // Delete existing subscriptions for this user
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Insert new subscriptions
    $insertStmt = $pdo->prepare("
        INSERT INTO subscriptions (
            user_id, merchant_name, name, cost, billing_cycle, 
            confidence, provider, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'gocardless', 1, NOW(), NOW())
    ");
    
    foreach ($detectedSubscriptions as $sub) {
        $insertStmt->execute([
            $userId,
            $sub['merchant'],
            $sub['merchant'],
            $sub['amount'],
            $sub['cycle'],
            $sub['confidence']
        ]);
    }
    
    echo "✅ Saved " . count($detectedSubscriptions) . " subscriptions\n";
    
    echo "\n=== FILTERED OUT EXAMPLES ===\n";
    $displayCount = 0;
    foreach ($skippedMerchants as $skipped) {
        if ($displayCount >= 10) {
            echo "... and " . (count($skippedMerchants) - 10) . " more filtered out\n";
            break;
        }
        echo sprintf("⏭️  %-40s | %s\n",
            substr($skipped['merchant'], 0, 39),
            $skipped['reason']
        );
        $displayCount++;
    }
    
    echo "\n🎉 IMPROVED SUBSCRIPTION DETECTION WITH INCOME EXCLUSION COMPLETE!\n";
    echo "Only true OUTGOING subscriptions detected!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
