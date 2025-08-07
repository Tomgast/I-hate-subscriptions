<?php
/**
 * Database Migration: New Pricing Model Support
 * 
 * This script adds support for the new pricing model:
 * - Monthly subscription (€3/month)
 * - Yearly subscription (€25/year) 
 * - One-time scan (€25 with 1-year reminder access)
 */

require_once __DIR__ . '/../config/db_config.php';

function migratePricingModel() {
    try {
        $pdo = getDBConnection();
        
        echo "Starting pricing model migration...\n";
        
        // Add new columns to users table
        $userTableUpdates = [
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_status ENUM('active', 'cancelled', 'expired') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS has_scan_access BOOLEAN DEFAULT FALSE",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS scan_access_type ENUM('one_time', 'subscription') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS reminder_access_expires_at DATETIME DEFAULT NULL"
        ];
        
        foreach ($userTableUpdates as $sql) {
            echo "Executing: " . substr($sql, 0, 50) . "...\n";
            $pdo->exec($sql);
        }
        
        // Add plan_type column to checkout_sessions table
        $checkoutSessionUpdate = "ALTER TABLE checkout_sessions ADD COLUMN IF NOT EXISTS plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan'";
        echo "Executing: " . substr($checkoutSessionUpdate, 0, 50) . "...\n";
        $pdo->exec($checkoutSessionUpdate);
        
        // Add plan_type column to payment_history table
        $paymentHistoryUpdate = "ALTER TABLE payment_history ADD COLUMN IF NOT EXISTS plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan'";
        echo "Executing: " . substr($paymentHistoryUpdate, 0, 50) . "...\n";
        $pdo->exec($paymentHistoryUpdate);
        
        // Create subscription_history table for tracking subscription changes
        $subscriptionHistoryTable = "
            CREATE TABLE IF NOT EXISTS subscription_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subscription_type ENUM('monthly', 'yearly', 'one_time_scan') NOT NULL,
                action ENUM('created', 'renewed', 'cancelled', 'expired') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'EUR',
                stripe_subscription_id VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_subscription (user_id, subscription_type),
                INDEX idx_created_at (created_at)
            )
        ";
        echo "Creating subscription_history table...\n";
        $pdo->exec($subscriptionHistoryTable);
        
        // Migrate existing premium users to new model
        echo "Migrating existing premium users...\n";
        $migrateExistingUsers = "
            UPDATE users 
            SET subscription_type = 'one_time_scan',
                has_scan_access = TRUE,
                scan_access_type = 'one_time',
                reminder_access_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
            WHERE is_premium = 1 AND subscription_type IS NULL
        ";
        $pdo->exec($migrateExistingUsers);
        
        echo "Migration completed successfully!\n";
        
        // Display summary
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_premium = 1 THEN 1 ELSE 0 END) as premium_users,
                SUM(CASE WHEN subscription_type = 'monthly' THEN 1 ELSE 0 END) as monthly_subs,
                SUM(CASE WHEN subscription_type = 'yearly' THEN 1 ELSE 0 END) as yearly_subs,
                SUM(CASE WHEN subscription_type = 'one_time_scan' THEN 1 ELSE 0 END) as one_time_scans
            FROM users
        ")->fetch(PDO::FETCH_ASSOC);
        
        echo "\nMigration Summary:\n";
        echo "- Total users: " . $stats['total_users'] . "\n";
        echo "- Premium users: " . $stats['premium_users'] . "\n";
        echo "- Monthly subscriptions: " . $stats['monthly_subs'] . "\n";
        echo "- Yearly subscriptions: " . $stats['yearly_subs'] . "\n";
        echo "- One-time scans: " . $stats['one_time_scans'] . "\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== CashControl Pricing Model Migration ===\n";
    echo "This will update the database to support the new pricing model.\n";
    echo "Press Enter to continue or Ctrl+C to cancel...\n";
    readline();
    
    if (migratePricingModel()) {
        echo "\n✅ Migration completed successfully!\n";
        exit(0);
    } else {
        echo "\n❌ Migration failed!\n";
        exit(1);
    }
}
?>
