<?php
/**
 * VIEW ALL RAW TRANSACTION DATA
 * Simple viewer to see exactly what data we're storing from GoCardless
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Raw Transaction Data Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .transaction { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
        .raw-json { background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap; }
        .summary { background: #e8f4f8; padding: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>";

echo "<h1>🗄️ Raw Transaction Data Viewer</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    // Get summary stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN raw_data IS NOT NULL THEN 1 END) as with_raw_data,
            MIN(booking_date) as oldest_date,
            MAX(booking_date) as newest_date,
            COUNT(DISTINCT account_id) as unique_accounts
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='summary'>";
    echo "<h2>📊 Data Summary</h2>";
    echo "<ul>";
    echo "<li><strong>Total Transactions:</strong> {$stats['total_transactions']}</li>";
    echo "<li><strong>With Raw Data:</strong> {$stats['with_raw_data']}</li>";
    echo "<li><strong>Date Range:</strong> {$stats['oldest_date']} to {$stats['newest_date']}</li>";
    echo "<li><strong>Unique Accounts:</strong> {$stats['unique_accounts']}</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($stats['total_transactions'] == 0) {
        echo "<p>❌ No transaction data found. Run a bank scan first.</p>";
        echo "</body></html>";
        exit;
    }
    
    // Get recent transactions with raw data
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    echo "<h2>🔍 Recent Transactions (showing $limit)</h2>";
    echo "<p><a href='?limit=5'>Show 5</a> | <a href='?limit=10'>Show 10</a> | <a href='?limit=25'>Show 25</a> | <a href='?limit=50'>Show 50</a></p>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, account_id, transaction_id, amount, currency, booking_date, 
            merchant_name, description, raw_data, created_at
        FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY booking_date DESC, created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($transactions as $i => $transaction) {
        echo "<div class='transaction'>";
        echo "<h3>Transaction " . ($i + 1) . "</h3>";
        
        echo "<p><strong>Basic Info:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Date:</strong> {$transaction['booking_date']}</li>";
        echo "<li><strong>Amount:</strong> {$transaction['currency']} {$transaction['amount']}</li>";
        echo "<li><strong>Merchant:</strong> " . htmlspecialchars($transaction['merchant_name'] ?? 'N/A') . "</li>";
        echo "<li><strong>Description:</strong> " . htmlspecialchars($transaction['description'] ?? 'N/A') . "</li>";
        echo "<li><strong>Account ID:</strong> " . htmlspecialchars($transaction['account_id']) . "</li>";
        echo "</ul>";
        
        if ($transaction['raw_data']) {
            echo "<p><strong>Raw JSON Data from GoCardless:</strong></p>";
            echo "<div class='raw-json'>";
            
            $rawData = json_decode($transaction['raw_data'], true);
            if ($rawData) {
                echo htmlspecialchars(json_encode($rawData, JSON_PRETTY_PRINT));
            } else {
                echo htmlspecialchars($transaction['raw_data']);
            }
            echo "</div>";
        } else {
            echo "<p><strong>⚠️ No raw data stored for this transaction</strong></p>";
        }
        
        echo "</div>";
    }
    
    echo "<h2>🔧 Data Analysis Tools</h2>";
    echo "<ul>";
    echo "<li><a href='debug-scan-results.php'>View Scan Results</a></li>";
    echo "<li><a href='debug-gocardless-transactions.php'>GoCardless Transaction Debug</a></li>";
    echo "<li><a href='debug-transaction-structure.php'>Transaction Structure Debug</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
