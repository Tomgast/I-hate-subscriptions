<?php
// Database configuration for Plesk MariaDB hosting
$host = '45.82.188.227';
$port = '3306';
$dbname = 'vxmjmwlj_';
$username = '123cashcontrol';
$password = 'Super-mannetje45';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Function to create tables if they don't exist
function createTables($pdo) {
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255),
                google_id VARCHAR(255),
                is_pro BOOLEAN DEFAULT FALSE,
                email_verified BOOLEAN DEFAULT FALSE,
                verification_token VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_google_id (google_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'subscriptions' => "
            CREATE TABLE IF NOT EXISTS subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                cost DECIMAL(10,2) NOT NULL,
                billing_cycle ENUM('monthly', 'yearly', 'weekly', 'daily') NOT NULL,
                next_billing_date DATE NOT NULL,
                category VARCHAR(100),
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_next_billing (next_billing_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'user_preferences' => "
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                reminder_days JSON DEFAULT '[7, 3, 1]',
                reminder_frequency ENUM('once', 'daily', 'weekly') DEFAULT 'once',
                preferred_time TIME DEFAULT '09:00:00',
                email_welcome BOOLEAN DEFAULT TRUE,
                email_upgrade BOOLEAN DEFAULT TRUE,
                email_reminders BOOLEAN DEFAULT TRUE,
                email_bank_scan BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_prefs (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'reminder_logs' => "
            CREATE TABLE IF NOT EXISTS reminder_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subscription_id INT NOT NULL,
                reminder_type ENUM('email', 'push') NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                error_message TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];

    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            error_log("Table '$tableName' created successfully");
        } catch (PDOException $e) {
            error_log("Error creating table '$tableName': " . $e->getMessage());
            throw $e;
        }
    }
}

// Create tables on first connection
createTables($pdo);
?>
