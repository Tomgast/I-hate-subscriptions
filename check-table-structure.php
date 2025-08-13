<?php
/**
 * Check table structures to understand the data schema
 */

require_once __DIR__ . '/config/db_config.php';

echo "=== Checking Table Structures ===\n\n";

$pdo = getDBConnection();

$tables = ['raw_transactions', 'bank_transactions', 'subscriptions'];

foreach ($tables as $table) {
    echo "--- $table Table Structure ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $col) {
            echo "{$col['Field']} ({$col['Type']}) - {$col['Null']} - {$col['Key']}\n";
        }
        
        // Show sample data
        echo "\nSample data from $table:\n";
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
        $samples = $stmt->fetchAll();
        
        if (empty($samples)) {
            echo "No data in $table\n";
        } else {
            foreach ($samples as $i => $sample) {
                echo "Row " . ($i + 1) . ": " . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "=== Table Structure Check Complete ===\n";
?>
