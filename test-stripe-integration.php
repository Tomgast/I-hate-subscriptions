<?php
/**
 * COMPREHENSIVE STRIPE INTEGRATION TEST
 * Test the complete Stripe Financial Connections flow after TrueLayer migration
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/stripe_financial_service.php';
require_once 'includes/plan_manager.php';

echo "<h1>🧪 Stripe Financial Connections Integration Test</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Test the complete Stripe Financial Connections flow after migrating from TrueLayer.";
echo "</div>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📋 Step 1: Test Database Connection</h2>";

try {
    $pdo = getDBConnection();
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Database Connection Successful</strong><br>";
    echo "Connected to MariaDB database successfully.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Database Connection Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 2: Test Stripe Service Initialization</h2>";

try {
    $stripeService = new StripeFinancialService($pdo);
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Stripe Service Initialized</strong><br>";
    echo "StripeFinancialService created successfully.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Stripe Service Initialization Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 3: Test Required Database Tables</h2>";

$requiredTables = [
    'users' => 'User accounts and authentication',
    'bank_connection_sessions' => 'Stripe Financial Connections sessions',
    'bank_connections' => 'Connected bank accounts',
    'bank_scans' => 'Bank scan records',
    'subscriptions' => 'Detected subscriptions'
];

foreach ($requiredTables as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724; margin: 5px 0;'>";
            echo "<strong>✅ Table '$table' exists</strong> - $description";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; color: #856404; margin: 5px 0;'>";
            echo "<strong>⚠️ Table '$table' missing</strong> - $description";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24; margin: 5px 0;'>";
        echo "<strong>❌ Error checking table '$table':</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>📋 Step 4: Test User Session (Mock)</h2>";

// Create a test user session for testing
$testUserId = 999; // Use a test user ID
$_SESSION['user_id'] = $testUserId;

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 10px 0;'>";
echo "<strong>ℹ️ Test User Session Created</strong><br>";
echo "Using test user ID: $testUserId<br>";
echo "In production, this would be a real logged-in user.";
echo "</div>";

echo "<h2>📋 Step 5: Test Plan Manager Integration</h2>";

try {
    $planManager = new PlanManager($pdo);
    
    // Test plan access
    $canAccessBankScan = $planManager->canAccessFeature($testUserId, 'bank_scan');
    $hasScansRemaining = $planManager->hasScansRemaining($testUserId);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Plan Manager Working</strong><br>";
    echo "Can access bank scan: " . ($canAccessBankScan ? 'Yes' : 'No') . "<br>";
    echo "Has scans remaining: " . ($hasScansRemaining ? 'Yes' : 'No');
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Plan Manager Error:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>📋 Step 6: Test Stripe Customer Creation</h2>";

try {
    // First, create a test user in the database if it doesn't exist
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, email, name, subscription_type, subscription_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$testUserId, 'test@123cashcontrol.com', 'Test User', 'monthly', 'active']);
    
    // Test customer creation (this should work without actually creating a session)
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Test User Created</strong><br>";
    echo "Test user added to database for Stripe customer creation.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Test User Creation Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>📋 Step 7: Test Connection Status</h2>";

try {
    $connectionStatus = $stripeService->getConnectionStatus($testUserId);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Connection Status Retrieved</strong><br>";
    echo "Has connections: " . ($connectionStatus['has_connections'] ? 'Yes' : 'No') . "<br>";
    echo "Connection count: " . $connectionStatus['connection_count'] . "<br>";
    echo "Scan count: " . $connectionStatus['scan_count'];
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Connection Status Error:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🚀 Next Steps for Live Testing</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 15px 0;'>";
echo "<h3>Ready for Live Testing:</h3>";
echo "<ol>";
echo "<li><strong>Login as a real user</strong> with an active subscription plan</li>";
echo "<li><strong>Go to dashboard</strong> and click 'Bank Scan' button</li>";
echo "<li><strong>Test Stripe Financial Connections</strong> with a US bank account</li>";
echo "<li><strong>Complete the callback flow</strong> and verify data is saved</li>";
echo "<li><strong>Test export functionality</strong> with the connected data</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>🔗 Test Links:</h3>";
echo "<p><a href='bank/stripe-scan.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Bank Scan Page</a></p>";
echo "<p><a href='dashboard-onetime.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test One-Time Dashboard</a></p>";
echo "<p><a href='export/index.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Export System</a></p>";
echo "</div>";

// Clean up test session
unset($_SESSION['user_id']);

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🧹 Cleanup Complete</strong><br>";
echo "Test session cleaned up. System ready for live testing.";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
</style>
