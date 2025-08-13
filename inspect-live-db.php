<?php
require_once 'config/db_config.php';

echo "=== LIVE MARIADB DATABASE INSPECTION ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Check database connection and version
    echo "1. DATABASE CONNECTION INFO:\n";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "Database Version: " . $version['version'] . "\n";
    
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbName = $stmt->fetch();
    echo "Current Database: " . $dbName['db_name'] . "\n\n";
    
    // 2. List all tables in the database
    echo "2. ALL TABLES IN DATABASE:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    // 3. Detailed structure of subscriptions table
    echo "3. SUBSCRIPTIONS TABLE STRUCTURE:\n";
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll();
    
    echo "Columns in subscriptions table:\n";
    foreach($columns as $col) {
        echo sprintf("  %-20s %-15s %-10s %-10s %-15s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'], 
            $col['Default'] ?? 'NULL',
            $col['Extra'] ?? ''
        );
    }
    echo "\n";
    
    // 4. Check if subscriptions table exists and has data
    echo "4. SUBSCRIPTIONS TABLE DATA OVERVIEW:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total_count FROM subscriptions");
    $totalCount = $stmt->fetch();
    echo "Total subscriptions in database: " . $totalCount['total_count'] . "\n";
    
    if ($totalCount['total_count'] > 0) {
        // Check data by user
        $stmt = $pdo->query("SELECT user_id, COUNT(*) as count FROM subscriptions GROUP BY user_id ORDER BY count DESC");
        $userCounts = $stmt->fetchAll();
        echo "Subscriptions by user:\n";
        foreach($userCounts as $userCount) {
            echo "  User ID {$userCount['user_id']}: {$userCount['count']} subscriptions\n";
        }
        echo "\n";
        
        // Show sample data
        echo "5. SAMPLE SUBSCRIPTION DATA (first 3 rows):\n";
        $stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY created_at DESC LIMIT 3");
        $samples = $stmt->fetchAll();
        
        foreach($samples as $i => $sample) {
            echo "--- Sample #" . ($i+1) . " ---\n";
            foreach($sample as $key => $value) {
                if (!is_numeric($key)) { // Skip numeric indices
                    echo "  $key: " . ($value ?? 'NULL') . "\n";
                }
            }
            echo "\n";
        }
    }
    
    // 6. Check users table for support@origens.nl
    echo "6. USER VERIFICATION:\n";
    $stmt = $pdo->prepare("SELECT id, email, name, created_at FROM users WHERE email = ?");
    $stmt->execute(['support@origens.nl']);
    $supportUser = $stmt->fetch();
    
    if ($supportUser) {
        echo "Found support@origens.nl user:\n";
        echo "  ID: {$supportUser['id']}\n";
        echo "  Email: {$supportUser['email']}\n";
        echo "  Name: {$supportUser['name']}\n";
        echo "  Created: {$supportUser['created_at']}\n\n";
        
        $userId = $supportUser['id'];
        
        // 7. Check subscriptions for this specific user
        echo "7. SUBSCRIPTIONS FOR support@origens.nl (User ID: $userId):\n";
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $userSubs = $stmt->fetchAll();
        
        echo "Found " . count($userSubs) . " subscriptions for this user:\n";
        foreach($userSubs as $i => $sub) {
            echo "--- Subscription #" . ($i+1) . " ---\n";
            foreach($sub as $key => $value) {
                if (!is_numeric($key)) { // Skip numeric indices
                    echo "  $key: " . ($value ?? 'NULL') . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "ERROR: Could not find user with email support@origens.nl\n";
        
        // Show all users
        echo "All users in database:\n";
        $stmt = $pdo->query("SELECT id, email, name, created_at FROM users ORDER BY created_at DESC");
        $allUsers = $stmt->fetchAll();
        
        foreach($allUsers as $user) {
            echo "  ID: {$user['id']}, Email: {$user['email']}, Name: {$user['name']}, Created: {$user['created_at']}\n";
        }
    }
    
    // 8. Check related tables that might affect subscriptions
    echo "\n8. RELATED TABLES INSPECTION:\n";
    
    // Check bank_connections
    if (in_array('bank_connections', $tables)) {
        echo "bank_connections table structure:\n";
        $stmt = $pdo->query("DESCRIBE bank_connections");
        $bankCols = $stmt->fetchAll();
        foreach($bankCols as $col) {
            echo "  {$col['Field']} ({$col['Type']})\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bank_connections");
        $bankCount = $stmt->fetch();
        echo "Total bank connections: " . $bankCount['count'] . "\n\n";
    }
    
    // Check bank_scan_results
    if (in_array('bank_scan_results', $tables)) {
        echo "bank_scan_results table structure:\n";
        $stmt = $pdo->query("DESCRIBE bank_scan_results");
        $scanCols = $stmt->fetchAll();
        foreach($scanCols as $col) {
            echo "  {$col['Field']} ({$col['Type']})\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bank_scan_results");
        $scanCount = $stmt->fetch();
        echo "Total scan results: " . $scanCount['count'] . "\n\n";
    }
    
    // 9. Test the exact query that dashboard.php uses
    echo "9. TESTING DASHBOARD QUERY:\n";
    if ($supportUser) {
        $userId = $supportUser['id'];
        echo "Testing query: SELECT * FROM subscriptions WHERE user_id = $userId ORDER BY created_at DESC\n";
        
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $dashboardResult = $stmt->fetchAll();
        
        echo "Dashboard query returned: " . count($dashboardResult) . " rows\n";
        if (count($dashboardResult) > 0) {
            echo "First result keys: " . implode(', ', array_keys($dashboardResult[0])) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
