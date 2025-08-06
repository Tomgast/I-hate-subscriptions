<?php
// Minimal Dashboard Test - Step by step loading
echo "<h2>🔧 Minimal Dashboard Test</h2>";

try {
    echo "<p>Step 1: Starting session...</p>";
    session_start();
    echo "<p>✅ Session started</p>";
    
    echo "<p>Step 2: Checking login...</p>";
    if (!isset($_SESSION['user_id'])) {
        echo "<p>❌ Not logged in - redirecting to signin</p>";
        header('Location: auth/signin.php');
        exit;
    }
    echo "<p>✅ User logged in: " . $_SESSION['user_id'] . "</p>";
    
    echo "<p>Step 3: Loading config...</p>";
    require_once 'config/db_config.php';
    echo "<p>✅ Config loaded</p>";
    
    echo "<p>Step 4: Testing database...</p>";
    $pdo = getDBConnection();
    echo "<p>✅ Database connected</p>";
    
    echo "<p>Step 5: Loading header...</p>";
    ob_start();
    include 'includes/header.php';
    $headerContent = ob_get_clean();
    echo "<p>✅ Header loaded (length: " . strlen($headerContent) . ")</p>";
    
    echo "<p>Step 6: Basic query test...</p>";
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    echo "<p>✅ Query successful - " . $result['count'] . " subscriptions</p>";
    
    echo "<h3>🎉 All tests passed!</h3>";
    echo "<p>If this page loads successfully but dashboard.php doesn't, there's a specific issue in dashboard.php</p>";
    
    echo "<h3>🔗 Test Links</h3>";
    echo "<p><a href='dashboard.php'>Try Dashboard.php</a></p>";
    echo "<p><a href='auth/signin.php'>Back to Sign In</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error Found!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
