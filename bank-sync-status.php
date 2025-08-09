<?php
/**
 * BANK SYNC STATUS
 * Show transaction sync status and schedule re-scans for insufficient data
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/transaction_sync_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<h1>🔄 Bank Transaction Sync Status</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $syncManager = new TransactionSyncManager($pdo);
    
    // Create sync table if it doesn't exist
    $syncManager->createSyncTable();
    
    // Handle manual rescan request
    if (isset($_POST['schedule_rescan'])) {
        $provider = $_POST['provider'];
        $accountId = $_POST['account_id'];
        $delayHours = intval($_POST['delay_hours'] ?? 24);
        
        $result = $syncManager->scheduleRescan($userId, $provider, $accountId, $delayHours);
        
        if ($result['success']) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<strong>✅ Re-scan Scheduled!</strong><br>";
            echo $result['message'] . "<br>";
            echo "<strong>Next scan:</strong> " . $result['next_scan'];
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<strong>❌ Error:</strong> " . $result['error'];
            echo "</div>";
        }
    }
    
    echo "<h2>📊 Connected Bank Accounts</h2>";
    
    // Get all connected bank accounts
    $stmt = $pdo->prepare("
        SELECT * FROM bank_connections 
        WHERE user_id = ? AND status = 'active'
        ORDER BY provider, created_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p>❌ No active bank connections found</p>";
        echo "<p><a href='bank/unified-scan.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏦 Connect Bank Account</a></p>";
        exit;
    }
    
    foreach ($connections as $connection) {
        $provider = $connection['provider'];
        $accountId = $connection['account_id'];
        $connectedAt = $connection['created_at'];
        
        echo "<div style='border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 8px; background: #f9f9f9;'>";
        echo "<h3>🏦 " . strtoupper($provider) . " Account</h3>";
        echo "<p><strong>Account ID:</strong> " . htmlspecialchars($accountId) . "</p>";
        echo "<p><strong>Connected:</strong> $connectedAt</p>";
        
        // Check connection age
        $connectionAge = time() - strtotime($connectedAt);
        $ageHours = round($connectionAge / 3600, 1);
        $ageDays = round($connectionAge / (24 * 3600), 1);
        
        if ($ageHours < 1) {
            $ageDisplay = round($connectionAge / 60) . " minutes ago";
        } elseif ($ageHours < 24) {
            $ageDisplay = "$ageHours hours ago";
        } else {
            $ageDisplay = "$ageDays days ago";
        }
        
        echo "<p><strong>Connection Age:</strong> $ageDisplay</p>";
        
        // Check data sufficiency
        echo "<h4>📈 Transaction Data Analysis</h4>";
        
        try {
            $dataCheck = $syncManager->hasSufficientData($userId, $provider, $accountId);
            
            if (isset($dataCheck['error'])) {
                echo "<p>❌ <strong>Error checking data:</strong> " . $dataCheck['error'] . "</p>";
            } else {
                $metrics = $dataCheck['metrics'];
                $requirements = $dataCheck['requirements'];
                $missing = $dataCheck['missing'];
                
                if ($dataCheck['sufficient']) {
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;'>";
                    echo "<strong>✅ Sufficient Data Available</strong><br>";
                    echo "Ready for subscription analysis!";
                    echo "</div>";
                } else {
                    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>";
                    echo "<strong>⚠️ Insufficient Data for Analysis</strong><br>";
                    echo "Bank connection is too new or data is still syncing.";
                    echo "</div>";
                }
                
                // Show metrics
                echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                echo "<tr><th>Metric</th><th>Current</th><th>Required</th><th>Status</th></tr>";
                
                $metricChecks = [
                    'Total Transactions' => [$metrics['total_transactions'], $requirements['min_transactions']],
                    'Outgoing Payments' => [$metrics['outgoing_transactions'], $requirements['min_outgoing_transactions']],
                    'Date Range (days)' => [round($metrics['date_range_days']), $requirements['min_date_range_days']],
                    'Unique Merchants' => [$metrics['unique_merchants'], $requirements['min_unique_merchants']]
                ];
                
                foreach ($metricChecks as $label => $values) {
                    $current = $values[0];
                    $required = $values[1];
                    $status = $current >= $required ? '✅' : '❌';
                    $statusColor = $current >= $required ? '#d4edda' : '#f8d7da';
                    
                    echo "<tr style='background: $statusColor;'>";
                    echo "<td><strong>$label</strong></td>";
                    echo "<td>$current</td>";
                    echo "<td>$required</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Show data quality indicators
                echo "<h5>🔍 Data Quality</h5>";
                echo "<ul>";
                echo "<li><strong>Has Amount Data:</strong> " . ($metrics['has_amounts'] ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Has Merchant Data:</strong> " . ($metrics['has_merchants'] ? '✅ Yes' : '❌ No') . "</li>";
                echo "<li><strong>Date Range:</strong> " . ($metrics['oldest_transaction'] ? $metrics['oldest_transaction'] . ' to ' . $metrics['newest_transaction'] : 'No dates available') . "</li>";
                echo "</ul>";
                
                // Show what's missing
                if (!empty($missing)) {
                    echo "<h5>📋 What's Missing</h5>";
                    echo "<ul>";
                    foreach ($missing as $item) {
                        echo "<li>$item</li>";
                    }
                    echo "</ul>";
                }
                
                // Show rescan options if data is insufficient
                if (!$dataCheck['sufficient']) {
                    echo "<h5>🔄 Schedule Re-scan</h5>";
                    echo "<p>Since this is a fresh connection, the bank may need time to sync historical transaction data.</p>";
                    
                    // Check if already scheduled
                    $stmt = $pdo->prepare("
                        SELECT next_scan_at, retry_count, status 
                        FROM transaction_sync_schedule 
                        WHERE user_id = ? AND provider = ? AND account_id = ?
                    ");
                    $stmt->execute([$userId, $provider, $accountId]);
                    $scheduled = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($scheduled && $scheduled['status'] === 'pending') {
                        echo "<div style='background: #cce5ff; border: 1px solid #99ccff; padding: 10px; border-radius: 5px;'>";
                        echo "<strong>🕒 Re-scan Already Scheduled</strong><br>";
                        echo "<strong>Next scan:</strong> " . $scheduled['next_scan_at'] . "<br>";
                        echo "<strong>Retry count:</strong> " . $scheduled['retry_count'];
                        echo "</div>";
                    } else {
                        echo "<form method='POST' style='margin: 10px 0;'>";
                        echo "<input type='hidden' name='provider' value='$provider'>";
                        echo "<input type='hidden' name='account_id' value='$accountId'>";
                        echo "<label for='delay_hours'>Re-scan in:</label> ";
                        echo "<select name='delay_hours'>";
                        echo "<option value='1'>1 hour</option>";
                        echo "<option value='6'>6 hours</option>";
                        echo "<option value='24' selected>24 hours</option>";
                        echo "<option value='72'>3 days</option>";
                        echo "</select> ";
                        echo "<button type='submit' name='schedule_rescan' style='background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;'>📅 Schedule Re-scan</button>";
                        echo "</form>";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ <strong>Error analyzing data:</strong> " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    // Show pending rescans
    echo "<h2>📅 Scheduled Re-scans</h2>";
    $pendingRescans = $syncManager->getPendingRescans();
    
    if (empty($pendingRescans)) {
        echo "<p>No re-scans currently scheduled.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Provider</th><th>Account</th><th>Retry Count</th><th>Scheduled</th></tr>";
        foreach ($pendingRescans as $rescan) {
            echo "<tr>";
            echo "<td>" . strtoupper($rescan['provider']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($rescan['account_id'], 0, 20)) . "...</td>";
            echo "<td>" . $rescan['retry_count'] . "</td>";
            echo "<td>" . $rescan['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a> | <a href='bank/unified-scan.php'>🏦 Connect Another Bank</a></p>";
?>
