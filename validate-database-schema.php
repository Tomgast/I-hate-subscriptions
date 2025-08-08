<?php
/**
 * DATABASE SCHEMA VALIDATION TOOL
 * Comprehensive validation of database schema for subscription management
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>Database Schema Validation Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🔍 Purpose:</strong><br>";
echo "This tool validates that your database schema can properly store all subscription data needed for a comprehensive subscription management tool. It checks table structures, column types, indexes, and relationships.";
echo "</div>";

try {
    $pdo = getDBConnection();
    
    // Define required tables and their expected schema
    $requiredTables = [
        'users' => [
            'description' => 'User accounts and plan information',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'email' => ['type' => 'varchar', 'unique' => true, 'not_null' => true],
                'name' => ['type' => 'varchar', 'not_null' => false],
                'password_hash' => ['type' => 'varchar', 'not_null' => false],
                'google_id' => ['type' => 'varchar', 'not_null' => false],
                'plan_type' => ['type' => 'varchar', 'not_null' => false],
                'subscription_type' => ['type' => 'varchar', 'not_null' => false],
                'subscription_status' => ['type' => 'varchar', 'not_null' => false],
                'plan_expires_at' => ['type' => 'timestamp', 'not_null' => false],
                'subscription_expires_at' => ['type' => 'timestamp', 'not_null' => false],
                'stripe_customer_id' => ['type' => 'varchar', 'not_null' => false],
                'stripe_subscription_id' => ['type' => 'varchar', 'not_null' => false],
                'scan_count' => ['type' => 'int', 'default' => 0],
                'max_scans' => ['type' => 'int', 'default' => 0],
                'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
            ],
            'recommended_indexes' => ['email', 'plan_type', 'subscription_status', 'stripe_customer_id']
        ],
        
        'subscriptions' => [
            'description' => 'User subscriptions and recurring payments',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'user_id' => ['type' => 'int', 'not_null' => true, 'foreign_key' => 'users.id'],
                'name' => ['type' => 'varchar', 'not_null' => true],
                'description' => ['type' => 'text', 'not_null' => false],
                'cost' => ['type' => 'decimal', 'precision' => '10,2', 'not_null' => true],
                'currency' => ['type' => 'varchar', 'length' => 3, 'default' => 'EUR'],
                'billing_cycle' => ['type' => 'enum', 'values' => ['monthly', 'yearly', 'weekly', 'quarterly'], 'not_null' => true],
                'next_payment_date' => ['type' => 'date', 'not_null' => false],
                'category' => ['type' => 'varchar', 'not_null' => false],
                'website_url' => ['type' => 'varchar', 'not_null' => false],
                'logo_url' => ['type' => 'varchar', 'not_null' => false],
                'status' => ['type' => 'enum', 'values' => ['active', 'cancelled', 'paused', 'expired'], 'default' => 'active'],
                'cancellation_url' => ['type' => 'varchar', 'not_null' => false],
                'notes' => ['type' => 'text', 'not_null' => false],
                'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
            ],
            'recommended_indexes' => ['user_id', 'status', 'billing_cycle', 'next_payment_date']
        ],
        
        'bank_scans' => [
            'description' => 'Bank scan records and results',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'user_id' => ['type' => 'int', 'not_null' => true, 'foreign_key' => 'users.id'],
                'plan_type' => ['type' => 'varchar', 'not_null' => true],
                'status' => ['type' => 'enum', 'values' => ['initiated', 'in_progress', 'completed', 'failed', 'scheduled'], 'default' => 'initiated'],
                'started_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                'completed_at' => ['type' => 'timestamp', 'not_null' => false],
                'subscriptions_found' => ['type' => 'int', 'default' => 0],
                'total_monthly_cost' => ['type' => 'decimal', 'precision' => '10,2', 'default' => 0],
                'scan_data' => ['type' => 'json', 'not_null' => false],
                'error_message' => ['type' => 'text', 'not_null' => false],
                'updated_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
            ],
            'recommended_indexes' => ['user_id', 'status', 'started_at']
        ],
        
        'bank_connections' => [
            'description' => 'Bank account connections and tokens',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'user_id' => ['type' => 'int', 'not_null' => true, 'foreign_key' => 'users.id'],
                'provider' => ['type' => 'varchar', 'default' => 'truelayer'],
                'connection_id' => ['type' => 'varchar', 'not_null' => true],
                'access_token' => ['type' => 'text', 'not_null' => false],
                'refresh_token' => ['type' => 'text', 'not_null' => false],
                'token_expires_at' => ['type' => 'timestamp', 'not_null' => false],
                'connection_status' => ['type' => 'enum', 'values' => ['active', 'expired', 'revoked'], 'default' => 'active'],
                'bank_name' => ['type' => 'varchar', 'not_null' => false],
                'account_data' => ['type' => 'json', 'not_null' => false],
                'is_active' => ['type' => 'boolean', 'default' => true],
                'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
                'expires_at' => ['type' => 'timestamp', 'not_null' => false]
            ],
            'recommended_indexes' => ['user_id', 'connection_status', 'is_active']
        ],
        
        'subscription_categories' => [
            'description' => 'Predefined subscription categories',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'name' => ['type' => 'varchar', 'not_null' => true, 'unique' => true],
                'icon' => ['type' => 'varchar', 'not_null' => false],
                'color' => ['type' => 'varchar', 'not_null' => false],
                'description' => ['type' => 'text', 'not_null' => false],
                'is_active' => ['type' => 'boolean', 'default' => true],
                'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP']
            ],
            'recommended_indexes' => ['name', 'is_active'],
            'optional' => true
        ],
        
        'user_preferences' => [
            'description' => 'User settings and preferences',
            'required_columns' => [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary_key' => true],
                'user_id' => ['type' => 'int', 'not_null' => true, 'foreign_key' => 'users.id'],
                'email_notifications' => ['type' => 'boolean', 'default' => true],
                'reminder_days' => ['type' => 'json', 'not_null' => false],
                'currency_preference' => ['type' => 'varchar', 'length' => 3, 'default' => 'EUR'],
                'timezone' => ['type' => 'varchar', 'default' => 'Europe/Amsterdam'],
                'created_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
            ],
            'recommended_indexes' => ['user_id'],
            'optional' => true
        ]
    ];
    
    echo "<h2>📊 Database Schema Analysis</h2>";
    
    $overallStatus = true;
    $recommendations = [];
    
    foreach ($requiredTables as $tableName => $tableConfig) {
        $isOptional = $tableConfig['optional'] ?? false;
        
        echo "<h3>🗄️ Table: <code>{$tableName}</code>" . ($isOptional ? " <span style='color: #6c757d;'>(Optional)</span>" : "") . "</h3>";
        echo "<p style='color: #666; font-style: italic;'>{$tableConfig['description']}</p>";
        
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            if ($isOptional) {
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 10px 0;'>";
                echo "⚠️ Optional table <code>{$tableName}</code> does not exist.";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
                echo "❌ Required table <code>{$tableName}</code> does not exist!";
                echo "</div>";
                $overallStatus = false;
                $recommendations[] = "Create table: {$tableName}";
            }
            continue;
        }
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "✅ Table <code>{$tableName}</code> exists";
        echo "</div>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE {$tableName}");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = [];
        
        foreach ($columns as $column) {
            $existingColumns[$column['Field']] = [
                'type' => $column['Type'],
                'null' => $column['Null'],
                'key' => $column['Key'],
                'default' => $column['Default'],
                'extra' => $column['Extra']
            ];
        }
        
        // Check required columns
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Column Analysis:</strong><br>";
        
        $missingColumns = [];
        $typeIssues = [];
        
        foreach ($tableConfig['required_columns'] as $columnName => $columnConfig) {
            if (!isset($existingColumns[$columnName])) {
                $missingColumns[] = $columnName;
                echo "<span style='color: red;'>❌ Missing: <code>{$columnName}</code></span><br>";
            } else {
                $existing = $existingColumns[$columnName];
                
                // Basic type checking (simplified)
                $typeMatch = true;
                if (isset($columnConfig['type'])) {
                    $expectedType = strtolower($columnConfig['type']);
                    $actualType = strtolower($existing['type']);
                    
                    if ($expectedType === 'varchar' && !strpos($actualType, 'varchar')) {
                        $typeMatch = false;
                    } elseif ($expectedType === 'int' && !strpos($actualType, 'int')) {
                        $typeMatch = false;
                    } elseif ($expectedType === 'decimal' && !strpos($actualType, 'decimal')) {
                        $typeMatch = false;
                    } elseif ($expectedType === 'timestamp' && !strpos($actualType, 'timestamp')) {
                        $typeMatch = false;
                    } elseif ($expectedType === 'json' && !strpos($actualType, 'json') && !strpos($actualType, 'text')) {
                        $typeMatch = false;
                    }
                }
                
                if ($typeMatch) {
                    echo "<span style='color: green;'>✅ <code>{$columnName}</code>: {$existing['type']}</span><br>";
                } else {
                    echo "<span style='color: orange;'>⚠️ <code>{$columnName}</code>: {$existing['type']} (type mismatch)</span><br>";
                    $typeIssues[] = $columnName;
                }
            }
        }
        
        if (!empty($missingColumns)) {
            $overallStatus = false;
            $recommendations[] = "Add missing columns to {$tableName}: " . implode(', ', $missingColumns);
        }
        
        if (!empty($typeIssues)) {
            $recommendations[] = "Review column types in {$tableName}: " . implode(', ', $typeIssues);
        }
        
        echo "</div>";
        
        // Check indexes
        $stmt = $pdo->query("SHOW INDEX FROM {$tableName}");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingIndexes = [];
        
        foreach ($indexes as $index) {
            $existingIndexes[] = $index['Column_name'];
        }
        
        if (isset($tableConfig['recommended_indexes'])) {
            echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Index Analysis:</strong><br>";
            
            foreach ($tableConfig['recommended_indexes'] as $recommendedIndex) {
                if (in_array($recommendedIndex, $existingIndexes)) {
                    echo "<span style='color: green;'>✅ Index on <code>{$recommendedIndex}</code></span><br>";
                } else {
                    echo "<span style='color: orange;'>⚠️ Missing index on <code>{$recommendedIndex}</code></span><br>";
                    $recommendations[] = "Add index on {$tableName}.{$recommendedIndex}";
                }
            }
            echo "</div>";
        }
    }
    
    // Overall status
    echo "<h2>📋 Overall Assessment</h2>";
    
    if ($overallStatus) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; color: #155724;'>";
        echo "<h3 style='margin-top: 0;'>✅ Database Schema: EXCELLENT</h3>";
        echo "Your database schema is properly configured for a comprehensive subscription management tool. All required tables and columns are present.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; color: #721c24;'>";
        echo "<h3 style='margin-top: 0;'>❌ Database Schema: NEEDS ATTENTION</h3>";
        echo "Some required tables or columns are missing. Please review the recommendations below.";
        echo "</div>";
    }
    
    // Recommendations
    if (!empty($recommendations)) {
        echo "<h3>🔧 Recommendations</h3>";
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
        echo "<ul>";
        foreach ($recommendations as $rec) {
            echo "<li>{$rec}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Test data insertion
    echo "<h2>🧪 Data Storage Test</h2>";
    
    echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Test Mock Subscription Storage</h3>";
    echo "<p>Testing if the database can properly store subscription data:</p>";
    
    // Test subscription data
    $testSubscription = [
        'name' => 'Test Netflix Subscription',
        'description' => 'Video streaming service - test data',
        'cost' => 12.99,
        'currency' => 'EUR',
        'billing_cycle' => 'monthly',
        'next_payment_date' => date('Y-m-d', strtotime('+1 month')),
        'category' => 'Entertainment',
        'website_url' => 'https://netflix.com',
        'status' => 'active'
    ];
    
    try {
        // Test if subscriptions table can handle the data
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (
                user_id, name, description, cost, currency, billing_cycle,
                next_payment_date, category, website_url, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $testSubscription['name'],
            $testSubscription['description'],
            $testSubscription['cost'],
            $testSubscription['currency'],
            $testSubscription['billing_cycle'],
            $testSubscription['next_payment_date'],
            $testSubscription['category'],
            $testSubscription['website_url'],
            $testSubscription['status']
        ]);
        
        $testId = $pdo->lastInsertId();
        
        // Retrieve and verify
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        
        if ($retrieved) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "✅ <strong>Data Storage Test: PASSED</strong><br>";
            echo "Successfully stored and retrieved subscription data:<br>";
            echo "• Name: {$retrieved['name']}<br>";
            echo "• Cost: €{$retrieved['cost']} ({$retrieved['billing_cycle']})<br>";
            echo "• Category: {$retrieved['category']}<br>";
            echo "• Status: {$retrieved['status']}<br>";
            echo "</div>";
            
            // Clean up test data
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
            $stmt->execute([$testId]);
            echo "<p style='color: #666; font-size: 14px;'>Test data cleaned up automatically.</p>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "❌ <strong>Data Storage Test: FAILED</strong><br>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
        $overallStatus = false;
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Connection Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔧 Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='inject-test-subscriptions.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Inject Test Data</a>";
echo "<a href='test-bank-data-processing.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Data Processing</a>";
echo "<a href='dashboard.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Dashboard</a>";
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
code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
