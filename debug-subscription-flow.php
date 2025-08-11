<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in\n";
    exit;
}

$userId = $_SESSION['user_id'];
echo "=== SUBSCRIPTION FLOW DEBUG ===\n";
echo "User ID: $userId\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Check what's in subscriptions table
    echo "1. SUBSCRIPTIONS TABLE:\n";
    $stmt = $pdo->prepare("SELECT id, name, amount, status, billing_cycle, created_at FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subs = $stmt->fetchAll();
    
    echo "Found " . count($subs) . " subscriptions:\n";
    foreach($subs as $sub) {
        echo "- ID: {$sub['id']}, Name: {$sub['name']}, Amount: {$sub['amount']}, Status: {$sub['status']}, Cycle: {$sub['billing_cycle']}\n";
    }
    echo "\n";
    
    // 2. Check bank connections
    echo "2. BANK CONNECTIONS:\n";
    $stmt = $pdo->prepare("SELECT provider, account_id, status FROM bank_connections WHERE user_id = ?");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll();
    
    echo "Found " . count($connections) . " bank connections:\n";
    foreach($connections as $conn) {
        echo "- Provider: {$conn['provider']}, Account: {$conn['account_id']}, Status: {$conn['status']}\n";
    }
    echo "\n";
    
    // 3. Check recent scans
    echo "3. RECENT SCANS:\n";
    $stmt = $pdo->prepare("SELECT provider, status, subscriptions_found, created_at FROM bank_scan_results WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$userId]);
    $scans = $stmt->fetchAll();
    
    echo "Found " . count($scans) . " recent scans:\n";
    foreach($scans as $scan) {
        echo "- Provider: {$scan['provider']}, Status: {$scan['status']}, Subs Found: {$scan['subscriptions_found']}, Date: {$scan['created_at']}\n";
    }
    echo "\n";
    
    // 4. Calculate dashboard stats manually
    echo "4. DASHBOARD CALCULATION TEST:\n";
    $totalActive = 0;
    $monthlyTotal = 0;
    $yearlyTotal = 0;
    
    foreach($subs as $sub) {
        $isActive = ($sub['status'] == 'active');
        if ($isActive) {
            $totalActive++;
            $amount = floatval($sub['amount']);
            
            switch($sub['billing_cycle']) {
                case 'monthly':
                    $monthlyTotal += $amount;
                    break;
                case 'yearly':
                    $monthlyTotal += $amount / 12;
                    break;
                default:
                    $monthlyTotal += $amount;
            }
        }
    }
    
    $yearlyTotal = $monthlyTotal * 12;
    
    echo "Active subscriptions: $totalActive\n";
    echo "Monthly total: €" . number_format($monthlyTotal, 2) . "\n";
    echo "Yearly total: €" . number_format($yearlyTotal, 2) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
