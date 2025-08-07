<?php
/**
 * PHASE 1.2: DATABASE STATE ASSESSMENT
 * Comprehensive MariaDB database analysis for CashControl recovery
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
    <title>Phase 1.2 - Database State Assessment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 Phase 1.2: Database State Assessment</h1>
            <p class="text-gray-600 mb-8">Comprehensive MariaDB analysis for CashControl recovery</p>
            
            <div class="space-y-8">
                
                <!-- Database Connection Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔌 Database Connection Test</h2>
                    <?php
                    $connectionStatus = [];
                    
                    try {
                        // Test secure config loading
                        $dbPassword = getSecureConfig('DB_PASSWORD');
                        if (!$dbPassword) {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Database password not found in secure config</div>";
                            $connectionStatus['config'] = false;
                        } else {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Database credentials loaded from secure config</div>";
                            $connectionStatus['config'] = true;
                            
                            // Test database connection
                            $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
                            $pdo = new PDO($dsn, '123cashcontrol', $dbPassword, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                PDO::ATTR_EMULATE_PREPARES => false,
                            ]);
                            
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Database connection successful</div>";
                            $connectionStatus['connection'] = true;
                            
                            // Test basic query
                            $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as database_name, USER() as user");
                            $info = $stmt->fetch();
                            
                            echo "<div class='bg-blue-100 text-blue-800 p-3 rounded mb-4'>";
                            echo "<strong>Database Info:</strong><br>";
                            echo "Version: " . htmlspecialchars($info['version']) . "<br>";
                            echo "Database: " . htmlspecialchars($info['database_name']) . "<br>";
                            echo "User: " . htmlspecialchars($info['user']);
                            echo "</div>";
                            
                            $connectionStatus['query'] = true;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $connectionStatus['connection'] = false;
                    }
                    ?>
                </div>

                <?php if (isset($pdo) && $connectionStatus['connection']): ?>
                
                <!-- Table Schema Analysis -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Table Schema Analysis</h2>
                    <?php
                    try {
                        // Get all tables
                        $stmt = $pdo->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (empty($tables)) {
                            echo "<div class='bg-yellow-100 text-yellow-800 p-3 rounded mb-4'>⚠️ No tables found in database</div>";
                        } else {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Found " . count($tables) . " tables</div>";
                            
                            echo "<div class='overflow-x-auto'>";
                            echo "<table class='min-w-full table-auto border-collapse border border-gray-300'>";
                            echo "<thead class='bg-gray-100'>";
                            echo "<tr><th class='border border-gray-300 px-4 py-2'>Table Name</th><th class='border border-gray-300 px-4 py-2'>Row Count</th><th class='border border-gray-300 px-4 py-2'>Columns</th><th class='border border-gray-300 px-4 py-2'>Status</th></tr>";
                            echo "</thead><tbody>";
                            
                            foreach ($tables as $table) {
                                echo "<tr>";
                                echo "<td class='border border-gray-300 px-4 py-2 font-mono'>" . htmlspecialchars($table) . "</td>";
                                
                                // Get row count
                                try {
                                    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                                    $count = $countStmt->fetch()['count'];
                                    echo "<td class='border border-gray-300 px-4 py-2 text-center'>" . number_format($count) . "</td>";
                                } catch (Exception $e) {
                                    echo "<td class='border border-gray-300 px-4 py-2 text-center text-red-600'>Error</td>";
                                }
                                
                                // Get column count
                                try {
                                    $colStmt = $pdo->query("DESCRIBE `$table`");
                                    $columns = $colStmt->fetchAll();
                                    echo "<td class='border border-gray-300 px-4 py-2 text-center'>" . count($columns) . "</td>";
                                    
                                    // Determine status based on expected tables
                                    $expectedTables = ['users', 'subscriptions', 'user_preferences', 'reminder_logs', 'payment_history', 'checkout_sessions'];
                                    if (in_array($table, $expectedTables)) {
                                        echo "<td class='border border-gray-300 px-4 py-2 text-green-600'>✅ Expected</td>";
                                    } else {
                                        echo "<td class='border border-gray-300 px-4 py-2 text-yellow-600'>⚠️ Unexpected</td>";
                                    }
                                } catch (Exception $e) {
                                    echo "<td class='border border-gray-300 px-4 py-2 text-center text-red-600'>Error</td>";
                                    echo "<td class='border border-gray-300 px-4 py-2 text-red-600'>❌ Error</td>";
                                }
                                
                                echo "</tr>";
                            }
                            
                            echo "</tbody></table>";
                            echo "</div>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Schema analysis failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Expected vs Actual Schema Comparison -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔍 Schema Validation</h2>
                    <?php
                    // Define expected schema based on memories
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
                    
                    if (isset($tables)) {
                        foreach ($expectedSchema as $tableName => $expectedColumns) {
                            echo "<div class='mb-4 p-4 border rounded'>";
                            echo "<h3 class='font-bold text-lg mb-2'>Table: $tableName</h3>";
                            
                            if (in_array($tableName, $tables)) {
                                echo "<span class='text-green-600'>✅ Table exists</span><br>";
                                
                                // Check columns
                                try {
                                    $colStmt = $pdo->query("DESCRIBE `$tableName`");
                                    $actualColumns = $colStmt->fetchAll();
                                    $actualColumnNames = array_column($actualColumns, 'Field');
                                    
                                    $missingColumns = array_diff($expectedColumns, $actualColumnNames);
                                    $extraColumns = array_diff($actualColumnNames, $expectedColumns);
                                    
                                    if (empty($missingColumns) && empty($extraColumns)) {
                                        echo "<span class='text-green-600'>✅ All columns match expected schema</span>";
                                    } else {
                                        if (!empty($missingColumns)) {
                                            echo "<span class='text-red-600'>❌ Missing columns: " . implode(', ', $missingColumns) . "</span><br>";
                                        }
                                        if (!empty($extraColumns)) {
                                            echo "<span class='text-yellow-600'>⚠️ Extra columns: " . implode(', ', $extraColumns) . "</span>";
                                        }
                                    }
                                    
                                } catch (Exception $e) {
                                    echo "<span class='text-red-600'>❌ Error checking columns: " . htmlspecialchars($e->getMessage()) . "</span>";
                                }
                                
                            } else {
                                echo "<span class='text-red-600'>❌ Table missing</span>";
                            }
                            
                            echo "</div>";
                        }
                    }
                    ?>
                </div>

                <!-- Data Integrity Check -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔒 Data Integrity Check</h2>
                    <?php
                    if (isset($tables) && in_array('users', $tables)) {
                        try {
                            // Check for duplicate emails
                            $stmt = $pdo->query("SELECT email, COUNT(*) as count FROM users GROUP BY email HAVING count > 1");
                            $duplicates = $stmt->fetchAll();
                            
                            if (empty($duplicates)) {
                                echo "<div class='text-green-600'>✅ No duplicate email addresses found</div>";
                            } else {
                                echo "<div class='text-red-600'>❌ Found " . count($duplicates) . " duplicate email addresses</div>";
                            }
                            
                            // Check for orphaned records
                            if (in_array('subscriptions', $tables)) {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions s LEFT JOIN users u ON s.user_id = u.id WHERE u.id IS NULL");
                                $orphaned = $stmt->fetch()['count'];
                                
                                if ($orphaned == 0) {
                                    echo "<div class='text-green-600'>✅ No orphaned subscription records</div>";
                                } else {
                                    echo "<div class='text-red-600'>❌ Found $orphaned orphaned subscription records</div>";
                                }
                            }
                            
                            // Check for invalid data
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email IS NULL OR email = ''");
                            $invalidEmails = $stmt->fetch()['count'];
                            
                            if ($invalidEmails == 0) {
                                echo "<div class='text-green-600'>✅ All users have valid email addresses</div>";
                            } else {
                                echo "<div class='text-red-600'>❌ Found $invalidEmails users with invalid email addresses</div>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='text-red-600'>❌ Data integrity check failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                    } else {
                        echo "<div class='text-yellow-600'>⚠️ Cannot perform integrity checks - users table not found</div>";
                    }
                    ?>
                </div>

                <!-- Database Operations Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">⚙️ Database Operations Test</h2>
                    <?php
                    if (isset($pdo)) {
                        try {
                            // Test INSERT
                            $testTable = 'test_operations_' . time();
                            $pdo->exec("CREATE TEMPORARY TABLE $testTable (id INT AUTO_INCREMENT PRIMARY KEY, test_data VARCHAR(255))");
                            $pdo->exec("INSERT INTO $testTable (test_data) VALUES ('test')");
                            echo "<div class='text-green-600'>✅ INSERT operation works</div>";
                            
                            // Test SELECT
                            $stmt = $pdo->query("SELECT * FROM $testTable");
                            $result = $stmt->fetch();
                            if ($result && $result['test_data'] === 'test') {
                                echo "<div class='text-green-600'>✅ SELECT operation works</div>";
                            }
                            
                            // Test UPDATE
                            $pdo->exec("UPDATE $testTable SET test_data = 'updated' WHERE id = 1");
                            echo "<div class='text-green-600'>✅ UPDATE operation works</div>";
                            
                            // Test DELETE
                            $pdo->exec("DELETE FROM $testTable WHERE id = 1");
                            echo "<div class='text-green-600'>✅ DELETE operation works</div>";
                            
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mt-4'>✅ All basic database operations are functional</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='text-red-600'>❌ Database operations test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                    }
                    ?>
                </div>

                <?php endif; ?>

                <!-- Summary and Recommendations -->
                <div class="border rounded-lg p-6 bg-blue-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Assessment Summary</h2>
                    <?php
                    $issues = [];
                    $recommendations = [];
                    
                    if (!isset($connectionStatus['config']) || !$connectionStatus['config']) {
                        $issues[] = "Secure configuration loading failed";
                        $recommendations[] = "Fix secure-config.php location and loading";
                    }
                    
                    if (!isset($connectionStatus['connection']) || !$connectionStatus['connection']) {
                        $issues[] = "Database connection failed";
                        $recommendations[] = "Verify database credentials and network connectivity";
                    }
                    
                    if (isset($tables) && empty($tables)) {
                        $issues[] = "No database tables found";
                        $recommendations[] = "Initialize database schema";
                    }
                    
                    if (!empty($issues)) {
                        echo "<div class='mb-4'>";
                        echo "<h3 class='font-bold text-red-600 mb-2'>🚨 Critical Issues:</h3>";
                        echo "<ul class='list-disc list-inside text-red-600'>";
                        foreach ($issues as $issue) {
                            echo "<li>$issue</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                        
                        echo "<div class='mb-4'>";
                        echo "<h3 class='font-bold text-blue-600 mb-2'>💡 Recommendations:</h3>";
                        echo "<ul class='list-disc list-inside text-blue-600'>";
                        foreach ($recommendations as $rec) {
                            echo "<li>$rec</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-green-100 text-green-800 p-3 rounded'>";
                        echo "✅ Database assessment completed successfully. Ready for Phase 1.3: Configuration Audit.";
                        echo "</div>";
                    }
                    ?>
                </div>

            </div>
            
            <div class="mt-8 text-center">
                <a href="RECOVERY_PLAN.md" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 inline-block mr-4">
                    📋 View Recovery Plan
                </a>
                <a href="PHASE1_AUDIT_REPORT.md" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 inline-block">
                    📊 View Audit Report
                </a>
            </div>
        </div>
    </div>
</body>
</html>
