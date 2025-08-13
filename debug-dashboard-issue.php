<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in - please login first\n";
    exit;
}

$userId = $_SESSION['user_id'];
echo "=== DASHBOARD DEBUG FOR USER ID: $userId ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Check what's actually in the subscriptions table
    echo "1. RAW SUBSCRIPTIONS TABLE DATA:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    echo "Found " . count($subscriptions) . " subscriptions:\n";
    foreach($subscriptions as $i => $sub) {
        echo "--- Subscription #" . ($i+1) . " ---\n";
        foreach($sub as $key => $value) {
            if (!is_numeric($key)) { // Skip numeric indices from PDO::FETCH_BOTH
                echo "  $key: " . ($value ?? 'NULL') . "\n";
            }
        }
        echo "\n";
    }
    
    // 2. Check what columns actually exist
    echo "2. SUBSCRIPTIONS TABLE STRUCTURE:\n";
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll();
    
    echo "Available columns:\n";
    foreach($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) - Default: {$col['Default']}, Null: {$col['Null']}\n";
    }
    echo "\n";
    
    // 3. Test the exact query from dashboard.php
    echo "3. TESTING DASHBOARD QUERY:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $dashboardSubs = $stmt->fetchAll();
    
    echo "Dashboard query returned " . count($dashboardSubs) . " subscriptions\n";
    if (count($dashboardSubs) > 0) {
        echo "First subscription keys: " . implode(', ', array_keys($dashboardSubs[0])) . "\n";
    }
    echo "\n";
    
    // 4. Check for any database errors
    echo "4. DATABASE CONNECTION STATUS:\n";
    echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    
    // 5. Check user session
    echo "5. SESSION INFO:\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    echo "User Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
    echo "User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
