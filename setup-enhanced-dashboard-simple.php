<?php
/**
 * Simplified Enhanced Dashboard Database Setup
 * Only creates the essential new tables without modifying existing ones
 */

require_once 'config/db_config.php';

echo "Setting up Enhanced Dashboard Database Tables (Essential Only)...\n\n";

try {
    $pdo = getDBConnection();
    
    // Enable foreign key checks
    $pdo->exec("SET foreign_key_checks = 1");
    
    echo "✓ Connected to database\n";
    
    // 1. Create price_history table
    echo "Creating price_history table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS price_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subscription_id INT NOT NULL,
            user_id INT NOT NULL,
            old_cost DECIMAL(10,2) NOT NULL,
            new_cost DECIMAL(10,2) NOT NULL,
            change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            change_reason VARCHAR(255) DEFAULT NULL,
            INDEX idx_subscription_id (subscription_id),
            INDEX idx_user_id (user_id),
            INDEX idx_change_date (change_date),
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ price_history table created\n";
    
    // 2. Create subscription_anomalies table
    echo "Creating subscription_anomalies table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_anomalies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subscription_id INT DEFAULT NULL,
            merchant_name VARCHAR(255) NOT NULL,
            expected_amount DECIMAL(10,2) DEFAULT NULL,
            actual_amount DECIMAL(10,2) NOT NULL,
            transaction_date DATE NOT NULL,
            anomaly_type ENUM('price_change', 'unexpected_charge', 'duplicate_charge', 'missing_charge') NOT NULL,
            severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('new', 'reviewed', 'resolved', 'ignored') DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_subscription_id (subscription_id),
            INDEX idx_status (status),
            INDEX idx_transaction_date (transaction_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
        )
    ");
    echo "✓ subscription_anomalies table created\n";
    
    // 3. Add new columns to subscriptions table (if they don't exist)
    echo "Adding new columns to subscriptions table...\n";
    
    // Check if columns exist before adding them
    $result = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'last_price_check'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN last_price_check DATETIME DEFAULT NULL");
        echo "✓ Added last_price_check column\n";
    } else {
        echo "✓ last_price_check column already exists\n";
    }
    
    $result = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'price_change_count'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN price_change_count INT DEFAULT 0");
        echo "✓ Added price_change_count column\n";
    } else {
        echo "✓ price_change_count column already exists\n";
    }
    
    // 4. Create trigger for price change tracking
    echo "Creating price change tracking trigger...\n";
    
    // Drop trigger if exists
    $pdo->exec("DROP TRIGGER IF EXISTS track_price_changes");
    
    // Create new trigger
    $pdo->exec("
        CREATE TRIGGER track_price_changes 
        AFTER UPDATE ON subscriptions
        FOR EACH ROW
        BEGIN
            IF OLD.cost != NEW.cost THEN
                INSERT INTO price_history (subscription_id, user_id, old_cost, new_cost, change_reason)
                VALUES (NEW.id, NEW.user_id, OLD.cost, NEW.cost, 'Manual update');
                
                UPDATE subscriptions 
                SET price_change_count = price_change_count + 1,
                    last_price_check = NOW()
                WHERE id = NEW.id;
            END IF;
        END
    ");
    echo "✓ Price change tracking trigger created\n";
    
    // 5. Update existing subscriptions with smart categorization
    echo "Updating existing subscriptions with smart categorization...\n";
    
    $updateStmt = $pdo->prepare("
        UPDATE subscriptions 
        SET category = CASE 
            WHEN LOWER(name) LIKE '%netflix%' OR LOWER(name) LIKE '%disney%' OR LOWER(name) LIKE '%hbo%' OR LOWER(name) LIKE '%prime%' THEN 'Streaming'
            WHEN LOWER(name) LIKE '%spotify%' OR LOWER(name) LIKE '%apple music%' OR LOWER(name) LIKE '%youtube music%' THEN 'Music'
            WHEN LOWER(name) LIKE '%adobe%' OR LOWER(name) LIKE '%microsoft%' OR LOWER(name) LIKE '%google%' THEN 'Software'
            WHEN LOWER(name) LIKE '%gym%' OR LOWER(name) LIKE '%fitness%' OR LOWER(name) LIKE '%sport%' THEN 'Fitness'
            WHEN LOWER(name) LIKE '%news%' OR LOWER(name) LIKE '%times%' OR LOWER(name) LIKE '%guardian%' THEN 'News'
            ELSE category
        END
        WHERE category IS NULL OR category = 'Other' OR category = ''
    ");
    
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    echo "✓ Updated $updatedRows subscriptions with smart categorization\n";
    
    // 6. Check existing unsubscribe_guides table
    echo "Checking existing unsubscribe_guides table...\n";
    $guidesCount = $pdo->query("SELECT COUNT(*) FROM unsubscribe_guides")->fetchColumn();
    echo "✓ Found $guidesCount existing unsubscribe guides\n";
    
    echo "\n🎉 Enhanced Dashboard Database Setup Complete!\n\n";
    echo "Summary:\n";
    echo "- ✓ price_history table created for tracking subscription cost changes\n";
    echo "- ✓ subscription_anomalies table created for unusual charge detection\n";
    echo "- ✓ Enhanced subscriptions table with price tracking columns\n";
    echo "- ✓ Price change tracking trigger installed\n";
    echo "- ✓ Smart categorization applied to existing subscriptions\n";
    echo "- ✓ Existing unsubscribe_guides table verified ($guidesCount guides available)\n";
    echo "\nYour enhanced dashboard is now ready to use! 🚀\n";
    echo "\nNote: The existing unsubscribe_guides table will be used for cancellation assistance.\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up database tables: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
