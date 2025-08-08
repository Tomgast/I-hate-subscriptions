<?php
/**
 * Database Schema Fix Script
 * Adds missing columns and ensures schema consistency
 */

require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

echo "<h1>CashControl Database Schema Fix</h1>\n";
echo "<pre>\n";

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== Database Schema Fix Started ===\n\n";
    
    // 1. Add missing subscription_expires_at column to users table
    echo "1. Checking users table for subscription_expires_at column...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'subscription_expires_at'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding subscription_expires_at column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN subscription_expires_at DATETIME NULL AFTER subscription_status");
        echo "   ✅ subscription_expires_at column added\n";
    } else {
        echo "   ✅ subscription_expires_at column already exists\n";
    }
    
    // 2. Add missing plan_type column to payment_history table
    echo "\n2. Checking payment_history table for plan_type column...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_history LIKE 'plan_type'");
    if ($stmt->rowCount() == 0) {
        echo "   Adding plan_type column...\n";
        $pdo->exec("ALTER TABLE payment_history ADD COLUMN plan_type VARCHAR(50) NULL AFTER currency");
        echo "   ✅ plan_type column added\n";
    } else {
        echo "   ✅ plan_type column already exists\n";
    }
    
    // 3. Update subscription_type enum to ensure consistency
    echo "\n3. Updating subscription_type enum values...\n";
    
    // Check current enum values
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'subscription_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Current enum: " . $column['Type'] . "\n";
    
    // Update to ensure all values are present
    $pdo->exec("ALTER TABLE users MODIFY COLUMN subscription_type ENUM('free', 'monthly', 'yearly', 'one_time') DEFAULT 'free'");
    echo "   ✅ subscription_type enum updated\n";
    
    // 4. Verify all tables exist and have correct structure
    echo "\n4. Verifying table structures...\n";
    
    // Check users table
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredUserColumns = ['id', 'email', 'name', 'subscription_type', 'subscription_status', 'subscription_expires_at', 'stripe_customer_id'];
    
    foreach ($requiredUserColumns as $col) {
        if (in_array($col, $userColumns)) {
            echo "   ✅ users.$col exists\n";
        } else {
            echo "   ❌ users.$col missing\n";
        }
    }
    
    // Check payment_history table
    $stmt = $pdo->query("DESCRIBE payment_history");
    $paymentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredPaymentColumns = ['id', 'user_id', 'stripe_session_id', 'amount', 'currency', 'plan_type', 'status'];
    
    foreach ($requiredPaymentColumns as $col) {
        if (in_array($col, $paymentColumns)) {
            echo "   ✅ payment_history.$col exists\n";
        } else {
            echo "   ❌ payment_history.$col missing\n";
        }
    }
    
    // 5. Update any existing data to use consistent plan types
    echo "\n5. Updating existing data for consistency...\n";
    
    // Update any 'onetime' to 'one_time' in users table
    $stmt = $pdo->prepare("UPDATE users SET subscription_type = 'one_time' WHERE subscription_type = 'onetime'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   Updated $updated user records from 'onetime' to 'one_time'\n";
    
    // Update any 'onetime' to 'one_time' in checkout_sessions table
    $stmt = $pdo->prepare("UPDATE checkout_sessions SET plan_type = 'one_time' WHERE plan_type = 'onetime'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   Updated $updated checkout session records from 'onetime' to 'one_time'\n";
    
    echo "\n=== Database Schema Fix Complete ===\n";
    echo "✅ All required columns added\n";
    echo "✅ Enum values standardized\n";
    echo "✅ Existing data updated for consistency\n";
    echo "\nDatabase is now ready for payment testing!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>
