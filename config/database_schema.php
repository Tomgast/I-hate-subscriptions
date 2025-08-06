<?php
// Complete database schema for CashControl subscription tracking
require_once 'db_config.php';

function initializeFullDatabase() {
    $pdo = getDBConnection();
    
    // Users table (already exists, but ensure all columns)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        password_hash VARCHAR(255),
        google_id VARCHAR(255),
        is_pro BOOLEAN DEFAULT FALSE,
        email_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(255),
        stripe_customer_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Subscriptions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        cost DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        billing_cycle ENUM('monthly', 'yearly', 'weekly', 'daily') DEFAULT 'monthly',
        next_payment_date DATE NOT NULL,
        category VARCHAR(100),
        website_url VARCHAR(500),
        logo_url VARCHAR(500),
        is_active BOOLEAN DEFAULT TRUE,
        auto_detected BOOLEAN DEFAULT FALSE,
        bank_reference VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_next_payment (next_payment_date),
        INDEX idx_active (is_active)
    )");
    
    // Categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(50),
        color VARCHAR(7) DEFAULT '#3B82F6',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Payment history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subscription_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50),
        bank_transaction_id VARCHAR(255),
        status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
        INDEX idx_subscription_id (subscription_id),
        INDEX idx_payment_date (payment_date)
    )");
    
    // Bank accounts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        account_name VARCHAR(255) NOT NULL,
        bank_name VARCHAR(255),
        account_number VARCHAR(50),
        iban VARCHAR(34),
        is_primary BOOLEAN DEFAULT FALSE,
        last_sync TIMESTAMP NULL,
        sync_enabled BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id)
    )");
    
    // Bank transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bank_account_id INT NOT NULL,
        transaction_id VARCHAR(255) UNIQUE,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        description TEXT,
        merchant_name VARCHAR(255),
        transaction_date DATE NOT NULL,
        category VARCHAR(100),
        is_subscription BOOLEAN DEFAULT FALSE,
        matched_subscription_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (matched_subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
        INDEX idx_bank_account (bank_account_id),
        INDEX idx_transaction_date (transaction_date),
        INDEX idx_merchant (merchant_name)
    )");
    
    // Notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subscription_id INT NULL,
        type ENUM('renewal_reminder', 'payment_detected', 'new_subscription', 'price_change') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        scheduled_for TIMESTAMP NULL,
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_scheduled (scheduled_for)
    )");
    
    // User preferences table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        currency VARCHAR(3) DEFAULT 'EUR',
        timezone VARCHAR(50) DEFAULT 'Europe/Amsterdam',
        email_notifications BOOLEAN DEFAULT TRUE,
        reminder_days JSON DEFAULT '[1, 3, 7]',
        dark_mode BOOLEAN DEFAULT FALSE,
        language VARCHAR(5) DEFAULT 'en',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Insert default categories
    $defaultCategories = [
        ['Streaming', '🎬', '#EF4444'],
        ['Software', '💻', '#3B82F6'],
        ['Music', '🎵', '#10B981'],
        ['Gaming', '🎮', '#8B5CF6'],
        ['News', '📰', '#F59E0B'],
        ['Cloud Storage', '☁️', '#06B6D4'],
        ['Fitness', '💪', '#84CC16'],
        ['Education', '📚', '#F97316'],
        ['Finance', '💰', '#14B8A6'],
        ['Productivity', '⚡', '#6366F1'],
        ['Communication', '💬', '#EC4899'],
        ['Other', '📦', '#6B7280']
    ];
    
    foreach ($defaultCategories as $category) {
        $pdo->exec("INSERT IGNORE INTO categories (name, icon, color) VALUES ('{$category[0]}', '{$category[1]}', '{$category[2]}')");
    }
    
    return true;
}

// Initialize database when this file is included
try {
    initializeFullDatabase();
    echo "✅ Database schema initialized successfully!<br>";
} catch (Exception $e) {
    echo "❌ Database initialization error: " . $e->getMessage() . "<br>";
}
?>
