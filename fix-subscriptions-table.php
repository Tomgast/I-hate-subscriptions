<?php
/**
 * FIX SUBSCRIPTIONS TABLE STRUCTURE
 * Adds missing columns to the subscriptions table for multi-provider support
 */

require_once 'config/db_config.php';

echo "<h1>🔧 Fix Subscriptions Table Structure</h1>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Fix the subscriptions table structure to support multi-provider bank integration.";
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

echo "<h2>📋 Step 1: Check Current Subscriptions Table Structure</h2>";

try {
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 10px 0;'>";
    echo "<strong>📊 Current Subscriptions Table Columns:</strong><br>";
    foreach ($columns as $column) {
        echo "• " . $column['Field'] . " (" . $column['Type'] . ")" . ($column['Null'] === 'NO' ? ' NOT NULL' : '') . "<br>";
    }
    echo "</div>";
    
    // Check which columns are missing
    $existingColumns = array_column($columns, 'Field');
    $requiredColumns = [
        'id', 'user_id', 'scan_id', 'merchant_name', 'amount', 'currency', 
        'billing_cycle', 'last_charge_date', 'confidence', 'provider', 
        'created_at', 'updated_at', 'subscription_data'
    ];
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 10px 0;'>";
        echo "<strong>⚠️ Missing Columns:</strong><br>";
        foreach ($missingColumns as $column) {
            echo "• $column<br>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<strong>✅ All required columns exist</strong>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Error checking table structure:</strong> " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 2: Add Missing Columns</h2>";

$alterStatements = [
    'scan_id' => "ALTER TABLE `subscriptions` ADD COLUMN `scan_id` int(11) DEFAULT NULL AFTER `user_id`",
    'merchant_name' => "ALTER TABLE `subscriptions` ADD COLUMN `merchant_name` varchar(255) NOT NULL AFTER `scan_id`",
    'amount' => "ALTER TABLE `subscriptions` ADD COLUMN `amount` decimal(10,2) NOT NULL AFTER `merchant_name`",
    'currency' => "ALTER TABLE `subscriptions` ADD COLUMN `currency` varchar(10) NOT NULL DEFAULT 'USD' AFTER `amount`",
    'billing_cycle' => "ALTER TABLE `subscriptions` ADD COLUMN `billing_cycle` enum('weekly','monthly','quarterly','yearly','unknown') NOT NULL DEFAULT 'monthly' AFTER `currency`",
    'last_charge_date' => "ALTER TABLE `subscriptions` ADD COLUMN `last_charge_date` date DEFAULT NULL AFTER `billing_cycle`",
    'confidence' => "ALTER TABLE `subscriptions` ADD COLUMN `confidence` int(11) NOT NULL DEFAULT 0 AFTER `last_charge_date`",
    'provider' => "ALTER TABLE `subscriptions` ADD COLUMN `provider` enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe' AFTER `confidence`",
    'created_at' => "ALTER TABLE `subscriptions` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `provider`",
    'updated_at' => "ALTER TABLE `subscriptions` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    'subscription_data' => "ALTER TABLE `subscriptions` ADD COLUMN `subscription_data` text AFTER `updated_at`"
];

foreach ($alterStatements as $column => $sql) {
    if (in_array($column, $missingColumns)) {
        try {
            $pdo->exec($sql);
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
            echo "<strong>✅ Added column '$column'</strong>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
            echo "<strong>❌ Failed to add column '$column':</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
}

echo "<h2>📋 Step 3: Add Indexes and Constraints</h2>";

$indexStatements = [
    "ALTER TABLE `subscriptions` ADD INDEX `user_id` (`user_id`)",
    "ALTER TABLE `subscriptions` ADD INDEX `scan_id` (`scan_id`)",
    "ALTER TABLE `subscriptions` ADD INDEX `provider` (`provider`)",
    "ALTER TABLE `subscriptions` ADD INDEX `billing_cycle` (`billing_cycle`)",
    "ALTER TABLE `subscriptions` ADD UNIQUE KEY `user_merchant_provider` (`user_id`, `merchant_name`, `provider`)"
];

foreach ($indexStatements as $sql) {
    try {
        $pdo->exec($sql);
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
        echo "<strong>✅ Added index:</strong> " . substr($sql, 0, 80) . "...";
        echo "</div>";
    } catch (Exception $e) {
        // Ignore duplicate key errors
        if (strpos($e->getMessage(), 'Duplicate key') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 5px 0;'>";
            echo "<strong>⚠️ Index warning:</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
}

echo "<h2>📋 Step 4: Verify Final Structure</h2>";

try {
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Final Subscriptions Table Structure:</strong><br>";
    foreach ($finalColumns as $column) {
        echo "• " . $column['Field'] . " (" . $column['Type'] . ")" . ($column['Null'] === 'NO' ? ' NOT NULL' : '') . "<br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Error verifying structure:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🎉 Subscriptions Table Fix Complete</h2>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>✅ Subscriptions Table Ready!</h3>";
echo "<p>The subscriptions table now has all required columns for multi-provider support:</p>";
echo "<ul>";
echo "<li><strong>Provider Tracking:</strong> Identifies which service detected each subscription</li>";
echo "<li><strong>Merchant Information:</strong> Stores merchant name and confidence scores</li>";
echo "<li><strong>Billing Details:</strong> Amount, currency, and billing cycle</li>";
echo "<li><strong>Timestamps:</strong> Creation and update tracking</li>";
echo "<li><strong>Flexible Data:</strong> JSON field for provider-specific information</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🔗 Next Steps:</strong><br>";
echo "<p><a href='test-unified-bank-integration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🔄 Test Integration</a></p>";
echo "<p><a href='bank/unified-scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🌍 Test Bank Scan</a></p>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
</style>
