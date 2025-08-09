<?php
/**
 * CHECK AND CREATE MISSING TABLES
 * Verify and create the bank_scans table needed for GoCardless scans
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Missing Tables</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Check Missing Tables</h1>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>1. Check Required Tables</h2>";
    
    $requiredTables = [
        'bank_scans' => "
            CREATE TABLE bank_scans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                provider VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'completed',
                subscriptions_found INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                INDEX idx_user_provider (user_id, provider),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'subscriptions' => "
            CREATE TABLE subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                scan_id INT NULL,
                merchant_name VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
                billing_cycle VARCHAR(20) NOT NULL,
                last_charge_date DATE NULL,
                confidence DECIMAL(3,2) NOT NULL DEFAULT 0.00,
                provider VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY unique_user_merchant (user_id, merchant_name, provider),
                INDEX idx_user_id (user_id),
                INDEX idx_provider (provider),
                INDEX idx_billing_cycle (billing_cycle)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($requiredTables as $tableName => $createSql) {
        echo "<h3>📋 Checking table: $tableName</h3>";
        
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p class='success'>✅ Table $tableName exists</p>";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $tableName");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<details>";
            echo "<summary>View table structure</summary>";
            echo "<pre>";
            foreach ($columns as $column) {
                echo $column['Field'] . " | " . $column['Type'] . " | " . $column['Null'] . " | " . $column['Key'] . "\n";
            }
            echo "</pre>";
            echo "</details>";
            
        } else {
            echo "<p class='error'>❌ Table $tableName is missing</p>";
            
            if (isset($_POST["create_$tableName"])) {
                echo "<p class='info'>🔨 Creating table $tableName...</p>";
                
                try {
                    $pdo->exec($createSql);
                    echo "<p class='success'>✅ Table $tableName created successfully!</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Failed to create table $tableName: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                
            } else {
                echo "<form method='post' style='margin: 10px 0;'>";
                echo "<button type='submit' name='create_$tableName' style='background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
                echo "🔨 Create $tableName Table";
                echo "</button>";
                echo "</form>";
                
                echo "<details>";
                echo "<summary>View SQL that will be executed</summary>";
                echo "<pre>" . htmlspecialchars($createSql) . "</pre>";
                echo "</details>";
            }
        }
    }
    
    echo "<h2>2. Test GoCardless Scan</h2>";
    
    // Check if all tables exist
    $allTablesExist = true;
    foreach ($requiredTables as $tableName => $createSql) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if (!$stmt->fetch()) {
            $allTablesExist = false;
            break;
        }
    }
    
    if ($allTablesExist) {
        if (isset($_POST['test_scan'])) {
            echo "<p class='info'>🔄 Testing GoCardless scan...</p>";
            
            require_once 'includes/gocardless_financial_service.php';
            $gocardlessService = new GoCardlessFinancialService($pdo);
            $scanResult = $gocardlessService->scanForSubscriptions($_SESSION['user_id']);
            
            echo "<h3>Scan Result:</h3>";
            echo "<pre>" . json_encode($scanResult, JSON_PRETTY_PRINT) . "</pre>";
            
            if ($scanResult['success']) {
                echo "<p class='success'>✅ Scan completed successfully!</p>";
                echo "<p>Transactions processed: " . $scanResult['transactions_processed'] . "</p>";
                echo "<p>Subscriptions found: " . $scanResult['subscriptions_found'] . "</p>";
                echo "<p><a href='view-raw-data.php'>View stored transaction data</a></p>";
                echo "<p><a href='dashboard.php'>Go to dashboard</a></p>";
            } else {
                echo "<p class='error'>❌ Scan failed: " . htmlspecialchars($scanResult['error']) . "</p>";
            }
            
        } else {
            echo "<p class='success'>✅ All required tables exist. Ready to test scan!</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='test_scan' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🔄 Test GoCardless Scan";
            echo "</button>";
            echo "</form>";
            echo "<p><small>This will fetch your real bank transactions and store them in the database.</small></p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Create the missing tables above before testing the scan.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
