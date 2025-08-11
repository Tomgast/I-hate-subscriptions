<?php
require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "=== DATABASE SUBSCRIPTIONS DEBUG ===\n";
    
    // Check subscriptions table
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = 2");
    $stmt->execute();
    $subs = $stmt->fetchAll();
    
    echo "Database subscriptions found: " . count($subs) . "\n\n";
    
    foreach($subs as $sub) {
        echo "ID: " . $sub['id'] . "\n";
        echo "Name: " . $sub['name'] . "\n";
        echo "Amount: " . $sub['amount'] . "\n";
        echo "Status: " . $sub['status'] . "\n";
        echo "Billing Cycle: " . $sub['billing_cycle'] . "\n";
        echo "Created: " . $sub['created_at'] . "\n";
        echo "---\n";
    }
    
    // Check recent bank scan results
    echo "\n=== RECENT BANK SCAN RESULTS ===\n";
    $stmt = $pdo->prepare("SELECT * FROM bank_scan_results WHERE user_id = 2 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "Recent scan results: " . count($results) . "\n\n";
    
    foreach($results as $result) {
        echo "Scan ID: " . $result['id'] . "\n";
        echo "Provider: " . $result['provider'] . "\n";
        echo "Status: " . $result['status'] . "\n";
        echo "Subscriptions Found: " . $result['subscriptions_found'] . "\n";
        echo "Created: " . $result['created_at'] . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
