<?php
/**
 * Check Alternative Transaction Tables
 * Investigate bank_transactions and raw_transactions tables
 */

session_start();
require_once 'includes/database_helper.php';

echo "<h1>Alternative Transaction Tables Analysis</h1>";
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
</style>";

$userId = $_GET['user_id'] ?? 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. bank_transactions Table</h2>";
    
    // Check bank_transactions table
    try {
        $stmt = $pdo->query("DESCRIBE bank_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Schema:</h3>";
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
        
        // Check data count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        echo "<p class='info'>Total records for user $userId: <strong>$count</strong></p>";
        
        if ($count > 0) {
            // Get sample data
            $stmt = $pdo->prepare("SELECT * FROM bank_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Data (Last 10):</h3>";
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($transactions[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            
            foreach ($transactions as $trans) {
                echo "<tr>";
                foreach ($trans as $value) {
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        echo "<td>€" . number_format($value, 2) . "</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
            
            // Date range
            $stmt = $pdo->prepare("
                SELECT MIN(transaction_date) as earliest, MAX(transaction_date) as latest 
                FROM bank_transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dateRange['earliest']) {
                echo "<div class='highlight'>";
                echo "<h3>Date Range:</h3>";
                echo "<p><strong>Earliest:</strong> " . $dateRange['earliest'] . "</p>";
                echo "<p><strong>Latest:</strong> " . $dateRange['latest'] . "</p>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Error accessing bank_transactions: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. raw_transactions Table</h2>";
    
    // Check raw_transactions table
    try {
        $stmt = $pdo->query("DESCRIBE raw_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Schema:</h3>";
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
        
        // Check data count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM raw_transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        echo "<p class='info'>Total records for user $userId: <strong>$count</strong></p>";
        
        if ($count > 0) {
            // Get sample data
            $stmt = $pdo->prepare("SELECT * FROM raw_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Data (Last 10):</h3>";
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($transactions[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            
            foreach ($transactions as $trans) {
                echo "<tr>";
                foreach ($trans as $value) {
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        echo "<td>€" . number_format($value, 2) . "</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Error accessing raw_transactions: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Data Summary</h2>";
    echo "<div class='highlight'>";
    echo "<h3>🎯 Complete Data Picture:</h3>";
    
    // Get counts from all relevant tables
    $dataSummary = [];
    
    $tables = ['subscriptions', 'bank_transactions', 'raw_transactions', 'bank_scans'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
            $stmt->execute([$userId]);
            $dataSummary[$table] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $dataSummary[$table] = 0;
        }
    }
    
    echo "<ul>";
    echo "<li><strong>✅ Subscriptions:</strong> {$dataSummary['subscriptions']} detected patterns</li>";
    echo "<li><strong>✅ Bank Transactions:</strong> {$dataSummary['bank_transactions']} records</li>";
    echo "<li><strong>✅ Raw Transactions:</strong> {$dataSummary['raw_transactions']} records</li>";
    echo "<li><strong>✅ Bank Scans:</strong> {$dataSummary['bank_scans']} scan operations</li>";
    echo "</ul>";
    
    $totalTransactions = $dataSummary['bank_transactions'] + $dataSummary['raw_transactions'];
    
    if ($totalTransactions > 0) {
        echo "<div style='background-color: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🎉 Excellent News!</h4>";
        echo "<p><strong>You have COMPLETE data from your bank scan!</strong></p>";
        echo "<p>Total transaction records: <strong>$totalTransactions</strong></p>";
        echo "<p>This means you can:</p>";
        echo "<ul>";
        echo "<li>✅ Export complete transaction history</li>";
        echo "<li>✅ Re-analyze for better subscription detection</li>";
        echo "<li>✅ View detailed spending patterns</li>";
        echo "<li>✅ Track all financial activity over time</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>⚠️ Limited Data</h4>";
        echo "<p>Only processed subscriptions available - no raw transaction data found</p>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Analysis</a></p>";
?>
