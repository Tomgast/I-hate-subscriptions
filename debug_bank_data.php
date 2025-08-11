<?php
require_once 'config/db_config.php';
session_start();

// Force user ID for testing (replace with actual user ID if needed)
$userId = 1; // You may need to update this with the actual user ID

// Get database connection
$pdo = getDBConnection();

// Function to print results in a readable format
function printResults($title, $results) {
    echo "<h2>$title</h2>";
    if (empty($results)) {
        echo "<p>No results found.</p>";
        return;
    }
    echo "<pre>";
    print_r($results);
    echo "</pre>";
}

// Check database tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<h1>Database Tables</h1>";
printResults("Available Tables", $tables);

// Check bank connections
$stmt = $pdo->prepare("SELECT * FROM bank_connections WHERE user_id = ?");
$stmt->execute([$userId]);
$connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

printResults("Bank Connections", $connections);

// Check transactions if we have connections
if (!empty($connections)) {
    foreach ($connections as $connection) {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE bank_connection_id = ? ORDER BY date DESC LIMIT 10");
        $stmt->execute([$connection['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        printResults("Transactions for Connection: " . $connection['id'], $transactions);
    }
}

// Check subscriptions
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
printResults("Subscriptions", $subscriptions);

// Check bank scans
$stmt = $pdo->prepare("SELECT * FROM bank_scans WHERE user_id = ? ORDER BY started_at DESC");
$stmt->execute([$userId]);
$scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
printResults("Bank Scans", $scans);

// Check if we have a transactions table
if (in_array('transactions', $tables)) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<h2>Total Transactions in Database: " . $count . "</h2>";
}

echo "<h2>PHP Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Add a form to manually trigger a scan
if (!empty($connections)) {
    echo "<h2>Manual Actions</h2>";
    echo "<form method='post' action='bank/scan.php'>";
    echo "<input type='hidden' name='action' value='scan'>";
    echo "<button type='submit'>Trigger Manual Scan</button>";
    echo "</form>";
}

// Add some basic CSS for readability
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    h1 { color: #333; }
    h2 { color: #444; margin-top: 30px; }
    button { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #45a049; }
</style>";
