<?php
/**
 * Database Schema Fix for CashControl
 * This script will add missing columns to existing tables
 */

require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    echo "<h2>CashControl Database Schema Fix</h2>";
    
    // Check if subscriptions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'subscriptions'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Subscriptions table doesn't exist. Creating it...</p>";
        
        $sql = "CREATE TABLE subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            cost DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'EUR',
            billing_cycle ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
            next_payment_date DATE,
            category VARCHAR(100) DEFAULT 'Other',
            website_url VARCHAR(500),
            logo_url VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_next_payment (next_payment_date),
            INDEX idx_active (is_active)
        )";
        
        $pdo->exec($sql);
        echo "<p>✅ Subscriptions table created successfully!</p>";
    } else {
        echo "<p>✅ Subscriptions table exists.</p>";
        
        // Check and add missing columns
        $columns = [];
        $stmt = $pdo->query("DESCRIBE subscriptions");
        while ($row = $stmt->fetch()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = [
            'next_payment_date' => "ADD COLUMN next_payment_date DATE AFTER billing_cycle",
            'category' => "ADD COLUMN category VARCHAR(100) DEFAULT 'Other' AFTER next_payment_date",
            'website_url' => "ADD COLUMN website_url VARCHAR(500) AFTER category",
            'logo_url' => "ADD COLUMN logo_url VARCHAR(500) AFTER website_url",
            'is_active' => "ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER logo_url",
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
        ];
        
        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE subscriptions $sql");
                    echo "<p>✅ Added missing column: $column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Could not add column $column: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p>✅ Column $column already exists.</p>";
            }
        }
    }
    
    // Create categories table if it doesn't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Categories table doesn't exist. Creating it...</p>";
        
        $sql = "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            icon VARCHAR(50) DEFAULT '📱',
            color VARCHAR(7) DEFAULT '#3B82F6',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insert default categories
        $defaultCategories = [
            ['Entertainment', '🎬', '#EF4444'],
            ['Music', '🎵', '#10B981'],
            ['Software', '💻', '#3B82F6'],
            ['News', '📰', '#F59E0B'],
            ['Fitness', '💪', '#8B5CF6'],
            ['Food', '🍔', '#F97316'],
            ['Shopping', '🛒', '#EC4899'],
            ['Business', '💼', '#6B7280'],
            ['Education', '📚', '#059669'],
            ['Other', '📱', '#6B7280']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, color) VALUES (?, ?, ?)");
        foreach ($defaultCategories as $category) {
            $stmt->execute($category);
        }
        
        echo "<p>✅ Categories table created with default categories!</p>";
    } else {
        echo "<p>✅ Categories table exists.</p>";
    }
    
    // Create payment_history table if it doesn't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_history'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Payment history table doesn't exist. Creating it...</p>";
        
        $sql = "CREATE TABLE payment_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subscription_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'manual',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_subscription_id (subscription_id),
            INDEX idx_payment_date (payment_date)
        )";
        
        $pdo->exec($sql);
        echo "<p>✅ Payment history table created successfully!</p>";
    } else {
        echo "<p>✅ Payment history table exists.</p>";
    }
    
    echo "<h3>🎉 Database schema is now up to date!</h3>";
    echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error fixing database schema:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Please contact support with this error message.</strong></p>";
}
?>
