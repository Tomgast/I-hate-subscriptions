<?php
/**
 * TRUELAYER LIVE ENVIRONMENT SWITCH TOOL
 * Safely switch from sandbox to live TrueLayer environment
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>🔄 TrueLayer Live Environment Switch</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>⚠️ Important:</strong><br>";
echo "This tool will help you safely switch from TrueLayer sandbox to live environment. Make sure you have your live TrueLayer credentials ready.";
echo "</div>";

try {
    // Load current configuration
    require_once 'config/secure_loader.php';
    
    $currentEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT') ?: 'sandbox';
    $currentClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
    $currentClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
    
    echo "<h2>📊 Current Configuration</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Environment:</strong> <span style='color: " . ($currentEnvironment === 'live' ? 'green' : 'orange') . ";'>{$currentEnvironment}</span><br>";
    echo "<strong>Client ID:</strong> " . (substr($currentClientId, 0, 20) . '...') . "<br>";
    echo "<strong>Client Secret:</strong> " . (substr($currentClientSecret, 0, 10) . '...') . "<br>";
    echo "</div>";
    
    // Check if already live
    if ($currentEnvironment === 'live') {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<strong>✅ Already in Live Environment</strong><br>";
        echo "Your TrueLayer integration is already configured for live/production environment.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Configuration Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🔧 Environment Switch Options</h2>";

// Option 1: Switch to Live Environment
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 1: Switch to Live Environment</h3>";
echo "<p>Update your configuration to use TrueLayer live environment with your production credentials.</p>";

echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='switch_to_live'>";

echo "<div style='margin: 10px 0;'>";
echo "<label style='display: block; font-weight: bold; margin-bottom: 5px;'>Live Client ID:</label>";
echo "<input type='text' name='live_client_id' placeholder='Your live TrueLayer Client ID' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;' required>";
echo "<small style='color: #666;'>This should NOT have the 'sandbox-' prefix</small>";
echo "</div>";

echo "<div style='margin: 10px 0;'>";
echo "<label style='display: block; font-weight: bold; margin-bottom: 5px;'>Live Client Secret:</label>";
echo "<input type='password' name='live_client_secret' placeholder='Your live TrueLayer Client Secret' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;' required>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>📋 What this will do:</strong><br>";
echo "• Update TRUELAYER_ENVIRONMENT to 'live'<br>";
echo "• Update TRUELAYER_CLIENT_ID to your live credentials<br>";
echo "• Update TRUELAYER_CLIENT_SECRET to your live credentials<br>";
echo "• Create backup of current sandbox configuration<br>";
echo "• Test connectivity with new credentials";
echo "</div>";

echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Switch to Live Environment</button>";
echo "</form>";
echo "</div>";

// Option 2: Test Live Credentials First
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 2: Test Live Credentials First</h3>";
echo "<p>Test your live credentials without changing the configuration.</p>";

echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='test_live_credentials'>";

echo "<div style='margin: 10px 0;'>";
echo "<label style='display: block; font-weight: bold; margin-bottom: 5px;'>Live Client ID:</label>";
echo "<input type='text' name='test_client_id' placeholder='Your live TrueLayer Client ID' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;' required>";
echo "</div>";

echo "<div style='margin: 10px 0;'>";
echo "<label style='display: block; font-weight: bold; margin-bottom: 5px;'>Live Client Secret:</label>";
echo "<input type='password' name='test_client_secret' placeholder='Your live TrueLayer Client Secret' style='width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;' required>";
echo "</div>";

echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Live Credentials</button>";
echo "</form>";
echo "</div>";

// Option 3: Revert to Sandbox
if ($currentEnvironment === 'live') {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 3: Revert to Sandbox</h3>";
    echo "<p>Switch back to sandbox environment for testing.</p>";
    echo "<form method='POST' style='display: inline;'>";
    echo "<input type='hidden' name='action' value='revert_to_sandbox'>";
    echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Revert to Sandbox</button>";
    echo "</form>";
    echo "</div>";
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'test_live_credentials':
                echo "<h2>🧪 Testing Live Credentials</h2>";
                
                $testClientId = $_POST['test_client_id'];
                $testClientSecret = $_POST['test_client_secret'];
                
                // Test API connectivity
                $testUrl = "https://auth.truelayer.com/connect/token";
                $testData = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $testClientId,
                    'client_secret' => $testClientSecret,
                    'scope' => 'info'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $testUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
                    echo "<strong>✅ Live Credentials Test: SUCCESS</strong><br>";
                    echo "Your live TrueLayer credentials are valid and working!";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
                    echo "<strong>❌ Live Credentials Test: FAILED</strong><br>";
                    echo "HTTP Code: {$httpCode}<br>";
                    echo "Response: " . substr($response, 0, 200) . "...";
                    echo "</div>";
                }
                break;
                
            case 'switch_to_live':
                echo "<h2>🔄 Switching to Live Environment</h2>";
                
                $liveClientId = $_POST['live_client_id'];
                $liveClientSecret = $_POST['live_client_secret'];
                
                // First test the credentials
                $testUrl = "https://auth.truelayer.com/connect/token";
                $testData = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $liveClientId,
                    'client_secret' => $liveClientSecret,
                    'scope' => 'info'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $testUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
                    echo "<strong>❌ Cannot Switch: Invalid Credentials</strong><br>";
                    echo "The live credentials failed validation. Please check your Client ID and Secret.";
                    echo "</div>";
                    break;
                }
                
                // Create backup of current config
                $configFile = __DIR__ . '/secure-config.php';
                $backupFile = __DIR__ . '/secure-config-backup-' . date('Y-m-d-H-i-s') . '.php';
                
                if (file_exists($configFile)) {
                    copy($configFile, $backupFile);
                    echo "<span style='color: green;'>✅ Created backup: " . basename($backupFile) . "</span><br>";
                }
                
                // Read current config
                $configContent = file_get_contents($configFile);
                
                // Update environment
                $configContent = preg_replace(
                    "/'TRUELAYER_ENVIRONMENT'\s*=>\s*'[^']*'/",
                    "'TRUELAYER_ENVIRONMENT' => 'live'",
                    $configContent
                );
                
                // Update client ID
                $configContent = preg_replace(
                    "/'TRUELAYER_CLIENT_ID'\s*=>\s*'[^']*'/",
                    "'TRUELAYER_CLIENT_ID' => '{$liveClientId}'",
                    $configContent
                );
                
                // Update client secret
                $configContent = preg_replace(
                    "/'TRUELAYER_CLIENT_SECRET'\s*=>\s*'[^']*'/",
                    "'TRUELAYER_CLIENT_SECRET' => '{$liveClientSecret}'",
                    $configContent
                );
                
                // Write updated config
                file_put_contents($configFile, $configContent);
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>✅ Successfully Switched to Live Environment!</strong><br>";
                echo "• Environment: sandbox → live<br>";
                echo "• Client ID: Updated to live credentials<br>";
                echo "• Client Secret: Updated to live credentials<br>";
                echo "• Backup created: " . basename($backupFile);
                echo "</div>";
                
                echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<strong>🎯 Next Steps:</strong><br>";
                echo "1. Test bank connection with real bank account<br>";
                echo "2. Verify subscription detection works with real data<br>";
                echo "3. Monitor for any integration issues<br>";
                echo "4. Keep backup file in case you need to revert";
                echo "</div>";
                break;
                
            case 'revert_to_sandbox':
                echo "<h2>🔄 Reverting to Sandbox Environment</h2>";
                
                $configFile = __DIR__ . '/secure-config.php';
                $configContent = file_get_contents($configFile);
                
                // Update environment back to sandbox
                $configContent = preg_replace(
                    "/'TRUELAYER_ENVIRONMENT'\s*=>\s*'[^']*'/",
                    "'TRUELAYER_ENVIRONMENT' => 'sandbox'",
                    $configContent
                );
                
                file_put_contents($configFile, $configContent);
                
                echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<strong>✅ Reverted to Sandbox Environment</strong><br>";
                echo "You can now test with sandbox credentials again.";
                echo "</div>";
                break;
        }
        
        // Refresh page to show updated status
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>📋 Important Notes</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>🔑 Live vs Sandbox Differences:</h3>";
echo "<ul>";
echo "<li><strong>Sandbox:</strong> Test environment with mock bank data</li>";
echo "<li><strong>Live:</strong> Real bank connections with actual user data</li>";
echo "<li><strong>Client ID:</strong> Live credentials don't have 'sandbox-' prefix</li>";
echo "<li><strong>URLs:</strong> Live uses auth.truelayer.com (not truelayer-sandbox.com)</li>";
echo "<li><strong>Data:</strong> Live environment will show real transactions and subscriptions</li>";
echo "</ul>";

echo "<h3>⚠️ Security Considerations:</h3>";
echo "<ul>";
echo "<li>Live credentials provide access to real financial data</li>";
echo "<li>Ensure secure storage and transmission of tokens</li>";
echo "<li>Monitor API usage and rate limits</li>";
echo "<li>Keep backup of working sandbox configuration</li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='bank/scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Bank Scan</a>";
echo "<a href='debug-truelayer.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Debug TrueLayer</a>";
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
input, button {
    font-family: inherit;
}
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
</style>
