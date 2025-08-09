<?php
/**
 * DIAGNOSE GOCARDLESS CONNECTION
 * Check if we're getting real data or test data
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
    <title>GoCardless Connection Diagnosis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .issue { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔧 GoCardless Connection Diagnosis</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    echo "<h2>1. Check GoCardless Credentials</h2>";
    try {
        $accessToken = $gocardlessService->getAccessToken();
        echo "<p class='success'>✅ GoCardless API access token obtained</p>";
    } catch (Exception $e) {
        echo "<p class='issue'>❌ Failed to get access token: " . $e->getMessage() . "</p>";
        echo "</body></html>";
        exit;
    }
    
    echo "<h2>2. Check Bank Connections in Database</h2>";
    $stmt = $pdo->prepare("
        SELECT account_id, connection_data, status, created_at
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "<p class='issue'>❌ No GoCardless connections found</p>";
        echo "<p>You need to connect a bank account first via the bank connection flow.</p>";
        echo "</body></html>";
        exit;
    }
    
    echo "<p class='success'>✅ Found " . count($connections) . " connection(s)</p>";
    
    foreach ($connections as $i => $conn) {
        echo "<h3>Connection " . ($i + 1) . ":</h3>";
        echo "<ul>";
        echo "<li><strong>Account ID:</strong> " . htmlspecialchars($conn['account_id']) . "</li>";
        echo "<li><strong>Status:</strong> " . $conn['status'] . "</li>";
        echo "<li><strong>Created:</strong> " . $conn['created_at'] . "</li>";
        echo "</ul>";
        
        // Check if this looks like test data
        if (strpos($conn['account_id'], 'test') !== false) {
            echo "<p class='issue'>⚠️ This looks like TEST DATA (account ID contains 'test')</p>";
        }
    }
    
    echo "<h2>3. Test Direct API Call to GoCardless</h2>";
    
    $accountId = $connections[0]['account_id'];
    echo "<p>Testing with account: <strong>$accountId</strong></p>";
    
    // Test account details first
    echo "<h3>3a. Account Details</h3>";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    
    if ($httpCode === 200) {
        $accountData = json_decode($response, true);
        echo "<p class='success'>✅ Account details retrieved</p>";
        echo "<pre>" . json_encode($accountData, JSON_PRETTY_PRINT) . "</pre>";
        
        // Check if this is real bank data
        if (isset($accountData['institution_id'])) {
            echo "<p><strong>Institution:</strong> " . $accountData['institution_id'] . "</p>";
        }
    } else {
        echo "<p class='issue'>❌ Failed to get account details</p>";
        echo "<pre>$response</pre>";
    }
    
    // Test transactions without date filter first
    echo "<h3>3b. All Available Transactions (no date filter)</h3>";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    
    if ($httpCode === 200) {
        $transactionData = json_decode($response, true);
        
        if (isset($transactionData['transactions'])) {
            $transactions = $transactionData['transactions'];
            echo "<p class='success'>✅ Retrieved " . count($transactions) . " transactions</p>";
            
            if (count($transactions) === 1 && isset($transactions[0]['creditorName']) && $transactions[0]['creditorName'] === 'Netflix') {
                echo "<p class='issue'>⚠️ This appears to be TEST DATA (single Netflix transaction)</p>";
                echo "<p><strong>Likely issue:</strong> You're connected to GoCardless sandbox or test account</p>";
            }
            
            // Show first few transactions
            echo "<h4>Sample Transactions:</h4>";
            $sampleCount = min(3, count($transactions));
            for ($i = 0; $i < $sampleCount; $i++) {
                $t = $transactions[$i];
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
                echo "<strong>Transaction " . ($i + 1) . ":</strong><br>";
                echo "Amount: " . ($t['transactionAmount']['amount'] ?? 'N/A') . " " . ($t['transactionAmount']['currency'] ?? '') . "<br>";
                echo "Date: " . ($t['bookingDate'] ?? $t['valueDate'] ?? 'N/A') . "<br>";
                echo "Merchant: " . ($t['creditorName'] ?? 'N/A') . "<br>";
                echo "Description: " . ($t['remittanceInformationUnstructured'] ?? 'N/A') . "<br>";
                echo "</div>";
            }
            
        } else {
            echo "<p class='issue'>❌ No transactions array in response</p>";
            echo "<pre>" . json_encode($transactionData, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p class='issue'>❌ Failed to get transactions</p>";
        echo "<pre>$response</pre>";
    }
    
    echo "<h2>4. Diagnosis Summary</h2>";
    
    if (strpos($accountId, 'test') !== false) {
        echo "<div style='border: 2px solid red; padding: 15px; background: #ffe6e6;'>";
        echo "<h3 class='issue'>🚨 ISSUE IDENTIFIED: TEST DATA</h3>";
        echo "<p>You're getting test data instead of real bank transactions. This means:</p>";
        echo "<ul>";
        echo "<li>You might be using GoCardless <strong>sandbox/test environment</strong></li>";
        echo "<li>The bank connection process didn't complete properly</li>";
        echo "<li>You need to connect to a <strong>real bank account</strong> in <strong>live mode</strong></li>";
        echo "</ul>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Check your GoCardless credentials - make sure you're using LIVE credentials, not sandbox</li>";
        echo "<li>Re-connect your bank account using the proper live bank connection flow</li>";
        echo "<li>Make sure you're selecting your actual bank, not a test institution</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='border: 2px solid green; padding: 15px; background: #e6ffe6;'>";
        echo "<h3 class='success'>✅ Connection looks legitimate</h3>";
        echo "<p>The account ID doesn't appear to be test data. The issue might be:</p>";
        echo "<ul>";
        echo "<li>Date range limitations</li>";
        echo "<li>Bank-specific data availability</li>";
        echo "<li>Consent scope issues</li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='issue'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
