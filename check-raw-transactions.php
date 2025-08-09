<?php
/**
 * QUICK CHECK: Raw Transaction Data
 * Check if raw_transactions table exists and contains data for user 2
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Raw Transaction Data Check</h1>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Check if raw_transactions table exists
    echo "<h2>📊 Table Status</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'raw_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ raw_transactions table exists</p>";
        
        // Check data in table
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM raw_transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>Total transactions for user $userId:</strong> $count</p>";
        
        if ($count > 0) {
            // Show sample transactions
            echo "<h2>📋 Sample Transaction Data</h2>";
            $stmt = $pdo->prepare("
                SELECT * FROM raw_transactions 
                WHERE user_id = ? 
                ORDER BY booking_date DESC 
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Amount</th><th>Currency</th><th>Date</th><th>Merchant</th><th>Description</th><th>Status</th></tr>";
            foreach ($transactions as $tx) {
                echo "<tr>";
                echo "<td>" . $tx['id'] . "</td>";
                echo "<td>" . $tx['amount'] . "</td>";
                echo "<td>" . $tx['currency'] . "</td>";
                echo "<td>" . $tx['booking_date'] . "</td>";
                echo "<td>" . htmlspecialchars($tx['merchant_name'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars(substr($tx['description'] ?? '', 0, 50)) . "...</td>";
                echo "<td>" . $tx['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show outgoing transactions (potential subscriptions)
            echo "<h2>💸 Outgoing Transactions (Potential Subscriptions)</h2>";
            $stmt = $pdo->prepare("
                SELECT * FROM raw_transactions 
                WHERE user_id = ? AND amount < 0 
                ORDER BY booking_date DESC 
                LIMIT 20
            ");
            $stmt->execute([$userId]);
            $outgoing = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Outgoing transactions:</strong> " . count($outgoing) . "</p>";
            
            if (count($outgoing) > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Amount</th><th>Date</th><th>Merchant</th><th>Description</th></tr>";
                foreach ($outgoing as $tx) {
                    echo "<tr>";
                    echo "<td>€" . abs($tx['amount']) . "</td>";
                    echo "<td>" . $tx['booking_date'] . "</td>";
                    echo "<td>" . htmlspecialchars($tx['merchant_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($tx['description'] ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>❌ No outgoing transactions found (this explains why no subscriptions were detected)</p>";
            }
            
        } else {
            echo "<p>❌ No transaction data found for user $userId</p>";
            echo "<p><strong>This means the new transaction processor wasn't used or failed</strong></p>";
        }
        
    } else {
        echo "<p>❌ raw_transactions table does not exist</p>";
        echo "<p><strong>The new transaction processor wasn't integrated properly</strong></p>";
    }
    
    // Also check old payment_history table
    echo "<h2>📜 Legacy Payment History</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_history'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_history WHERE user_id = ?");
        $stmt->execute([$userId]);
        $legacyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p><strong>Legacy payment_history records:</strong> $legacyCount</p>";
    } else {
        echo "<p>No legacy payment_history table</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
