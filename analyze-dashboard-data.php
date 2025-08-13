<?php
require_once 'config/db_config.php';

echo "=== COMPREHENSIVE DATABASE ANALYSIS FOR DASHBOARD UPGRADE ===\n\n";

try {
    $pdo = getDBConnection();
    $userId = 2; // support@origens.nl
    
    // 1. Subscription Analytics
    echo "1. SUBSCRIPTION ANALYTICS:\n";
    
    // Basic subscription stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_subscriptions,
            SUM(cost) as total_monthly_cost,
            AVG(cost) as avg_subscription_cost,
            MIN(cost) as cheapest_subscription,
            MAX(cost) as most_expensive_subscription,
            COUNT(DISTINCT provider) as unique_providers,
            COUNT(DISTINCT billing_cycle) as billing_cycles_used
        FROM subscriptions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $subStats = $stmt->fetch();
    
    foreach($subStats as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "\n";
    
    // Subscription by provider breakdown
    echo "2. SUBSCRIPTIONS BY PROVIDER:\n";
    $stmt = $pdo->prepare("
        SELECT provider, COUNT(*) as count, SUM(cost) as total_cost, AVG(cost) as avg_cost
        FROM subscriptions 
        WHERE user_id = ? 
        GROUP BY provider
        ORDER BY total_cost DESC
    ");
    $stmt->execute([$userId]);
    $providerStats = $stmt->fetchAll();
    
    foreach($providerStats as $stat) {
        echo "  {$stat['provider']}: {$stat['count']} subscriptions, €{$stat['total_cost']} total, €{$stat['avg_cost']} avg\n";
    }
    echo "\n";
    
    // Subscription by category (if available)
    echo "3. SUBSCRIPTIONS BY CATEGORY:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(category, 'Uncategorized') as category, 
            COUNT(*) as count, 
            SUM(cost) as total_cost
        FROM subscriptions 
        WHERE user_id = ? 
        GROUP BY category
        ORDER BY total_cost DESC
    ");
    $stmt->execute([$userId]);
    $categoryStats = $stmt->fetchAll();
    
    foreach($categoryStats as $stat) {
        echo "  {$stat['category']}: {$stat['count']} subscriptions, €{$stat['total_cost']} total\n";
    }
    echo "\n";
    
    // 4. Bank Connection Analysis
    echo "4. BANK CONNECTION ANALYSIS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            provider,
            account_name,
            status,
            created_at,
            DATEDIFF(NOW(), created_at) as days_connected
        FROM bank_connections 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $bankConnections = $stmt->fetchAll();
    
    foreach($bankConnections as $conn) {
        echo "  {$conn['provider']}: {$conn['account_name']} - {$conn['status']} ({$conn['days_connected']} days connected)\n";
    }
    echo "\n";
    
    // 5. Transaction Analysis (if available)
    echo "5. TRANSACTION ANALYSIS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(DISTINCT merchant_name) as unique_merchants,
            MIN(booking_date) as oldest_transaction,
            MAX(booking_date) as newest_transaction,
            SUM(CASE WHEN amount < 0 THEN 1 ELSE 0 END) as outgoing_transactions,
            SUM(CASE WHEN amount > 0 THEN 1 ELSE 0 END) as incoming_transactions
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $transactionStats = $stmt->fetch();
    
    if ($transactionStats && $transactionStats['total_transactions'] > 0) {
        foreach($transactionStats as $key => $value) {
            echo "  $key: $value\n";
        }
        
        // Recent transaction activity
        echo "\n  RECENT TRANSACTION ACTIVITY (Last 30 days):\n";
        $stmt = $pdo->prepare("
            SELECT 
                DATE(booking_date) as transaction_date,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT merchant_name) as unique_merchants
            FROM raw_transactions 
            WHERE user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(booking_date)
            ORDER BY transaction_date DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentActivity = $stmt->fetchAll();
        
        foreach($recentActivity as $activity) {
            echo "    {$activity['transaction_date']}: {$activity['transaction_count']} transactions, {$activity['unique_merchants']} merchants\n";
        }
    } else {
        echo "  No transaction data available\n";
    }
    echo "\n";
    
    // 6. Bank Scan Results Analysis
    echo "6. BANK SCAN RESULTS ANALYSIS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            provider,
            status,
            subscriptions_found,
            scan_duration_seconds,
            created_at,
            DATEDIFF(NOW(), created_at) as days_ago
        FROM bank_scan_results 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $scanResults = $stmt->fetchAll();
    
    if (count($scanResults) > 0) {
        foreach($scanResults as $scan) {
            echo "  {$scan['created_at']} ({$scan['days_ago']} days ago): {$scan['provider']} - {$scan['status']} - Found {$scan['subscriptions_found']} subscriptions in {$scan['scan_duration_seconds']}s\n";
        }
    } else {
        echo "  No scan results available\n";
    }
    echo "\n";
    
    // 7. Potential Dashboard Features Analysis
    echo "7. POTENTIAL DASHBOARD FEATURES:\n";
    
    echo "  AVAILABLE DATA POINTS:\n";
    echo "  ✅ Subscription costs and billing cycles\n";
    echo "  ✅ Provider information (bank detection)\n";
    echo "  ✅ Confidence scores for bank-detected subscriptions\n";
    echo "  ✅ Bank connection status and history\n";
    echo "  ✅ Subscription creation dates (growth tracking)\n";
    
    if ($transactionStats && $transactionStats['total_transactions'] > 0) {
        echo "  ✅ Transaction history and patterns\n";
        echo "  ✅ Merchant analysis\n";
        echo "  ✅ Spending trends over time\n";
    }
    
    if (count($scanResults) > 0) {
        echo "  ✅ Bank scan performance metrics\n";
        echo "  ✅ Detection accuracy tracking\n";
    }
    
    echo "\n  POSSIBLE DASHBOARD UPGRADES:\n";
    echo "  🎯 Interactive spending charts and trends\n";
    echo "  🎯 Category-based spending breakdown\n";
    echo "  🎯 Provider comparison and insights\n";
    echo "  🎯 Subscription growth timeline\n";
    echo "  🎯 Cost optimization suggestions\n";
    echo "  🎯 Bank connection health monitoring\n";
    echo "  🎯 Detection confidence analytics\n";
    echo "  🎯 Upcoming payments calendar\n";
    echo "  🎯 Spending forecasting\n";
    echo "  🎯 Duplicate subscription detection\n";
    
    if ($transactionStats && $transactionStats['total_transactions'] > 0) {
        echo "  🎯 Transaction pattern analysis\n";
        echo "  🎯 Merchant spending insights\n";
        echo "  🎯 Income vs expense tracking\n";
    }
    
    // 8. Sample data for dashboard design
    echo "\n8. SAMPLE DATA FOR DASHBOARD DESIGN:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY cost DESC LIMIT 3");
    $stmt->execute([$userId]);
    $topSubscriptions = $stmt->fetchAll();
    
    echo "  TOP 3 MOST EXPENSIVE SUBSCRIPTIONS:\n";
    foreach($topSubscriptions as $sub) {
        $name = $sub['name'] ?: $sub['merchant_name'] ?: 'Unknown';
        echo "    {$name}: €{$sub['cost']}/{$sub['billing_cycle']} - {$sub['confidence']}% confidence\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
