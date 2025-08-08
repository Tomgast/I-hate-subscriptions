<?php
require_once 'includes/database_helper.php';

echo "<h1>Database Schema Test</h1>";

try {
    $pdo = DatabaseHelper::getConnection();
    
    // Check payment_history table structure
    echo "<h2>payment_history table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE payment_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasplanType = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'plan_type') {
            $hasplanType = true;
        }
    }
    echo "</table>";
    
    if ($hasplanType) {
        echo "<p style='color: green;'>✅ plan_type column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ plan_type column is MISSING</p>";
        echo "<p><strong>Fix:</strong> Run database migration to add missing column</p>";
    }
    
    // Check users table structure
    echo "<h2>users table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['subscription_type', 'subscription_status', 'subscription_expires_at', 'reminder_access_expires_at'];
    $missingColumns = [];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $required) {
        if (in_array($required, $existingColumns)) {
            echo "<p style='color: green;'>✅ $required column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ $required column is MISSING</p>";
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'><strong>✅ All required columns exist</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Missing columns need to be added</strong></p>";
        echo "<p>Run database migration to fix schema</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
