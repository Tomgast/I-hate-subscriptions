<?php
/**
 * Test database connection and show available data
 */

// Try to load the database configuration
try {
    require_once __DIR__ . '/config/db_config.php';
    echo "✓ Database configuration loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to load database config: " . $e->getMessage() . "\n";
    exit(1);
}

// Test database connection
try {
    $pdo = getDBConnection();
    echo "✓ Database connection successful\n";
    
    // Show database info
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "✓ Connected to database: " . $result['db_name'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// List all tables
echo "\n=== Available Tables ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in database\n";
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Failed to list tables: " . $e->getMessage() . "\n";
}

// Check users table
echo "\n=== Users Table ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Total users: " . $result['count'] . "\n";
    
    // Show first few users (without sensitive data)
    $stmt = $pdo->query("SELECT id, email, name, is_pro, created_at FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "User {$user['id']}: {$user['email']} (Pro: " . ($user['is_pro'] ? 'Yes' : 'No') . ")\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to query users: " . $e->getMessage() . "\n";
}

// Check subscriptions table
echo "\n=== Subscriptions Table ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions");
    $result = $stmt->fetch();
    echo "Total subscriptions: " . $result['count'] . "\n";
    
    // Show subscriptions by user
    $stmt = $pdo->query("
        SELECT user_id, COUNT(*) as sub_count 
        FROM subscriptions 
        GROUP BY user_id 
        ORDER BY user_id
    ");
    $userSubs = $stmt->fetchAll();
    
    foreach ($userSubs as $userSub) {
        echo "User {$userSub['user_id']}: {$userSub['sub_count']} subscriptions\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to query subscriptions: " . $e->getMessage() . "\n";
}

// Check bank scan data
echo "\n=== Bank Scan Data ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bank_scan_data");
    $result = $stmt->fetch();
    echo "Total bank scan records: " . $result['count'] . "\n";
    
    // Show scan data by user
    $stmt = $pdo->query("
        SELECT user_id, COUNT(*) as scan_count 
        FROM bank_scan_data 
        GROUP BY user_id 
        ORDER BY user_id
    ");
    $userScans = $stmt->fetchAll();
    
    foreach ($userScans as $userScan) {
        echo "User {$userScan['user_id']}: {$userScan['scan_count']} bank scan records\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to query bank scan data: " . $e->getMessage() . "\n";
}

echo "\n=== Connection Test Complete ===\n";
?>
