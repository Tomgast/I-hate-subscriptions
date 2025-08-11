<?php
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';

try {
    $pdo = getDBConnection();
    $userId = 2; // Your user ID
    
    echo "=== REPROCESSING EXISTING DATA ===\n";
    
    // Clear broken subscriptions first
    $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Cleared existing broken subscriptions\n";
    
    // Initialize GoCardless service
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    // Get the processed transactions that are already stored
    require_once 'gocardless_transaction_processor.php';
    $processor = new GoCardlessTransactionProcessor($pdo);
    
    // Get user's connected accounts
    $stmt = $pdo->prepare("
        SELECT account_id 
        FROM bank_connections 
        WHERE user_id = ? AND provider = 'gocardless' AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allSubscriptions = [];
    
    foreach ($accounts as $account) {
        $accountId = $account['account_id'];
        echo "Processing account: $accountId\n";
        
        // Get processed transactions for this account
        $processedTransactions = $processor->getTransactionsForAnalysis($userId, $accountId);
        echo "Found " . count($processedTransactions) . " processed transactions\n";
        
        // Use reflection to call the private method for subscription analysis
        $reflection = new ReflectionClass($gocardlessService);
        $method = $reflection->getMethod('analyzeProcessedTransactionsForSubscriptions');
        $method->setAccessible(true);
        
        // Analyze for subscription patterns
        $subscriptions = $method->invoke($gocardlessService, $processedTransactions);
        $allSubscriptions = array_merge($allSubscriptions, $subscriptions);
        
        echo "Found " . count($subscriptions) . " subscriptions for this account\n";
    }
    
    echo "\nTotal subscriptions found: " . count($allSubscriptions) . "\n";
    
    // Save subscriptions using the fixed saveScanResults method
    $reflection = new ReflectionClass($gocardlessService);
    $method = $reflection->getMethod('saveScanResults');
    $method->setAccessible(true);
    
    $scanId = $method->invoke($gocardlessService, $userId, $allSubscriptions);
    
    echo "Saved subscriptions with scan ID: $scanId\n";
    
    // Verify the results
    $stmt = $pdo->prepare("SELECT name, amount, billing_cycle, status FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $savedSubs = $stmt->fetchAll();
    
    echo "\n=== VERIFICATION ===\n";
    echo "Subscriptions now in database: " . count($savedSubs) . "\n";
    
    foreach($savedSubs as $sub) {
        echo "- {$sub['name']}: €{$sub['amount']} ({$sub['billing_cycle']}) - {$sub['status']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
