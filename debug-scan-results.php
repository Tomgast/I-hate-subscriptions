<?php
/**
 * DEBUG SCAN RESULTS
 * View the actual scan results from GoCardless to improve dashboard
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/bank_provider_router.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<h1>🔍 Scan Results Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $providerRouter = new BankProviderRouter($pdo);
    
    echo "<h2>📊 Connection Status</h2>";
    $connectionStatus = $providerRouter->getUnifiedConnectionStatus($userId);
    echo "<pre>" . print_r($connectionStatus, true) . "</pre>";
    
    echo "<h2>🏦 Bank Connections</h2>";
    $stmt = $pdo->prepare("
        SELECT * FROM bank_connections 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p>❌ No bank connections found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Provider</th><th>Account ID</th><th>Account Name</th><th>Status</th><th>Created</th></tr>";
        foreach ($connections as $conn) {
            echo "<tr>";
            echo "<td>" . $conn['id'] . "</td>";
            echo "<td>" . $conn['provider'] . "</td>";
            echo "<td>" . htmlspecialchars($conn['account_id']) . "</td>";
            echo "<td>" . htmlspecialchars($conn['account_name'] ?? 'N/A') . "</td>";
            echo "<td>" . $conn['status'] . "</td>";
            echo "<td>" . $conn['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🔍 Bank Scans</h2>";
    $stmt = $pdo->prepare("
        SELECT * FROM bank_scans 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scans)) {
        echo "<p>❌ No bank scans found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Provider</th><th>Status</th><th>Subscriptions Found</th><th>Created</th><th>Details</th></tr>";
        foreach ($scans as $scan) {
            echo "<tr>";
            echo "<td>" . $scan['id'] . "</td>";
            echo "<td>" . $scan['provider'] . "</td>";
            echo "<td>" . $scan['status'] . "</td>";
            echo "<td>" . ($scan['subscriptions_found'] ?? 'N/A') . "</td>";
            echo "<td>" . $scan['created_at'] . "</td>";
            echo "<td><pre>" . htmlspecialchars(substr($scan['scan_data'] ?? '', 0, 200)) . "...</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>💳 Detected Subscriptions</h2>";
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "<p>❌ No subscriptions found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Amount</th><th>Currency</th><th>Billing Cycle</th><th>Next Payment</th><th>Status</th><th>Bank Account</th><th>Created</th></tr>";
        foreach ($subscriptions as $sub) {
            echo "<tr>";
            echo "<td>" . $sub['id'] . "</td>";
            echo "<td>" . htmlspecialchars($sub['name']) . "</td>";
            echo "<td>" . $sub['amount'] . "</td>";
            echo "<td>" . $sub['currency'] . "</td>";
            echo "<td>" . $sub['billing_cycle'] . "</td>";
            echo "<td>" . ($sub['next_billing_date'] ?? 'N/A') . "</td>";
            echo "<td>" . $sub['status'] . "</td>";
            echo "<td>" . htmlspecialchars($sub['bank_account_id'] ?? 'N/A') . "</td>";
            echo "<td>" . $sub['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>📈 Unified Scan Results</h2>";
    $unifiedResults = $providerRouter->getUnifiedScanResults($userId);
    
    if (empty($unifiedResults)) {
        echo "<p>❌ No unified scan results found</p>";
    } else {
        echo "<p><strong>Found " . count($unifiedResults) . " results</strong></p>";
        echo "<pre>" . print_r($unifiedResults, true) . "</pre>";
    }
    
    echo "<h2>📋 Available Scans</h2>";
    $availableScans = $providerRouter->getUnifiedAvailableScans($userId);
    
    if (empty($availableScans)) {
        echo "<p>❌ No available scans found</p>";
    } else {
        echo "<p><strong>Found " . count($availableScans) . " available scans</strong></p>";
        echo "<pre>" . print_r($availableScans, true) . "</pre>";
    }
    
    echo "<h2>🔧 Database Tables Structure</h2>";
    
    // Check what tables exist
    $tables = ['bank_connections', 'bank_scans', 'subscriptions', 'bank_connection_sessions'];
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . $col['Field'] . "</td>";
                echo "<td>" . $col['Type'] . "</td>";
                echo "<td>" . $col['Null'] . "</td>";
                echo "<td>" . $col['Key'] . "</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Count records
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p><strong>Records:</strong> $count</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Error accessing table $table: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
