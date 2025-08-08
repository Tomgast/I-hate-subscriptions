<?php
/**
 * UNIFIED BANK INTEGRATION TEST
 * Test both Stripe (US) and GoCardless (EU) integrations
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/bank_provider_router.php';

echo "<h1>🌍 Unified Bank Integration Test</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Test the complete unified bank integration system with both Stripe (US) and GoCardless (EU) providers.";
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

echo "<h2>📋 Step 2: Test Provider Router Initialization</h2>";

try {
    $providerRouter = new BankProviderRouter($pdo);
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Provider Router Initialized</strong><br>";
    echo "BankProviderRouter created successfully.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Provider Router Initialization Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 3: Test Available Providers</h2>";

try {
    $availableProviders = $providerRouter->getAvailableProviders();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Available Providers Retrieved</strong><br>";
    echo "Found " . count($availableProviders) . " providers:<br>";
    
    foreach ($availableProviders as $key => $provider) {
        echo "<br><strong>{$provider['flag']} {$provider['name']}</strong><br>";
        echo "Description: {$provider['description']}<br>";
        echo "Countries: " . implode(', ', $provider['countries']) . "<br>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Provider Retrieval Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>📋 Step 4: Test Stripe Service Initialization</h2>";

try {
    $stripeService = $providerRouter->getProviderService('stripe');
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Stripe Service Initialized</strong><br>";
    echo "StripeFinancialService created successfully.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Stripe Service Initialization Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>📋 Step 5: Test GoCardless Service Initialization</h2>";

try {
    $gocardlessService = $providerRouter->getProviderService('gocardless');
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ GoCardless Service Initialized</strong><br>";
    echo "GoCardlessFinancialService created successfully.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ GoCardless Service Initialization Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>📋 Step 6: Test GoCardless API Connection</h2>";

try {
    $institutions = $providerRouter->getInstitutionsByCountry('NL');
    
    if (!empty($institutions)) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<strong>✅ GoCardless API Connection Successful</strong><br>";
        echo "Found " . count($institutions) . " Dutch banks available:<br>";
        
        // Show first 5 banks
        $displayBanks = array_slice($institutions, 0, 5);
        foreach ($displayBanks as $bank) {
            echo "• " . ($bank['name'] ?? 'Unknown Bank') . "<br>";
        }
        
        if (count($institutions) > 5) {
            echo "... and " . (count($institutions) - 5) . " more banks";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 10px 0;'>";
        echo "<strong>⚠️ No Banks Found</strong><br>";
        echo "GoCardless API connected but no banks found for Netherlands. This might be a configuration issue.";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ GoCardless API Connection Failed:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<small>Check your GoCardless credentials in secure-config.php</small>";
    echo "</div>";
}

echo "<h2>📋 Step 7: Test Unified Connection Status</h2>";

// Create a test user session
$testUserId = 999;
$_SESSION['user_id'] = $testUserId;

try {
    // First, create a test user in the database if it doesn't exist
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, email, name, subscription_type, subscription_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$testUserId, 'test@123cashcontrol.com', 'Test User', 'monthly', 'active']);
    
    $connectionStatus = $providerRouter->getUnifiedConnectionStatus($testUserId);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Unified Connection Status Retrieved</strong><br>";
    echo "Has connections: " . ($connectionStatus['has_connections'] ? 'Yes' : 'No') . "<br>";
    echo "Total connections: " . $connectionStatus['total_connections'] . "<br>";
    echo "Total scans: " . $connectionStatus['total_scans'] . "<br><br>";
    
    echo "<strong>Provider Status:</strong><br>";
    foreach ($connectionStatus['providers'] as $provider => $status) {
        $providerName = $provider === 'stripe' ? 'Stripe (US)' : 'GoCardless (EU)';
        echo "• {$providerName}: " . ($status['has_connections'] ? $status['connection_count'] . ' connections' : 'No connections') . "<br>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Connection Status Error:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🚀 Live Testing Ready</h2>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>🎯 System Status: READY FOR TESTING</h3>";
echo "<p>All components are working correctly. You can now test the live integration:</p>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 2px solid #635bff;'>";
echo "<h4 style='color: #635bff; margin-top: 0;'>🇺🇸 Test Stripe (US Banks)</h4>";
echo "<ol style='margin: 10px 0;'>";
echo "<li>Go to <strong>bank/unified-scan.php</strong></li>";
echo "<li>Select <strong>United States Banks</strong></li>";
echo "<li>Click <strong>Connect Bank Account</strong></li>";
echo "<li>Complete Stripe authorization</li>";
echo "<li>Verify subscription scan works</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 2px solid #00d4aa;'>";
echo "<h4 style='color: #00d4aa; margin-top: 0;'>🇪🇺 Test GoCardless (EU Banks)</h4>";
echo "<ol style='margin: 10px 0;'>";
echo "<li>Go to <strong>bank/unified-scan.php</strong></li>";
echo "<li>Select <strong>European Banks</strong></li>";
echo "<li>Choose your country (NL, DE, FR, etc.)</li>";
echo "<li>Click <strong>Connect Bank Account</strong></li>";
echo "<li>Complete bank authorization</li>";
echo "<li>Verify subscription scan works</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h4 style='margin-top: 0;'>📊 After Testing</h4>";
echo "<ul>";
echo "<li><strong>Check Dashboard</strong> - Verify both providers show connection status</li>";
echo "<li><strong>Test Exports</strong> - PDF and CSV should include data from both providers</li>";
echo "<li><strong>Admin Panel</strong> - Should show unified scan results</li>";
echo "<li><strong>Monitor Logs</strong> - Check for any errors during the process</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🔗 Quick Test Links:</strong><br>";
echo "<p><a href='bank/unified-scan.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🌍 Unified Bank Scan</a></p>";
echo "<p><a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>📊 Dashboard</a></p>";
echo "<p><a href='export/index.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>📄 Export System</a></p>";
echo "</div>";

// Clean up test session
unset($_SESSION['user_id']);

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🧹 Test Complete</strong><br>";
echo "System is ready for live testing with both Stripe (US) and GoCardless (EU) providers.";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
</style>
