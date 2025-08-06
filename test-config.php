<?php
// Test configuration loading
echo "<h2>Configuration Test</h2>";

try {
    require_once 'config/db_config.php';
    echo "<p>✅ db_config.php loaded successfully</p>";
    
    // Test getSecureConfig function
    if (function_exists('getSecureConfig')) {
        echo "<p>✅ getSecureConfig function exists</p>";
        
        $dbPassword = getSecureConfig('DB_PASSWORD');
        echo "<p>DB_PASSWORD: " . (empty($dbPassword) ? "❌ NOT SET" : "✅ SET (length: " . strlen($dbPassword) . ")") . "</p>";
        
        $googleSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
        echo "<p>GOOGLE_CLIENT_SECRET: " . (empty($googleSecret) ? "❌ NOT SET" : "✅ SET (length: " . strlen($googleSecret) . ")") . "</p>";
    } else {
        echo "<p>❌ getSecureConfig function not found</p>";
    }
    
    // Test database connection
    echo "<h3>Database Connection Test</h3>";
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✅ Users table accessible - " . $result['count'] . " users found</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
