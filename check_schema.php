<?php
require_once 'config/db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h3>Current user_preferences table schema:</h3>";
    $stmt = $pdo->query('DESCRIBE user_preferences');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>" . $column['Field'] . "</strong> (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<h3>Sample data (if any):</h3>";
    $stmt = $pdo->query('SELECT * FROM user_preferences LIMIT 3');
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        echo "<p>No data in user_preferences table</p>";
    } else {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
