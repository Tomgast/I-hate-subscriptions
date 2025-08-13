<?php
/**
 * Setup Enhanced Dashboard Database Tables
 * Run this script to create the required tables for enhanced dashboard features
 */

require_once 'config/db_config.php';

echo "Setting up Enhanced Dashboard Database Tables...\n\n";

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
    
    // 3. Create/update unsubscribe_guides table
    echo "Creating unsubscribe_guides table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS unsubscribe_guides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_name VARCHAR(255) NOT NULL,
            service_slug VARCHAR(255) NOT NULL UNIQUE,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
            estimated_time VARCHAR(50) DEFAULT '5-10 minutes',
            steps TEXT NOT NULL,
            cancellation_url VARCHAR(500) DEFAULT NULL,
            phone_number VARCHAR(50) DEFAULT NULL,
            email_address VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_service_slug (service_slug),
            INDEX idx_is_active (is_active)
        )
    ");
    echo "✓ unsubscribe_guides table created\n";
    
    // 4. Add new columns to subscriptions table
    echo "Adding new columns to subscriptions table...\n";
    
    // Check if columns exist before adding them
    $result = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'cancellation_url'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN cancellation_url VARCHAR(500) DEFAULT NULL");
        echo "✓ Added cancellation_url column\n";
    } else {
        echo "✓ cancellation_url column already exists\n";
    }
    
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
    
    // 5. Create trigger for price change tracking
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
    
    // 6. Insert sample unsubscribe guides
    echo "Inserting sample unsubscribe guides...\n";
    
    $guides = [
        [
            'service_name' => 'Netflix',
            'service_slug' => 'netflix',
            'category' => 'Streaming',
            'description' => 'Cancel your Netflix subscription easily',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'steps' => "1. Sign in to your Netflix account\n2. Go to Account settings\n3. Click \"Cancel Membership\"\n4. Confirm cancellation",
            'cancellation_url' => 'https://www.netflix.com/cancelplan'
        ],
        [
            'service_name' => 'Spotify',
            'service_slug' => 'spotify',
            'category' => 'Music',
            'description' => 'Cancel your Spotify Premium subscription',
            'difficulty' => 'Easy',
            'estimated_time' => '3-5 minutes',
            'steps' => "1. Log into your Spotify account\n2. Go to Account Overview\n3. Click \"Change or cancel your subscription\"\n4. Select \"Cancel Premium\"",
            'cancellation_url' => 'https://www.spotify.com/account/subscription/'
        ],
        [
            'service_name' => 'Adobe Creative Cloud',
            'service_slug' => 'adobe-cc',
            'category' => 'Software',
            'description' => 'Cancel Adobe Creative Cloud subscription',
            'difficulty' => 'Medium',
            'estimated_time' => '5-10 minutes',
            'steps' => "1. Sign in to your Adobe account\n2. Go to Plans & Products\n3. Select your plan\n4. Click \"Cancel plan\"\n5. Follow cancellation process",
            'cancellation_url' => 'https://account.adobe.com/plans'
        ],
        [
            'service_name' => 'Disney+',
            'service_slug' => 'disney-plus',
            'category' => 'Streaming',
            'description' => 'Cancel Disney+ subscription',
            'difficulty' => 'Easy',
            'estimated_time' => '2-3 minutes',
            'steps' => "1. Log into your Disney+ account\n2. Go to Account settings\n3. Click \"Subscription\"\n4. Select \"Cancel Subscription\"",
            'cancellation_url' => 'https://www.disneyplus.com/account/subscription'
        ],
        [
            'service_name' => 'YouTube Premium',
            'service_slug' => 'youtube-premium',
            'category' => 'Streaming',
            'description' => 'Cancel YouTube Premium subscription',
            'difficulty' => 'Easy',
            'estimated_time' => '3-5 minutes',
            'steps' => "1. Go to YouTube Premium settings\n2. Click \"Deactivate\"\n3. Follow the cancellation steps\n4. Confirm cancellation",
            'cancellation_url' => 'https://www.youtube.com/premium'
        ]
    ];
    
    // Check if unsubscribe_guides table has the right structure
    $columns = $pdo->query("SHOW COLUMNS FROM unsubscribe_guides")->fetchAll(PDO::FETCH_COLUMN);
    echo "Available columns in unsubscribe_guides: " . implode(', ', $columns) . "\n";
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO unsubscribe_guides 
        (service_name, service_slug, category, description, difficulty, estimated_time, steps, website_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insertedCount = 0;
    foreach ($guides as $guide) {
        $result = $stmt->execute([
            $guide['service_name'],
            $guide['service_slug'],
            $guide['category'],
            $guide['description'],
            $guide['difficulty'],
            $guide['estimated_time'],
            $guide['steps'],
            $guide['cancellation_url'] // Using website_url column instead
        ]);
        if ($result) $insertedCount++;
    }
    
    echo "✓ Inserted $insertedCount sample unsubscribe guides\n";
    
    // 7. Update existing subscriptions with smart categorization
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
    
    echo "\n🎉 Enhanced Dashboard Database Setup Complete!\n\n";
    echo "Summary:\n";
    echo "- ✓ price_history table created for tracking subscription cost changes\n";
    echo "- ✓ subscription_anomalies table created for unusual charge detection\n";
    echo "- ✓ unsubscribe_guides table created with sample cancellation guides\n";
    echo "- ✓ Enhanced subscriptions table with new columns\n";
    echo "- ✓ Price change tracking trigger installed\n";
    echo "- ✓ Smart categorization applied to existing subscriptions\n";
    echo "\nYour enhanced dashboard is now ready to use! 🚀\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up database tables: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
