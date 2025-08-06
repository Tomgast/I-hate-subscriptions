<?php
// Debug Dashboard Issues
echo "<h2>🔍 Dashboard Debug</h2>";

try {
    echo "<h3>1. Session Check</h3>";
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User logged in - ID: " . $_SESSION['user_id'] . "</p>";
        echo "<p>✅ User email: " . ($_SESSION['user_email'] ?? 'not set') . "</p>";
        echo "<p>✅ User name: " . ($_SESSION['user_name'] ?? 'not set') . "</p>";
        echo "<p>✅ Is paid: " . ($_SESSION['is_paid'] ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p>❌ No user session found</p>";
    }
    
    echo "<h3>2. Configuration Test</h3>";
    require_once 'config/secure_loader.php';
    echo "<p>✅ Secure loader loaded</p>";
    
    if (function_exists('getSecureConfig')) {
        echo "<p>✅ getSecureConfig function available</p>";
        $dbPass = getSecureConfig('DB_PASSWORD');
        echo "<p>DB Password: " . (empty($dbPass) ? "❌ Not set" : "✅ Set (length: " . strlen($dbPass) . ")") . "</p>";
    } else {
        echo "<p>❌ getSecureConfig function not available</p>";
    }
    
    echo "<h3>3. Database Connection Test</h3>";
    require_once 'config/db_config.php';
    echo "<p>✅ db_config.php loaded</p>";
    
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    echo "<h3>4. Include Files Test</h3>";
    
    // Test subscription manager
    try {
        require_once 'includes/subscription_manager.php';
        echo "<p>✅ subscription_manager.php loaded</p>";
        
        $subscriptionManager = new SubscriptionManager();
        echo "<p>✅ SubscriptionManager class instantiated</p>";
    } catch (Exception $e) {
        echo "<p>❌ SubscriptionManager error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    }
    
    // Test email service
    try {
        require_once 'includes/email_service.php';
        echo "<p>✅ email_service.php loaded</p>";
        
        $emailService = new EmailService();
        echo "<p>✅ EmailService class instantiated</p>";
    } catch (Exception $e) {
        echo "<p>❌ EmailService error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    }
    
    echo "<h3>5. Dashboard Simulation</h3>";
    
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Test basic dashboard queries
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            echo "<p>✅ Subscriptions query successful - " . $result['count'] . " subscriptions found</p>";
        } catch (Exception $e) {
            echo "<p>❌ Subscriptions query error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test user data query
        try {
            $stmt = $pdo->prepare("SELECT name, email, is_pro FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                echo "<p>✅ User data query successful - " . htmlspecialchars($user['name']) . "</p>";
            } else {
                echo "<p>❌ User not found in database</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ User query error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>6. Header Include Test</h3>";
    
    try {
        // Test if header include works
        ob_start();
        include 'includes/header.php';
        $headerOutput = ob_get_clean();
        echo "<p>✅ Header include loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Header include error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    }
    
    echo "<h2>🎯 Summary</h2>";
    echo "<p>If all tests above show ✅, then the dashboard should work. If any show ❌, that's likely the cause of the 500 error.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Critical Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
