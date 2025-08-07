<?php
/**
 * Secure Database Helper
 * Provides database access without exposing credentials in code
 */

class DatabaseHelper {
    private static $connection = null;
    private static $config = null;
    
    /**
     * Load secure configuration
     */
    private static function loadConfig() {
        if (self::$config === null) {
            $configPath = dirname(__DIR__) . '/secure-config.php';
            if (file_exists($configPath)) {
                self::$config = require $configPath;
            } else {
                throw new Exception('Secure configuration file not found. Please create secure-config.php');
            }
        }
        return self::$config;
    }
    
    /**
     * Get database connection
     */
    public static function getConnection() {
        if (self::$connection === null) {
            $config = self::loadConfig();
            
            // Database credentials from your Plesk hosting
            $host = $config['DB_HOST'] ?? '45.82.188.227';
            $port = $config['DB_PORT'] ?? '3306';
            $database = $config['DB_NAME'] ?? 'vxmjmwlj_';
            $username = $config['DB_USER'] ?? '123cashcontrol';
            $password = $config['DB_PASSWORD'] ?? '';
            
            if (empty($password)) {
                throw new Exception('Database password not configured in secure-config.php');
            }
            
            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Get secure configuration value
     */
    public static function getSecureConfig($key) {
        $config = self::loadConfig();
        if (!isset($config[$key])) {
            throw new Exception("Configuration key '{$key}' not found in secure-config.php");
        }
        return $config[$key];
    }
    
    /**
     * Get Google OAuth credentials
     */
    public static function getGoogleOAuthConfig() {
        $config = self::loadConfig();
        return [
            'client_id' => '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com',
            'client_secret' => $config['GOOGLE_CLIENT_SECRET'] ?? '',
            'redirect_uri' => 'https://123cashcontrol.com/auth/google-callback.php'
        ];
    }
    
    /**
     * Get TrueLayer credentials
     */
    public static function getTrueLayerConfig() {
        $config = self::loadConfig();
        return [
            'client_id' => $config['TRUELAYER_CLIENT_ID'] ?? '',
            'client_secret' => $config['TRUELAYER_CLIENT_SECRET'] ?? '',
            'environment' => $config['TRUELAYER_ENVIRONMENT'] ?? 'sandbox',
            'redirect_uri' => 'https://123cashcontrol.com/bank/callback.php'
        ];
    }
    
    /**
     * Get Stripe credentials
     */
    public static function getStripeConfig() {
        $config = self::loadConfig();
        return [
            'publishable_key' => $config['STRIPE_PUBLISHABLE_KEY'] ?? '',
            'secret_key' => $config['STRIPE_SECRET_KEY'] ?? '',
            'webhook_secret' => $config['STRIPE_WEBHOOK_SECRET'] ?? ''
        ];
    }
    
    /**
     * Get SMTP email configuration
     */
    public static function getEmailConfig() {
        $config = self::loadConfig();
        return [
            'host' => $config['SMTP_HOST'] ?? '',
            'port' => $config['SMTP_PORT'] ?? '587',
            'username' => $config['SMTP_USERNAME'] ?? '',
            'password' => $config['SMTP_PASSWORD'] ?? '',
            'from_email' => $config['FROM_EMAIL'] ?? '',
            'from_name' => $config['FROM_NAME'] ?? 'CashControl'
        ];
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            return ['success' => true, 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test all service configurations
     */
    public static function testAllConfigurations() {
        $results = [];
        
        // Test database
        $results['database'] = self::testConnection();
        
        // Test Google OAuth config
        try {
            $googleConfig = self::getGoogleOAuthConfig();
            $results['google_oauth'] = [
                'success' => !empty($googleConfig['client_secret']),
                'message' => !empty($googleConfig['client_secret']) ? 'Google OAuth configured' : 'Missing Google client secret'
            ];
        } catch (Exception $e) {
            $results['google_oauth'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Test TrueLayer config
        try {
            $trueLayerConfig = self::getTrueLayerConfig();
            $results['truelayer'] = [
                'success' => !empty($trueLayerConfig['client_id']) && !empty($trueLayerConfig['client_secret']),
                'message' => (!empty($trueLayerConfig['client_id']) && !empty($trueLayerConfig['client_secret'])) ? 'TrueLayer configured' : 'Missing TrueLayer credentials'
            ];
        } catch (Exception $e) {
            $results['truelayer'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Test Stripe config
        try {
            $stripeConfig = self::getStripeConfig();
            $results['stripe'] = [
                'success' => !empty($stripeConfig['publishable_key']) && !empty($stripeConfig['secret_key']),
                'message' => (!empty($stripeConfig['publishable_key']) && !empty($stripeConfig['secret_key'])) ? 'Stripe configured' : 'Missing Stripe credentials'
            ];
        } catch (Exception $e) {
            $results['stripe'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Test Email config
        try {
            $emailConfig = self::getEmailConfig();
            $results['email'] = [
                'success' => !empty($emailConfig['host']) && !empty($emailConfig['username']) && !empty($emailConfig['password']),
                'message' => (!empty($emailConfig['host']) && !empty($emailConfig['username']) && !empty($emailConfig['password'])) ? 'Email SMTP configured' : 'Missing email credentials'
            ];
        } catch (Exception $e) {
            $results['email'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    /**
     * Initialize database tables
     */
    public static function initializeTables() {
        $pdo = self::getConnection();
        
        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                google_id VARCHAR(255) UNIQUE,
                subscription_type ENUM('free', 'monthly', 'yearly', 'one_time') DEFAULT 'free',
                subscription_status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
                subscription_expires_at DATETIME NULL,
                reminder_access_expires_at DATETIME NULL,
                stripe_customer_id VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Subscriptions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'EUR',
                billing_cycle ENUM('monthly', 'yearly', 'weekly', 'daily') NOT NULL,
                next_billing_date DATE NOT NULL,
                status ENUM('active', 'cancelled', 'paused') DEFAULT 'active',
                category VARCHAR(100),
                notes TEXT,
                bank_account_id VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // User preferences table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email_reminders BOOLEAN DEFAULT TRUE,
                reminder_days JSON DEFAULT '[7, 3, 1]',
                reminder_frequency ENUM('once', 'daily', 'weekly') DEFAULT 'once',
                preferred_email_time TIME DEFAULT '09:00:00',
                email_welcome BOOLEAN DEFAULT TRUE,
                email_upgrade BOOLEAN DEFAULT TRUE,
                email_bank_scan BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Reminder logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reminder_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subscription_id INT NOT NULL,
                reminder_type ENUM('renewal', 'cancellation', 'price_change') NOT NULL,
                days_before INT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                email_status ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
            )
        ");
        
        // Bank accounts table (for TrueLayer integration)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bank_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                account_id VARCHAR(255) NOT NULL,
                account_name VARCHAR(255) NOT NULL,
                account_type VARCHAR(100),
                provider VARCHAR(100),
                access_token TEXT,
                refresh_token TEXT,
                token_expires_at DATETIME,
                last_sync_at DATETIME,
                status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        return ['success' => true, 'message' => 'Database tables initialized successfully'];
    }
    
    /**
     * Get database statistics
     */
    public static function getStats() {
        $pdo = self::getConnection();
        
        $stats = [];
        $tables = ['users', 'subscriptions', 'user_preferences', 'reminder_logs', 'bank_accounts'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                $stats[$table] = $stmt->fetch()['count'];
            } catch (Exception $e) {
                $stats[$table] = 'Table not found';
            }
        }
        
        return $stats;
    }
}
?>
