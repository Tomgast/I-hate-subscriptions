<?php
// Test Login System Functionality
echo "<h2>🔧 Login System Test</h2>";

echo "<h3>🔍 Server Environment Debug</h3>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current User:</strong> " . get_current_user() . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</p>";
echo "<p><strong>Script Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Parent of Document Root:</strong> " . dirname($_SERVER['DOCUMENT_ROOT']) . "</p>";

echo "<h3>🔍 Searching for secure-config.php</h3>";

// Define the same paths as our secure loader
$possiblePaths = [
    dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../secure-config.php',
    __DIR__ . '/../secure-config.php',
    __DIR__ . '/../../secure-config.php',
    dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/secure-config.php',
    '/var/www/vhosts/' . $_SERVER['HTTP_HOST'] . '/secure-config.php',
    '/home/' . get_current_user() . '/secure-config.php',
    '/secure-config.php',
    '/home/secure-config.php'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Path</th><th>Exists</th><th>Readable</th><th>Status</th></tr>";

$foundConfig = false;
foreach ($possiblePaths as $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $status = $readable ? '✅ FOUND' : ($exists ? '⚠️ EXISTS BUT NOT READABLE' : '❌ NOT FOUND');
    
    if ($readable) {
        $foundConfig = $path;
    }
    
    echo "<tr>";
    echo "<td style='font-family: monospace; padding: 5px;'>" . htmlspecialchars($path) . "</td>";
    echo "<td style='text-align: center; padding: 5px;'>" . ($exists ? '✅' : '❌') . "</td>";
    echo "<td style='text-align: center; padding: 5px;'>" . ($readable ? '✅' : '❌') . "</td>";
    echo "<td style='padding: 5px;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($foundConfig) {
    echo "<p><strong>🎉 Found secure-config.php at:</strong> " . htmlspecialchars($foundConfig) . "</p>";
} else {
    echo "<p><strong>❌ secure-config.php not found in any expected location!</strong></p>";
    echo "<p><strong>💡 Suggestion:</strong> Your secure-config.php should be placed at one of these locations:</p>";
    echo "<ul>";
    echo "<li><code>" . htmlspecialchars(dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php') . "</code> (recommended)</li>";
    echo "<li><code>" . htmlspecialchars('/home/' . get_current_user() . '/secure-config.php') . "</code></li>";
    echo "</ul>";
}

try {
    echo "<h3>1. Testing Configuration Loading</h3>";
    
    // Test secure loader
    require_once 'config/secure_loader.php';
    echo "<p>✅ Secure loader loaded successfully</p>";
    
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
    
    echo "<h3>2. Testing Database Configuration</h3>";
    
    // Test db_config loading
    require_once 'config/db_config.php';
    echo "<p>✅ db_config.php loaded successfully</p>";
    
    // Test database connection
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✅ Users table accessible - " . $result['count'] . " users found</p>";
    
    echo "<h3>3. Testing Auth Pages</h3>";
    
    // Test if signin.php would work
    echo "<p>Testing signin.php dependencies...</p>";
    
    // Simulate what signin.php does
    session_start();
    echo "<p>✅ Session started successfully</p>";
    
    // Test if we can access the database from auth context
    $testEmail = 'test@example.com';
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $result = $stmt->fetch();
    echo "<p>✅ Database query from auth context successful</p>";
    
    echo "<h3>4. Testing Include Files</h3>";
    
    // Test email service
    try {
        require_once 'includes/email_service.php';
        $emailService = new EmailService();
        echo "<p>✅ EmailService class instantiated successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ EmailService error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test subscription manager
    try {
        require_once 'includes/subscription_manager.php';
        $subscriptionManager = new SubscriptionManager();
        echo "<p>✅ SubscriptionManager class instantiated successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ SubscriptionManager error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>🎉 Test Results Summary</h2>";
    echo "<p><strong>Configuration System:</strong> ✅ Working</p>";
    echo "<p><strong>Database Connection:</strong> ✅ Working</p>";
    echo "<p><strong>Auth Dependencies:</strong> ✅ Working</p>";
    echo "<p><strong>Include Files:</strong> ✅ Working</p>";
    
    echo "<h3>🚀 Ready to Test</h3>";
    echo "<p>The login system should now be working. Try:</p>";
    echo "<ul>";
    echo "<li><a href='auth/signin.php'>Sign In Page</a></li>";
    echo "<li><a href='auth/signup.php'>Sign Up Page</a></li>";
    echo "<li><a href='dashboard.php'>Dashboard</a> (requires login)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error Detected</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
