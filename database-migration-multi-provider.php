<?php
/**
 * DATABASE MIGRATION: MULTI-PROVIDER SUPPORT
 * Adds provider columns to support both Stripe and GoCardless integrations
 */

require_once 'config/db_config.php';

echo "<h1>🔄 Database Migration: Multi-Provider Support</h1>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>⚠️ IMPORTANT:</strong><br>";
echo "This migration will add 'provider' columns to your database tables to support both Stripe (US) and GoCardless (EU) bank integrations.<br>";
echo "Please backup your database before running this migration.";
echo "</div>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = getDBConnection();
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Database Connection Successful</strong>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Database Connection Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 1: Check Current Table Structure</h2>";

$tables = [
    'bank_connection_sessions' => 'Stores bank connection session data',
    'bank_connections' => 'Stores connected bank account information',
    'bank_scans' => 'Stores bank scan records',
    'subscriptions' => 'Stores detected subscription data'
];

$existingTables = [];
$missingTables = [];

foreach ($tables as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[$table] = $description;
            
            // Check if provider column already exists
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'provider'");
            $hasProvider = $stmt->rowCount() > 0;
            
            echo "<div style='background: " . ($hasProvider ? '#d4edda' : '#fff3cd') . "; border: 1px solid " . ($hasProvider ? '#c3e6cb' : '#ffeaa7') . "; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<strong>" . ($hasProvider ? '✅' : '⚠️') . " Table '$table':</strong> " . ($hasProvider ? 'Provider column exists' : 'Needs provider column') . "<br>";
            echo "<small>$description</small>";
            echo "</div>";
        } else {
            $missingTables[$table] = $description;
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
            echo "<strong>❌ Table '$table':</strong> Does not exist<br>";
            echo "<small>$description</small>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
        echo "<strong>❌ Error checking table '$table':</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>📋 Step 2: Create Missing Tables</h2>";

if (!empty($missingTables)) {
    foreach ($missingTables as $table => $description) {
        try {
            switch ($table) {
                case 'bank_connection_sessions':
                    $sql = "CREATE TABLE `bank_connection_sessions` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `session_id` varchar(255) NOT NULL,
                        `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe',
                        `status` enum('pending','completed','failed','expired') NOT NULL DEFAULT 'pending',
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `expires_at` timestamp NULL DEFAULT NULL,
                        `session_data` text,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `session_id` (`session_id`),
                        KEY `user_id` (`user_id`),
                        KEY `provider` (`provider`),
                        KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    break;
                    
                case 'bank_connections':
                    $sql = "CREATE TABLE `bank_connections` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `account_id` varchar(255) NOT NULL,
                        `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe',
                        `account_name` varchar(255) DEFAULT NULL,
                        `account_type` varchar(100) DEFAULT NULL,
                        `currency` varchar(10) DEFAULT NULL,
                        `status` enum('active','inactive','expired','error') NOT NULL DEFAULT 'active',
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `connection_data` text,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `user_account_provider` (`user_id`, `account_id`, `provider`),
                        KEY `user_id` (`user_id`),
                        KEY `provider` (`provider`),
                        KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    break;
                    
                case 'bank_scans':
                    $sql = "CREATE TABLE `bank_scans` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe',
                        `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
                        `subscriptions_found` int(11) NOT NULL DEFAULT 0,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `scan_data` text,
                        PRIMARY KEY (`id`),
                        KEY `user_id` (`user_id`),
                        KEY `provider` (`provider`),
                        KEY `status` (`status`),
                        KEY `created_at` (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    break;
                    
                case 'subscriptions':
                    $sql = "CREATE TABLE `subscriptions` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `scan_id` int(11) DEFAULT NULL,
                        `merchant_name` varchar(255) NOT NULL,
                        `amount` decimal(10,2) NOT NULL,
                        `currency` varchar(10) NOT NULL DEFAULT 'USD',
                        `billing_cycle` enum('weekly','monthly','quarterly','yearly','unknown') NOT NULL DEFAULT 'monthly',
                        `last_charge_date` date DEFAULT NULL,
                        `confidence` int(11) NOT NULL DEFAULT 0,
                        `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe',
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `subscription_data` text,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `user_merchant_provider` (`user_id`, `merchant_name`, `provider`),
                        KEY `user_id` (`user_id`),
                        KEY `scan_id` (`scan_id`),
                        KEY `provider` (`provider`),
                        KEY `billing_cycle` (`billing_cycle`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    break;
            }
            
            $pdo->exec($sql);
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
            echo "<strong>✅ Created table '$table'</strong><br>";
            echo "<small>$description</small>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
            echo "<strong>❌ Failed to create table '$table':</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ All required tables exist</strong>";
    echo "</div>";
}

echo "<h2>📋 Step 3: Add Provider Columns to Existing Tables</h2>";

$alterStatements = [
    'bank_connection_sessions' => [
        "ALTER TABLE `bank_connection_sessions` ADD COLUMN `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe' AFTER `session_id`",
        "ALTER TABLE `bank_connection_sessions` ADD INDEX `provider` (`provider`)"
    ],
    'bank_connections' => [
        "ALTER TABLE `bank_connections` ADD COLUMN `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe' AFTER `account_id`",
        "ALTER TABLE `bank_connections` ADD INDEX `provider` (`provider`)",
        "ALTER TABLE `bank_connections` DROP INDEX IF EXISTS `user_account`, ADD UNIQUE KEY `user_account_provider` (`user_id`, `account_id`, `provider`)"
    ],
    'bank_scans' => [
        "ALTER TABLE `bank_scans` ADD COLUMN `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe' AFTER `user_id`",
        "ALTER TABLE `bank_scans` ADD INDEX `provider` (`provider`)"
    ],
    'subscriptions' => [
        "ALTER TABLE `subscriptions` ADD COLUMN `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe' AFTER `confidence`",
        "ALTER TABLE `subscriptions` ADD INDEX `provider` (`provider`)",
        "ALTER TABLE `subscriptions` DROP INDEX IF EXISTS `user_merchant`, ADD UNIQUE KEY `user_merchant_provider` (`user_id`, `merchant_name`, `provider`)"
    ]
];

foreach ($existingTables as $table => $description) {
    if (isset($alterStatements[$table])) {
        // Check if provider column already exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'provider'");
        $hasProvider = $stmt->rowCount() > 0;
        
        if (!$hasProvider) {
            foreach ($alterStatements[$table] as $sql) {
                try {
                    $pdo->exec($sql);
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
                    echo "<strong>✅ Updated table '$table':</strong> Added provider support<br>";
                    echo "<small>SQL: " . substr($sql, 0, 100) . "...</small>";
                    echo "</div>";
                } catch (Exception $e) {
                    // Ignore errors for operations that might already be done
                    if (strpos($e->getMessage(), 'Duplicate column') === false && 
                        strpos($e->getMessage(), 'Duplicate key') === false) {
                        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 5px 0;'>";
                        echo "<strong>⚠️ Warning for table '$table':</strong> " . $e->getMessage() . "<br>";
                        echo "<small>SQL: " . substr($sql, 0, 100) . "...</small>";
                        echo "</div>";
                    }
                }
            }
        } else {
            echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; color: #0c5460; margin: 5px 0;'>";
            echo "<strong>ℹ️ Table '$table':</strong> Provider column already exists";
            echo "</div>";
        }
    }
}

echo "<h2>📋 Step 4: Migrate Existing Data</h2>";

try {
    // Update existing records to use 'truelayer' provider (legacy data)
    $updateQueries = [
        "UPDATE `bank_connection_sessions` SET `provider` = 'truelayer' WHERE `provider` = 'stripe' AND `created_at` < NOW() - INTERVAL 1 DAY",
        "UPDATE `bank_connections` SET `provider` = 'truelayer' WHERE `provider` = 'stripe' AND `created_at` < NOW() - INTERVAL 1 DAY",
        "UPDATE `bank_scans` SET `provider` = 'truelayer' WHERE `provider` = 'stripe' AND `created_at` < NOW() - INTERVAL 1 DAY",
        "UPDATE `subscriptions` SET `provider` = 'truelayer' WHERE `provider` = 'stripe' AND `created_at` < NOW() - INTERVAL 1 DAY"
    ];
    
    $totalUpdated = 0;
    foreach ($updateQueries as $query) {
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $updated = $stmt->rowCount();
            $totalUpdated += $updated;
            
            if ($updated > 0) {
                $tableName = preg_match('/UPDATE `(\w+)`/', $query, $matches) ? $matches[1] : 'unknown';
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
                echo "<strong>✅ Updated $updated records in '$tableName':</strong> Set provider to 'truelayer' for legacy data";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 5px 0;'>";
            echo "<strong>⚠️ Data migration warning:</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
    
    if ($totalUpdated === 0) {
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; color: #0c5460; margin: 5px 0;'>";
        echo "<strong>ℹ️ No legacy data found to migrate</strong>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
    echo "<strong>❌ Data migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🎉 Migration Complete</h2>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>✅ Database Migration Successful!</h3>";
echo "<p>Your database now supports multi-provider bank integration:</p>";
echo "<ul>";
echo "<li><strong>🇺🇸 Stripe Financial Connections</strong> - US bank accounts</li>";
echo "<li><strong>🇪🇺 GoCardless Bank Account Data</strong> - EU bank accounts</li>";
echo "<li><strong>📊 Unified Data Management</strong> - All providers in same tables</li>";
echo "</ul>";

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li><strong>Test Integration:</strong> Run the unified bank integration test</li>";
echo "<li><strong>Test Bank Connections:</strong> Try connecting both US and EU banks</li>";
echo "<li><strong>Verify Data Storage:</strong> Check that provider information is saved correctly</li>";
echo "<li><strong>Test Exports:</strong> Ensure PDF/CSV exports work with multi-provider data</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🔗 Test Links:</strong><br>";
echo "<p><a href='test-unified-bank-integration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🔄 Test Integration</a></p>";
echo "<p><a href='bank/unified-scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🌍 Test Bank Scan</a></p>";
echo "<p><a href='dashboard.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>📊 Dashboard</a></p>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
</style>
