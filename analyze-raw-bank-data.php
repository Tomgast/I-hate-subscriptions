<?php
require_once 'config/db_config.php';

echo "=== RAW BANK DATA ANALYSIS FOR ADVANCED DASHBOARD ===\n\n";

try {
    $pdo = getDBConnection();
    $userId = 2; // support@origens.nl
    
    // 1. Raw Transaction Overview
    echo "1. RAW TRANSACTION DATA OVERVIEW:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(DISTINCT merchant_name) as unique_merchants,
            MIN(booking_date) as oldest_transaction,
            MAX(booking_date) as newest_transaction,
            MIN(amount) as smallest_amount,
            MAX(amount) as largest_amount,
            SUM(CASE WHEN amount < 0 THEN 1 ELSE 0 END) as outgoing_count,
            SUM(CASE WHEN amount > 0 THEN 1 ELSE 0 END) as incoming_count,
            SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as total_outgoing,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_incoming,
            AVG(CASE WHEN amount < 0 THEN ABS(amount) ELSE NULL END) as avg_expense,
            AVG(CASE WHEN amount > 0 THEN amount ELSE NULL END) as avg_income
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $overview = $stmt->fetch();
    
    foreach($overview as $key => $value) {
        if (in_array($key, ['total_outgoing', 'total_incoming', 'avg_expense', 'avg_income', 'smallest_amount', 'largest_amount'])) {
            echo "  $key: €" . number_format($value, 2) . "\n";
        } else {
            echo "  $key: $value\n";
        }
    }
    echo "\n";
    
    // 2. Monthly Cash Flow Analysis
    echo "2. MONTHLY CASH FLOW ANALYSIS (Last 12 months):\n";
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(booking_date, '%Y-%m') as month,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as expenses,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
            SUM(amount) as net_flow,
            COUNT(DISTINCT merchant_name) as unique_merchants
        FROM raw_transactions 
        WHERE user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$userId]);
    $monthlyFlow = $stmt->fetchAll();
    
    foreach($monthlyFlow as $month) {
        echo "  {$month['month']}: Income €" . number_format($month['income'], 2) . 
             " | Expenses €" . number_format(abs($month['expenses']), 2) . 
             " | Net €" . number_format($month['net_flow'], 2) . 
             " | {$month['transaction_count']} transactions | {$month['unique_merchants']} merchants\n";
    }
    echo "\n";
    
    // 3. Top Expense Categories (by merchant analysis)
    echo "3. TOP EXPENSE MERCHANTS (Last 6 months):\n";
    $stmt = $pdo->prepare("
        SELECT 
            merchant_name,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_spent,
            AVG(CASE WHEN amount < 0 THEN ABS(amount) ELSE NULL END) as avg_transaction,
            MIN(booking_date) as first_transaction,
            MAX(booking_date) as last_transaction,
            DATEDIFF(MAX(booking_date), MIN(booking_date)) as days_span
        FROM raw_transactions 
        WHERE user_id = ? AND amount < 0 AND booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY merchant_name
        HAVING total_spent > 10
        ORDER BY total_spent DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $topMerchants = $stmt->fetchAll();
    
    foreach($topMerchants as $merchant) {
        $frequency = $merchant['days_span'] > 0 ? round($merchant['transaction_count'] / ($merchant['days_span'] / 30), 1) : 0;
        echo "  {$merchant['merchant_name']}: €" . number_format($merchant['total_spent'], 2) . 
             " ({$merchant['transaction_count']} transactions, €" . number_format($merchant['avg_transaction'], 2) . " avg, ~{$frequency}/month)\n";
    }
    echo "\n";
    
    // 4. Recurring Payment Detection (Potential Subscriptions)
    echo "4. RECURRING PAYMENT DETECTION:\n";
    $stmt = $pdo->prepare("
        SELECT 
            merchant_name,
            COUNT(*) as transaction_count,
            COUNT(DISTINCT ROUND(ABS(amount), 2)) as unique_amounts,
            GROUP_CONCAT(DISTINCT ROUND(ABS(amount), 2) ORDER BY ROUND(ABS(amount), 2) DESC) as amounts,
            AVG(ABS(amount)) as avg_amount,
            MIN(booking_date) as first_seen,
            MAX(booking_date) as last_seen,
            DATEDIFF(MAX(booking_date), MIN(booking_date)) as period_days,
            ROUND(DATEDIFF(MAX(booking_date), MIN(booking_date)) / COUNT(*), 0) as avg_days_between
        FROM raw_transactions 
        WHERE user_id = ? AND amount < 0 
        GROUP BY merchant_name
        HAVING transaction_count >= 3 AND unique_amounts <= 2
        ORDER BY transaction_count DESC, avg_amount DESC
    ");
    $stmt->execute([$userId]);
    $recurringPayments = $stmt->fetchAll();
    
    foreach($recurringPayments as $recurring) {
        $likely_subscription = ($recurring['avg_days_between'] >= 25 && $recurring['avg_days_between'] <= 35) ? "🎯 LIKELY MONTHLY" : 
                              (($recurring['avg_days_between'] >= 85 && $recurring['avg_days_between'] <= 95) ? "🎯 LIKELY QUARTERLY" :
                              (($recurring['avg_days_between'] >= 360 && $recurring['avg_days_between'] <= 370) ? "🎯 LIKELY YEARLY" : ""));
        
        echo "  {$recurring['merchant_name']}: {$recurring['transaction_count']} payments, €" . 
             number_format($recurring['avg_amount'], 2) . " avg, every ~{$recurring['avg_days_between']} days $likely_subscription\n";
    }
    echo "\n";
    
    // 5. Income Sources Analysis
    echo "5. INCOME SOURCES ANALYSIS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            merchant_name,
            COUNT(*) as payment_count,
            SUM(amount) as total_income,
            AVG(amount) as avg_payment,
            MIN(booking_date) as first_payment,
            MAX(booking_date) as last_payment
        FROM raw_transactions 
        WHERE user_id = ? AND amount > 0 AND booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY merchant_name
        ORDER BY total_income DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $incomeSources = $stmt->fetchAll();
    
    foreach($incomeSources as $income) {
        echo "  {$income['merchant_name']}: €" . number_format($income['total_income'], 2) . 
             " ({$income['payment_count']} payments, €" . number_format($income['avg_payment'], 2) . " avg)\n";
    }
    echo "\n";
    
    // 6. Transaction Patterns by Day of Week/Month
    echo "6. TRANSACTION PATTERNS:\n";
    $stmt = $pdo->prepare("
        SELECT 
            DAYNAME(booking_date) as day_of_week,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_spent
        FROM raw_transactions 
        WHERE user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY DAYOFWEEK(booking_date), DAYNAME(booking_date)
        ORDER BY DAYOFWEEK(booking_date)
    ");
    $stmt->execute([$userId]);
    $dayPatterns = $stmt->fetchAll();
    
    echo "  BY DAY OF WEEK:\n";
    foreach($dayPatterns as $day) {
        echo "    {$day['day_of_week']}: {$day['transaction_count']} transactions, €" . 
             number_format($day['total_spent'], 2) . " spent\n";
    }
    echo "\n";
    
    // 7. Unusual/Large Transactions
    echo "7. NOTABLE TRANSACTIONS (Last 3 months):\n";
    $stmt = $pdo->prepare("
        SELECT booking_date, merchant_name, amount, description
        FROM raw_transactions 
        WHERE user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND (ABS(amount) > 500 OR amount > 100)
        ORDER BY ABS(amount) DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notableTransactions = $stmt->fetchAll();
    
    foreach($notableTransactions as $txn) {
        $type = $txn['amount'] > 0 ? "INCOME" : "EXPENSE";
        echo "  {$txn['booking_date']}: {$txn['merchant_name']} - €" . 
             number_format(abs($txn['amount']), 2) . " ($type)\n";
    }
    echo "\n";
    
    // 8. Dashboard Feature Recommendations
    echo "8. ADVANCED DASHBOARD FEATURES WE CAN BUILD:\n";
    echo "  📊 CASH FLOW VISUALIZATION:\n";
    echo "    - Monthly income vs expenses chart\n";
    echo "    - Net cash flow trends\n";
    echo "    - Spending velocity indicators\n";
    echo "\n";
    echo "  🎯 SUBSCRIPTION INTELLIGENCE:\n";
    echo "    - Automatic recurring payment detection\n";
    echo "    - Subscription cost optimization suggestions\n";
    echo "    - Cancellation risk alerts\n";
    echo "\n";
    echo "  💡 SPENDING INSIGHTS:\n";
    echo "    - Merchant category breakdown\n";
    echo "    - Spending pattern analysis\n";
    echo "    - Budget vs actual comparisons\n";
    echo "\n";
    echo "  🔍 FINANCIAL HEALTH:\n";
    echo "    - Income stability metrics\n";
    echo "    - Expense predictability scores\n";
    echo "    - Cash flow forecasting\n";
    echo "\n";
    echo "  ⚡ REAL-TIME ALERTS:\n";
    echo "    - Unusual spending detection\n";
    echo "    - New subscription alerts\n";
    echo "    - Budget threshold warnings\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
