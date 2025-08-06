<?php
/**
 * Database Configuration for CashControl
 * MariaDB connection for Plesk hosting
 */

class Database {
    // Database credentials from existing setup
    private $host = '45.82.188.227';
    private $port = '3306';
    private $db_name = 'vxmjmwlj_';
    private $username = '123cashcontrol';
    private $password;
    private $charset = 'utf8mb4';
    
    private $pdo = null;
    
    /**
     * Create database connection
     */
    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Query execution failed");
        }
    }
    
    /**
     * Get single row
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Get all rows
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insert and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connect()->lastInsertId();
    }
    
    /**
     * Update/Delete and return affected rows
     */
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Initialize database tables (same as existing schema)
     */
    public function initializeTables() {
        $tables = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                avatar_url TEXT,
                is_paid BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Subscriptions table
            "CREATE TABLE IF NOT EXISTS subscriptions (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                cost DECIMAL(10,2) NOT NULL,
                billing_cycle ENUM('monthly', 'yearly', 'weekly', 'daily') NOT NULL,
                next_billing_date DATE NOT NULL,
                category VARCHAR(100),
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // User preferences table
            "CREATE TABLE IF NOT EXISTS user_preferences (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                email_reminders BOOLEAN DEFAULT TRUE,
                reminder_days JSON DEFAULT '[]',
                reminder_frequency ENUM('once', 'daily', 'weekly') DEFAULT 'once',
                preferred_email_time TIME DEFAULT '09:00:00',
                timezone VARCHAR(50) DEFAULT 'Europe/Amsterdam',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Reminder logs table
            "CREATE TABLE IF NOT EXISTS reminder_logs (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                subscription_id VARCHAR(36) NOT NULL,
                reminder_type ENUM('renewal', 'welcome', 'upgrade', 'bank_scan') NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                email_status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                error_message TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->query($sql);
        }
        
        return true;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $result = $this->fetch("SELECT 1 as test");
            return $result['test'] === 1;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Global database instance
$db = new Database();
?>
