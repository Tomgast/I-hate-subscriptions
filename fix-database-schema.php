<?php
/**
 * DATABASE SCHEMA FIX TOOL
 * Comprehensive database schema migration to fix all identified issues
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>Database Schema Fix Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>⚠️ Important:</strong><br>";
echo "This tool will modify your database schema to add missing columns and indexes. It's designed to be safe and non-destructive, but it's recommended to backup your database first if you have important data.";
echo "</div>";

try {
    $pdo = getDBConnection();
    
    // Define all schema fixes needed
    $schemaFixes = [
        'subscriptions' => [
            'description' => 'Add missing subscription management columns',
            'columns' => [
                'next_payment_date' => "ADD COLUMN next_payment_date DATE NULL AFTER billing_cycle",
                'website_url' => "ADD COLUMN website_url VARCHAR(500) NULL AFTER category",
                'logo_url' => "ADD COLUMN logo_url VARCHAR(500) NULL AFTER website_url",
                'cancellation_url' => "ADD COLUMN cancellation_url VARCHAR(500) NULL AFTER status"
            ],
            'indexes' => [
                'idx_subscriptions_status' => "ADD INDEX idx_subscriptions_status (status)",
                'idx_subscriptions_billing_cycle' => "ADD INDEX idx_subscriptions_billing_cycle (billing_cycle)",
                'idx_subscriptions_next_payment' => "ADD INDEX idx_subscriptions_next_payment (next_payment_date)"
            ]
        ],
        
        'bank_scans' => [
            'description' => 'Add missing bank scan tracking columns',
            'columns' => [
                'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER error_message"
            ],
            'indexes' => [
                'idx_bank_scans_status' => "ADD INDEX idx_bank_scans_status (status)"
            ]
        ],
        
        'bank_connections' => [
            'description' => 'Add missing bank connection management columns',
            'columns' => [
                'provider' => "ADD COLUMN provider VARCHAR(50) NOT NULL DEFAULT 'truelayer' AFTER user_id",
                'connection_id' => "ADD COLUMN connection_id VARCHAR(255) NOT NULL AFTER provider",
                'token_expires_at' => "ADD COLUMN token_expires_at TIMESTAMP NULL AFTER refresh_token",
                'connection_status' => "ADD COLUMN connection_status ENUM('active', 'expired', 'revoked') DEFAULT 'active' AFTER token_expires_at"
            ],
            'indexes' => [
                'idx_bank_connections_status' => "ADD INDEX idx_bank_connections_status (connection_status)"
            ]
        ],
        
        'user_preferences' => [
            'description' => 'Add missing user preference columns',
            'columns' => [
                'email_notifications' => "ADD COLUMN email_notifications BOOLEAN DEFAULT TRUE AFTER user_id",
                'currency_preference' => "ADD COLUMN currency_preference VARCHAR(3) DEFAULT 'EUR' AFTER reminder_days"
            ]
        ],
        
        'users' => [
            'description' => 'Add missing user table indexes',
            'indexes' => [
                'idx_users_subscription_status' => "ADD INDEX idx_users_subscription_status (subscription_status)"
            ]
        ]
    ];
    
    // Optional table creation
    $optionalTables = [
        'subscription_categories' => "
            CREATE TABLE IF NOT EXISTS subscription_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                icon VARCHAR(100) NULL,
                color VARCHAR(7) NULL,
                description TEXT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_categories_name (name),
                INDEX idx_categories_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    echo "<h2>🔧 Schema Fix Analysis</h2>";
    
    // Show what will be fixed
    foreach ($schemaFixes as $tableName => $fixes) {
        echo "<h3>📋 Table: <code>{$tableName}</code></h3>";
        echo "<p style='color: #666; font-style: italic;'>{$fixes['description']}</p>";
        
        if (isset($fixes['columns'])) {
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Columns to Add:</strong><br>";
            foreach ($fixes['columns'] as $columnName => $sql) {
                echo "• <code>{$columnName}</code><br>";
            }
            echo "</div>";
        }
        
        if (isset($fixes['indexes'])) {
            echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Indexes to Add:</strong><br>";
            foreach ($fixes['indexes'] as $indexName => $sql) {
                echo "• <code>{$indexName}</code><br>";
            }
            echo "</div>";
        }
    }
    
    echo "<h3>📋 Optional Tables</h3>";
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Optional Tables to Create:</strong><br>";
    foreach ($optionalTables as $tableName => $sql) {
        echo "• <code>{$tableName}</code> - Predefined subscription categories<br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Connection Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Fix Actions
echo "<h2>🛠️ Fix Actions</h2>";

// Action 1: Apply all fixes
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 1: Apply All Schema Fixes</h3>";
echo "<p>This will add all missing columns and indexes to make your database production-ready.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='apply_all_fixes'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Apply All Fixes</button>";
echo "</form>";
echo "</div>";

// Action 2: Apply fixes step by step
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 2: Apply Fixes by Table</h3>";
echo "<p>Apply fixes one table at a time for more control.</p>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
foreach ($schemaFixes as $tableName => $fixes) {
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='action' value='fix_table'>";
    echo "<input type='hidden' name='table' value='{$tableName}'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;'>Fix {$tableName}</button>";
    echo "</form>";
}
echo "</div>";
echo "</div>";

// Action 3: Create optional tables
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 3: Create Optional Tables</h3>";
echo "<p>Create optional enhancement tables for better categorization and features.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='create_optional_tables'>";
echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Optional Tables</button>";
echo "</form>";
echo "</div>";

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        $fixesApplied = [];
        $errors = [];
        
        switch ($_POST['action']) {
            case 'apply_all_fixes':
                echo "<h2>🔄 Applying All Schema Fixes</h2>";
                
                foreach ($schemaFixes as $tableName => $fixes) {
                    echo "<h3>Fixing table: <code>{$tableName}</code></h3>";
                    
                    // Apply column fixes
                    if (isset($fixes['columns'])) {
                        foreach ($fixes['columns'] as $columnName => $alterSql) {
                            try {
                                $sql = "ALTER TABLE {$tableName} {$alterSql}";
                                $pdo->exec($sql);
                                $fixesApplied[] = "Added column {$tableName}.{$columnName}";
                                echo "<span style='color: green;'>✅ Added column: {$columnName}</span><br>";
                            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                                    echo "<span style='color: blue;'>ℹ️ Column {$columnName} already exists</span><br>";
                                } else {
                                    $errors[] = "Failed to add column {$tableName}.{$columnName}: " . $e->getMessage();
                                    echo "<span style='color: red;'>❌ Failed to add column {$columnName}: " . $e->getMessage() . "</span><br>";
                                }
                            }
                        }
                    }
                    
                    // Apply index fixes
                    if (isset($fixes['indexes'])) {
                        foreach ($fixes['indexes'] as $indexName => $alterSql) {
                            try {
                                $sql = "ALTER TABLE {$tableName} {$alterSql}";
                                $pdo->exec($sql);
                                $fixesApplied[] = "Added index {$indexName}";
                                echo "<span style='color: green;'>✅ Added index: {$indexName}</span><br>";
                            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                    echo "<span style='color: blue;'>ℹ️ Index {$indexName} already exists</span><br>";
                                } else {
                                    $errors[] = "Failed to add index {$indexName}: " . $e->getMessage();
                                    echo "<span style='color: red;'>❌ Failed to add index {$indexName}: " . $e->getMessage() . "</span><br>";
                                }
                            }
                        }
                    }
                }
                break;
                
            case 'fix_table':
                $tableName = $_POST['table'];
                if (isset($schemaFixes[$tableName])) {
                    echo "<h2>🔄 Fixing Table: <code>{$tableName}</code></h2>";
                    
                    $fixes = $schemaFixes[$tableName];
                    
                    // Apply column fixes
                    if (isset($fixes['columns'])) {
                        foreach ($fixes['columns'] as $columnName => $alterSql) {
                            try {
                                $sql = "ALTER TABLE {$tableName} {$alterSql}";
                                $pdo->exec($sql);
                                $fixesApplied[] = "Added column {$tableName}.{$columnName}";
                                echo "<span style='color: green;'>✅ Added column: {$columnName}</span><br>";
                            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                                    echo "<span style='color: blue;'>ℹ️ Column {$columnName} already exists</span><br>";
                                } else {
                                    $errors[] = "Failed to add column {$tableName}.{$columnName}: " . $e->getMessage();
                                    echo "<span style='color: red;'>❌ Failed to add column {$columnName}: " . $e->getMessage() . "</span><br>";
                                }
                            }
                        }
                    }
                    
                    // Apply index fixes
                    if (isset($fixes['indexes'])) {
                        foreach ($fixes['indexes'] as $indexName => $alterSql) {
                            try {
                                $sql = "ALTER TABLE {$tableName} {$alterSql}";
                                $pdo->exec($sql);
                                $fixesApplied[] = "Added index {$indexName}";
                                echo "<span style='color: green;'>✅ Added index: {$indexName}</span><br>";
                            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                    echo "<span style='color: blue;'>ℹ️ Index {$indexName} already exists</span><br>";
                                } else {
                                    $errors[] = "Failed to add index {$indexName}: " . $e->getMessage();
                                    echo "<span style='color: red;'>❌ Failed to add index {$indexName}: " . $e->getMessage() . "</span><br>";
                                }
                            }
                        }
                    }
                }
                break;
                
            case 'create_optional_tables':
                echo "<h2>🔄 Creating Optional Tables</h2>";
                
                foreach ($optionalTables as $tableName => $createSql) {
                    try {
                        $pdo->exec($createSql);
                        $fixesApplied[] = "Created table {$tableName}";
                        echo "<span style='color: green;'>✅ Created table: {$tableName}</span><br>";
                        
                        // Add default categories
                        if ($tableName === 'subscription_categories') {
                            $defaultCategories = [
                                ['Entertainment', '🎬', '#e74c3c'],
                                ['Software', '💻', '#3498db'],
                                ['Music', '🎵', '#9b59b6'],
                                ['Gaming', '🎮', '#e67e22'],
                                ['News', '📰', '#34495e'],
                                ['Fitness', '💪', '#27ae60'],
                                ['Education', '📚', '#f39c12'],
                                ['Business', '💼', '#2c3e50'],
                                ['Utilities', '⚡', '#95a5a6'],
                                ['Other', '📦', '#7f8c8d']
                            ];
                            
                            foreach ($defaultCategories as $cat) {
                                $stmt = $pdo->prepare("INSERT IGNORE INTO subscription_categories (name, icon, color) VALUES (?, ?, ?)");
                                $stmt->execute($cat);
                            }
                            echo "<span style='color: green;'>✅ Added default categories</span><br>";
                        }
                        
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'already exists') !== false) {
                            echo "<span style='color: blue;'>ℹ️ Table {$tableName} already exists</span><br>";
                        } else {
                            $errors[] = "Failed to create table {$tableName}: " . $e->getMessage();
                            echo "<span style='color: red;'>❌ Failed to create table {$tableName}: " . $e->getMessage() . "</span><br>";
                        }
                    }
                }
                break;
        }
        
        // Summary
        if (!empty($fixesApplied)) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
            echo "<strong>✅ Schema Fixes Applied Successfully!</strong><br>";
            echo "<ul>";
            foreach ($fixesApplied as $fix) {
                echo "<li>{$fix}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        if (!empty($errors)) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
            echo "<strong>⚠️ Some Fixes Had Issues:</strong><br>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>{$error}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        // Refresh page to show updated data
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🧪 Next Steps</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='validate-database-schema.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Re-validate Schema</a>";
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
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
