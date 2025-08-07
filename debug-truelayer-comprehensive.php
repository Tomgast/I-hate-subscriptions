<?php
/**
 * Comprehensive TrueLayer Debug Script
 * Deep dive into every aspect of TrueLayer configuration and URL generation
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate logged in user for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'support@origens.nl';

require_once __DIR__ . '/config/secure_loader.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueLayer Deep Debug - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 TrueLayer Deep Debug Analysis</h1>
            <p class="text-gray-600 mb-8">Comprehensive analysis of TrueLayer configuration, URL generation, and potential issues</p>
            
            <?php
            try {
                echo "<div class='space-y-6'>";
                
                // ===== SECTION 1: CONFIGURATION ANALYSIS =====
                echo "<div class='bg-blue-50 border border-blue-200 p-6 rounded-lg'>";
                echo "<h2 class='text-xl font-bold text-blue-900 mb-4'>📋 1. Configuration Analysis</h2>";
                
                // Get all TrueLayer configuration
                $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
                $trueLayerClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
                $trueLayerEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT') ?: 'sandbox';
                $trueLayerRedirectUri = getSecureConfig('TRUELAYER_REDIRECT_URI');
                
                echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
                
                // Environment
                echo "<div class='bg-white p-4 rounded border'>";
                echo "<h3 class='font-bold text-gray-900'>Environment</h3>";
                echo "<p class='font-mono text-lg'>" . htmlspecialchars($trueLayerEnvironment) . "</p>";
                if ($trueLayerEnvironment === 'sandbox') {
                    echo "<p class='text-sm text-orange-600'>⚠️ SANDBOX mode</p>";
                    echo "<p class='text-xs text-gray-600'>Console: https://console.truelayer-sandbox.com</p>";
                } else {
                    echo "<p class='text-sm text-green-600'>🔴 LIVE mode</p>";
                    echo "<p class='text-xs text-gray-600'>Console: https://console.truelayer.com</p>";
                }
                echo "</div>";
                
                // Client ID
                echo "<div class='bg-white p-4 rounded border'>";
                echo "<h3 class='font-bold text-gray-900'>Client ID</h3>";
                if ($trueLayerClientId) {
                    echo "<p class='font-mono text-sm'>" . htmlspecialchars($trueLayerClientId) . "</p>";
                    echo "<p class='text-sm text-green-600'>✅ Present</p>";
                } else {
                    echo "<p class='text-sm text-red-600'>❌ Missing</p>";
                }
                echo "</div>";
                
                // Client Secret
                echo "<div class='bg-white p-4 rounded border'>";
                echo "<h3 class='font-bold text-gray-900'>Client Secret</h3>";
                if ($trueLayerClientSecret) {
                    echo "<p class='font-mono text-sm'>" . htmlspecialchars(substr($trueLayerClientSecret, 0, 12)) . "..." . htmlspecialchars(substr($trueLayerClientSecret, -4)) . "</p>";
                    echo "<p class='text-sm text-green-600'>✅ Present</p>";
                } else {
                    echo "<p class='text-sm text-red-600'>❌ Missing</p>";
                }
                echo "</div>";
                
                // Redirect URI
                echo "<div class='bg-white p-4 rounded border'>";
                echo "<h3 class='font-bold text-gray-900'>Redirect URI</h3>";
                if ($trueLayerRedirectUri) {
                    echo "<p class='font-mono text-sm break-all'>" . htmlspecialchars($trueLayerRedirectUri) . "</p>";
                    if ($trueLayerRedirectUri === 'https://123cashcontrol.com/bank/callback.php') {
                        echo "<p class='text-sm text-green-600'>✅ Correct format</p>";
                    } else {
                        echo "<p class='text-sm text-red-600'>❌ Incorrect format</p>";
                        echo "<p class='text-xs text-gray-600'>Should be: https://123cashcontrol.com/bank/callback.php</p>";
                    }
                } else {
                    echo "<p class='text-sm text-red-600'>❌ Missing</p>";
                }
                echo "</div>";
                
                echo "</div>";
                echo "</div>";
                
                // ===== SECTION 2: URL GENERATION TEST =====
                echo "<div class='bg-green-50 border border-green-200 p-6 rounded-lg'>";
                echo "<h2 class='text-xl font-bold text-green-900 mb-4'>🔗 2. URL Generation Test</h2>";
                
                if ($trueLayerClientId && $trueLayerClientSecret) {
                    // Manual URL generation (exactly like the code does)
                    $userId = $_SESSION['user_id'];
                    $redirectUri = $trueLayerRedirectUri ?: 'https://123cashcontrol.com/bank/callback.php';
                    $state = base64_encode(json_encode(['user_id' => $userId, 'timestamp' => time()]));
                    
                    $params = [
                        'response_type' => 'code',
                        'client_id' => $trueLayerClientId,
                        'scope' => 'info accounts balance transactions offline_access',
                        'redirect_uri' => $redirectUri,
                        'state' => $state,
                        'providers' => 'uk-ob-all uk-oauth-all'
                    ];
                    
                    $baseUrl = $trueLayerEnvironment === 'live' 
                        ? 'https://auth.truelayer.com' 
                        : 'https://auth.truelayer-sandbox.com';
                        
                    $authUrl = $baseUrl . '?' . http_build_query($params);
                    
                    echo "<div class='bg-white p-4 rounded border mb-4'>";
                    echo "<h3 class='font-bold text-gray-900 mb-2'>Generated Authorization URL:</h3>";
                    echo "<div class='bg-gray-100 p-3 rounded text-xs font-mono break-all'>";
                    echo htmlspecialchars($authUrl);
                    echo "</div>";
                    echo "</div>";
                    
                    // Break down the URL parameters
                    echo "<div class='bg-white p-4 rounded border mb-4'>";
                    echo "<h3 class='font-bold text-gray-900 mb-2'>URL Parameters Breakdown:</h3>";
                    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-2 text-sm'>";
                    foreach ($params as $key => $value) {
                        echo "<div class='flex'>";
                        echo "<span class='font-semibold w-32'>$key:</span>";
                        if ($key === 'state') {
                            $stateData = json_decode(base64_decode($value), true);
                            echo "<span class='font-mono'>" . htmlspecialchars(json_encode($stateData)) . "</span>";
                        } else {
                            echo "<span class='font-mono'>" . htmlspecialchars($value) . "</span>";
                        }
                        echo "</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    
                    // Test the URL
                    echo "<div class='bg-yellow-50 border border-yellow-200 p-4 rounded'>";
                    echo "<h3 class='font-bold text-yellow-900 mb-2'>🧪 URL Test</h3>";
                    echo "<p class='text-sm mb-3'>Click this button to test the generated URL:</p>";
                    echo "<a href='" . htmlspecialchars($authUrl) . "' target='_blank' class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mr-3'>Test Authorization URL</a>";
                    echo "<p class='text-xs text-gray-600 mt-2'>If you get 'invalid redirect_uri', the issue is in your TrueLayer console configuration.</p>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='bg-red-50 border border-red-200 p-4 rounded'>";
                    echo "<p class='text-red-600'>❌ Cannot generate URL - missing credentials</p>";
                    echo "</div>";
                }
                echo "</div>";
                
                // ===== SECTION 3: CALLBACK FILE TEST =====
                echo "<div class='bg-purple-50 border border-purple-200 p-6 rounded-lg'>";
                echo "<h2 class='text-xl font-bold text-purple-900 mb-4'>📞 3. Callback File Test</h2>";
                
                $callbackPath = __DIR__ . '/bank/callback.php';
                if (file_exists($callbackPath)) {
                    echo "<p class='text-green-600 mb-2'>✅ Callback file exists: /bank/callback.php</p>";
                    echo "<p class='text-sm text-gray-600 mb-4'>File size: " . filesize($callbackPath) . " bytes</p>";
                    
                    // Test callback URL accessibility
                    echo "<div class='bg-white p-4 rounded border'>";
                    echo "<h3 class='font-bold text-gray-900 mb-2'>Callback URL Test:</h3>";
                    echo "<p class='text-sm mb-2'>Your callback URL: <span class='font-mono'>https://123cashcontrol.com/bank/callback.php</span></p>";
                    echo "<a href='https://123cashcontrol.com/bank/callback.php' target='_blank' class='bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700'>Test Callback URL</a>";
                    echo "<p class='text-xs text-gray-600 mt-2'>Should show 'no authorization code received' (this is correct behavior)</p>";
                    echo "</div>";
                } else {
                    echo "<p class='text-red-600'>❌ Callback file missing: /bank/callback.php</p>";
                }
                echo "</div>";
                
                // ===== SECTION 4: TRUELAYER CONSOLE CHECKLIST =====
                echo "<div class='bg-red-50 border border-red-200 p-6 rounded-lg'>";
                echo "<h2 class='text-xl font-bold text-red-900 mb-4'>🎯 4. TrueLayer Console Checklist</h2>";
                
                $consoleUrl = $trueLayerEnvironment === 'sandbox' 
                    ? 'https://console.truelayer-sandbox.com' 
                    : 'https://console.truelayer.com';
                    
                echo "<div class='bg-white p-4 rounded border mb-4'>";
                echo "<h3 class='font-bold text-gray-900 mb-2'>Console URL:</h3>";
                echo "<a href='$consoleUrl' target='_blank' class='text-blue-600 underline'>$consoleUrl</a>";
                echo "</div>";
                
                echo "<div class='bg-white p-4 rounded border'>";
                echo "<h3 class='font-bold text-gray-900 mb-2'>Required Settings in Console:</h3>";
                echo "<div class='space-y-2 text-sm'>";
                echo "<div class='flex items-center'>";
                echo "<span class='w-4 h-4 border-2 border-gray-400 rounded mr-2'></span>";
                echo "<span>App/Client ID: <span class='font-mono'>" . htmlspecialchars($trueLayerClientId) . "</span></span>";
                echo "</div>";
                echo "<div class='flex items-center'>";
                echo "<span class='w-4 h-4 border-2 border-gray-400 rounded mr-2'></span>";
                echo "<span>Redirect URI: <span class='font-mono'>https://123cashcontrol.com/bank/callback.php</span></span>";
                echo "</div>";
                echo "<div class='flex items-center'>";
                echo "<span class='w-4 h-4 border-2 border-gray-400 rounded mr-2'></span>";
                echo "<span>Environment: <span class='font-mono'>" . strtoupper($trueLayerEnvironment) . "</span></span>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                
                // ===== SECTION 5: DIAGNOSTIC RECOMMENDATIONS =====
                echo "<div class='bg-gray-50 border border-gray-200 p-6 rounded-lg'>";
                echo "<h2 class='text-xl font-bold text-gray-900 mb-4'>🔧 5. Diagnostic Recommendations</h2>";
                
                echo "<div class='space-y-3 text-sm'>";
                
                if ($trueLayerEnvironment === 'sandbox') {
                    echo "<div class='bg-orange-100 p-3 rounded'>";
                    echo "<h4 class='font-bold text-orange-900'>Sandbox Environment Issues:</h4>";
                    echo "<ul class='list-disc list-inside mt-2 space-y-1'>";
                    echo "<li>Make sure you're logged into the SANDBOX console, not the live console</li>";
                    echo "<li>Sandbox and live consoles are completely separate</li>";
                    echo "<li>Your client ID should start with 'sandbox-' for sandbox apps</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                
                echo "<div class='bg-blue-100 p-3 rounded'>";
                echo "<h4 class='font-bold text-blue-900'>Common Issues:</h4>";
                echo "<ul class='list-disc list-inside mt-2 space-y-1'>";
                echo "<li>Redirect URI must be EXACTLY: https://123cashcontrol.com/bank/callback.php</li>";
                echo "<li>No trailing slashes, no extra parameters</li>";
                echo "<li>HTTPS is required (not HTTP)</li>";
                echo "<li>Case sensitive - must match exactly</li>";
                echo "<li>Console changes can take 5-10 minutes to propagate</li>";
                echo "</ul>";
                echo "</div>";
                
                echo "<div class='bg-green-100 p-3 rounded'>";
                echo "<h4 class='font-bold text-green-900'>Next Steps:</h4>";
                echo "<ol class='list-decimal list-inside mt-2 space-y-1'>";
                echo "<li>Double-check your TrueLayer console has the exact redirect URI</li>";
                echo "<li>Wait 10 minutes after making console changes</li>";
                echo "<li>Test the authorization URL above</li>";
                echo "<li>If still failing, try removing and re-adding the redirect URI</li>";
                echo "<li>Check if you're using the correct console (sandbox vs live)</li>";
                echo "</ol>";
                echo "</div>";
                
                echo "</div>";
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg'>";
                echo "<h3 class='font-bold'>❌ Fatal Error</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex space-x-4">
                    <a href="test-bank-scan.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Simple Bank Test
                    </a>
                    <a href="bank/scan.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Go to Bank Scan
                    </a>
                    <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
