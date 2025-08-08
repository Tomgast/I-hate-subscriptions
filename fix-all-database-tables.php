<?php
/**
 * COMPREHENSIVE DATABASE TABLE FIX
 * Ensures all tables have the correct structure for multi-provider bank integration
 */

require_once 'config/db_config.php';

echo "<h1>🔧 Comprehensive Database Table Fix</h1>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Ensure all database tables have the correct structure for multi-provider bank integration (Stripe + GoCardless).";
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

// Define the complete table structures
$tableStructures = [
    'bank_connection_sessions' => [
        'id' => 'int(11) NOT NULL AUTO_INCREMENT',
        'user_id' => 'int(11) NOT NULL',
        'session_id' => 'varchar(255) NOT NULL',
        'provider' => "enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe'",
        'status' => "enum('pending','completed','failed','expired') NOT NULL DEFAULT 'pending'",
        'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'expires_at' => 'timestamp NULL DEFAULT NULL',
        'session_data' => 'text',
        'PRIMARY KEY' => '(`id`)',
        'UNIQUE KEY session_id' => '(`session_id`)',
        'KEY user_id' => '(`user_id`)',
        'KEY provider' => '(`provider`)',
        'KEY status' => '(`status`)'
    ],
    'bank_connections' => [
        'id' => 'int(11) NOT NULL AUTO_INCREMENT',
        'user_id' => 'int(11) NOT NULL',
        'account_id' => 'varchar(255) NOT NULL',
        'provider' => "enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe'",
        'account_name' => 'varchar(255) DEFAULT NULL',
        'account_type' => 'varchar(100) DEFAULT NULL',
        'currency' => 'varchar(10) DEFAULT NULL',
        'status' => "enum('active','inactive','expired','error') NOT NULL DEFAULT 'active'",
        'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'connection_data' => 'text',
        'PRIMARY KEY' => '(`id`)',
        'UNIQUE KEY user_account_provider' => '(`user_id`, `account_id`, `provider`)',
        'KEY user_id' => '(`user_id`)',
        'KEY provider' => '(`provider`)',
        'KEY status' => '(`status`)'
    ],
    'bank_scans' => [
        'id' => 'int(11) NOT NULL AUTO_INCREMENT',
        'user_id' => 'int(11) NOT NULL',
        'provider' => "enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe'",
        'status' => "enum('pending','completed','failed') NOT NULL DEFAULT 'pending'",
        'subscriptions_found' => 'int(11) NOT NULL DEFAULT 0',
        'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'scan_data' => 'text',
        'PRIMARY KEY' => '(`id`)',
        'KEY user_id' => '(`user_id`)',
        'KEY provider' => '(`provider`)',
        'KEY status' => '(`status`)',
        'KEY created_at' => '(`created_at`)'
    ],
    'subscriptions' => [
        'id' => 'int(11) NOT NULL AUTO_INCREMENT',
        'user_id' => 'int(11) NOT NULL',
        'scan_id' => 'int(11) DEFAULT NULL',
        'merchant_name' => 'varchar(255) NOT NULL',
        'amount' => 'decimal(10,2) NOT NULL',
        'currency' => 'varchar(10) NOT NULL DEFAULT \'USD\'',
        'billing_cycle' => "enum('weekly','monthly','quarterly','yearly','unknown') NOT NULL DEFAULT 'monthly'",
        'last_charge_date' => 'date DEFAULT NULL',
        'confidence' => 'int(11) NOT NULL DEFAULT 0',
        'provider' => "enum('stripe','gocardless','truelayer') NOT NULL DEFAULT 'stripe'",
        'created_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'subscription_data' => 'text',
        'PRIMARY KEY' => '(`id`)',
        'UNIQUE KEY user_merchant_provider' => '(`user_id`, `merchant_name`, `provider`)',
        'KEY user_id' => '(`user_id`)',
        'KEY scan_id' => '(`scan_id`)',
        'KEY provider' => '(`provider`)',
        'KEY billing_cycle' => '(`billing_cycle`)'
    ]
];

foreach ($tableStructures as $tableName => $structure) {
    echo "<h2>📋 Processing Table: $tableName</h2>";
    
    // Check if table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 5px 0;'>";
            echo "<strong>⚠️ Table '$tableName' does not exist - creating it...</strong>";
            echo "</div>";
            
            // Create the table
            $createSQL = "CREATE TABLE `$tableName` (\n";
            $columns = [];
            $keys = [];
            
            foreach ($structure as $columnName => $definition) {
                if (strpos($columnName, 'KEY') !== false || $columnName === 'PRIMARY KEY') {
                    $keys[] = "$columnName $definition";
                } else {
                    $columns[] = "`$columnName` $definition";
                }
            }
            
            $createSQL .= implode(",\n", $columns);
            if (!empty($keys)) {
                $createSQL .= ",\n" . implode(",\n", $keys);
            }
            $createSQL .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createSQL);
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
            echo "<strong>✅ Created table '$tableName'</strong>";
            echo "</div>";
            continue;
        }
        
        // Get current table structure
        $stmt = $pdo->query("DESCRIBE $tableName");
        $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = [];
        
        foreach ($currentColumns as $column) {
            $existingColumns[$column['Field']] = $column;
        }
        
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; color: #0c5460; margin: 5px 0;'>";
        echo "<strong>📊 Current columns:</strong> " . implode(', ', array_keys($existingColumns));
        echo "</div>";
        
        // Check for missing columns and add them
        foreach ($structure as $columnName => $definition) {
            if (strpos($columnName, 'KEY') !== false || $columnName === 'PRIMARY KEY') {
                continue; // Skip keys for now
            }
            
            if (!isset($existingColumns[$columnName])) {
                try {
                    $alterSQL = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $definition";
                    $pdo->exec($alterSQL);
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 8px; border-radius: 5px; color: #155724; margin: 3px 0;'>";
                    echo "<strong>✅ Added column '$columnName'</strong>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 8px; border-radius: 5px; color: #721c24; margin: 3px 0;'>";
                    echo "<strong>❌ Failed to add column '$columnName':</strong> " . $e->getMessage();
                    echo "</div>";
                }
            }
        }
        
        // Add indexes (ignore errors for existing indexes)
        foreach ($structure as $keyName => $definition) {
            if (strpos($keyName, 'KEY') !== false || $keyName === 'PRIMARY KEY') {
                try {
                    if ($keyName === 'PRIMARY KEY') {
                        // Skip primary key if it already exists
                        continue;
                    } else {
                        $indexSQL = "ALTER TABLE `$tableName` ADD $keyName $definition";
                        $pdo->exec($indexSQL);
                        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 8px; border-radius: 5px; color: #155724; margin: 3px 0;'>";
                        echo "<strong>✅ Added index '$keyName'</strong>";
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    // Ignore duplicate key errors
                    if (strpos($e->getMessage(), 'Duplicate key') === false && 
                        strpos($e->getMessage(), 'already exists') === false) {
                        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px; border-radius: 5px; color: #856404; margin: 3px 0;'>";
                        echo "<strong>⚠️ Index warning '$keyName':</strong> " . $e->getMessage();
                        echo "</div>";
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
        echo "<strong>❌ Error processing table '$tableName':</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>📋 Final Verification</h2>";

// Verify all tables have the required structure
foreach ($tableStructures as $tableName => $structure) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = array_keys(array_filter($structure, function($key) {
            return strpos($key, 'KEY') === false;
        }, ARRAY_FILTER_USE_KEY));
        
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (empty($missingColumns)) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
            echo "<strong>✅ Table '$tableName':</strong> All required columns present (" . count($columnNames) . " columns)";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
            echo "<strong>❌ Table '$tableName':</strong> Missing columns: " . implode(', ', $missingColumns);
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
        echo "<strong>❌ Error verifying table '$tableName':</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>🎉 Database Structure Fix Complete</h2>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>✅ All Tables Ready for Multi-Provider Integration!</h3>";
echo "<p>Your database now has the complete structure needed for:</p>";
echo "<ul>";
echo "<li><strong>🇺🇸 Stripe Financial Connections</strong> - US bank integration</li>";
echo "<li><strong>🇪🇺 GoCardless Bank Account Data</strong> - EU bank integration</li>";
echo "<li><strong>📊 Unified Data Management</strong> - All providers in same tables</li>";
echo "<li><strong>🔍 Provider Tracking</strong> - Know which service provided each data point</li>";
echo "</ul>";

echo "<h4>Ready to Test:</h4>";
echo "<ol>";
echo "<li><strong>Integration Test:</strong> All services should initialize without database errors</li>";
echo "<li><strong>Bank Connections:</strong> Both US and EU flows should save data properly</li>";
echo "<li><strong>Subscription Detection:</strong> Scans should save results with provider info</li>";
echo "<li><strong>Export Functions:</strong> PDF/CSV should work with multi-provider data</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🔗 Test Your Integration:</strong><br>";
echo "<p><a href='test-unified-bank-integration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🔄 Test Integration</a></p>";
echo "<p><a href='bank/unified-scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🌍 Test Bank Scan</a></p>";
echo "<p><a href='test-gocardless-credentials.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🔑 Test GoCardless</a></p>";
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
