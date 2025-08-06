<?php
// Database configuration
// Load secure configuration using universal loader
require_once __DIR__ . '/secure_loader.php';

define('DB_HOST', '45.82.188.227');
define('DB_PORT', '3306');
define('DB_NAME', 'vxmjmwlj_');
define('DB_USER', '123cashcontrol');
define('DB_PASS', getSecureConfig('DB_PASSWORD'));

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ":" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Initialize database tables if they don't exist
function initializeTables() {
    $pdo = getDBConnection();
    
    // Create users table with correct schema
    $sql = "CREATE TABLE IF NOT EXISTS users (
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
    )";
    $pdo->exec($sql);
    
    // Create sessions table for login management
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    
    // Create subscriptions table
    $sql = "CREATE TABLE IF NOT EXISTS subscriptions (
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
    )";
    $pdo->exec($sql);
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(100),
        color VARCHAR(7) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create user_preferences table
    $sql = "CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_reminders BOOLEAN DEFAULT TRUE,
        reminder_days_before JSON DEFAULT '[7, 3, 1]',
        preferred_currency VARCHAR(3) DEFAULT 'EUR',
        timezone VARCHAR(50) DEFAULT 'Europe/Amsterdam',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    
    // Create payment_history table
    $sql = "CREATE TABLE IF NOT EXISTS payment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subscription_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        payment_date DATE NOT NULL,
        status ENUM('completed', 'failed', 'pending') DEFAULT 'completed',
        transaction_reference VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    
    // Insert default categories if they don't exist
    $defaultCategories = [
        ['Entertainment', '🎬', '#ef4444'],
        ['Software', '💻', '#3b82f6'],
        ['Music', '🎵', '#10b981'],
        ['News', '📰', '#f59e0b'],
        ['Fitness', '💪', '#8b5cf6'],
        ['Food', '🍔', '#f97316'],
        ['Shopping', '🛍️', '#ec4899'],
        ['Finance', '💰', '#059669'],
        ['Education', '📚', '#0ea5e9'],
        ['Other', '📦', '#6b7280']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, icon, color) VALUES (?, ?, ?)");
    foreach ($defaultCategories as $category) {
        $stmt->execute($category);
    }
}

// Initialize tables on include
initializeTables();
?>
