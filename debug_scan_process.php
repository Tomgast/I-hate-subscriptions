<?php
/**
 * DEBUG SCRIPT: Bank Scan Process Analysis
 * This script helps debug why subscriptions aren't being generated from bank transactions
 */

require_once 'config/db_config.php';

// Get database connection
$pdo = getDBConnection();

// Get user ID from session or use default
$userId = 2; // Default to user 2 if not in session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}

echo "<h1>🔍 Bank Scan Process Debugger</h1>";
echo "<p>User ID: $userId</p>";

try {
    // 1. Check bank connections
    echo "<h2>1. Bank Connections</h2>";
    $stmt = $pdo->prepare("SELECT * FROM bank_connections WHERE user_id = ?");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p>No bank connections found for this user.</p>";
    } else {
        echo "<p>Found " . count($connections) . " bank connections:</p>";
        echo "<pre>" . print_r($connections, true) . "</pre>";
    }
    
    // 2. Check bank scans
    echo "<h2>2. Bank Scans</h2>";
    $stmt = $pdo->prepare("SELECT * FROM bank_scans WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scans)) {
        echo "<p>No bank scans found for this user.</p>";
    } else {
        echo "<p>Found " . count($scans) . " bank scans:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Created At</th><th>Status</th><th>Subs Found</th><th>Provider</th></tr>";
        foreach ($scans as $scan) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($scan['id']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['status']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['subscriptions_found']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['provider']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check raw transactions
    echo "<h2>3. Raw Transactions</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM raw_transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $rawCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($rawCount == 0) {
        echo "<p>No raw transactions found for this user.</p>";
    } else {
        echo "<p>Found $rawCount raw transactions.</p>";
        
        // Show sample of raw transactions
        $stmt = $pdo->prepare("SELECT * FROM raw_transactions WHERE user_id = ? ORDER BY booking_date DESC LIMIT 5");
        $stmt->execute([$userId]);
        $sampleTx = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Raw Transactions</h3>";
        echo "<pre>" . print_r($sampleTx, true) . "</pre>";
    }
    
    // 4. Check processed transactions
    echo "<h2>4. Processed Transactions</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $processedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($processedCount == 0) {
        echo "<p>No processed transactions found for this user.</p>";
    } else {
        echo "<p>Found $processedCount processed transactions.</p>";
        
        // Show sample of processed transactions
        $stmt = $pdo->prepare("SELECT * FROM bank_transactions WHERE user_id = ? ORDER BY booking_date DESC LIMIT 5");
        $stmt->execute([$userId]);
        $sampleProcessed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Processed Transactions</h3>";
        echo "<pre>" . print_r($sampleProcessed, true) . "</pre>";
    }
    
    // 5. Check subscriptions
    echo "<h2>5. Subscriptions</h2>";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "<p>No subscriptions found for this user.</p>";
    } else {
        echo "<p>Found " . count($subscriptions) . " subscriptions:</p>";
        echo "<pre>" . print_r($subscriptions, true) . "</pre>";
    }
    
    // 6. Check for errors in the logs
    echo "<h2>6. Error Logs</h2>";
    $logFile = dirname(__DIR__) . '/logs/error.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logLines = array_slice(explode("\n", $logContent), -50); // Get last 50 lines
        echo "<h3>Recent Error Logs</h3>";
        echo "<pre>" . implode("\n", $logLines) . "</pre>";
    } else {
        echo "<p>No error log file found at: $logFile</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Add some basic styling
echo "<style>
    body { 
        font-family: Arial, sans-serif; 
        line-height: 1.6; 
        margin: 20px; 
        padding: 20px; 
        max-width: 1200px; 
        margin: 0 auto;
    }
    h1 { color: #2c3e50; }
    h2 { 
        color: #3498db; 
        margin-top: 30px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }
    h3 { color: #7f8c8d; margin-top: 20px; }
    pre {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        overflow-x: auto;
        font-size: 13px;
        border: 1px solid #e1e4e8;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin: 15px 0;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
</style>";
?>
