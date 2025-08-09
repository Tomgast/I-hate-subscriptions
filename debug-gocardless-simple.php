<?php
/**
 * SIMPLE GOCARDLESS DEBUG
 * Step-by-step debugging of GoCardless API issues
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

echo "<h1>🔍 Simple GoCardless Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>Step 1: Check Connected Accounts</h2>";
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data, status
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless' AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p>❌ No active GoCardless accounts found</p>";
        exit;
    }
    
    echo "<p>✅ Found " . count($accounts) . " active GoCardless account(s)</p>";
    foreach ($accounts as $account) {
        echo "<p><strong>Account ID:</strong> " . htmlspecialchars($account['account_id']) . "</p>";
    }
    
    echo "<h2>Step 2: Initialize GoCardless Service</h2>";
    try {
        $gocardlessService = new GoCardlessFinancialService($pdo);
        echo "<p>✅ GoCardless service initialized</p>";
    } catch (Exception $e) {
        echo "<p>❌ Failed to initialize GoCardless service: " . $e->getMessage() . "</p>";
        exit;
    }
    
    echo "<h2>Step 3: Test Access Token</h2>";
    try {
        // Use reflection to access the private getAccessToken method
        $reflection = new ReflectionClass($gocardlessService);
        $method = $reflection->getMethod('getAccessToken');
        $method->setAccessible(true);
        
        $accessToken = $method->invoke($gocardlessService);
        echo "<p>✅ Access token obtained successfully</p>";
        echo "<p><strong>Token length:</strong> " . strlen($accessToken) . " characters</p>";
        echo "<p><strong>Token preview:</strong> " . substr($accessToken, 0, 20) . "...</p>";
    } catch (Exception $e) {
        echo "<p>❌ Failed to get access token: " . $e->getMessage() . "</p>";
        exit;
    }
    
    echo "<h2>Step 4: Test Account Details API Call</h2>";
    $accountId = $accounts[0]['account_id'];
    
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ]
        ]);
        
        echo "<p>🔄 Making API call to: " . 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/' . "</p>";
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
        
        if ($curlError) {
            echo "<p>❌ cURL Error: $curlError</p>";
        } elseif ($httpCode === 200) {
            echo "<p>✅ Account details retrieved successfully</p>";
            $accountData = json_decode($response, true);
            echo "<pre>" . json_encode($accountData, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>❌ API Error (HTTP $httpCode)</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Exception during API call: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 5: Test Transactions API Call</h2>";
    
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ]
        ]);
        
        echo "<p>🔄 Making transactions API call...</p>";
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
        
        if ($curlError) {
            echo "<p>❌ cURL Error: $curlError</p>";
        } elseif ($httpCode === 200) {
            echo "<p>✅ Transactions retrieved successfully</p>";
            $transactionData = json_decode($response, true);
            
            if (isset($transactionData['transactions'])) {
                $transactions = $transactionData['transactions'];
                echo "<p><strong>Transaction Count:</strong> " . count($transactions) . "</p>";
                
                if (count($transactions) > 0) {
                    echo "<h3>First Transaction Structure:</h3>";
                    echo "<pre>" . json_encode($transactions[0], JSON_PRETTY_PRINT) . "</pre>";
                } else {
                    echo "<p>⚠️ No transactions found in response</p>";
                }
            } else {
                echo "<p>❌ No 'transactions' key in response</p>";
                echo "<pre>" . json_encode($transactionData, JSON_PRETTY_PRINT) . "</pre>";
            }
        } else {
            echo "<p>❌ API Error (HTTP $httpCode)</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Exception during transactions API call: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Critical Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
