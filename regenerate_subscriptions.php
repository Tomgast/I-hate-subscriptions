<?php
/**
 * REGENERATE SUBSCRIPTIONS FROM RAW TRANSACTIONS
 * 
 * This script will:
 * 1. Clear existing subscriptions for the user
 * 2. Process all raw transactions
 * 3. Regenerate subscriptions based on transaction patterns
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';
require_once 'includes/gocardless_transaction_processor.php';

// Set user ID (you may need to change this)
$userId = 2; // Default to user 2 if not in session
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}

try {
    $pdo = getDBConnection();
    $gocardless = new GoCardlessFinancialService($pdo);
    $processor = new GoCardlessTransactionProcessor($pdo);
    
    echo "<h1>🔁 Regenerating Subscriptions</h1>";
    echo "<p>User ID: $userId</p>";
    
    // Step 1: Clear existing subscriptions for this user
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $deleted = $stmt->rowCount();
    echo "<p>🗑️ Cleared $deleted existing subscriptions</p>";
    
    // Step 2: Get all raw transactions for this user
    $stmt = $pdo->prepare("
        SELECT * FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY booking_date DESC
    ");
    $stmt->execute([$userId]);
    $rawTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rawTransactions)) {
        throw new Exception("No raw transactions found for user $userId");
    }
    
    echo "<p>📊 Found " . count($rawTransactions) . " raw transactions</p>";
    
    // Step 3: Process transactions using the processor
    $accountId = 'recovery_' . time(); // Create a temporary account ID
    
    // Format transactions for the processor
    $transactionData = ['transactions' => []];
    foreach ($rawTransactions as $tx) {
        $transactionData['transactions'][] = [
            'transactionId' => $tx['transaction_id'],
            'bookingDate' => $tx['booking_date'],
            'amount' => $tx['amount'],
            'currency' => $tx['currency'],
            'remittanceInformationUnstructured' => $tx['description'],
            'merchantName' => $tx['merchant_name'] ?? null,
            'status' => 'booked',
            'additionalInformation' => $tx['additional_info'] ?? ''
        ];
    }
    
    // Process transactions
    $result = $processor->processTransactions($userId, $accountId, $transactionData);
    
    if (!$result['success']) {
        throw new Exception("Transaction processing failed: " . ($result['error'] ?? 'Unknown error'));
    }
    
    echo "<p>✅ Processed " . $result['valid_transactions'] . " valid transactions</p>";
    
    // Step 4: Get processed transactions for analysis
    $stmt = $pdo->prepare("
        SELECT * FROM bank_transactions 
        WHERE user_id = ? AND account_id = ?
        ORDER BY booking_date DESC
    ");
    $stmt->execute([$userId, $accountId]);
    $processedTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($processedTransactions)) {
        throw new Exception("No processed transactions found after processing");
    }
    
    // Step 5: Analyze for subscriptions
    $subscriptions = $gocardless->analyzeProcessedTransactionsForSubscriptions($processedTransactions);
    
    if (empty($subscriptions)) {
        throw new Exception("No subscriptions detected in the transaction data");
    }
    
    echo "<p>🔍 Detected " . count($subscriptions) . " potential subscriptions</p>";
    
    // Step 6: Save the subscriptions
    $saved = $gocardless->saveScanResults($userId, $subscriptions);
    
    echo "<h2>✅ Done!</h2>";
    echo "<p>Successfully regenerated $saved subscriptions from transaction data.</p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a> to view your subscriptions.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Add some basic styling
echo "<style>
    body { 
        font-family: Arial, sans-serif; 
        line-height: 1.6; 
        margin: 20px; 
        padding: 20px; 
        max-width: 900px; 
        margin: 0 auto;
    }
    h1 { color: #2c3e50; }
    h2 { color: #3498db; margin-top: 30px; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    pre { 
        background: #f5f5f5; 
        padding: 15px; 
        border-radius: 5px; 
        overflow-x: auto;
    }
</style>";
?>
