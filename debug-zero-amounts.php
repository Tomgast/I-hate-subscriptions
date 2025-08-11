<?php
/**
 * Debug Zero Amount Subscriptions Issue
 * Investigates why GoCardless is detecting subscriptions with €0 cost
 */

session_start();
require_once 'includes/database_helper.php';
require_once 'includes/gocardless_financial_service.php';

echo "<h1>Zero Amount Subscriptions Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { color: red; }
    .success { color: green; }
    .info { color: blue; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: #fff3cd; padding: 10px; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

$userId = $_GET['user_id'] ?? 1;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Subscription Amount Analysis</h2>";
    
    // Get all subscriptions with their amounts
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, frequency, created_at, 
               COUNT(*) as count
        FROM subscriptions 
        WHERE user_id = ? 
        GROUP BY merchant_name, amount, frequency
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $zeroCount = 0;
    $nonZeroCount = 0;
    
    echo "<table>";
    echo "<tr><th>Merchant</th><th>Amount</th><th>Frequency</th><th>Count</th><th>Created</th></tr>";
    
    foreach ($subscriptions as $sub) {
        $amount = floatval($sub['amount']);
        if ($amount == 0) {
            $zeroCount += $sub['count'];
            $rowStyle = 'style="background-color: #ffebee;"';
        } else {
            $nonZeroCount += $sub['count'];
            $rowStyle = 'style="background-color: #e8f5e8;"';
        }
        
        echo "<tr $rowStyle>";
        echo "<td>{$sub['merchant_name']}</td>";
        echo "<td>€" . number_format($amount, 2) . "</td>";
        echo "<td>{$sub['frequency']}</td>";
        echo "<td>{$sub['count']}</td>";
        echo "<td>{$sub['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='highlight'>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li class='error'>Zero amount subscriptions: $zeroCount</li>";
    echo "<li class='success'>Non-zero amount subscriptions: $nonZeroCount</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Raw Transaction Data Sample</h2>";
    
    // Let's look at some raw transaction data to see what amounts are being processed
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE user_id = ? 
        ORDER BY transaction_date DESC 
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($transactions)) {
        echo "<p class='info'>Sample of recent transactions:</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Amount</th><th>Description</th><th>Date</th><th>Merchant</th></tr>";
        
        foreach ($transactions as $trans) {
            $amount = $trans['amount'] ?? 0;
            echo "<tr>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>€" . number_format($amount, 2) . "</td>";
            echo "<td>" . htmlspecialchars($trans['description'] ?? '') . "</td>";
            echo "<td>{$trans['transaction_date']}</td>";
            echo "<td>" . htmlspecialchars($trans['merchant_name'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No transactions found in database</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Test Subscription Detection Logic</h2>";
    
    if (!empty($transactions)) {
        echo "<p class='info'>Testing subscription detection on sample transactions...</p>";
        
        // Group transactions by merchant for testing
        $merchantGroups = [];
        foreach ($transactions as $trans) {
            $merchant = $trans['merchant_name'] ?? 'Unknown';
            if (!isset($merchantGroups[$merchant])) {
                $merchantGroups[$merchant] = [];
            }
            $merchantGroups[$merchant][] = $trans;
        }
        
        echo "<table>";
        echo "<tr><th>Merchant</th><th>Transaction Count</th><th>Amounts Found</th><th>Detection Result</th></tr>";
        
        foreach ($merchantGroups as $merchant => $merchantTransactions) {
            if (count($merchantTransactions) >= 2) {
                $amounts = array_column($merchantTransactions, 'amount');
                $uniqueAmounts = array_unique($amounts);
                
                // Test our fixed algorithm
                $amountStrings = array_map(function($amount) {
                    return (string)$amount;
                }, $amounts);
                $amountCounts = array_count_values($amountStrings);
                
                $result = "No pattern";
                $detectedAmount = 0;
                
                if (!empty($amountCounts)) {
                    $recurringAmountString = array_keys($amountCounts, max($amountCounts))[0];
                    $detectedAmount = (float)$recurringAmountString;
                    $occurrences = $amountCounts[$recurringAmountString];
                    $result = "€" . number_format($detectedAmount, 2) . " ({$occurrences}x)";
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($merchant) . "</td>";
                echo "<td>" . count($merchantTransactions) . "</td>";
                echo "<td>" . implode(', ', array_map(function($a) { return '€' . number_format($a, 2); }, $uniqueAmounts)) . "</td>";
                echo "<td>$result</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. Potential Issues to Check</h2>";
    echo "<div class='highlight'>";
    echo "<h3>🔍 Common Causes of Zero Amount Subscriptions:</h3>";
    echo "<ol>";
    echo "<li><strong>Data Type Issues:</strong> Amount field stored as string but processed as number</li>";
    echo "<li><strong>Currency Conversion:</strong> Amounts in wrong currency or format</li>";
    echo "<li><strong>Null/Empty Values:</strong> Missing amount data defaulting to 0</li>";
    echo "<li><strong>Processing Logic:</strong> Bug in subscription detection algorithm</li>";
    echo "<li><strong>GoCardless API:</strong> API returning amounts in unexpected format</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Database Schema Check</h2>";
    
    // Check the actual database schema
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Subscriptions table schema:</p>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Analysis</a></p>";
?>
