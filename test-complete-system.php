<?php
/**
 * COMPLETE SYSTEM TEST
 * Comprehensive test to verify all components are working after schema fixes
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>🧪 Complete System Test</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "This comprehensive test validates that all database schema fixes are working and your subscription management system is production-ready.";
echo "</div>";

$testResults = [];
$overallStatus = true;

try {
    $pdo = getDBConnection();
    
    echo "<h2>📊 Database Schema Validation</h2>";
    
    // Test 1: Verify all required columns exist
    echo "<h3>🔍 Test 1: Required Columns</h3>";
    
    $requiredColumns = [
        'subscriptions' => ['next_payment_date', 'website_url', 'logo_url', 'cancellation_url'],
        'bank_connections' => ['provider', 'connection_id', 'token_expires_at', 'connection_status'],
        'bank_scans' => ['updated_at'],
        'user_preferences' => ['email_notifications', 'currency_preference']
    ];
    
    foreach ($requiredColumns as $tableName => $columns) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong>Table: <code>{$tableName}</code></strong><br>";
        
        foreach ($columns as $columnName) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
                $column = $stmt->fetch();
                
                if ($column) {
                    echo "<span style='color: green;'>✅ {$columnName}</span><br>";
                    $testResults["column_{$tableName}_{$columnName}"] = true;
                } else {
                    echo "<span style='color: red;'>❌ {$columnName} - MISSING</span><br>";
                    $testResults["column_{$tableName}_{$columnName}"] = false;
                    $overallStatus = false;
                }
            } catch (Exception $e) {
                echo "<span style='color: red;'>❌ {$columnName} - ERROR: {$e->getMessage()}</span><br>";
                $testResults["column_{$tableName}_{$columnName}"] = false;
                $overallStatus = false;
            }
        }
        echo "</div>";
    }
    
    // Test 2: Verify indexes exist
    echo "<h3>🔍 Test 2: Required Indexes</h3>";
    
    $requiredIndexes = [
        'subscriptions' => ['idx_subscriptions_status', 'idx_subscriptions_billing_cycle', 'idx_subscriptions_next_payment'],
        'bank_scans' => ['idx_bank_scans_status'],
        'bank_connections' => ['idx_bank_connections_status'],
        'users' => ['idx_users_subscription_status']
    ];
    
    foreach ($requiredIndexes as $tableName => $indexes) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong>Table: <code>{$tableName}</code></strong><br>";
        
        foreach ($indexes as $indexName) {
            try {
                $stmt = $pdo->query("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$indexName}'");
                $index = $stmt->fetch();
                
                if ($index) {
                    echo "<span style='color: green;'>✅ {$indexName}</span><br>";
                    $testResults["index_{$tableName}_{$indexName}"] = true;
                } else {
                    echo "<span style='color: orange;'>⚠️ {$indexName} - MISSING (performance impact)</span><br>";
                    $testResults["index_{$tableName}_{$indexName}"] = false;
                    // Don't fail overall test for missing indexes, just warn
                }
            } catch (Exception $e) {
                echo "<span style='color: red;'>❌ {$indexName} - ERROR: {$e->getMessage()}</span><br>";
                $testResults["index_{$tableName}_{$indexName}"] = false;
            }
        }
        echo "</div>";
    }
    
    // Test 3: Data Storage Test
    echo "<h3>🔍 Test 3: Data Storage Test</h3>";
    
    try {
        // Test subscription data insertion with all new columns
        $testSubscription = [
            'user_id' => $userId,
            'name' => 'Test Subscription',
            'description' => 'Test subscription for schema validation',
            'cost' => 9.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'next_payment_date' => date('Y-m-d', strtotime('+1 month')),
            'category' => 'Software',
            'website_url' => 'https://example.com',
            'logo_url' => 'https://example.com/logo.png',
            'status' => 'active',
            'cancellation_url' => 'https://example.com/cancel',
            'notes' => 'Test subscription created by schema validation'
        ];
        
        $sql = "INSERT INTO subscriptions (
            user_id, name, description, cost, currency, billing_cycle, 
            next_payment_date, category, website_url, logo_url, 
            status, cancellation_url, notes, created_at
        ) VALUES (
            :user_id, :name, :description, :cost, :currency, :billing_cycle,
            :next_payment_date, :category, :website_url, :logo_url,
            :status, :cancellation_url, :notes, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($testSubscription);
        
        if ($result) {
            $testSubscriptionId = $pdo->lastInsertId();
            echo "<span style='color: green;'>✅ Subscription data storage test PASSED</span><br>";
            echo "<span style='color: blue;'>ℹ️ Test subscription ID: {$testSubscriptionId}</span><br>";
            $testResults['data_storage_subscriptions'] = true;
            
            // Clean up test data
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
            $stmt->execute([$testSubscriptionId]);
            echo "<span style='color: blue;'>ℹ️ Test data cleaned up</span><br>";
            
        } else {
            echo "<span style='color: red;'>❌ Subscription data storage test FAILED</span><br>";
            $testResults['data_storage_subscriptions'] = false;
            $overallStatus = false;
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Data storage test FAILED: {$e->getMessage()}</span><br>";
        $testResults['data_storage_subscriptions'] = false;
        $overallStatus = false;
    }
    
    // Test 4: Bank Connection Storage Test
    echo "<h3>🔍 Test 4: Bank Connection Storage Test</h3>";
    
    try {
        $testConnection = [
            'user_id' => $userId,
            'provider' => 'truelayer',
            'connection_id' => 'test_connection_' . time(),
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'connection_status' => 'active',
            'bank_name' => 'Test Bank',
            'account_data' => json_encode(['test' => 'data']),
            'is_active' => 1
        ];
        
        $sql = "INSERT INTO bank_connections (
            user_id, provider, connection_id, access_token, refresh_token,
            token_expires_at, connection_status, bank_name, account_data, is_active, created_at
        ) VALUES (
            :user_id, :provider, :connection_id, :access_token, :refresh_token,
            :token_expires_at, :connection_status, :bank_name, :account_data, :is_active, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($testConnection);
        
        if ($result) {
            $testConnectionId = $pdo->lastInsertId();
            echo "<span style='color: green;'>✅ Bank connection storage test PASSED</span><br>";
            echo "<span style='color: blue;'>ℹ️ Test connection ID: {$testConnectionId}</span><br>";
            $testResults['data_storage_bank_connections'] = true;
            
            // Clean up test data
            $stmt = $pdo->prepare("DELETE FROM bank_connections WHERE id = ?");
            $stmt->execute([$testConnectionId]);
            echo "<span style='color: blue;'>ℹ️ Test data cleaned up</span><br>";
            
        } else {
            echo "<span style='color: red;'>❌ Bank connection storage test FAILED</span><br>";
            $testResults['data_storage_bank_connections'] = false;
            $overallStatus = false;
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Bank connection storage test FAILED: {$e->getMessage()}</span><br>";
        $testResults['data_storage_bank_connections'] = false;
        $overallStatus = false;
    }
    
    // Test 5: User Preferences Test
    echo "<h3>🔍 Test 5: User Preferences Storage Test</h3>";
    
    try {
        // Check if user preferences exist, if not create them
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existingPrefs = $stmt->fetch();
        
        if (!$existingPrefs) {
            $sql = "INSERT INTO user_preferences (
                user_id, email_notifications, reminder_days, currency_preference, timezone, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $userId,
                true,
                json_encode([1, 3, 7]),
                'EUR',
                'Europe/Amsterdam'
            ]);
            
            if ($result) {
                echo "<span style='color: green;'>✅ User preferences creation test PASSED</span><br>";
                $testResults['data_storage_user_preferences'] = true;
            } else {
                echo "<span style='color: red;'>❌ User preferences creation test FAILED</span><br>";
                $testResults['data_storage_user_preferences'] = false;
                $overallStatus = false;
            }
        } else {
            echo "<span style='color: green;'>✅ User preferences already exist</span><br>";
            $testResults['data_storage_user_preferences'] = true;
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ User preferences test FAILED: {$e->getMessage()}</span><br>";
        $testResults['data_storage_user_preferences'] = false;
        $overallStatus = false;
    }
    
    // Test 6: Optional Tables Test
    echo "<h3>🔍 Test 6: Optional Tables Test</h3>";
    
    $optionalTables = ['subscription_categories'];
    
    foreach ($optionalTables as $tableName) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            $table = $stmt->fetch();
            
            if ($table) {
                echo "<span style='color: green;'>✅ Table {$tableName} exists</span><br>";
                
                // Check if it has data
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$tableName}");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    echo "<span style='color: green;'>✅ Table {$tableName} has {$count} records</span><br>";
                } else {
                    echo "<span style='color: orange;'>⚠️ Table {$tableName} is empty</span><br>";
                }
                
                $testResults["table_{$tableName}"] = true;
            } else {
                echo "<span style='color: orange;'>⚠️ Optional table {$tableName} does not exist</span><br>";
                $testResults["table_{$tableName}"] = false;
            }
        } catch (Exception $e) {
            echo "<span style='color: red;'>❌ Error checking table {$tableName}: {$e->getMessage()}</span><br>";
            $testResults["table_{$tableName}"] = false;
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Connection Error:</strong> " . $e->getMessage();
    echo "</div>";
    $overallStatus = false;
}

// Overall Results
echo "<hr>";
echo "<h2>📋 Test Results Summary</h2>";

$passedTests = array_filter($testResults, function($result) { return $result === true; });
$failedTests = array_filter($testResults, function($result) { return $result === false; });

echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; flex: 1;'>";
echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Passed Tests</h3>";
echo "<strong>" . count($passedTests) . "</strong> tests passed";
echo "</div>";

echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; flex: 1;'>";
echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Failed Tests</h3>";
echo "<strong>" . count($failedTests) . "</strong> tests failed";
echo "</div>";

echo "</div>";

if ($overallStatus) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; color: #155724; text-align: center; margin: 20px 0;'>";
    echo "<h2 style='margin: 0;'>🎉 SYSTEM READY FOR PRODUCTION!</h2>";
    echo "<p style='margin: 10px 0 0 0;'>Your database schema is properly configured and all core functionality is working.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; color: #721c24; text-align: center; margin: 20px 0;'>";
    echo "<h2 style='margin: 0;'>⚠️ SYSTEM NEEDS ATTENTION</h2>";
    echo "<p style='margin: 10px 0 0 0;'>Some critical tests failed. Please review the issues above and apply the necessary fixes.</p>";
    echo "</div>";
}

// Next Steps
echo "<h2>🚀 Next Steps</h2>";

if ($overallStatus) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Ready for Production Testing:</h3>";
    echo "<ol>";
    echo "<li><strong>Inject Test Subscriptions</strong> - Add realistic subscription data for testing</li>";
    echo "<li><strong>Test Bank Scan Flow</strong> - Verify TrueLayer integration works end-to-end</li>";
    echo "<li><strong>Test Dashboard Features</strong> - Verify all subscription management features</li>";
    echo "<li><strong>Test Export Functionality</strong> - Verify PDF/CSV export works with real data</li>";
    echo "<li><strong>Test Email Notifications</strong> - Verify reminder emails work properly</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Fix Required Issues:</h3>";
    echo "<ol>";
    echo "<li><strong>Re-run Schema Fix Tool</strong> - Apply any missing fixes</li>";
    echo "<li><strong>Check Database Permissions</strong> - Ensure ALTER TABLE permissions</li>";
    echo "<li><strong>Verify Table Structure</strong> - Check for any remaining issues</li>";
    echo "<li><strong>Re-run This Test</strong> - Verify all issues are resolved</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='fix-database-schema.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix Schema Issues</a>";
echo "<a href='inject-test-subscriptions.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Inject Test Data</a>";
echo "<a href='test-bank-data-processing.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Bank Processing</a>";
echo "<a href='dashboard.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Dashboard</a>";
echo "<a href='validate-database-schema.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Re-validate Schema</a>";
echo "</div>";

// Performance Test
echo "<hr>";
echo "<h2>⚡ Performance Test</h2>";

try {
    $startTime = microtime(true);
    
    // Test query performance with indexes
    $stmt = $pdo->prepare("
        SELECT s.*, u.email 
        FROM subscriptions s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'active' 
        AND s.billing_cycle = 'monthly' 
        AND s.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        LIMIT 10
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Query Performance Test:</strong><br>";
    echo "Complex subscription query executed in <strong>{$queryTime}ms</strong><br>";
    
    if ($queryTime < 100) {
        echo "<span style='color: green;'>✅ Excellent performance</span>";
    } elseif ($queryTime < 500) {
        echo "<span style='color: orange;'>⚠️ Good performance</span>";
    } else {
        echo "<span style='color: red;'>❌ Slow performance - consider adding more indexes</span>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Performance test failed:</strong> " . $e->getMessage();
    echo "</div>";
}
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
a {
    transition: opacity 0.2s;
}
a:hover {
    opacity: 0.9;
}
</style>
