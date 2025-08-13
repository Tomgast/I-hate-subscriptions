<?php
/**
 * Analyze raw transaction data to find indicators of incoming vs outgoing transactions
 * Focus on finding patterns that distinguish income from expenses
 */

require_once __DIR__ . '/config/db_config.php';

echo "=== ANALYZING TRANSACTION DIRECTION INDICATORS ===\n\n";

$pdo = getDBConnection();

// Get the specific problematic transactions (DOEDENS A and WIEBE DE GRUIJTER)
echo "--- ANALYZING PROBLEMATIC INCOME TRANSACTIONS ---\n";
$stmt = $pdo->prepare("
    SELECT merchant_name, amount, currency, description, creditor_name, debtor_name, 
           raw_data, booking_date, bank_transaction_code
    FROM raw_transactions 
    WHERE user_id = 2 
    AND (merchant_name LIKE '%DOEDENS%' OR merchant_name LIKE '%WIEBE%')
    ORDER BY booking_date DESC
    LIMIT 5
");
$stmt->execute();
$incomeTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($incomeTransactions as $trans) {
    echo "\n=== INCOME TRANSACTION: {$trans['merchant_name']} ===\n";
    echo "Amount: {$trans['amount']} {$trans['currency']}\n";
    echo "Creditor: " . ($trans['creditor_name'] ?? 'NULL') . "\n";
    echo "Debtor: " . ($trans['debtor_name'] ?? 'NULL') . "\n";
    echo "Bank Code: " . ($trans['bank_transaction_code'] ?? 'NULL') . "\n";
    echo "Description: " . substr($trans['description'], 0, 200) . "...\n";
    
    // Parse raw JSON data for more clues
    if ($trans['raw_data']) {
        $rawData = json_decode($trans['raw_data'], true);
        if ($rawData) {
            echo "Raw Data Keys: " . implode(', ', array_keys($rawData)) . "\n";
            if (isset($rawData['proprietaryBankTransactionCode'])) {
                echo "Bank Transaction Type: {$rawData['proprietaryBankTransactionCode']}\n";
            }
            if (isset($rawData['creditorAccount'])) {
                echo "Creditor Account: " . json_encode($rawData['creditorAccount']) . "\n";
            }
            if (isset($rawData['debtorAccount'])) {
                echo "Debtor Account: " . json_encode($rawData['debtorAccount']) . "\n";
            }
        }
    }
    echo str_repeat("-", 60) . "\n";
}

// Now compare with legitimate outgoing subscriptions
echo "\n--- ANALYZING LEGITIMATE OUTGOING SUBSCRIPTIONS ---\n";
$stmt = $pdo->prepare("
    SELECT merchant_name, amount, currency, description, creditor_name, debtor_name, 
           raw_data, booking_date, bank_transaction_code
    FROM raw_transactions 
    WHERE user_id = 2 
    AND (merchant_name LIKE '%Spotify%' OR merchant_name LIKE '%KPN%' OR merchant_name LIKE '%Amazon%')
    ORDER BY booking_date DESC
    LIMIT 5
");
$stmt->execute();
$expenseTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expenseTransactions as $trans) {
    echo "\n=== EXPENSE TRANSACTION: {$trans['merchant_name']} ===\n";
    echo "Amount: {$trans['amount']} {$trans['currency']}\n";
    echo "Creditor: " . ($trans['creditor_name'] ?? 'NULL') . "\n";
    echo "Debtor: " . ($trans['debtor_name'] ?? 'NULL') . "\n";
    echo "Bank Code: " . ($trans['bank_transaction_code'] ?? 'NULL') . "\n";
    echo "Description: " . substr($trans['description'], 0, 200) . "...\n";
    
    // Parse raw JSON data for more clues
    if ($trans['raw_data']) {
        $rawData = json_decode($trans['raw_data'], true);
        if ($rawData) {
            echo "Raw Data Keys: " . implode(', ', array_keys($rawData)) . "\n";
            if (isset($rawData['proprietaryBankTransactionCode'])) {
                echo "Bank Transaction Type: {$rawData['proprietaryBankTransactionCode']}\n";
            }
            if (isset($rawData['creditorAccount'])) {
                echo "Creditor Account: " . json_encode($rawData['creditorAccount']) . "\n";
            }
            if (isset($rawData['debtorAccount'])) {
                echo "Debtor Account: " . json_encode($rawData['debtorAccount']) . "\n";
            }
        }
    }
    echo str_repeat("-", 60) . "\n";
}

// Look for patterns in amount signs and transaction structure
echo "\n--- AMOUNT SIGN ANALYSIS ---\n";
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN amount > 0 THEN 'POSITIVE' 
            WHEN amount < 0 THEN 'NEGATIVE' 
            ELSE 'ZERO' 
        END as amount_sign,
        COUNT(*) as count,
        AVG(amount) as avg_amount
    FROM raw_transactions 
    WHERE user_id = 2 
    GROUP BY amount_sign
");
$stmt->execute();
$signAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($signAnalysis as $analysis) {
    echo "{$analysis['amount_sign']}: {$analysis['count']} transactions, avg €{$analysis['avg_amount']}\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "Look for patterns in:\n";
echo "1. Amount signs (positive vs negative)\n";
echo "2. Creditor vs Debtor fields\n";
echo "3. Bank transaction codes\n";
echo "4. Account information in raw data\n";
?>
