<?php
/**
 * Check Required Tables for Signup Process
 */

// Suppress HTTP_HOST warnings in CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
}

require_once 'config/db_config.php';

echo "=== Table Existence Check ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Get all existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "All existing tables:\n";
    foreach ($existingTables as $table) {
        echo "- $table\n";
    }
    
    echo "\nChecking tables required for signup:\n";
    $requiredTables = ['users', 'user_sessions'];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✅ $table - EXISTS\n";
        } else {
            echo "❌ $table - MISSING\n";
        }
    }
    
    // If user_sessions doesn't exist, show what we need to do
    if (!in_array('user_sessions', $existingTables)) {
        echo "\n⚠️  user_sessions table is missing!\n";
        echo "Signup process will fail because it tries to insert session data.\n";
        echo "Options:\n";
        echo "1. Create user_sessions table\n";
        echo "2. Modify signup to not use sessions table\n";
        echo "3. Use PHP sessions only (no database sessions)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== End of Check ===\n";
?>
