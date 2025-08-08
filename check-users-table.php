<?php
/**
 * Check Users Table Schema
 */

// Suppress HTTP_HOST warnings in CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
}

require_once 'config/db_config.php';

echo "=== Users Table Schema Check ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Check users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "Current users table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - Null: {$column['Null']} - Default: {$column['Default']}\n";
    }
    
    echo "\nChecking for required columns:\n";
    $requiredColumns = ['id', 'email', 'name', 'subscription_type', 'status'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $required) {
        if (in_array($required, $existingColumns)) {
            echo "✅ $required - EXISTS\n";
        } else {
            echo "❌ $required - MISSING\n";
        }
    }
    
    // Check if problematic columns exist
    $problematicColumns = ['password_hash', 'is_pro'];
    echo "\nChecking for problematic columns:\n";
    foreach ($problematicColumns as $problematic) {
        if (in_array($problematic, $existingColumns)) {
            echo "⚠️  $problematic - EXISTS (signup code expects this)\n";
        } else {
            echo "❌ $problematic - MISSING (signup code will fail)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== End of Check ===\n";
?>
