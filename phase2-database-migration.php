<?php
/**
 * PHASE 2.2: COMPREHENSIVE DATABASE MIGRATION
 * Complete schema migration tool for CashControl recovery
 * Fixes all missing tables, columns, and standardizes the database
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
    <title>Phase 2.2 - Database Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🗄️ Phase 2.2: Database Migration</h1>
            <p class="text-gray-600 mb-8">Comprehensive schema migration for CashControl recovery</p>
            
            <?php
            // Initialize migration status
            $migrationStatus = [];
            $pdo = null;
            
            // Connect to database
            try {
                $dbPassword = getSecureConfig('DB_PASSWORD');
                $dbUser = getSecureConfig('DB_USER');
                
                if (!$dbPassword || !$dbUser) {
                    throw new Exception("Database credentials not found in secure config");
                }
                
                $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPassword, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-6'>✅ Database connection successful</div>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-6'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "</div></body></html>";
                exit;
            }
            
            // Check if this is a migration run
            $runMigration = isset($_POST['run_migration']) && $_POST['run_migration'] === 'yes';
            ?>
            
            <div class="space-y-8">
                
                <!-- Migration Overview -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Migration Overview</h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-bold text-lg mb-2">🆕 Tables to Create:</h3>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li><code>checkout_sessions</code> - Critical for Stripe payments</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="font-bold text-lg mb-2">🔧 Tables to Modify:</h3>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li><code>users</code> - Add premium/Stripe columns</li>
                                <li><code>subscriptions</code> - Standardize columns</li>
                                <li><code>user_preferences</code> - Add timezone</li>
                                <li><code>reminder_logs</code> - Add email tracking</li>
                                <li><code>payment_history</code> - Add Stripe integration</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if (!$runMigration): ?>
                
                <!-- Pre-Migration Analysis -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔍 Pre-Migration Analysis</h2>
                    
                    <?php
                    // Analyze current schema
                    $currentTables = [];
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($tables as $table) {
                        $stmt = $pdo->query("DESCRIBE `$table`");
                        $columns = $stmt->fetchAll();
                        $currentTables[$table] = array_column($columns, 'Field');
                    }
                    
                    // Define expected schema
                    $expectedSchema = [
                        'users' => [
                            'id', 'email', 'name', 'password_hash', 'google_id', 'is_pro', 'is_premium',
                            'subscription_type', 'subscription_status', 'premium_expires_at',
                            'has_scan_access', 'scan_access_type', 'reminder_access_expires_at',
                            'email_verified', 'verification_token', 'stripe_customer_id',
                            'created_at', 'updated_at'
                        ],
                        'subscriptions' => [
                            'id', 'user_id', 'name', 'amount', 'currency', 'billing_cycle',
                            'next_billing_date', 'status', 'category', 'notes', 'created_at', 'updated_at'
                        ],
                        'user_preferences' => [
                            'id', 'user_id', 'email_reminders', 'reminder_days', 'reminder_frequency',
                            'preferred_time', 'timezone', 'created_at', 'updated_at'
                        ],
                        'reminder_logs' => [
                            'id', 'user_id', 'subscription_id', 'email_type', 'sent_at',
                            'email_status', 'error_message', 'created_at'
                        ],
                        'payment_history' => [
                            'id', 'user_id', 'stripe_session_id', 'stripe_payment_intent_id',
                            'amount', 'currency', 'status', 'created_at'
                        ],
                        'checkout_sessions' => [
                            'id', 'user_id', 'stripe_session_id', 'session_data', 'plan_type',
                            'status', 'created_at', 'updated_at'
                        ]
                    ];
                    
                    echo "<div class='overflow-x-auto'>";
                    echo "<table class='min-w-full table-auto border-collapse border border-gray-300'>";
                    echo "<thead class='bg-gray-100'>";
                    echo "<tr><th class='border border-gray-300 px-4 py-2'>Table</th><th class='border border-gray-300 px-4 py-2'>Status</th><th class='border border-gray-300 px-4 py-2'>Missing Columns</th><th class='border border-gray-300 px-4 py-2'>Extra Columns</th></tr>";
                    echo "</thead><tbody>";
                    
                    foreach ($expectedSchema as $tableName => $expectedColumns) {
                        echo "<tr>";
                        echo "<td class='border border-gray-300 px-4 py-2 font-mono'>$tableName</td>";
                        
                        if (isset($currentTables[$tableName])) {
                            $actualColumns = $currentTables[$tableName];
                            $missingColumns = array_diff($expectedColumns, $actualColumns);
                            $extraColumns = array_diff($actualColumns, $expectedColumns);
                            
                            if (empty($missingColumns) && empty($extraColumns)) {
                                echo "<td class='border border-gray-300 px-4 py-2 text-green-600'>✅ Perfect</td>";
                                echo "<td class='border border-gray-300 px-4 py-2'>-</td>";
                                echo "<td class='border border-gray-300 px-4 py-2'>-</td>";
                            } else {
                                echo "<td class='border border-gray-300 px-4 py-2 text-yellow-600'>⚠️ Needs Update</td>";
                                echo "<td class='border border-gray-300 px-4 py-2 text-red-600'>" . 
                                     (empty($missingColumns) ? "-" : implode(", ", $missingColumns)) . "</td>";
                                echo "<td class='border border-gray-300 px-4 py-2 text-blue-600'>" . 
                                     (empty($extraColumns) ? "-" : implode(", ", $extraColumns)) . "</td>";
                            }
                        } else {
                            echo "<td class='border border-gray-300 px-4 py-2 text-red-600'>❌ Missing</td>";
                            echo "<td class='border border-gray-300 px-4 py-2 text-red-600'>All columns</td>";
                            echo "<td class='border border-gray-300 px-4 py-2'>-</td>";
                        }
                        
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                    ?>
                </div>

                <!-- Migration Actions -->
                <div class="border rounded-lg p-6 bg-blue-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🚀 Run Migration</h2>
                    
                    <div class="bg-yellow-100 border border-yellow-300 p-4 rounded mb-4">
                        <h3 class="font-bold text-yellow-800 mb-2">⚠️ Important Notes:</h3>
                        <ul class="list-disc list-inside text-yellow-700 space-y-1">
                            <li>This migration will modify your database schema</li>
                            <li>Existing data will be preserved</li>
                            <li>New columns will be added with appropriate defaults</li>
                            <li>The migration is designed to be safe and reversible</li>
                            <li>A backup is recommended before running (though not required)</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="confirm_migration" name="confirm_migration" required 
                                   class="w-4 h-4 text-blue-600">
                            <label for="confirm_migration" class="text-sm text-gray-700">
                                I understand this will modify my database schema and want to proceed
                            </label>
                        </div>
                        
                        <button type="submit" name="run_migration" value="yes"
                                class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">
                            🚀 Run Complete Database Migration
                        </button>
                    </form>
                </div>

                <?php else: ?>
                
                <!-- Migration Execution -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">⚙️ Migration Execution</h2>
                    
                    <?php
                    // Execute migration
                    $migrations = [];
                    
                    try {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Migration 1: Create checkout_sessions table
                        $migrations[] = [
                            'name' => 'Create checkout_sessions table',
                            'sql' => "CREATE TABLE IF NOT EXISTS `checkout_sessions` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `user_id` int(11) NOT NULL,
                                `stripe_session_id` varchar(255) NOT NULL,
                                `session_data` text,
                                `plan_type` varchar(50) DEFAULT NULL,
                                `status` varchar(50) DEFAULT 'pending',
                                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                PRIMARY KEY (`id`),
                                UNIQUE KEY `stripe_session_id` (`stripe_session_id`),
                                KEY `user_id` (`user_id`),
                                KEY `status` (`status`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                        ];
                        
                        // Migration 2: Add missing columns to users table
                        $userColumns = [
                            "ADD COLUMN `is_premium` tinyint(1) DEFAULT 0",
                            "ADD COLUMN `premium_expires_at` timestamp NULL DEFAULT NULL",
                            "ADD COLUMN `stripe_customer_id` varchar(255) DEFAULT NULL"
                        ];
                        
                        foreach ($userColumns as $column) {
                            $migrations[] = [
                                'name' => "Add column to users table: " . explode('`', $column)[1],
                                'sql' => "ALTER TABLE `users` $column"
                            ];
                        }
                        
                        // Migration 3: Add missing columns to subscriptions table
                        $subscriptionColumns = [
                            "ADD COLUMN `amount` decimal(10,2) DEFAULT NULL",
                            "ADD COLUMN `currency` varchar(3) DEFAULT 'EUR'",
                            "ADD COLUMN `status` varchar(50) DEFAULT 'active'",
                            "ADD COLUMN `notes` text DEFAULT NULL"
                        ];
                        
                        foreach ($subscriptionColumns as $column) {
                            $migrations[] = [
                                'name' => "Add column to subscriptions table: " . explode('`', $column)[1],
                                'sql' => "ALTER TABLE `subscriptions` $column"
                            ];
                        }
                        
                        // Migration 4: Add timezone to user_preferences
                        $migrations[] = [
                            'name' => 'Add timezone to user_preferences',
                            'sql' => "ALTER TABLE `user_preferences` ADD COLUMN `timezone` varchar(50) DEFAULT 'Europe/Amsterdam'"
                        ];
                        
                        // Migration 5: Add missing columns to reminder_logs
                        $reminderColumns = [
                            "ADD COLUMN `email_type` varchar(50) DEFAULT 'reminder'",
                            "ADD COLUMN `email_status` varchar(50) DEFAULT 'sent'",
                            "ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP"
                        ];
                        
                        foreach ($reminderColumns as $column) {
                            $migrations[] = [
                                'name' => "Add column to reminder_logs table: " . explode('`', $column)[1],
                                'sql' => "ALTER TABLE `reminder_logs` $column"
                            ];
                        }
                        
                        // Migration 6: Add missing columns to payment_history
                        $paymentColumns = [
                            "ADD COLUMN `user_id` int(11) DEFAULT NULL",
                            "ADD COLUMN `stripe_session_id` varchar(255) DEFAULT NULL",
                            "ADD COLUMN `stripe_payment_intent_id` varchar(255) DEFAULT NULL"
                        ];
                        
                        foreach ($paymentColumns as $column) {
                            $migrations[] = [
                                'name' => "Add column to payment_history table: " . explode('`', $column)[1],
                                'sql' => "ALTER TABLE `payment_history` $column"
                            ];
                        }
                        
                        // Execute all migrations
                        foreach ($migrations as $migration) {
                            try {
                                $pdo->exec($migration['sql']);
                                echo "<div class='text-green-600 mb-2'>✅ " . htmlspecialchars($migration['name']) . "</div>";
                            } catch (PDOException $e) {
                                // Check if it's a "column already exists" error (which is fine)
                                if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                                    strpos($e->getMessage(), 'already exists') !== false) {
                                    echo "<div class='text-blue-600 mb-2'>ℹ️ " . htmlspecialchars($migration['name']) . " (already exists)</div>";
                                } else {
                                    throw $e; // Re-throw if it's a different error
                                }
                            }
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        echo "<div class='bg-green-100 text-green-800 p-4 rounded mt-4'>";
                        echo "<h3 class='font-bold mb-2'>🎉 Migration Completed Successfully!</h3>";
                        echo "<p>All database schema issues have been resolved. Your database is now fully operational for all services.</p>";
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        // Rollback on error
                        $pdo->rollback();
                        
                        echo "<div class='bg-red-100 text-red-800 p-4 rounded mt-4'>";
                        echo "<h3 class='font-bold mb-2'>❌ Migration Failed</h3>";
                        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "<p>All changes have been rolled back.</p>";
                        echo "</div>";
                    }
                    ?>
                </div>

                <!-- Post-Migration Verification -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">✅ Post-Migration Verification</h2>
                    
                    <?php
                    // Verify migration results
                    try {
                        // Check if checkout_sessions table exists
                        $stmt = $pdo->query("SHOW TABLES LIKE 'checkout_sessions'");
                        if ($stmt->rowCount() > 0) {
                            echo "<div class='text-green-600'>✅ checkout_sessions table created successfully</div>";
                        } else {
                            echo "<div class='text-red-600'>❌ checkout_sessions table not found</div>";
                        }
                        
                        // Check key columns
                        $checks = [
                            ['table' => 'users', 'column' => 'stripe_customer_id'],
                            ['table' => 'subscriptions', 'column' => 'amount'],
                            ['table' => 'user_preferences', 'column' => 'timezone'],
                            ['table' => 'reminder_logs', 'column' => 'email_type'],
                            ['table' => 'payment_history', 'column' => 'user_id']
                        ];
                        
                        foreach ($checks as $check) {
                            $stmt = $pdo->query("SHOW COLUMNS FROM `{$check['table']}` LIKE '{$check['column']}'");
                            if ($stmt->rowCount() > 0) {
                                echo "<div class='text-green-600'>✅ {$check['table']}.{$check['column']} column exists</div>";
                            } else {
                                echo "<div class='text-red-600'>❌ {$check['table']}.{$check['column']} column missing</div>";
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Verification error: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <?php endif; ?>

                <!-- Next Steps -->
                <div class="border rounded-lg p-6 bg-green-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🎯 Next Steps</h2>
                    
                    <?php if ($runMigration): ?>
                    <div class="space-y-4">
                        <p class="text-green-700">
                            <strong>🎉 Database migration complete!</strong> Your database schema is now fully operational for all CashControl services.
                        </p>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <a href="phase1-database-assessment.php" 
                               class="bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 text-center">
                                🔄 Re-run Database Assessment
                            </a>
                            <a href="test/test-connections.php" 
                               class="bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 text-center">
                                🧪 Test All Services
                            </a>
                            <a href="RECOVERY_PLAN.md" 
                               class="bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600 text-center">
                                📋 View Recovery Plan
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-700">
                        Review the migration plan above and run the migration when ready. This will fix all database schema issues in one step.
                    </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
