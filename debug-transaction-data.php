<?php
/**
 * DEBUG TRANSACTION DATA
 * Analyze the actual transaction data returned by GoCardless
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<h1>🔍 Transaction Data Analysis</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    // Get connected accounts
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data, status, created_at
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless' AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p>❌ No active GoCardless accounts found</p>";
        exit;
    }
    
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    // Use reflection to access the private getAccessToken method
    $reflection = new ReflectionClass($gocardlessService);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);
    $accessToken = $method->invoke($gocardlessService);
    
    foreach ($accounts as $account) {
        $accountId = $account['account_id'];
        echo "<h2>Account: $accountId</h2>";
        echo "<p><strong>Connected:</strong> " . $account['created_at'] . "</p>";
        
        // Test different date ranges for transactions
        $dateRanges = [
            'default' => '',
            'last_30_days' => '?date_from=' . date('Y-m-d', strtotime('-30 days')),
            'last_90_days' => '?date_from=' . date('Y-m-d', strtotime('-90 days')),
            'last_365_days' => '?date_from=' . date('Y-m-d', strtotime('-365 days'))
        ];
        
        foreach ($dateRanges as $rangeName => $dateParam) {
            echo "<h3>📅 Testing: $rangeName</h3>";
            
            $url = 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/' . $dateParam;
            echo "<p><strong>URL:</strong> $url</p>";
            
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
            
            echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
            
            if ($curlError) {
                echo "<p>❌ cURL Error: $curlError</p>";
                continue;
            }
            
            if ($httpCode !== 200) {
                echo "<p>❌ API Error</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['transactions'])) {
                echo "<p>❌ No transactions array in response</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                continue;
            }
            
            $transactions = $data['transactions'];
            echo "<p><strong>Transaction Count:</strong> " . count($transactions) . "</p>";
            
            if (empty($transactions)) {
                echo "<p>⚠️ No transactions found for this date range</p>";
                continue;
            }
            
            // Analyze transaction quality
            $validTransactions = 0;
            $nullTransactions = 0;
            $transactionAmounts = [];
            $transactionDates = [];
            $merchants = [];
            
            foreach ($transactions as $i => $transaction) {
                if ($transaction === null) {
                    $nullTransactions++;
                    continue;
                }
                
                $validTransactions++;
                
                // Extract amount
                if (isset($transaction['transactionAmount']['amount'])) {
                    $amount = floatval($transaction['transactionAmount']['amount']);
                    $transactionAmounts[] = $amount;
                }
                
                // Extract date
                if (isset($transaction['bookingDate'])) {
                    $transactionDates[] = $transaction['bookingDate'];
                } elseif (isset($transaction['valueDate'])) {
                    $transactionDates[] = $transaction['valueDate'];
                }
                
                // Extract merchant
                $merchant = 'Unknown';
                if (isset($transaction['creditorName'])) {
                    $merchant = $transaction['creditorName'];
                } elseif (isset($transaction['remittanceInformationUnstructured'])) {
                    $merchant = substr($transaction['remittanceInformationUnstructured'], 0, 50);
                }
                
                if (!isset($merchants[$merchant])) {
                    $merchants[$merchant] = 0;
                }
                $merchants[$merchant]++;
            }
            
            echo "<p><strong>Valid Transactions:</strong> $validTransactions</p>";
            echo "<p><strong>Null Transactions:</strong> $nullTransactions</p>";
            
            if ($validTransactions > 0) {
                // Show transaction amount distribution
                echo "<h4>💰 Amount Analysis</h4>";
                if (!empty($transactionAmounts)) {
                    $outgoing = array_filter($transactionAmounts, function($amt) { return $amt < 0; });
                    $incoming = array_filter($transactionAmounts, function($amt) { return $amt > 0; });
                    
                    echo "<p><strong>Outgoing Payments:</strong> " . count($outgoing) . " (potential subscriptions)</p>";
                    echo "<p><strong>Incoming Payments:</strong> " . count($incoming) . "</p>";
                    
                    if (!empty($outgoing)) {
                        echo "<p><strong>Outgoing Amount Range:</strong> €" . number_format(min($outgoing), 2) . " to €" . number_format(max($outgoing), 2) . "</p>";
                    }
                }
                
                // Show date range
                echo "<h4>📅 Date Analysis</h4>";
                if (!empty($transactionDates)) {
                    sort($transactionDates);
                    echo "<p><strong>Date Range:</strong> " . $transactionDates[0] . " to " . end($transactionDates) . "</p>";
                }
                
                // Show top merchants
                echo "<h4>🏪 Merchant Analysis</h4>";
                arsort($merchants);
                $topMerchants = array_slice($merchants, 0, 10, true);
                foreach ($topMerchants as $merchant => $count) {
                    echo "<p><strong>" . htmlspecialchars($merchant) . ":</strong> $count transaction(s)</p>";
                }
                
                // Show sample valid transactions
                echo "<h4>📋 Sample Valid Transactions</h4>";
                $sampleCount = min(3, $validTransactions);
                $validSamples = array_filter($transactions, function($t) { return $t !== null; });
                $validSamples = array_slice($validSamples, 0, $sampleCount);
                
                foreach ($validSamples as $i => $transaction) {
                    echo "<h5>Transaction " . ($i + 1) . ":</h5>";
                    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
                    echo htmlspecialchars(json_encode($transaction, JSON_PRETTY_PRINT));
                    echo "</pre>";
                }
            }
            
            echo "<hr>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
