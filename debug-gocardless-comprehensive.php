<?php
/**
 * COMPREHENSIVE GOCARDLESS DEBUG SCRIPT
 * Systematically analyze GoCardless integration and data flow
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';
require_once 'includes/gocardless_transaction_processor.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>GoCardless Comprehensive Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .highlight { background: yellow; }
    </style>
</head>
<body>";

echo "<h1>🔍 GoCardless Comprehensive Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";
echo "<p><strong>Debug Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    $processor = new GoCardlessTransactionProcessor($pdo);
    
    // SECTION 1: Check GoCardless API Access
    echo "<div class='section'>";
    echo "<h2>📡 Section 1: GoCardless API Access</h2>";
    
    try {
        $accessToken = $gocardlessService->getAccessToken();
        echo "<p class='success'>✅ GoCardless API access token obtained successfully</p>";
        echo "<p><strong>Token (first 20 chars):</strong> " . substr($accessToken, 0, 20) . "...</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Failed to get GoCardless access token: " . $e->getMessage() . "</p>";
        echo "</div></body></html>";
        exit;
    }
    echo "</div>";
    
    // SECTION 2: Check Database Connections
    echo "<div class='section'>";
    echo "<h2>🗄️ Section 2: Database Connection Status</h2>";
    
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data, status, created_at
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p class='error'>❌ No GoCardless connections found in database</p>";
        echo "<p><strong>Action needed:</strong> User needs to connect a bank account first</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✅ Found " . count($connections) . " GoCardless connection(s)</p>";
    
    $activeConnections = array_filter($connections, function($conn) { return $conn['status'] === 'active'; });
    echo "<p><strong>Active connections:</strong> " . count($activeConnections) . "</p>";
    
    foreach ($connections as $i => $conn) {
        echo "<h4>Connection " . ($i + 1) . ":</h4>";
        echo "<ul>";
        echo "<li><strong>Account ID:</strong> " . htmlspecialchars($conn['account_id']) . "</li>";
        echo "<li><strong>Status:</strong> " . $conn['status'] . "</li>";
        echo "<li><strong>Created:</strong> " . $conn['created_at'] . "</li>";
        echo "</ul>";
    }
    echo "</div>";
    
    // SECTION 3: Test Transaction Data Retrieval
    echo "<div class='section'>";
    echo "<h2>📊 Section 3: Transaction Data Retrieval Test</h2>";
    
    $totalTransactionsFound = 0;
    $oldestTransaction = null;
    $newestTransaction = null;
    
    foreach ($activeConnections as $conn) {
        $accountId = $conn['account_id'];
        echo "<h3>Testing Account: $accountId</h3>";
        
        // Test different date ranges to see what data we actually get
        $dateRanges = [
            '30 days' => ['-30 days', 'Last 30 days'],
            '90 days' => ['-90 days', 'Last 90 days'], 
            '180 days' => ['-180 days', 'Last 180 days'],
            '365 days' => ['-365 days', 'Last 365 days'],
            '730 days' => ['-730 days', 'Last 2 years']
        ];
        
        foreach ($dateRanges as $label => $range) {
            $dateFrom = date('Y-m-d', strtotime($range[0]));
            $dateTo = date('Y-m-d');
            
            $url = 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/' .
                   '?date_from=' . $dateFrom . '&date_to=' . $dateTo;
            
            echo "<h4>Testing {$range[1]} ($dateFrom to $dateTo)</h4>";
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            if ($curlError) {
                echo "<p class='error'>❌ cURL Error: $curlError</p>";
                continue;
            }
            
            if ($httpCode !== 200) {
                echo "<p class='error'>❌ HTTP Error $httpCode: $response</p>";
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['transactions'])) {
                echo "<p class='error'>❌ No transactions array in response</p>";
                continue;
            }
            
            $transactions = $data['transactions'];
            $transactionCount = count($transactions);
            $totalTransactionsFound = max($totalTransactionsFound, $transactionCount);
            
            echo "<p><strong>Transactions found:</strong> $transactionCount</p>";
            
            if ($transactionCount > 0) {
                // Find date range of actual transactions
                $dates = [];
                foreach ($transactions as $t) {
                    $date = $t['bookingDate'] ?? $t['valueDate'] ?? null;
                    if ($date) $dates[] = $date;
                }
                
                if (!empty($dates)) {
                    sort($dates);
                    $actualOldest = $dates[0];
                    $actualNewest = end($dates);
                    
                    echo "<p><strong>Actual date range:</strong> $actualOldest to $actualNewest</p>";
                    
                    if (!$oldestTransaction || $actualOldest < $oldestTransaction) {
                        $oldestTransaction = $actualOldest;
                    }
                    if (!$newestTransaction || $actualNewest > $newestTransaction) {
                        $newestTransaction = $actualNewest;
                    }
                    
                    // Calculate actual days of data
                    $daysDiff = (strtotime($actualNewest) - strtotime($actualOldest)) / (60 * 60 * 24);
                    echo "<p><strong>Actual days of data:</strong> " . round($daysDiff) . " days</p>";
                    
                    if ($daysDiff >= 350) {
                        echo "<p class='success'>✅ We have close to a full year of data!</p>";
                    } elseif ($daysDiff >= 80) {
                        echo "<p class='warning'>⚠️ We have about 3 months of data</p>";
                    } else {
                        echo "<p class='error'>❌ Limited data - only " . round($daysDiff) . " days</p>";
                    }
                }
            }
            
            echo "<hr style='margin: 10px 0;'>";
        }
    }
    echo "</div>";
    
    // SECTION 4: Analyze Stored Transaction Data
    echo "<div class='section'>";
    echo "<h2>🗃️ Section 4: Stored Transaction Analysis</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN amount < 0 THEN 1 END) as outgoing_transactions,
            MIN(booking_date) as oldest_stored,
            MAX(booking_date) as newest_stored,
            COUNT(DISTINCT merchant_name) as unique_merchants,
            COUNT(DISTINCT account_id) as accounts_with_data
        FROM raw_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats['total_transactions'] > 0) {
        echo "<p class='success'>✅ Found {$stats['total_transactions']} stored transactions</p>";
        echo "<ul>";
        echo "<li><strong>Outgoing transactions:</strong> {$stats['outgoing_transactions']}</li>";
        echo "<li><strong>Unique merchants:</strong> {$stats['unique_merchants']}</li>";
        echo "<li><strong>Accounts with data:</strong> {$stats['accounts_with_data']}</li>";
        echo "<li><strong>Date range:</strong> {$stats['oldest_stored']} to {$stats['newest_stored']}</li>";
        
        if ($stats['oldest_stored'] && $stats['newest_stored']) {
            $storedDays = (strtotime($stats['newest_stored']) - strtotime($stats['oldest_stored'])) / (60 * 60 * 24);
            echo "<li><strong>Days of stored data:</strong> " . round($storedDays) . " days</li>";
        }
        echo "</ul>";
        
        // Show sample merchants for subscription analysis
        $stmt = $pdo->prepare("
            SELECT merchant_name, COUNT(*) as transaction_count, 
                   MIN(booking_date) as first_seen, MAX(booking_date) as last_seen,
                   AVG(ABS(amount)) as avg_amount
            FROM raw_transactions 
            WHERE user_id = ? AND amount < 0 AND merchant_name IS NOT NULL
            GROUP BY merchant_name 
            HAVING transaction_count >= 2
            ORDER BY transaction_count DESC, avg_amount DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($merchants)) {
            echo "<h4>🏪 Top Recurring Merchants (Potential Subscriptions):</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Merchant</th><th>Count</th><th>Avg Amount</th><th>First Seen</th><th>Last Seen</th></tr>";
            
            foreach ($merchants as $merchant) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($merchant['merchant_name']) . "</td>";
                echo "<td>" . $merchant['transaction_count'] . "</td>";
                echo "<td>€" . number_format($merchant['avg_amount'], 2) . "</td>";
                echo "<td>" . $merchant['first_seen'] . "</td>";
                echo "<td>" . $merchant['last_seen'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p class='error'>❌ No transactions stored in database</p>";
        echo "<p><strong>This suggests the transaction processing pipeline is not working</strong></p>";
    }
    echo "</div>";
    
    // SECTION 5: Test Subscription Detection
    echo "<div class='section'>";
    echo "<h2>🔍 Section 5: Subscription Detection Test</h2>";
    
    if ($stats['total_transactions'] > 0) {
        // Test the subscription detection algorithm
        $stmt = $pdo->prepare("
            SELECT * FROM raw_transactions 
            WHERE user_id = ? AND amount < 0 
            ORDER BY booking_date DESC 
            LIMIT 100
        ");
        $stmt->execute([$userId]);
        $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Testing subscription detection on {count($recentTransactions)} recent outgoing transactions...</p>";
        
        // Group by merchant
        $merchantGroups = [];
        foreach ($recentTransactions as $transaction) {
            $merchant = $transaction['merchant_name'] ?? 'Unknown';
            if (!isset($merchantGroups[$merchant])) {
                $merchantGroups[$merchant] = [];
            }
            $merchantGroups[$merchant][] = $transaction;
        }
        
        echo "<h4>🎯 Subscription Pattern Analysis:</h4>";
        
        foreach ($merchantGroups as $merchant => $transactions) {
            if (count($transactions) < 2) continue; // Need at least 2 transactions
            
            echo "<h5>Merchant: " . htmlspecialchars($merchant) . " (" . count($transactions) . " transactions)</h5>";
            
            // Sort by date
            usort($transactions, function($a, $b) {
                return strtotime($a['booking_date']) - strtotime($b['booking_date']);
            });
            
            // Calculate intervals
            $intervals = [];
            $amounts = [];
            
            for ($i = 1; $i < count($transactions); $i++) {
                $prevDate = strtotime($transactions[$i-1]['booking_date']);
                $currDate = strtotime($transactions[$i]['booking_date']);
                $interval = ($currDate - $prevDate) / (60 * 60 * 24); // days
                $intervals[] = $interval;
                $amounts[] = abs($transactions[$i]['amount']);
            }
            
            if (!empty($intervals)) {
                $avgInterval = array_sum($intervals) / count($intervals);
                $avgAmount = array_sum($amounts) / count($amounts);
                
                echo "<ul>";
                echo "<li><strong>Average interval:</strong> " . round($avgInterval, 1) . " days</li>";
                echo "<li><strong>Average amount:</strong> €" . number_format($avgAmount, 2) . "</li>";
                echo "<li><strong>Intervals:</strong> " . implode(', ', array_map(function($i) { return round($i, 1); }, $intervals)) . " days</li>";
                
                // Determine if this looks like a subscription
                $isSubscription = false;
                $confidence = 0;
                
                if ($avgInterval >= 28 && $avgInterval <= 32) {
                    $isSubscription = true;
                    $confidence = 90;
                    echo "<li class='success'><strong>🎯 LIKELY MONTHLY SUBSCRIPTION</strong> (confidence: $confidence%)</li>";
                } elseif ($avgInterval >= 350 && $avgInterval <= 380) {
                    $isSubscription = true;
                    $confidence = 85;
                    echo "<li class='success'><strong>🎯 LIKELY YEARLY SUBSCRIPTION</strong> (confidence: $confidence%)</li>";
                } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
                    $isSubscription = true;
                    $confidence = 75;
                    echo "<li class='success'><strong>🎯 LIKELY WEEKLY SUBSCRIPTION</strong> (confidence: $confidence%)</li>";
                } else {
                    echo "<li class='warning'>⚠️ Irregular pattern - not clearly a subscription</li>";
                }
                
                echo "</ul>";
            }
            
            echo "<hr style='margin: 10px 0;'>";
        }
        
    } else {
        echo "<p class='error'>❌ Cannot test subscription detection - no transaction data available</p>";
    }
    echo "</div>";
    
    // SECTION 6: Summary and Recommendations
    echo "<div class='section'>";
    echo "<h2>📋 Section 6: Summary & Recommendations</h2>";
    
    echo "<h3>🔍 Key Findings:</h3>";
    echo "<ul>";
    
    if ($totalTransactionsFound > 0) {
        echo "<li class='success'>✅ GoCardless API is returning transaction data ($totalTransactionsFound transactions found)</li>";
        
        if ($oldestTransaction && $newestTransaction) {
            $totalDays = (strtotime($newestTransaction) - strtotime($oldestTransaction)) / (60 * 60 * 24);
            echo "<li class='success'>✅ Transaction history spans " . round($totalDays) . " days ($oldestTransaction to $newestTransaction)</li>";
            
            if ($totalDays >= 350) {
                echo "<li class='success'>✅ We have nearly a full year of data - excellent for yearly subscription detection!</li>";
            } elseif ($totalDays >= 80) {
                echo "<li class='warning'>⚠️ We have ~3 months of data - good for monthly subscriptions, limited for yearly</li>";
            } else {
                echo "<li class='error'>❌ Limited historical data - may miss subscription patterns</li>";
            }
        }
    } else {
        echo "<li class='error'>❌ No transaction data retrieved from GoCardless API</li>";
    }
    
    if ($stats['total_transactions'] > 0) {
        echo "<li class='success'>✅ Transaction data is being stored in database ({$stats['total_transactions']} transactions)</li>";
    } else {
        echo "<li class='error'>❌ No transaction data stored in database - processing pipeline issue</li>";
    }
    
    echo "</ul>";
    
    echo "<h3>🎯 Next Actions:</h3>";
    echo "<ol>";
    
    if ($totalTransactionsFound > 0 && $stats['total_transactions'] == 0) {
        echo "<li class='error'><strong>CRITICAL:</strong> Fix transaction processing pipeline - API returns data but nothing is stored</li>";
    }
    
    if ($stats['total_transactions'] > 0) {
        echo "<li class='success'>Run subscription detection algorithm on stored data</li>";
        echo "<li>Improve merchant name normalization for better pattern matching</li>";
        echo "<li>Implement confidence scoring for subscription detection</li>";
    }
    
    if ($totalTransactionsFound == 0) {
        echo "<li class='error'><strong>CRITICAL:</strong> Investigate why GoCardless API returns no transaction data</li>";
        echo "<li>Check if bank connection consent includes transaction access</li>";
        echo "<li>Verify API credentials and permissions</li>";
    }
    
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Critical Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
