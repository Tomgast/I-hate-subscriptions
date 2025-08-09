<?php
/**
 * CLEANUP TEST DATA
 * Remove test Netflix data and show real bank data
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Test Data</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>";

echo "<h1>🧹 Cleanup Test Data</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>1. Current Data Analysis</h2>";
    
    // Show current data
    $stmt = $pdo->prepare("
        SELECT 
            id, account_id, transaction_id, amount, currency, booking_date, 
            merchant_name, description, created_at
        FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $allTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total transactions found:</strong> " . count($allTransactions) . "</p>";
    
    if (empty($allTransactions)) {
        echo "<p class='warning'>⚠️ No transaction data found at all</p>";
        echo "<p>This means either:</p>";
        echo "<ul>";
        echo "<li>No bank scan has been completed yet</li>";
        echo "<li>Real bank data failed to store</li>";
        echo "<li>Data is stored in a different table</li>";
        echo "</ul>";
        echo "</body></html>";
        exit;
    }
    
    // Identify test data
    $testData = [];
    $realData = [];
    
    foreach ($allTransactions as $transaction) {
        if (
            $transaction['account_id'] === 'test-account' ||
            $transaction['merchant_name'] === 'Netflix' ||
            $transaction['transaction_id'] === 'test-123' ||
            strpos($transaction['account_id'], 'test') !== false
        ) {
            $testData[] = $transaction;
        } else {
            $realData[] = $transaction;
        }
    }
    
    echo "<h3>📊 Data Breakdown:</h3>";
    echo "<ul>";
    echo "<li><strong>Test Data:</strong> " . count($testData) . " transactions</li>";
    echo "<li><strong>Real Data:</strong> " . count($realData) . " transactions</li>";
    echo "</ul>";
    
    if (!empty($testData)) {
        echo "<h3>🧪 Test Data Found:</h3>";
        foreach ($testData as $i => $t) {
            echo "<div style='border: 1px solid red; padding: 10px; margin: 5px 0; background: #ffe6e6;'>";
            echo "<strong>Test Transaction " . ($i + 1) . ":</strong><br>";
            echo "Account: " . htmlspecialchars($t['account_id']) . "<br>";
            echo "Merchant: " . htmlspecialchars($t['merchant_name'] ?? 'N/A') . "<br>";
            echo "Amount: " . $t['currency'] . " " . $t['amount'] . "<br>";
            echo "Date: " . $t['booking_date'] . "<br>";
            echo "Created: " . $t['created_at'] . "<br>";
            echo "</div>";
        }
    }
    
    if (!empty($realData)) {
        echo "<h3>✅ Real Data Found:</h3>";
        foreach ($realData as $i => $t) {
            echo "<div style='border: 1px solid green; padding: 10px; margin: 5px 0; background: #e6ffe6;'>";
            echo "<strong>Real Transaction " . ($i + 1) . ":</strong><br>";
            echo "Account: " . htmlspecialchars($t['account_id']) . "<br>";
            echo "Merchant: " . htmlspecialchars($t['merchant_name'] ?? 'N/A') . "<br>";
            echo "Amount: " . $t['currency'] . " " . $t['amount'] . "<br>";
            echo "Date: " . $t['booking_date'] . "<br>";
            echo "Created: " . $t['created_at'] . "<br>";
            echo "</div>";
        }
    } else {
        echo "<h3 class='error'>❌ No Real Data Found</h3>";
        echo "<p>This means your real bank data is not being stored properly.</p>";
    }
    
    // Cleanup option
    if (!empty($testData)) {
        echo "<h2>2. Cleanup Test Data</h2>";
        
        if (isset($_POST['cleanup_test_data'])) {
            echo "<p>🧹 Cleaning up test data...</p>";
            
            $stmt = $pdo->prepare("
                DELETE FROM raw_transactions 
                WHERE user_id = ? 
                AND (
                    account_id = 'test-account' 
                    OR merchant_name = 'Netflix' 
                    OR transaction_id = 'test-123'
                    OR account_id LIKE '%test%'
                )
            ");
            $stmt->execute([$userId]);
            $deletedCount = $stmt->rowCount();
            
            echo "<p class='success'>✅ Deleted $deletedCount test transactions</p>";
            echo "<p><a href='cleanup-test-data.php'>Refresh to see updated data</a></p>";
            
        } else {
            echo "<form method='post'>";
            echo "<p class='warning'>⚠️ Found " . count($testData) . " test transactions that should be removed.</p>";
            echo "<button type='submit' name='cleanup_test_data' style='background: red; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🗑️ Delete Test Data";
            echo "</button>";
            echo "</form>";
        }
    }
    
    echo "<h2>3. Next Steps</h2>";
    
    if (empty($realData)) {
        echo "<div style='border: 2px solid orange; padding: 15px; background: #fff3cd;'>";
        echo "<h3>🔧 Action Required: Get Real Bank Data</h3>";
        echo "<p>You need to run a fresh bank scan to get your real transaction data:</p>";
        echo "<ol>";
        echo "<li><a href='bank/unified-scan.php'>Go to Bank Scan</a></li>";
        echo "<li>Connect to your real bank account</li>";
        echo "<li>Complete the authentication process</li>";
        echo "<li>Wait for the scan to complete</li>";
        echo "<li>Come back here to verify real data is stored</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='border: 2px solid green; padding: 15px; background: #d4edda;'>";
        echo "<h3>✅ Real Data Found!</h3>";
        echo "<p>You have " . count($realData) . " real transactions. Now you can:</p>";
        echo "<ul>";
        echo "<li><a href='view-raw-data.php'>View all raw transaction data</a></li>";
        echo "<li><a href='debug-gocardless-transactions.php'>Test subscription detection</a></li>";
        echo "<li><a href='dashboard.php'>Go to dashboard</a></li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
