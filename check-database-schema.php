<?php
/**
 * CHECK DATABASE SCHEMA COMPATIBILITY
 * Verify if the raw_transactions table can handle GoCardless data
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
    <title>Database Schema Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Schema Compatibility Check</h1>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>1. Check raw_transactions Table Schema</h2>";
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE raw_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "<p class='error'>❌ raw_transactions table does not exist!</p>";
        echo "<p>The table needs to be created. Would you like me to create it?</p>";
    } else {
        echo "<p class='success'>✅ raw_transactions table exists</p>";
        
        echo "<h3>📋 Current Table Structure:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $fieldInfo = [];
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
            
            $fieldInfo[$column['Field']] = $column;
        }
        echo "</table>";
    }
    
    echo "<h2>2. Test Data Compatibility</h2>";
    
    // Sample GoCardless transaction data
    $sampleTransaction = [
        'transactionId' => '2025080801645717-1',
        'transactionAmount' => [
            'amount' => '-95.35',
            'currency' => 'EUR'
        ],
        'bookingDate' => '2025-08-08',
        'valueDate' => '2025-08-08',
        'creditorName' => 'Freshto Ideal',
        'remittanceInformationUnstructured' => 'Freshto Ideal Clerkenwell',
        'bankTransactionCode' => 'PMNT',
        'proprietaryBankTransactionCode' => 'PURCHASE',
        'internalTransactionId' => 'a2979b79410852ecae9ca0693b329924'
    ];
    
    echo "<h3>🧪 Sample Transaction Data:</h3>";
    echo "<pre>" . json_encode($sampleTransaction, JSON_PRETTY_PRINT) . "</pre>";
    
    // Process the sample transaction like our code would
    require_once 'includes/gocardless_transaction_processor.php';
    $processor = new GoCardlessTransactionProcessor($pdo);
    
    // Use reflection to access the private processTransaction method
    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('processTransaction');
    $method->setAccessible(true);
    
    $processedTransaction = $method->invoke($processor, $sampleTransaction);
    
    if ($processedTransaction) {
        echo "<h3>✅ Processed Transaction:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th><th>Length</th><th>DB Field Type</th><th>Compatible?</th></tr>";
        
        foreach ($processedTransaction as $field => $value) {
            $valueStr = is_string($value) ? $value : json_encode($value);
            $length = strlen($valueStr);
            
            // Check if this field exists in database
            $dbField = $fieldInfo[$field] ?? null;
            $dbType = $dbField ? $dbField['Type'] : 'MISSING';
            
            // Check compatibility
            $compatible = '❓';
            if (!$dbField) {
                $compatible = '❌ Missing';
            } else {
                // Check length constraints
                if (preg_match('/varchar\((\d+)\)/', $dbType, $matches)) {
                    $maxLength = (int)$matches[1];
                    if ($length > $maxLength) {
                        $compatible = "❌ Too long ($length > $maxLength)";
                    } else {
                        $compatible = '✅ OK';
                    }
                } elseif (strpos($dbType, 'text') !== false) {
                    $compatible = '✅ OK';
                } elseif (strpos($dbType, 'int') !== false) {
                    $compatible = is_numeric($value) ? '✅ OK' : '❌ Not numeric';
                } elseif (strpos($dbType, 'decimal') !== false || strpos($dbType, 'float') !== false) {
                    $compatible = is_numeric($value) ? '✅ OK' : '❌ Not numeric';
                } else {
                    $compatible = '✅ OK';
                }
            }
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($field) . "</strong></td>";
            echo "<td>" . htmlspecialchars($valueStr) . "</td>";
            echo "<td>$length</td>";
            echo "<td>" . htmlspecialchars($dbType) . "</td>";
            echo "<td>$compatible</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p class='error'>❌ Failed to process sample transaction</p>";
    }
    
    echo "<h2>3. Test Database Insert</h2>";
    
    if ($processedTransaction && isset($_POST['test_insert'])) {
        echo "<p class='info'>🧪 Testing actual database insert...</p>";
        
        try {
            $userId = $_SESSION['user_id'];
            $accountId = 'test-schema-check';
            
            // Try to insert the processed transaction
            $stmt = $pdo->prepare("
                INSERT INTO raw_transactions (
                    user_id, account_id, transaction_id, amount, currency,
                    booking_date, value_date, merchant_name, creditor_name, debtor_name,
                    description, bank_transaction_code, merchant_category_code,
                    end_to_end_id, mandate_id, status, raw_data, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $accountId,
                $processedTransaction['transaction_id'],
                $processedTransaction['amount'],
                $processedTransaction['currency'],
                $processedTransaction['booking_date'],
                $processedTransaction['value_date'],
                $processedTransaction['merchant_name'],
                $processedTransaction['creditor_name'],
                $processedTransaction['debtor_name'],
                $processedTransaction['description'],
                $processedTransaction['bank_transaction_code'],
                $processedTransaction['merchant_category_code'],
                $processedTransaction['end_to_end_id'],
                $processedTransaction['mandate_id'],
                $processedTransaction['status'],
                $processedTransaction['raw_data']
            ]);
            
            if ($result) {
                echo "<p class='success'>✅ Database insert successful!</p>";
                echo "<p>The schema is compatible. The issue must be elsewhere in the pipeline.</p>";
                
                // Clean up test data
                $stmt = $pdo->prepare("DELETE FROM raw_transactions WHERE account_id = ?");
                $stmt->execute([$accountId]);
                echo "<p><small>Test data cleaned up.</small></p>";
                
            } else {
                echo "<p class='error'>❌ Database insert failed</p>";
                $errorInfo = $stmt->errorInfo();
                echo "<p>Error: " . htmlspecialchars($errorInfo[2]) . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Database insert exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } elseif ($processedTransaction) {
        echo "<form method='post'>";
        echo "<button type='submit' name='test_insert' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "🧪 Test Database Insert";
        echo "</button>";
        echo "</form>";
        echo "<p><small>This will test if the processed transaction can be inserted into the database.</small></p>";
    }
    
    echo "<h2>4. Recommendations</h2>";
    
    if (empty($columns)) {
        echo "<div style='border: 2px solid red; padding: 15px; background: #ffe6e6;'>";
        echo "<h3>❌ Critical Issue: Missing Table</h3>";
        echo "<p>The raw_transactions table does not exist. You need to create it first.</p>";
        echo "</div>";
    } else {
        echo "<div style='border: 2px solid green; padding: 15px; background: #e6ffe6;'>";
        echo "<h3>✅ Next Steps</h3>";
        echo "<p>If the test insert works, the schema is fine and the issue is in the data pipeline logic.</p>";
        echo "<p>If the test insert fails, we need to fix the schema compatibility issues shown above.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
