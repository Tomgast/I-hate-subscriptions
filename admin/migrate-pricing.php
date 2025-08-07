<?php
/**
 * Web-based Database Migration for New Pricing Model
 * Access: /admin/migrate-pricing.php
 */

session_start();
require_once __DIR__ . '/../config/db_config.php';

// Check if secure config exists and use it for authentication
$secureConfigPath = dirname(__DIR__) . '/secure-config.php';
$hasSecureConfig = file_exists($secureConfigPath);

// Allow access if:
// 1. Secure config exists (indicating proper server setup)
// 2. Force parameter is provided
// 3. User is logged in (basic check)
if (!$hasSecureConfig && !isset($_GET['force']) && !isset($_SESSION['user_id'])) {
    die('Access denied. Migration requires secure configuration or admin access.');
}

// If secure config exists, we can proceed (server is properly configured)
if ($hasSecureConfig) {
    $isAuthorized = true;
} elseif (isset($_GET['force'])) {
    $isAuthorized = true;
    echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">';
    echo '<p class="text-yellow-800"><strong>⚠️ Force Mode:</strong> Running migration without full security checks.</p>';
    echo '</div>';
} else {
    $isAuthorized = isset($_SESSION['user_id']);
}

$migrationComplete = false;
$migrationResults = [];

if ($_POST && isset($_POST['run_migration'])) {
    try {
        $pdo = getDBConnection();
        
        $migrationResults[] = "Starting pricing model migration...";
        
        // Add new columns to users table
        $userTableUpdates = [
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_status ENUM('active', 'cancelled', 'expired') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS has_scan_access BOOLEAN DEFAULT FALSE",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS scan_access_type ENUM('one_time', 'subscription') DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS reminder_access_expires_at DATETIME DEFAULT NULL"
        ];
        
        foreach ($userTableUpdates as $sql) {
            $migrationResults[] = "Executing: " . substr($sql, 0, 60) . "...";
            $pdo->exec($sql);
        }
        
        // Create checkout_sessions table if it doesn't exist
        $createCheckoutSessions = "
            CREATE TABLE IF NOT EXISTS checkout_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                stripe_session_id VARCHAR(255) NOT NULL UNIQUE,
                session_data TEXT,
                plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan',
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_stripe_session (stripe_session_id),
                INDEX idx_status (status)
            )
        ";
        $migrationResults[] = "Creating checkout_sessions table...";
        $pdo->exec($createCheckoutSessions);
        
        // Create payment_history table if it doesn't exist
        $createPaymentHistory = "
            CREATE TABLE IF NOT EXISTS payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'EUR',
                plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan',
                payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
                transaction_reference VARCHAR(255),
                stripe_payment_intent_id VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_payment_date (payment_date),
                INDEX idx_plan_type (plan_type)
            )
        ";
        $migrationResults[] = "Creating payment_history table...";
        $pdo->exec($createPaymentHistory);
        
        // Now add plan_type column to existing tables (if they exist but don't have the column)
        try {
            $pdo->exec("ALTER TABLE checkout_sessions ADD COLUMN IF NOT EXISTS plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan'");
            $migrationResults[] = "Added plan_type to existing checkout_sessions...";
        } catch (Exception $e) {
            // Column might already exist, that's fine
        }
        
        try {
            $pdo->exec("ALTER TABLE payment_history ADD COLUMN IF NOT EXISTS plan_type ENUM('monthly', 'yearly', 'one_time_scan') DEFAULT 'one_time_scan'");
            $migrationResults[] = "Added plan_type to existing payment_history...";
        } catch (Exception $e) {
            // Column might already exist, that's fine
        }
        
        // Create subscription_history table
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
        $migrationResults[] = "Creating subscription_history table...";
        $pdo->exec($subscriptionHistoryTable);
        
        // Migrate existing premium users
        $migrationResults[] = "Migrating existing premium users...";
        $migrateExistingUsers = "
            UPDATE users 
            SET subscription_type = 'one_time_scan',
                has_scan_access = TRUE,
                scan_access_type = 'one_time',
                reminder_access_expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR)
            WHERE is_premium = 1 AND subscription_type IS NULL
        ";
        $result = $pdo->exec($migrateExistingUsers);
        $migrationResults[] = "Migrated $result existing premium users to one-time scan model";
        
        // Get migration statistics
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_premium = 1 THEN 1 ELSE 0 END) as premium_users,
                SUM(CASE WHEN subscription_type = 'monthly' THEN 1 ELSE 0 END) as monthly_subs,
                SUM(CASE WHEN subscription_type = 'yearly' THEN 1 ELSE 0 END) as yearly_subs,
                SUM(CASE WHEN subscription_type = 'one_time_scan' THEN 1 ELSE 0 END) as one_time_scans
            FROM users
        ")->fetch(PDO::FETCH_ASSOC);
        
        $migrationResults[] = "Migration completed successfully!";
        $migrationResults[] = "Statistics:";
        $migrationResults[] = "- Total users: " . $stats['total_users'];
        $migrationResults[] = "- Premium users: " . $stats['premium_users'];
        $migrationResults[] = "- Monthly subscriptions: " . $stats['monthly_subs'];
        $migrationResults[] = "- Yearly subscriptions: " . $stats['yearly_subs'];
        $migrationResults[] = "- One-time scans: " . $stats['one_time_scans'];
        
        $migrationComplete = true;
        
    } catch (Exception $e) {
        $migrationResults[] = "Migration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Model Migration - CashControl Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Pricing Model Migration</h1>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">New Pricing Model</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-blue-900">Monthly Subscription</h3>
                        <p class="text-blue-700">€3/month - Full access</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-green-900">Yearly Subscription</h3>
                        <p class="text-green-700">€25/year - Save 31%</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-purple-900">One-Time Scan</h3>
                        <p class="text-purple-700">€25 - Scan + 1yr reminders</p>
                    </div>
                </div>
            </div>
            
            <?php if (!$migrationComplete && empty($migrationResults)): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <h3 class="text-yellow-800 font-semibold mb-2">⚠️ Migration Required</h3>
                    <p class="text-yellow-700">This migration will update your database to support the new pricing model. It will:</p>
                    <ul class="list-disc list-inside text-yellow-700 mt-2">
                        <li>Add new columns to support subscription types</li>
                        <li>Create subscription history tracking</li>
                        <li>Migrate existing premium users to one-time scan model</li>
                        <li>Preserve all existing data</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="run_migration" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                        🚀 Run Migration
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!empty($migrationResults)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Migration Results</h3>
                    <div class="space-y-2">
                        <?php foreach ($migrationResults as $result): ?>
                            <div class="text-sm <?php echo $migrationComplete ? 'text-green-700' : 'text-gray-700'; ?>">
                                <?php echo htmlspecialchars($result); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($migrationComplete): ?>
                        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="text-green-800 font-semibold">✅ Migration Completed Successfully!</h4>
                            <p class="text-green-700 mt-2">Your database now supports the new pricing model. You can now:</p>
                            <ul class="list-disc list-inside text-green-700 mt-2">
                                <li>Accept monthly subscriptions (€3/month)</li>
                                <li>Accept yearly subscriptions (€25/year)</li>
                                <li>Offer one-time scans (€25 with 1-year reminders)</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <a href="../dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
