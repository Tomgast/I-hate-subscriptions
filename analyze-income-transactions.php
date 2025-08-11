<?php
/**
 * Analyze Income/Positive Transactions
 * Identify transactions that are income (positive amounts) that shouldn't be subscriptions
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== ANALYZING INCOME/POSITIVE TRANSACTIONS ===\n\n";
    
    // Get positive transactions (income) from raw data
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, booking_date, description
        FROM raw_transactions 
        WHERE user_id = ? AND amount > 0
        ORDER BY amount DESC
    ");
    $stmt->execute([$userId]);
    $positiveTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($positiveTransactions) . " positive (income) transactions\n\n";
    
    // Group by merchant to see patterns
    $merchantGroups = [];
    foreach ($positiveTransactions as $transaction) {
        $merchant = $transaction['merchant_name'];
        if (!isset($merchantGroups[$merchant])) {
            $merchantGroups[$merchant] = [];
        }
        $merchantGroups[$merchant][] = $transaction;
    }
    
    echo "=== POSITIVE TRANSACTIONS BY MERCHANT ===\n";
    foreach ($merchantGroups as $merchant => $transactions) {
        $amounts = array_column($transactions, 'amount');
        $totalAmount = array_sum($amounts);
        $avgAmount = $totalAmount / count($amounts);
        
        echo sprintf("%-40s | %2d transactions | €%8.2f avg | €%8.2f total\n", 
            substr($merchant, 0, 39),
            count($transactions),
            $avgAmount,
            $totalAmount
        );
    }
    
    echo "\n=== INCOME PATTERNS TO EXCLUDE ===\n";
    
    // Check current subscriptions for any positive amounts
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, billing_cycle, confidence
        FROM subscriptions 
        WHERE user_id = ? AND amount > 0
    ");
    $stmt->execute([$userId]);
    $positiveSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($positiveSubscriptions)) {
        echo "⚠️  FOUND POSITIVE AMOUNT 'SUBSCRIPTIONS' (These should be removed):\n";
        foreach ($positiveSubscriptions as $sub) {
            echo sprintf("❌ %-35s | +€%6.2f | %s\n", 
                $sub['merchant_name'],
                $sub['amount'],
                $sub['billing_cycle']
            );
        }
    } else {
        echo "✅ No positive amount subscriptions found in database\n";
    }
    
    // Identify common income sources
    $incomeKeywords = [
        'salary', 'loon', 'salaris', 'wage', 'pay', 'betaling',
        'transfer', 'overboeking', 'storting', 'deposit',
        'refund', 'terugbetaling', 'restitutie', 'cashback',
        'interest', 'rente', 'dividend', 'bonus',
        'gift', 'geschenk', 'cadeau', 'donation'
    ];
    
    echo "\n=== RECOMMENDED INCOME BLACKLIST ADDITIONS ===\n";
    $incomeBlacklist = [];
    
    foreach ($merchantGroups as $merchant => $transactions) {
        $merchantLower = strtolower($merchant);
        
        // Check if it looks like income
        $isIncome = false;
        foreach ($incomeKeywords as $keyword) {
            if (strpos($merchantLower, $keyword) !== false) {
                $isIncome = true;
                break;
            }
        }
        
        // Also check specific names mentioned by user
        if (strpos($merchantLower, 'doedens') !== false || 
            strpos($merchantLower, 'wiebe') !== false ||
            strpos($merchantLower, 'gruijter') !== false) {
            $isIncome = true;
        }
        
        if ($isIncome) {
            $incomeBlacklist[] = $merchant;
            echo "📥 $merchant (income source)\n";
        }
    }
    
    echo "\n=== IMPLEMENTATION PLAN ===\n";
    echo "1. Add positive amount filter to subscription detection\n";
    echo "2. Add income-related merchants to blacklist\n";
    echo "3. Remove any existing positive subscriptions from database\n";
    echo "4. Test with fresh scan\n\n";
    
    if (!empty($incomeBlacklist)) {
        echo "Merchants to add to blacklist:\n";
        foreach ($incomeBlacklist as $merchant) {
            echo "- '$merchant'\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
