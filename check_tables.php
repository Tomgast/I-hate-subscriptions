<?php
require_once 'config/db_config.php';

// Get database connection
$pdo = getDBConnection();

// Check all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Database Tables</h1>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table";
    
    // Check if table has data
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    echo " ($count records)";
    
    // If it's a subscriptions table, show a preview
    if (strpos(strtolower($table), 'subscription') !== false) {
        echo "<br><small>";
        $preview = $pdo->query("SELECT * FROM `$table` ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($preview as $row) {
            echo htmlspecialchars(json_encode($row)) . "<br>";
        }
        echo "</small>";
    }
    
    echo "</li>";
}
echo "</ul>";

// Add some basic CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    ul { list-style-type: none; padding: 0; }
    li { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
    small { color: #666; }
</style>";
