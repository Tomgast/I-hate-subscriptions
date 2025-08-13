<?php
/**
 * Check User 2 Enhanced Dashboard Data
 * Investigate what data user 2 has for enhanced features
 */

require_once 'config/db_config.php';

$userId = 2; // support@origens.nl

echo "=== USER 2 ENHANCED DASHBOARD DATA CHECK ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Check subscriptions and categories
    echo "1. SUBSCRIPTIONS & CATEGORIES:\n";
    $stmt = $pdo->prepare("
        SELECT id, name, cost, billing_cycle, category, is_active, created_at
        FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total subscriptions: " . count($subscriptions) . "\n";
    foreach ($subscriptions as $sub) {
        echo "- {$sub['name']}: €{$sub['cost']} ({$sub['category']}) - " . ($sub['is_active'] ? 'Active' : 'Inactive') . "\n";
    }
    
    // 2. Check for price history
    echo "\n2. PRICE HISTORY:\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM price_history WHERE user_id = ?");
    $stmt->execute([$userId]);
    $priceHistoryCount = $stmt->fetchColumn();
    echo "Price history records: $priceHistoryCount\n";
    
    if ($priceHistoryCount > 0) {
        $stmt = $pdo->prepare("
            SELECT ph.*, s.name as subscription_name 
            FROM price_history ph
            JOIN subscriptions s ON ph.subscription_id = s.id
            WHERE ph.user_id = ?
            ORDER BY ph.change_date DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $priceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($priceHistory as $change) {
            echo "- {$change['subscription_name']}: €{$change['old_cost']} → €{$change['new_cost']} on {$change['change_date']}\n";
        }
    }
    
    // 3. Check for anomalies
    echo "\n3. SUBSCRIPTION ANOMALIES:\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_anomalies WHERE user_id = ?");
    $stmt->execute([$userId]);
    $anomaliesCount = $stmt->fetchColumn();
    echo "Anomaly records: $anomaliesCount\n";
    
    // 4. Check raw transactions for potential new subscriptions
    echo "\n4. RAW TRANSACTIONS ANALYSIS:\n";
    $stmt = $pdo->prepare("
        SELECT merchant_name, COUNT(*) as frequency, AVG(amount) as avg_amount, MIN(transaction_date) as first_seen, MAX(transaction_date) as last_seen
        FROM raw_transactions 
        WHERE user_id = ? AND amount < 0
        GROUP BY merchant_name
        HAVING frequency >= 2
        ORDER BY frequency DESC, avg_amount DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $potentialSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Potential recurring transactions: " . count($potentialSubs) . "\n";
    foreach ($potentialSubs as $potential) {
        echo "- {$potential['merchant_name']}: {$potential['frequency']} times, avg €" . abs($potential['avg_amount']) . " ({$potential['first_seen']} to {$potential['last_seen']})\n";
    }
    
    // 5. Check for duplicates by category
    echo "\n5. DUPLICATE DETECTION:\n";
    $stmt = $pdo->prepare("
        SELECT category, COUNT(*) as count, GROUP_CONCAT(name SEPARATOR ', ') as services, SUM(cost) as total_cost
        FROM subscriptions 
        WHERE user_id = ? AND is_active = 1 AND category IS NOT NULL AND category != 'Other'
        GROUP BY category
        HAVING count > 1
        ORDER BY count DESC, total_cost DESC
    ");
    $stmt->execute([$userId]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Categories with multiple subscriptions: " . count($duplicates) . "\n";
    foreach ($duplicates as $dup) {
        echo "- {$dup['category']}: {$dup['count']} services (€{$dup['total_cost']}/month) - {$dup['services']}\n";
    }
    
    // 6. Check bank connections and sync status
    echo "\n6. BANK CONNECTION STATUS:\n";
    $stmt = $pdo->prepare("
        SELECT provider, account_name, status, created_at, updated_at
        FROM bank_connections 
        WHERE user_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Bank connections: " . count($connections) . "\n";
    foreach ($connections as $conn) {
        echo "- {$conn['provider']}: {$conn['account_name']} ({$conn['status']}) - Updated: {$conn['updated_at']}\n";
    }
    
    // 7. Check recent transaction activity
    echo "\n7. RECENT TRANSACTION ACTIVITY:\n";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_transactions, 
               MAX(transaction_date) as last_transaction,
               COUNT(DISTINCT merchant_name) as unique_merchants
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $transactionStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total transactions: {$transactionStats['total_transactions']}\n";
    echo "Last transaction: {$transactionStats['last_transaction']}\n";
    echo "Unique merchants: {$transactionStats['unique_merchants']}\n";
    
    // 8. Simulate creating some test data for enhanced features
    echo "\n8. CREATING TEST DATA FOR ENHANCED FEATURES:\n";
    
    // Create a price change for testing
    if (count($subscriptions) > 0) {
        $testSub = $subscriptions[0];
        $newCost = $testSub['cost'] + 2.99; // Increase price by €2.99
        
        echo "Creating test price change for '{$testSub['name']}': €{$testSub['cost']} → €{$newCost}\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO price_history (subscription_id, user_id, old_cost, new_cost, change_reason)
            VALUES (?, ?, ?, ?, 'Test price increase')
        ");
        $stmt->execute([$testSub['id'], $userId, $testSub['cost'], $newCost]);
        
        echo "✓ Test price change created\n";
    }
    
    // Create a test anomaly
    if (count($potentialSubs) > 0) {
        $testMerchant = $potentialSubs[0];
        $expectedAmount = abs($testMerchant['avg_amount']);
        $actualAmount = $expectedAmount + 5.00; // €5 more than expected
        
        echo "Creating test anomaly for '{$testMerchant['merchant_name']}': Expected €{$expectedAmount}, Actual €{$actualAmount}\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO subscription_anomalies (user_id, merchant_name, expected_amount, actual_amount, transaction_date, anomaly_type, severity)
            VALUES (?, ?, ?, ?, CURDATE(), 'unexpected_charge', 'medium')
        ");
        $stmt->execute([$userId, $testMerchant['merchant_name'], $expectedAmount, $actualAmount]);
        
        echo "✓ Test anomaly created\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "User 2 has:\n";
    echo "- " . count($subscriptions) . " subscriptions\n";
    echo "- " . count($connections) . " bank connections\n";
    echo "- " . count($potentialSubs) . " potential recurring transactions\n";
    echo "- " . count($duplicates) . " categories with multiple subscriptions\n";
    echo "- Test data created for enhanced features\n";
    echo "\nThe enhanced dashboard should now show insights!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
