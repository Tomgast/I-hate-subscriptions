<?php
/**
 * DEBUG GOCARDLESS TRANSACTION ANALYSIS
 * Trace exactly where the subscription detection pipeline is failing
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

echo "<h1>🔍 GoCardless Transaction Analysis Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    echo "<h2>Step 1: Check Connected Accounts</h2>";
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data, status, created_at
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless'
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p>❌ No GoCardless accounts found in bank_connections table</p>";
        exit;
    }
    
    echo "<p>✅ Found " . count($accounts) . " GoCardless account(s)</p>";
    foreach ($accounts as $account) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Account ID:</strong> " . htmlspecialchars($account['account_id']) . "<br>";
        echo "<strong>Status:</strong> " . $account['status'] . "<br>";
        echo "<strong>Created:</strong> " . $account['created_at'] . "<br>";
        echo "<strong>Connection Data:</strong><br>";
        echo "<pre>" . htmlspecialchars($account['connection_data'] ?? 'NULL') . "</pre>";
        echo "</div>";
    }
    
    echo "<h2>Step 2: Test GoCardless API Access</h2>";
    
    foreach ($accounts as $account) {
        $accountId = $account['account_id'];
        echo "<h3>Testing Account: $accountId</h3>";
        
        // Test account details
        echo "<h4>2.1 Account Details</h4>";
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $gocardlessService->getAccessToken(),
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
            if ($curlError) {
                echo "<p><strong>cURL Error:</strong> $curlError</p>";
            }
            
            if ($httpCode === 200) {
                $accountDetails = json_decode($response, true);
                echo "<p>✅ Account details retrieved successfully</p>";
                echo "<pre>" . json_encode($accountDetails, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p>❌ Failed to get account details</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Error getting account details: " . $e->getMessage() . "</p>";
        }
        
        // Test transactions
        echo "<h4>2.2 Account Transactions</h4>";
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $gocardlessService->getAccessToken(),
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
            if ($curlError) {
                echo "<p><strong>cURL Error:</strong> $curlError</p>";
            }
            
            if ($httpCode === 200) {
                $transactionData = json_decode($response, true);
                echo "<p>✅ Transactions retrieved successfully</p>";
                
                if (isset($transactionData['transactions'])) {
                    $transactions = $transactionData['transactions'];
                    echo "<p><strong>Total Transactions:</strong> " . count($transactions) . "</p>";
                    
                    // Show sample transactions
                    echo "<h5>Sample Transactions (first 5):</h5>";
                    $sampleTransactions = array_slice($transactions, 0, 5);
                    foreach ($sampleTransactions as $i => $transaction) {
                        echo "<div style='border: 1px solid #ddd; padding: 8px; margin: 5px 0;'>";
                        echo "<strong>Transaction " . ($i + 1) . ":</strong><br>";
                        echo "<strong>Amount:</strong> " . ($transaction['transactionAmount']['amount'] ?? 'N/A') . " " . ($transaction['transactionAmount']['currency'] ?? '') . "<br>";
                        echo "<strong>Date:</strong> " . ($transaction['bookingDate'] ?? $transaction['valueDate'] ?? 'N/A') . "<br>";
                        echo "<strong>Description:</strong> " . htmlspecialchars($transaction['remittanceInformationUnstructured'] ?? $transaction['additionalInformation'] ?? 'N/A') . "<br>";
                        echo "<strong>Creditor:</strong> " . htmlspecialchars($transaction['creditorName'] ?? 'N/A') . "<br>";
                        echo "</div>";
                    }
                    
                    // Test subscription analysis
                    echo "<h4>2.3 Subscription Analysis Test</h4>";
                    
                    // Look for potential subscription patterns
                    $potentialSubscriptions = [];
                    $merchantCounts = [];
                    
                    foreach ($transactions as $transaction) {
                        // Skip positive amounts (incoming money)
                        $amount = floatval($transaction['transactionAmount']['amount'] ?? 0);
                        if ($amount >= 0) continue;
                        
                        // Extract merchant name
                        $merchant = $transaction['creditorName'] ?? 
                                   $transaction['remittanceInformationUnstructured'] ?? 
                                   $transaction['additionalInformation'] ?? 
                                   'Unknown';
                        
                        // Clean up merchant name
                        $merchant = trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $merchant));
                        $merchant = preg_replace('/\s+/', ' ', $merchant);
                        
                        if (strlen($merchant) < 3) continue;
                        
                        if (!isset($merchantCounts[$merchant])) {
                            $merchantCounts[$merchant] = [];
                        }
                        
                        $merchantCounts[$merchant][] = [
                            'amount' => abs($amount),
                            'date' => $transaction['bookingDate'] ?? $transaction['valueDate'] ?? '',
                            'description' => $transaction['remittanceInformationUnstructured'] ?? ''
                        ];
                    }
                    
                    // Find merchants with multiple transactions (potential subscriptions)
                    echo "<h5>Potential Subscription Merchants:</h5>";
                    $foundPotentialSubs = false;
                    
                    foreach ($merchantCounts as $merchant => $merchantTransactions) {
                        if (count($merchantTransactions) >= 2) {
                            $foundPotentialSubs = true;
                            echo "<div style='border: 1px solid #green; padding: 8px; margin: 5px 0; background: #f0f8f0;'>";
                            echo "<strong>Merchant:</strong> " . htmlspecialchars($merchant) . "<br>";
                            echo "<strong>Transaction Count:</strong> " . count($merchantTransactions) . "<br>";
                            echo "<strong>Amounts:</strong> ";
                            foreach ($merchantTransactions as $t) {
                                echo "€" . number_format($t['amount'], 2) . " (" . $t['date'] . "), ";
                            }
                            echo "<br>";
                            echo "</div>";
                        }
                    }
                    
                    if (!$foundPotentialSubs) {
                        echo "<p>❌ No potential subscription patterns found in transaction analysis</p>";
                        echo "<p><strong>Debug Info:</strong></p>";
                        echo "<p>- Total transactions processed: " . count($transactions) . "</p>";
                        echo "<p>- Unique merchants found: " . count($merchantCounts) . "</p>";
                        echo "<p>- Merchants with single transactions: " . count(array_filter($merchantCounts, function($t) { return count($t) == 1; })) . "</p>";
                    }
                    
                } else {
                    echo "<p>❌ No 'transactions' key in response</p>";
                    echo "<pre>" . json_encode($transactionData, JSON_PRETTY_PRINT) . "</pre>";
                }
                
            } else {
                echo "<p>❌ Failed to get transactions</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Error getting transactions: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Step 3: Check Database Scan Records</h2>";
    $stmt = $pdo->prepare("
        SELECT * FROM bank_scans 
        WHERE user_id = ? AND provider = 'gocardless' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scans)) {
        echo "<p>❌ No scan records found in bank_scans table</p>";
    } else {
        echo "<p>✅ Found " . count($scans) . " scan record(s)</p>";
        foreach ($scans as $scan) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Scan ID:</strong> " . $scan['id'] . "<br>";
            echo "<strong>Status:</strong> " . $scan['status'] . "<br>";
            echo "<strong>Subscriptions Found:</strong> " . ($scan['subscriptions_found'] ?? 'NULL') . "<br>";
            echo "<strong>Created:</strong> " . $scan['created_at'] . "<br>";
            echo "<strong>Scan Data:</strong><br>";
            echo "<pre>" . htmlspecialchars(substr($scan['scan_data'] ?? 'NULL', 0, 500)) . "...</pre>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Critical Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
