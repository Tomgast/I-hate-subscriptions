<?php
/**
 * COMPREHENSIVE GOCARDLESS API DEBUG
 * Debug why GoCardless API returns zero transactions for real bank account
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
    <title>GoCardless API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 12px; }
        .api-call { border: 2px solid #007cba; padding: 10px; margin: 10px 0; background: #f0f8ff; }
    </style>
</head>
<body>";

echo "<h1>🔍 GoCardless API Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    echo "<div class='section'>";
    echo "<h2>1. Check Access Token</h2>";
    
    // Get access token using reflection to access private method
    $reflection = new ReflectionClass($gocardlessService);
    $getTokenMethod = $reflection->getMethod('getAccessToken');
    $getTokenMethod->setAccessible(true);
    
    try {
        $accessToken = $getTokenMethod->invoke($gocardlessService);
        if ($accessToken) {
            echo "<p class='success'>✅ Access token obtained: " . substr($accessToken, 0, 20) . "...</p>";
        } else {
            echo "<p class='error'>❌ Failed to get access token</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Access token error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Check Connected Accounts</h2>";
    
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data 
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless' AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p class='error'>❌ No active GoCardless accounts found</p>";
    } else {
        echo "<p class='success'>✅ Found " . count($accounts) . " active account(s)</p>";
        
        foreach ($accounts as $i => $account) {
            $accountId = $account['account_id'];
            $connectionData = json_decode($account['connection_data'], true);
            
            echo "<h3>Account " . ($i + 1) . ": $accountId</h3>";
            echo "<pre>" . json_encode($connectionData, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
    echo "</div>";
    
    if (!empty($accounts) && $accessToken) {
        echo "<div class='section'>";
        echo "<h2>3. Test Direct API Calls</h2>";
        
        foreach ($accounts as $i => $account) {
            $accountId = $account['account_id'];
            
            echo "<h3>Testing Account: $accountId</h3>";
            
            // Test different date ranges
            $dateRanges = [
                ['name' => 'Last 7 days', 'from' => date('Y-m-d', strtotime('-7 days')), 'to' => date('Y-m-d')],
                ['name' => 'Last 30 days', 'from' => date('Y-m-d', strtotime('-30 days')), 'to' => date('Y-m-d')],
                ['name' => 'Last 90 days', 'from' => date('Y-m-d', strtotime('-90 days')), 'to' => date('Y-m-d')],
                ['name' => 'Last 365 days', 'from' => date('Y-m-d', strtotime('-365 days')), 'to' => date('Y-m-d')]
            ];
            
            foreach ($dateRanges as $range) {
                echo "<div class='api-call'>";
                echo "<h4>📅 {$range['name']} ({$range['from']} to {$range['to']})</h4>";
                
                // Get API base URL using reflection
                $apiBaseUrlProperty = $reflection->getProperty('apiBaseUrl');
                $apiBaseUrlProperty->setAccessible(true);
                $apiBaseUrl = $apiBaseUrlProperty->getValue($gocardlessService);
                
                $transactionUrl = $apiBaseUrl . 'accounts/' . $accountId . '/transactions/' . 
                                 '?date_from=' . $range['from'] . '&date_to=' . $range['to'];
                
                echo "<p><strong>API URL:</strong> " . htmlspecialchars($transactionUrl) . "</p>";
                
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $transactionUrl,
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
                    echo "<p class='error'>❌ cURL Error: " . htmlspecialchars($curlError) . "</p>";
                } elseif ($httpCode !== 200) {
                    echo "<p class='error'>❌ HTTP Error $httpCode</p>";
                    echo "<p><strong>Response:</strong></p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                } else {
                    $data = json_decode($response, true);
                    if ($data) {
                        $bookedCount = isset($data['transactions']['booked']) ? count($data['transactions']['booked']) : 0;
                        $pendingCount = isset($data['transactions']['pending']) ? count($data['transactions']['pending']) : 0;
                        $totalCount = $bookedCount + $pendingCount;
                        
                        if ($totalCount > 0) {
                            echo "<p class='success'>✅ Found $totalCount transactions ($bookedCount booked, $pendingCount pending)</p>";
                            
                            // Show first few transactions
                            if ($bookedCount > 0) {
                                echo "<h5>📋 Sample Booked Transactions:</h5>";
                                $sampleTransactions = array_slice($data['transactions']['booked'], 0, 3);
                                foreach ($sampleTransactions as $j => $tx) {
                                    echo "<div style='border: 1px solid green; padding: 5px; margin: 5px 0; background: #f0fff0;'>";
                                    echo "<strong>Transaction " . ($j + 1) . ":</strong><br>";
                                    echo "Date: " . ($tx['bookingDate'] ?? 'N/A') . "<br>";
                                    echo "Amount: " . ($tx['transactionAmount']['amount'] ?? 'N/A') . " " . ($tx['transactionAmount']['currency'] ?? 'N/A') . "<br>";
                                    echo "Merchant: " . ($tx['creditorName'] ?? $tx['debtorName'] ?? 'N/A') . "<br>";
                                    echo "Description: " . ($tx['remittanceInformationUnstructured'] ?? 'N/A') . "<br>";
                                    echo "</div>";
                                }
                            }
                            
                            // This is the successful case - break out of loops
                            echo "<p class='info'>🎯 <strong>SUCCESS!</strong> This date range returns real transaction data.</p>";
                            break 2;
                            
                        } else {
                            echo "<p class='warning'>⚠️ No transactions found in this date range</p>";
                        }
                        
                        // Show full response structure for debugging
                        echo "<details>";
                        echo "<summary>View full API response</summary>";
                        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                        echo "</details>";
                        
                    } else {
                        echo "<p class='error'>❌ Invalid JSON response</p>";
                        echo "<pre>" . htmlspecialchars($response) . "</pre>";
                    }
                }
                echo "</div>";
            }
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h2>4. Account Details & Permissions</h2>";
        
        foreach ($accounts as $account) {
            $accountId = $account['account_id'];
            
            echo "<h3>Account Details: $accountId</h3>";
            
            // Get account details
            $accountUrl = $apiBaseUrl . 'accounts/' . $accountId . '/';
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $accountUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode === 200) {
                $accountDetails = json_decode($response, true);
                echo "<pre>" . json_encode($accountDetails, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='error'>❌ Failed to get account details (HTTP $httpCode)</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
        echo "</div>";
    }
    
    echo "<div class='section'>";
    echo "<h2>5. Recommendations</h2>";
    
    echo "<div style='border: 2px solid blue; padding: 15px; background: #f0f8ff;'>";
    echo "<h3>🔧 Next Steps Based on Results:</h3>";
    echo "<ul>";
    echo "<li><strong>If transactions found in shorter date ranges:</strong> The issue is with the 365-day date range in the scan code</li>";
    echo "<li><strong>If no transactions found in any range:</strong> Account permissions or consent issue</li>";
    echo "<li><strong>If API errors:</strong> Access token or account ID issue</li>";
    echo "<li><strong>If account details show limited permissions:</strong> Need to re-authorize with broader consent</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
