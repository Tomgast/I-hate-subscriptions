<?php
/**
 * PHASE 3A.1: DATABASE SCHEMA MIGRATION
 * Add plan tracking, scan limits, and expiration to users table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load secure configuration
require_once __DIR__ . '/config/secure_loader.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 3A.1 - Database Schema Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🗄️ Phase 3A.1: Database Schema Migration</h1>
            <p class="text-gray-600 mb-8">Adding plan tracking and usage limitations to support three-tier business model</p>
            
            <?php
            if ($_POST && isset($_POST['action']) && $_POST['action'] === 'migrate') {
                try {
                    // Get database connection
                    $dbPassword = getSecureConfig('DB_PASSWORD');
                    $dbUser = getSecureConfig('DB_USER');
                    $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg mb-6'>";
                    echo "<h3 class='font-bold mb-2'>🚀 Starting Database Migration...</h3>";
                    echo "</div>";
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Check current users table structure
                    echo "<div class='bg-gray-50 p-4 rounded-lg mb-4'>";
                    echo "<h4 class='font-bold mb-2'>📋 Current Users Table Structure:</h4>";
                    
                    $stmt = $pdo->query("DESCRIBE users");
                    $currentColumns = $stmt->fetchAll();
                    
                    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
                    foreach ($currentColumns as $column) {
                        echo "<div class='bg-white p-3 rounded border'>";
                        echo "<div class='font-semibold'>{$column['Field']}</div>";
                        echo "<div class='text-sm text-gray-600'>{$column['Type']}</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    
                    // Define new columns to add
                    $newColumns = [
                        'plan_type' => [
                            'sql' => "ALTER TABLE users ADD COLUMN plan_type ENUM('monthly', 'yearly', 'onetime') DEFAULT NULL",
                            'description' => 'User subscription plan type'
                        ],
                        'plan_expires_at' => [
                            'sql' => "ALTER TABLE users ADD COLUMN plan_expires_at DATETIME DEFAULT NULL",
                            'description' => 'When the current plan expires (NULL for one-time)'
                        ],
                        'scan_count' => [
                            'sql' => "ALTER TABLE users ADD COLUMN scan_count INT DEFAULT 0",
                            'description' => 'Number of bank scans performed'
                        ],
                        'max_scans' => [
                            'sql' => "ALTER TABLE users ADD COLUMN max_scans INT DEFAULT 0",
                            'description' => 'Maximum allowed scans (1 for one-time, unlimited for subscriptions)'
                        ],
                        'plan_purchased_at' => [
                            'sql' => "ALTER TABLE users ADD COLUMN plan_purchased_at DATETIME DEFAULT NULL",
                            'description' => 'When the plan was purchased'
                        ],
                        'stripe_customer_id' => [
                            'sql' => "ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL",
                            'description' => 'Stripe customer ID for subscription management'
                        ],
                        'stripe_subscription_id' => [
                            'sql' => "ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(255) DEFAULT NULL",
                            'description' => 'Stripe subscription ID for recurring plans'
                        ]
                    ];
                    
                    // Check which columns already exist
                    $existingColumns = array_column($currentColumns, 'Field');
                    $columnsToAdd = [];
                    
                    foreach ($newColumns as $columnName => $columnInfo) {
                        if (!in_array($columnName, $existingColumns)) {
                            $columnsToAdd[$columnName] = $columnInfo;
                        }
                    }
                    
                    if (empty($columnsToAdd)) {
                        echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-6'>";
                        echo "<h3 class='font-bold'>✅ Schema Already Up to Date!</h3>";
                        echo "<p>All required columns already exist in the users table.</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-lg mb-6'>";
                        echo "<h3 class='font-bold mb-2'>🔧 Adding New Columns:</h3>";
                        echo "<ul class='list-disc list-inside space-y-1'>";
                        foreach ($columnsToAdd as $columnName => $columnInfo) {
                            echo "<li><strong>{$columnName}</strong>: {$columnInfo['description']}</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                        
                        // Execute migrations
                        foreach ($columnsToAdd as $columnName => $columnInfo) {
                            try {
                                echo "<div class='bg-blue-50 p-3 rounded mb-2'>";
                                echo "<strong>Adding column: {$columnName}</strong><br>";
                                echo "<code class='text-sm text-gray-600'>{$columnInfo['sql']}</code>";
                                
                                $pdo->exec($columnInfo['sql']);
                                
                                echo "<div class='text-green-600 mt-1'>✅ Success</div>";
                                echo "</div>";
                            } catch (Exception $e) {
                                echo "<div class='text-red-600 mt-1'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                echo "</div>";
                                throw $e; // Re-throw to rollback transaction
                            }
                        }
                    }
                    
                    // Verify final structure
                    echo "<div class='bg-gray-50 p-4 rounded-lg mb-4'>";
                    echo "<h4 class='font-bold mb-2'>📋 Updated Users Table Structure:</h4>";
                    
                    $stmt = $pdo->query("DESCRIBE users");
                    $updatedColumns = $stmt->fetchAll();
                    
                    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
                    foreach ($updatedColumns as $column) {
                        $isNew = in_array($column['Field'], array_keys($newColumns));
                        $bgClass = $isNew ? 'bg-green-100 border-green-300' : 'bg-white border-gray-200';
                        
                        echo "<div class='p-3 rounded border {$bgClass}'>";
                        echo "<div class='font-semibold'>{$column['Field']}" . ($isNew ? ' <span class="text-green-600 text-xs">NEW</span>' : '') . "</div>";
                        echo "<div class='text-sm text-gray-600'>{$column['Type']}</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    
                    // Create indexes for performance
                    echo "<div class='bg-blue-50 p-4 rounded-lg mb-4'>";
                    echo "<h4 class='font-bold mb-2'>🔍 Creating Performance Indexes:</h4>";
                    
                    $indexes = [
                        "CREATE INDEX idx_users_plan_type ON users(plan_type)",
                        "CREATE INDEX idx_users_plan_expires ON users(plan_expires_at)",
                        "CREATE INDEX idx_users_stripe_customer ON users(stripe_customer_id)"
                    ];
                    
                    foreach ($indexes as $indexSql) {
                        try {
                            echo "<div class='text-sm mb-1'>";
                            echo "<code>{$indexSql}</code>";
                            $pdo->exec($indexSql);
                            echo " <span class='text-green-600'>✅</span>";
                            echo "</div>";
                        } catch (Exception $e) {
                            // Index might already exist, that's okay
                            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                echo " <span class='text-yellow-600'>⚠️ Already exists</span>";
                            } else {
                                echo " <span class='text-red-600'>❌ " . htmlspecialchars($e->getMessage()) . "</span>";
                            }
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-6'>";
                    echo "<h3 class='font-bold mb-2'>🎉 Migration Completed Successfully!</h3>";
                    echo "<p>Database schema has been updated to support the three-tier plan system.</p>";
                    echo "</div>";
                    
                    // Show next steps
                    echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg'>";
                    echo "<h3 class='font-bold mb-2'>🚀 Next Steps:</h3>";
                    echo "<ol class='list-decimal list-inside space-y-1'>";
                    echo "<li>Update payment system to set plan_type and expiration</li>";
                    echo "<li>Create plan manager class for access control</li>";
                    echo "<li>Update dashboard to check plan status</li>";
                    echo "<li>Implement bank scan limitations</li>";
                    echo "</ol>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    if ($pdo->inTransaction()) {
                        $pdo->rollback();
                    }
                    
                    echo "<div class='bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-6'>";
                    echo "<h3 class='font-bold mb-2'>❌ Migration Failed!</h3>";
                    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p class='mt-2 text-sm'>Transaction has been rolled back. No changes were made to the database.</p>";
                    echo "</div>";
                }
            } else {
                // Show migration preview
                ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg mb-6">
                    <h3 class="font-bold mb-2">📋 Migration Overview</h3>
                    <p>This migration will add the following columns to the <code>users</code> table:</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg border">
                        <h4 class="font-bold text-green-600 mb-2">Plan Management</h4>
                        <ul class="text-sm space-y-1">
                            <li><strong>plan_type</strong>: monthly, yearly, or onetime</li>
                            <li><strong>plan_expires_at</strong>: When plan expires</li>
                            <li><strong>plan_purchased_at</strong>: Purchase timestamp</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border">
                        <h4 class="font-bold text-blue-600 mb-2">Usage Tracking</h4>
                        <ul class="text-sm space-y-1">
                            <li><strong>scan_count</strong>: Bank scans performed</li>
                            <li><strong>max_scans</strong>: Maximum allowed scans</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border">
                        <h4 class="font-bold text-purple-600 mb-2">Stripe Integration</h4>
                        <ul class="text-sm space-y-1">
                            <li><strong>stripe_customer_id</strong>: Stripe customer</li>
                            <li><strong>stripe_subscription_id</strong>: Subscription ID</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border">
                        <h4 class="font-bold text-orange-600 mb-2">Performance</h4>
                        <ul class="text-sm space-y-1">
                            <li>Indexes on plan_type</li>
                            <li>Indexes on expiration dates</li>
                            <li>Indexes on Stripe IDs</li>
                        </ul>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-lg mb-6">
                    <h3 class="font-bold mb-2">⚠️ Important Notes</h3>
                    <ul class="list-disc list-inside space-y-1">
                        <li>This migration is <strong>safe</strong> and only adds new columns</li>
                        <li>Existing user data will not be modified</li>
                        <li>Transaction will rollback on any errors</li>
                        <li>Backup your database before running if desired</li>
                    </ul>
                </div>
                
                <form method="POST" class="text-center">
                    <input type="hidden" name="action" value="migrate">
                    <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:bg-green-700 transition-colors">
                        🚀 Execute Migration
                    </button>
                </form>
                <?php
            }
            ?>
            
            <div class="mt-8 text-center">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="PHASE3_IMPLEMENTATION_PLAN.md" 
                       class="bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600">
                        📋 Implementation Plan
                    </a>
                    <a href="phase2-integration-test.php" 
                       class="bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600">
                        📊 Phase 2 Results
                    </a>
                    <a href="dashboard.php" 
                       class="bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600">
                        📊 Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
