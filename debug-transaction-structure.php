<?php
/**
 * DEBUG TRANSACTION STRUCTURE
 * Examine the actual structure of GoCardless transactions to fix parsing
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

echo "<h1>🔍 GoCardless Transaction Structure Debug</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    // Get connected accounts
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
        $accountId = $account['account_id'];
        echo "<h2>Account: $accountId</h2>";
        
        // Get raw transactions
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
        curl_close($curl);
        
        if ($httpCode !== 200) {
            echo "<p>❌ Failed to get transactions (HTTP $httpCode)</p>";
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['transactions'])) {
            echo "<p>❌ No transactions array in response</p>";
            echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
            continue;
        }
        
        $transactions = $data['transactions'];
        echo "<p><strong>Total Transactions:</strong> " . count($transactions) . "</p>";
        
        if (empty($transactions)) {
            echo "<p>❌ No transactions found</p>";
            continue;
        }
        
        // Show first few transaction structures
        echo "<h3>📋 Transaction Structure Analysis</h3>";
        
        $sampleSize = min(3, count($transactions));
        for ($i = 0; $i < $sampleSize; $i++) {
            $transaction = $transactions[$i];
            echo "<h4>Transaction " . ($i + 1) . " Structure:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
            echo htmlspecialchars(json_encode($transaction, JSON_PRETTY_PRINT));
            echo "</pre>";
            
            // Analyze amount fields
            echo "<h5>Amount Field Analysis:</h5>";
            echo "<ul>";
            
            if (isset($transaction['transactionAmount'])) {
                if (is_array($transaction['transactionAmount'])) {
                    echo "<li><strong>transactionAmount (array):</strong> " . json_encode($transaction['transactionAmount']) . "</li>";
                    if (isset($transaction['transactionAmount']['amount'])) {
                        echo "<li><strong>transactionAmount.amount:</strong> " . $transaction['transactionAmount']['amount'] . "</li>";
                    }
                    if (isset($transaction['transactionAmount']['currency'])) {
                        echo "<li><strong>transactionAmount.currency:</strong> " . $transaction['transactionAmount']['currency'] . "</li>";
                    }
                } else {
                    echo "<li><strong>transactionAmount (scalar):</strong> " . $transaction['transactionAmount'] . "</li>";
                }
            } else {
                echo "<li>❌ No 'transactionAmount' field</li>";
            }
            
            if (isset($transaction['amount'])) {
                echo "<li><strong>amount:</strong> " . $transaction['amount'] . "</li>";
            } else {
                echo "<li>❌ No 'amount' field</li>";
            }
            
            // Check other possible amount fields
            $possibleAmountFields = ['value', 'transactionValue', 'amountValue', 'bookingAmount'];
            foreach ($possibleAmountFields as $field) {
                if (isset($transaction[$field])) {
                    echo "<li><strong>$field:</strong> " . json_encode($transaction[$field]) . "</li>";
                }
            }
            
            echo "</ul>";
            
            // Analyze merchant fields
            echo "<h5>Merchant Field Analysis:</h5>";
            echo "<ul>";
            
            $merchantFields = ['creditorName', 'debtorName', 'remittanceInformationUnstructured', 'additionalInformation', 'proprietaryBankTransactionCode'];
            foreach ($merchantFields as $field) {
                if (isset($transaction[$field]) && !empty($transaction[$field])) {
                    echo "<li><strong>$field:</strong> " . htmlspecialchars($transaction[$field]) . "</li>";
                }
            }
            
            echo "</ul>";
            
            echo "<hr>";
        }
        
        // Summary of all amount structures found
        echo "<h3>📊 Amount Structure Summary</h3>";
        $amountStructures = [];
        
        foreach ($transactions as $transaction) {
            $structure = 'unknown';
            
            if (isset($transaction['transactionAmount']['amount'])) {
                $structure = 'transactionAmount.amount';
            } elseif (isset($transaction['transactionAmount'])) {
                $structure = 'transactionAmount (scalar)';
            } elseif (isset($transaction['amount'])) {
                $structure = 'amount';
            }
            
            if (!isset($amountStructures[$structure])) {
                $amountStructures[$structure] = 0;
            }
            $amountStructures[$structure]++;
        }
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Amount Structure</th><th>Count</th><th>Percentage</th></tr>";
        foreach ($amountStructures as $structure => $count) {
            $percentage = round(($count / count($transactions)) * 100, 1);
            echo "<tr><td>$structure</td><td>$count</td><td>$percentage%</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
