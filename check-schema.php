<?php
require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "=== SUBSCRIPTIONS TABLE SCHEMA ===\n";
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll();
    
    foreach($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== CURRENT BROKEN SUBSCRIPTION ===\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = 2 LIMIT 1");
    $stmt->execute();
    $sub = $stmt->fetch();
    
    if ($sub) {
        foreach($sub as $key => $value) {
            if (!is_numeric($key)) {
                echo "$key: " . ($value ?? 'NULL') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
