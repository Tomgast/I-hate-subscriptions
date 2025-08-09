<?php
/**
 * DEBUG BANK CONNECTION AND DATA FLOW
 * Trace what happens during bank connection and subscription scan
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Bank Connection</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Debug Bank Connection & Data Flow</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Check Bank Connections</h2>";
    
    $stmt = $pdo->prepare("
        SELECT id, provider, status, account_id, created_at, connection_data
        FROM bank_connections 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p class='error'>❌ No bank connections found</p>";
        echo "<p>You need to connect a bank account first:</p>";
        echo "<p><a href='bank/unified-scan.php'>Go to Bank Scan</a></p>";
    } else {
        echo "<p class='success'>✅ Found " . count($connections) . " bank connection(s)</p>";
        
        foreach ($connections as $i => $conn) {
            echo "<div style='border: 1px solid blue; padding: 10px; margin: 5px 0; background: #e6f3ff;'>";
            echo "<strong>Connection " . ($i + 1) . ":</strong><br>";
            echo "Provider: " . htmlspecialchars($conn['provider']) . "<br>";
            echo "Status: " . htmlspecialchars($conn['status']) . "<br>";
            echo "Account ID: " . htmlspecialchars($conn['account_id']) . "<br>";
            echo "Created: " . $conn['created_at'] . "<br>";
            
            if ($conn['connection_data']) {
                $connData = json_decode($conn['connection_data'], true);
                if ($connData) {
                    echo "Connection Data: <pre>" . json_encode($connData, JSON_PRETTY_PRINT) . "</pre>";
                }
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Check Scan Results</h2>";
    
    $stmt = $pdo->prepare("
        SELECT id, provider, status, subscriptions_found, created_at, scan_data
        FROM scan_results 
        WHERE user_id = ? 
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scans)) {
        echo "<p class='warning'>⚠️ No scan results found</p>";
        echo "<p>This means no subscription scans have been completed yet.</p>";
    } else {
        echo "<p class='info'>📊 Found " . count($scans) . " recent scan(s)</p>";
        
        foreach ($scans as $i => $scan) {
            echo "<div style='border: 1px solid green; padding: 10px; margin: 5px 0; background: #e6ffe6;'>";
            echo "<strong>Scan " . ($i + 1) . ":</strong><br>";
            echo "Provider: " . htmlspecialchars($scan['provider']) . "<br>";
            echo "Status: " . htmlspecialchars($scan['status']) . "<br>";
            echo "Subscriptions Found: " . $scan['subscriptions_found'] . "<br>";
            echo "Created: " . $scan['created_at'] . "<br>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Check Raw Transaction Data</h2>";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_count
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalCount = $stmt->fetchColumn();
    
    echo "<p><strong>Total raw transactions:</strong> $totalCount</p>";
    
    if ($totalCount > 0) {
        $stmt = $pdo->prepare("
            SELECT account_id, COUNT(*) as count, MIN(booking_date) as earliest, MAX(booking_date) as latest
            FROM raw_transactions 
            WHERE user_id = ?
            GROUP BY account_id
            ORDER BY count DESC
        ");
        $stmt->execute([$userId]);
        $accountSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📈 Transaction Summary by Account:</h3>";
        foreach ($accountSummary as $account) {
            echo "<div style='border: 1px solid purple; padding: 10px; margin: 5px 0; background: #f3e6ff;'>";
            echo "<strong>Account:</strong> " . htmlspecialchars($account['account_id']) . "<br>";
            echo "<strong>Transactions:</strong> " . $account['count'] . "<br>";
            echo "<strong>Date Range:</strong> " . $account['earliest'] . " to " . $account['latest'] . "<br>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    // Manual scan trigger
    if (!empty($connections)) {
        echo "<div class='section'>";
        echo "<h2>4. Manual Scan Trigger</h2>";
        
        if (isset($_POST['trigger_scan'])) {
            echo "<p class='info'>🔄 Triggering manual subscription scan...</p>";
            
            $gocardlessService = new GoCardlessFinancialService($pdo);
            $scanResult = $gocardlessService->scanForSubscriptions($userId);
            
            echo "<h3>Scan Result:</h3>";
            echo "<pre>" . json_encode($scanResult, JSON_PRETTY_PRINT) . "</pre>";
            
            if ($scanResult['success']) {
                echo "<p class='success'>✅ Scan completed successfully!</p>";
                echo "<p><a href='debug-bank-connection.php'>Refresh to see updated data</a></p>";
            } else {
                echo "<p class='error'>❌ Scan failed: " . htmlspecialchars($scanResult['error']) . "</p>";
            }
            
        } else {
            echo "<p>You can manually trigger a subscription scan to test the data flow:</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='trigger_scan' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🔄 Trigger Manual Scan";
            echo "</button>";
            echo "</form>";
            echo "<p><small>This will fetch transactions from your connected bank accounts and analyze them for subscriptions.</small></p>";
        }
        echo "</div>";
    }
    
    echo "<div class='section'>";
    echo "<h2>5. Next Steps</h2>";
    
    if (empty($connections)) {
        echo "<p class='error'>🔗 <strong>Step 1:</strong> <a href='bank/unified-scan.php'>Connect your bank account</a></p>";
    } elseif ($totalCount === 0) {
        echo "<p class='warning'>📊 <strong>Step 2:</strong> Trigger a manual scan above to fetch transaction data</p>";
    } else {
        echo "<p class='success'>✅ You have bank connections and transaction data!</p>";
        echo "<ul>";
        echo "<li><a href='view-raw-data.php'>View raw transaction data</a></li>";
        echo "<li><a href='debug-gocardless-transactions.php'>Test subscription detection</a></li>";
        echo "<li><a href='dashboard.php'>Go to dashboard</a></li>";
        echo "</ul>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
