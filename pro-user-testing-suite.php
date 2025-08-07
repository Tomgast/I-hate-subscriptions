<?php
/**
 * 🎯 PRO USER TESTING SUITE - SIMPLE VERSION
 * Basic setup for Pro user testing with detailed error reporting
 */

echo "<h1>🎯 CashControl Pro User Testing Suite</h1>\n";
echo "<p>Setting up Pro user testing environment...</p>\n";

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$testEmail = 'support@origens.nl';
$testName = 'Pro Test User';

echo "<h2>🔧 Step 1: Setup Pro Test Account</h2>\n";
echo "<p>Test email: $testEmail</p>\n";

try {
    echo "<p>Loading database config...</p>\n";
    require_once 'config/db_config.php';
    echo "<div style='background: green; color: white; padding: 10px; margin: 5px;'>✅ Database config loaded</div>";
    
    echo "<p>Testing database connection...</p>\n";
    $testQuery = $pdo->query("SELECT 1");
    echo "<div style='background: green; color: white; padding: 10px; margin: 5px;'>✅ Database connection works</div>";
    
    echo "<p>Checking if user exists...</p>\n";
    $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p>Creating new user...</p>\n";
        $stmt = $pdo->prepare("INSERT INTO users (email, name, google_id, created_at, updated_at) VALUES (?, ?, 'test_google_id', NOW(), NOW())");
        $stmt->execute([$testEmail, $testName]);
        $userId = $pdo->lastInsertId();
        echo "<div style='background: green; color: white; padding: 10px; margin: 5px;'>✅ Created user: $testEmail (ID: $userId)</div>";
    } else {
        $userId = $user['id'];
        echo "<div style='background: blue; color: white; padding: 10px; margin: 5px;'>✅ Found existing user: {$user['email']} (ID: $userId)</div>";
    }
    
    // Assign Pro Monthly Plan to test user using users table columns
    echo "<p>Assigning Pro Monthly plan to test user...</p>\n";
    $stmt = $pdo->prepare("
        UPDATE users SET 
            plan_type = 'monthly',
            plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH),
            scan_count = 0,
            max_scans = 999999,
            plan_purchased_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    echo "<div style='background: darkgreen; color: white; padding: 10px; margin: 5px;'>✅ Pro Monthly Plan activated</div>";
    
    echo "<p>Creating test session...</p>\n";
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $testEmail;
    $_SESSION['user_name'] = $testName;
    echo "<div style='background: purple; color: white; padding: 10px; margin: 5px;'>✅ Session created - logged in as Pro user</div>";
    
    echo "<h2>🚀 Step 2: Pro Feature Testing Links</h2>\n";
    
    echo "<div style='background: #1f2937; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>🚀 Quick Test Links</h3>";
    echo "<p><a href='dashboard.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>🏠 TEST DASHBOARD</a> - Should load without redirect</p>";
    echo "<p><a href='bank/scan.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>🏦 TEST BANK INTEGRATION</a></p>";
    echo "<p><a href='export/index.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>📄 TEST EXPORT SYSTEM</a></p>";
    echo "<p><a href='guides/index.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>📚 TEST UNSUBSCRIBE GUIDES</a></p>";
    echo "<p><a href='settings.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>⚙️ TEST SETTINGS</a></p>";
    echo "<p><a href='upgrade.php' style='color: #10b981; font-size: 18px; font-weight: bold;'>💳 TEST UPGRADE PAGE</a> - Should show current plan</p>";
    echo "</div>";
    
    echo "<div style='background: #f0f9ff; border: 2px solid #0ea5e9; padding: 20px; margin: 20px;'>";
    echo "<h3>📋 Expected Results</h3>";
    echo "<ul>";
    echo "<li>✅ Dashboard loads without redirecting to upgrade</li>";
    echo "<li>✅ All Pro features are accessible</li>";
    echo "<li>✅ No 500 internal server errors</li>";
    echo "<li>✅ Session persists across pages</li>";
    echo "<li>✅ Plan status shows as 'monthly' and 'active'</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: orange; color: white; padding: 15px; margin: 20px;'>";
    echo "<h3>⚠️ If Problems Occur</h3>";
    echo "<p>1. Check browser console for JavaScript errors</p>";
    echo "<p>2. Look for PHP errors in server logs</p>";
    echo "<p>3. Verify database tables exist (users, user_plans)</p>";
    echo "<p>4. Check file permissions</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>❌ ERROR OCCURRED</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<h3>🔧 Debug Info</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Script completed execution.</p>";
?>
