-- Enhanced Dashboard Features Database Schema
-- Run this to add support for price change detection and other features

-- Price history tracking
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
);

-- Subscription anomalies tracking
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
);

-- Unsubscribe guides table (if not exists)
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
);

-- Add cancellation_url to subscriptions if not exists
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS cancellation_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_price_check DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS price_change_count INT DEFAULT 0;

-- Trigger to track price changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS track_price_changes 
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
END//
DELIMITER ;

-- Insert some sample unsubscribe guides
INSERT IGNORE INTO unsubscribe_guides (service_name, service_slug, category, description, difficulty, estimated_time, steps, cancellation_url) VALUES
('Netflix', 'netflix', 'Streaming', 'Cancel your Netflix subscription easily', 'Easy', '2-3 minutes', 
'1. Sign in to your Netflix account\n2. Go to Account settings\n3. Click "Cancel Membership"\n4. Confirm cancellation', 
'https://www.netflix.com/cancelplan'),

('Spotify', 'spotify', 'Music', 'Cancel your Spotify Premium subscription', 'Easy', '3-5 minutes',
'1. Log into your Spotify account\n2. Go to Account Overview\n3. Click "Change or cancel your subscription"\n4. Select "Cancel Premium"',
'https://www.spotify.com/account/subscription/'),

('Adobe Creative Cloud', 'adobe-cc', 'Software', 'Cancel Adobe Creative Cloud subscription', 'Medium', '5-10 minutes',
'1. Sign in to your Adobe account\n2. Go to Plans & Products\n3. Select your plan\n4. Click "Cancel plan"\n5. Follow cancellation process',
'https://account.adobe.com/plans'),

('Disney+', 'disney-plus', 'Streaming', 'Cancel Disney+ subscription', 'Easy', '2-3 minutes',
'1. Log into your Disney+ account\n2. Go to Account settings\n3. Click "Subscription"\n4. Select "Cancel Subscription"',
'https://www.disneyplus.com/account/subscription'),

('YouTube Premium', 'youtube-premium', 'Streaming', 'Cancel YouTube Premium subscription', 'Easy', '3-5 minutes',
'1. Go to YouTube Premium settings\n2. Click "Deactivate"\n3. Follow the cancellation steps\n4. Confirm cancellation',
'https://www.youtube.com/premium');
