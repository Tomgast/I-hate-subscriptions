<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database configuration
require_once 'config.php';

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    
    // Get table information
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tableName = $row[0];
        
        // Get row count for each table
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
        $count = $countStmt->fetch()['count'];
        
        $tables[$tableName] = $count;
    }
    
    // Get database size
    $sizeStmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    $sizeResult = $sizeStmt->fetch();
    $dbSize = $sizeResult['size_mb'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful and tables created',
        'database' => $dbname,
        'tables' => $tables,
        'database_size_mb' => $dbSize,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Database initialization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database initialization failed',
        'details' => $e->getMessage()
    ]);
}
?>
