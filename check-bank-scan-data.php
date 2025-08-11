<?php
/**
 * Bank Scan Data Analysis
 * Checks what data we have from the GoCardless bank scan
 */

session_start();
require_once 'includes/database_helper.php';

echo "<h1>Bank Scan Data Analysis</h1>";
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

$userId = $_GET['user_id'] ?? 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Database Tables Available</h2>";
    
    // Check what tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p class='info'>Available tables: " . implode(', ', $tables) . "</p>";
    
    $dataTypes = [
        'subscriptions' => 'Processed subscription patterns detected from transactions',
        'transactions' => 'Raw transaction data from bank accounts',
        'bank_accounts' => 'Connected bank account information',
        'scan_records' => 'Records of bank scan operations',
        'bank_scans' => 'Alternative scan records table'
    ];
    
    foreach ($dataTypes as $table => $description) {
        if (in_array($table, $tables)) {
            echo "<p class='success'>✅ $table - $description</p>";
        } else {
            echo "<p class='warning'>❌ $table - Not found</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Subscription Data (Processed Results)</h2>";
    
    if (in_array('subscriptions', $tables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subCount = $stmt->fetchColumn();
        
        echo "<p class='info'>Total subscriptions for user $userId: <strong>$subCount</strong></p>";
        
        if ($subCount > 0) {
            // Get sample subscription data
            $stmt = $pdo->prepare("
                SELECT merchant_name, amount, currency, billing_cycle, 
                       last_charge_date, confidence, provider, created_at 
                FROM subscriptions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Subscriptions (Last 10):</h3>";
            echo "<table>";
            echo "<tr><th>Merchant</th><th>Amount</th><th>Currency</th><th>Cycle</th><th>Last Charge</th><th>Confidence</th><th>Provider</th><th>Created</th></tr>";
            
            foreach ($subscriptions as $sub) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($sub['merchant_name'] ?? 'N/A') . "</td>";
                echo "<td>€" . number_format($sub['amount'] ?? 0, 2) . "</td>";
                echo "<td>" . htmlspecialchars($sub['currency'] ?? 'EUR') . "</td>";
                echo "<td>" . htmlspecialchars($sub['billing_cycle'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($sub['last_charge_date'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($sub['confidence'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($sub['provider'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($sub['created_at'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>Subscriptions table not found</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Raw Transaction Data</h2>";
    
    if (in_array('transactions', $tables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $transCount = $stmt->fetchColumn();
        
        echo "<p class='info'>Total transactions for user $userId: <strong>$transCount</strong></p>";
        
        if ($transCount > 0) {
            // Get sample transaction data
            $stmt = $pdo->prepare("
                SELECT amount, description, transaction_date, merchant_name, 
                       account_id, currency, created_at 
                FROM transactions 
                WHERE user_id = ? 
                ORDER BY transaction_date DESC 
                LIMIT 15
            ");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Transactions (Last 15):</h3>";
            echo "<table>";
            echo "<tr><th>Date</th><th>Merchant</th><th>Amount</th><th>Description</th><th>Account</th><th>Currency</th></tr>";
            
            foreach ($transactions as $trans) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($trans['transaction_date'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($trans['merchant_name'] ?? 'N/A') . "</td>";
                echo "<td>€" . number_format($trans['amount'] ?? 0, 2) . "</td>";
                echo "<td>" . htmlspecialchars(substr($trans['description'] ?? '', 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars(substr($trans['account_id'] ?? '', -8)) . "</td>";
                echo "<td>" . htmlspecialchars($trans['currency'] ?? 'EUR') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Transaction date range
            $stmt = $pdo->prepare("
                SELECT MIN(transaction_date) as earliest, MAX(transaction_date) as latest 
                FROM transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<div class='highlight'>";
            echo "<h3>Transaction Date Range:</h3>";
            echo "<p><strong>Earliest:</strong> " . ($dateRange['earliest'] ?? 'N/A') . "</p>";
            echo "<p><strong>Latest:</strong> " . ($dateRange['latest'] ?? 'N/A') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>Transactions table not found</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. Bank Account Information</h2>";
    
    if (in_array('bank_accounts', $tables)) {
        $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='info'>Connected bank accounts: <strong>" . count($bankAccounts) . "</strong></p>";
        
        if (!empty($bankAccounts)) {
            echo "<table>";
            echo "<tr><th>Account ID</th><th>Provider</th><th>Status</th><th>Created</th></tr>";
            
            foreach ($bankAccounts as $account) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(substr($account['account_id'] ?? '', -12)) . "</td>";
                echo "<td>" . htmlspecialchars($account['provider'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($account['status'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($account['created_at'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>Bank accounts table not found</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Scan Records</h2>";
    
    // Check both possible scan tables
    $scanTables = ['scan_records', 'bank_scans'];
    $foundScans = false;
    
    foreach ($scanTables as $scanTable) {
        if (in_array($scanTable, $tables)) {
            $stmt = $pdo->prepare("SELECT * FROM $scanTable WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$userId]);
            $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($scans)) {
                $foundScans = true;
                echo "<h3>$scanTable:</h3>";
                echo "<table>";
                echo "<tr>";
                foreach (array_keys($scans[0]) as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                
                foreach ($scans as $scan) {
                    echo "<tr>";
                    foreach ($scan as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    if (!$foundScans) {
        echo "<p class='warning'>No scan records found in any table</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>6. Data Summary</h2>";
    echo "<div class='highlight'>";
    echo "<h3>📊 What Data Do We Have?</h3>";
    
    if (in_array('subscriptions', $tables) && in_array('transactions', $tables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $transCount = $stmt->fetchColumn();
        
        echo "<ul>";
        echo "<li><strong>✅ Processed Subscriptions:</strong> $subCount subscription patterns detected</li>";
        echo "<li><strong>✅ Raw Transaction Data:</strong> $transCount individual transactions stored</li>";
        echo "<li><strong>✅ Full Historical Data:</strong> Complete transaction history available for analysis</li>";
        echo "<li><strong>✅ Re-analysis Possible:</strong> Can re-run subscription detection on raw data anytime</li>";
        echo "</ul>";
        
        echo "<div style='background-color: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🎉 Great News!</h4>";
        echo "<p><strong>You have BOTH the raw transaction data AND the processed subscriptions!</strong></p>";
        echo "<p>This means you can:</p>";
        echo "<ul>";
        echo "<li>View detailed transaction history</li>";
        echo "<li>Re-analyze data with improved algorithms</li>";
        echo "<li>Export complete financial data</li>";
        echo "<li>Track spending patterns over time</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<p class='warning'>Missing some data tables - may only have partial information</p>";
    }
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Analysis</a></p>";
?>
