<?php
require_once 'config/db_config.php';

// Force user ID for testing
$userId = 2; // Your user ID from the session

// Get database connection
$pdo = getDBConnection();

// Check for any transactions in bank_transactions
$stmt = $pdo->query("SELECT * FROM bank_transactions LIMIT 10");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check raw_transactions table
$stmt = $pdo->query("SELECT * FROM raw_transactions LIMIT 10");
$rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output results
echo "<h1>Transaction Check</h1>";

echo "<h2>Bank Transactions (first 10)</h2>";
echo "<pre>";
print_r($transactions);
echo "</pre>";

echo "<h2>Raw Transactions (first 10)</h2>";
echo "<pre>";
print_r($rawTransactions);
echo "</pre>";

// Add some basic CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    h1 { color: #333; }
    h2 { color: #444; margin-top: 30px; }
</style>";
