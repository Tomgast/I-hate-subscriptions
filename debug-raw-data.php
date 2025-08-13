<?php
require_once 'config/db_config.php';

echo "=== RAW DATABASE DEBUG (NO AUTH REQUIRED) ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Check all users to find the right user ID
    echo "1. USERS TABLE:\n";
    $stmt = $pdo->query("SELECT id, email, name FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    foreach($users as $user) {
        echo "  User ID: {$user['id']}, Email: {$user['email']}, Name: {$user['name']}\n";
    }
    echo "\n";
    
    // 2. Find the support@origens.nl user specifically
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['support@origens.nl']);
    $supportUser = $stmt->fetch();
    
    if ($supportUser) {
        $userId = $supportUser['id'];
        echo "2. FOUND SUPPORT USER - ID: $userId\n\n";
        
        // 3. Check subscriptions for this user
        echo "3. SUBSCRIPTIONS FOR support@origens.nl (ID: $userId):\n";
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll();
        
        echo "Found " . count($subscriptions) . " subscriptions:\n";
        foreach($subscriptions as $i => $sub) {
            echo "--- Subscription #" . ($i+1) . " ---\n";
            foreach($sub as $key => $value) {
                if (!is_numeric($key)) { // Skip numeric indices
                    echo "  $key: " . ($value ?? 'NULL') . "\n";
                }
            }
            echo "\n";
        }
        
        // 4. Check table structure
        echo "4. SUBSCRIPTIONS TABLE STRUCTURE:\n";
        $stmt = $pdo->query("DESCRIBE subscriptions");
        $columns = $stmt->fetchAll();
        
        echo "Available columns:\n";
        foreach($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) - Default: {$col['Default']}, Null: {$col['Null']}\n";
        }
        echo "\n";
        
        // 5. Check if there are any subscriptions at all in the database
        echo "5. TOTAL SUBSCRIPTIONS IN DATABASE:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM subscriptions");
        $total = $stmt->fetch();
        echo "Total subscriptions across all users: {$total['total']}\n";
        
        // 6. Show recent subscriptions from any user
        echo "6. RECENT SUBSCRIPTIONS (ANY USER):\n";
        $stmt = $pdo->query("SELECT id, user_id, name, cost, amount, status, is_active, created_at FROM subscriptions ORDER BY created_at DESC LIMIT 5");
        $recent = $stmt->fetchAll();
        
        foreach($recent as $sub) {
            echo "  ID: {$sub['id']}, User: {$sub['user_id']}, Name: {$sub['name']}, Cost: {$sub['cost']}, Amount: {$sub['amount']}, Status: {$sub['status']}, Active: {$sub['is_active']}\n";
        }
        
    } else {
        echo "2. ERROR: Could not find user with email support@origens.nl\n";
        
        // Show all users to help debug
        echo "All users in database:\n";
        $stmt = $pdo->query("SELECT id, email, name, created_at FROM users ORDER BY created_at DESC");
        $allUsers = $stmt->fetchAll();
        
        foreach($allUsers as $user) {
            echo "  ID: {$user['id']}, Email: {$user['email']}, Name: {$user['name']}, Created: {$user['created_at']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
