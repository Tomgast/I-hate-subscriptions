<?php
/**
 * GoCardless Debug Log Viewer
 * Shows recent GoCardless activity and subscription detection logs
 */

session_start();
require_once 'includes/database_helper.php';

echo "<h1>GoCardless Debug Log Viewer</h1>";
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
    .log-entry { margin: 5px 0; padding: 5px; border-left: 3px solid #ddd; }
    .log-error { border-left-color: red; }
    .log-success { border-left-color: green; }
    .log-info { border-left-color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Get user ID
$userId = $_GET['user_id'] ?? 1;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Recent Subscriptions Detected</h2>";
    
    // Get recent subscriptions for this user
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Found " . count($subscriptions) . " subscriptions for user $userId</p>";
    
    if (!empty($subscriptions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Merchant</th><th>Amount</th><th>Frequency</th><th>Status</th><th>Created</th></tr>";
        
        $zeroAmountCount = 0;
        foreach ($subscriptions as $sub) {
            $amount = $sub['amount'] ?? 0;
            if ($amount == 0) $zeroAmountCount++;
            
            $rowClass = $amount == 0 ? 'style="background-color: #ffebee;"' : '';
            echo "<tr $rowClass>";
            echo "<td>{$sub['id']}</td>";
            echo "<td>{$sub['merchant_name']}</td>";
            echo "<td>€" . number_format($amount, 2) . "</td>";
            echo "<td>{$sub['frequency']}</td>";
            echo "<td>{$sub['status']}</td>";
            echo "<td>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($zeroAmountCount > 0) {
            echo "<div class='highlight'>";
            echo "<p class='error'><strong>⚠️ Found $zeroAmountCount subscriptions with €0.00 amount!</strong></p>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Recent Bank Accounts</h2>";
    
    // Get recent bank accounts
    $stmt = $pdo->prepare("
        SELECT * FROM bank_accounts 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($bankAccounts)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Account ID</th><th>Provider</th><th>Status</th><th>Created</th></tr>";
        foreach ($bankAccounts as $account) {
            echo "<tr>";
            echo "<td>{$account['id']}</td>";
            echo "<td>{$account['account_id']}</td>";
            echo "<td>{$account['provider']}</td>";
            echo "<td>{$account['status']}</td>";
            echo "<td>{$account['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No bank accounts found</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Recent Scan Records</h2>";
    
    // Get recent scan records
    $stmt = $pdo->prepare("
        SELECT * FROM scan_records 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $scanRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($scanRecords)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Provider</th><th>Status</th><th>Subscriptions Found</th><th>Created</th></tr>";
        foreach ($scanRecords as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['provider']}</td>";
            echo "<td>{$record['status']}</td>";
            echo "<td>{$record['subscriptions_found']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No scan records found</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. PHP Error Log (Recent Entries)</h2>";
    
    // Try to read PHP error log
    $logFiles = [
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        '/var/log/php/error.log',
        '/tmp/php_errors.log',
        ini_get('error_log'),
        'error_log'
    ];
    
    $logFound = false;
    foreach ($logFiles as $logFile) {
        if ($logFile && file_exists($logFile) && is_readable($logFile)) {
            echo "<h3>Log file: $logFile</h3>";
            $logContent = file_get_contents($logFile);
            $lines = explode("\n", $logContent);
            $recentLines = array_slice($lines, -50); // Last 50 lines
            
            // Filter for GoCardless-related entries
            $gocardlessLines = array_filter($recentLines, function($line) {
                return stripos($line, 'gocardless') !== false || 
                       stripos($line, 'subscription') !== false ||
                       stripos($line, 'array_count_values') !== false ||
                       stripos($line, 'max()') !== false;
            });
            
            if (!empty($gocardlessLines)) {
                echo "<div style='max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px;'>";
                foreach ($gocardlessLines as $line) {
                    $class = 'log-info';
                    if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                        $class = 'log-error';
                    } elseif (stripos($line, 'success') !== false) {
                        $class = 'log-success';
                    }
                    echo "<div class='log-entry $class'>" . htmlspecialchars($line) . "</div>";
                }
                echo "</div>";
                $logFound = true;
                break;
            }
        }
    }
    
    if (!$logFound) {
        echo "<p class='warning'>Could not access PHP error logs. Check Plesk logs manually for GoCardless entries.</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Manual Log Check Instructions</h2>";
    echo "<div class='highlight'>";
    echo "<h3>To check Plesk logs manually:</h3>";
    echo "<ol>";
    echo "<li>Go to Plesk Panel → Websites & Domains</li>";
    echo "<li>Click on your domain</li>";
    echo "<li>Go to 'Logs' section</li>";
    echo "<li>Look for PHP Error Logs</li>";
    echo "<li>Search for entries containing 'GoCardless' or 'subscription'</li>";
    echo "</ol>";
    echo "<p><strong>Look for entries around the time you connected the bank account (recent entries).</strong></p>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>6. Quick Analysis</h2>";
echo "<p class='info'>Based on the data above, here's what to look for:</p>";
echo "<ul>";
echo "<li><strong>Zero Amount Subscriptions:</strong> If many subscriptions show €0.00, there's likely an issue with amount processing</li>";
echo "<li><strong>Error Log Entries:</strong> Look for GoCardless-related errors in the logs</li>";
echo "<li><strong>Scan Records:</strong> Check if the scan completed successfully</li>";
echo "<li><strong>Bank Account Status:</strong> Verify the bank account is properly connected</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Data</a></p>";
?>
