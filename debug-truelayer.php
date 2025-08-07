<?php
/**
 * TrueLayer Configuration Debug Script
 * Shows exactly what credentials and environment are being used
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load secure configuration
require_once __DIR__ . '/config/secure_loader.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueLayer Debug - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 TrueLayer Configuration Debug</h1>
            <p class="text-gray-600 mb-8">Let's see exactly what TrueLayer credentials and environment are being used</p>
            
            <?php
            try {
                echo "<div class='bg-blue-50 border border-blue-200 text-blue-700 px-6 py-4 rounded-lg mb-6'>";
                echo "<h3 class='font-bold mb-2'>📋 TrueLayer Configuration Status</h3>";
                echo "</div>";
                
                // Get TrueLayer configuration
                $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
                $trueLayerClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
                $trueLayerEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT') ?: 'sandbox';
                $trueLayerRedirectUri = getSecureConfig('TRUELAYER_REDIRECT_URI') ?: 'https://123cashcontrol.com/bank/callback.php';
                
                echo "<div class='space-y-4'>";
                
                // Environment
                echo "<div class='bg-gray-50 p-4 rounded-lg'>";
                echo "<h4 class='font-bold text-gray-900'>Environment:</h4>";
                echo "<p class='text-lg font-mono'>" . htmlspecialchars($trueLayerEnvironment) . "</p>";
                if ($trueLayerEnvironment === 'sandbox') {
                    echo "<p class='text-sm text-orange-600'>⚠️ Using SANDBOX environment</p>";
                } else {
                    echo "<p class='text-sm text-green-600'>✅ Using LIVE environment</p>";
                }
                echo "</div>";
                
                // Client ID
                echo "<div class='bg-gray-50 p-4 rounded-lg'>";
                echo "<h4 class='font-bold text-gray-900'>Client ID:</h4>";
                if ($trueLayerClientId) {
                    echo "<p class='text-sm font-mono'>" . htmlspecialchars(substr($trueLayerClientId, 0, 8)) . "..." . htmlspecialchars(substr($trueLayerClientId, -4)) . "</p>";
                    echo "<p class='text-sm text-green-600'>✅ Client ID found</p>";
                } else {
                    echo "<p class='text-sm text-red-600'>❌ Client ID missing</p>";
                }
                echo "</div>";
                
                // Client Secret
                echo "<div class='bg-gray-50 p-4 rounded-lg'>";
                echo "<h4 class='font-bold text-gray-900'>Client Secret:</h4>";
                if ($trueLayerClientSecret) {
                    echo "<p class='text-sm font-mono'>" . htmlspecialchars(substr($trueLayerClientSecret, 0, 8)) . "..." . htmlspecialchars(substr($trueLayerClientSecret, -4)) . "</p>";
                    echo "<p class='text-sm text-green-600'>✅ Client Secret found</p>";
                } else {
                    echo "<p class='text-sm text-red-600'>❌ Client Secret missing</p>";
                }
                echo "</div>";
                
                // Redirect URI
                echo "<div class='bg-gray-50 p-4 rounded-lg'>";
                echo "<h4 class='font-bold text-gray-900'>Redirect URI:</h4>";
                echo "<p class='text-sm font-mono'>" . htmlspecialchars($trueLayerRedirectUri) . "</p>";
                echo "</div>";
                
                // Generate test authorization URL
                echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-lg mt-6'>";
                echo "<h3 class='font-bold mb-2'>🔗 Generated Authorization URL</h3>";
                
                if ($trueLayerClientId && $trueLayerClientSecret) {
                    $state = base64_encode(json_encode(['user_id' => 999, 'timestamp' => time(), 'test' => true]));
                    
                    $params = [
                        'response_type' => 'code',
                        'client_id' => $trueLayerClientId,
                        'scope' => 'info accounts balance transactions offline_access',
                        'redirect_uri' => $trueLayerRedirectUri,
                        'state' => $state,
                        'providers' => 'uk-ob-all uk-oauth-all'
                    ];
                    
                    $baseUrl = $trueLayerEnvironment === 'live' 
                        ? 'https://auth.truelayer.com' 
                        : 'https://auth.truelayer-sandbox.com';
                        
                    $authUrl = $baseUrl . '?' . http_build_query($params);
                    
                    echo "<p class='text-sm mb-4'>This is the URL that would be generated for TrueLayer authorization:</p>";
                    echo "<div class='bg-white p-3 rounded border text-xs font-mono break-all'>";
                    echo htmlspecialchars($authUrl);
                    echo "</div>";
                    
                    echo "<div class='mt-4'>";
                    echo "<a href='" . htmlspecialchars($authUrl) . "' target='_blank' class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>Test This URL</a>";
                    echo "<p class='text-xs mt-2'>⚠️ This will attempt to redirect to TrueLayer. If you get 'invalid redirect_uri', the issue is in your TrueLayer console configuration.</p>";
                    echo "</div>";
                } else {
                    echo "<p class='text-red-600'>❌ Cannot generate URL - missing credentials</p>";
                }
                echo "</div>";
                
                // Environment-specific guidance
                echo "<div class='bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mt-6'>";
                echo "<h3 class='font-bold mb-2'>🎯 Environment-Specific Setup</h3>";
                
                if ($trueLayerEnvironment === 'sandbox') {
                    echo "<p class='mb-2'><strong>SANDBOX Environment:</strong></p>";
                    echo "<ul class='list-disc list-inside space-y-1 text-sm'>";
                    echo "<li>TrueLayer Console: <a href='https://console.truelayer-sandbox.com' target='_blank' class='underline'>https://console.truelayer-sandbox.com</a></li>";
                    echo "<li>Add redirect URI: <code>https://123cashcontrol.com/bank/callback.php</code></li>";
                    echo "<li>Use your SANDBOX client ID and secret</li>";
                    echo "<li>Test with sandbox banks (not real banks)</li>";
                    echo "</ul>";
                } else {
                    echo "<p class='mb-2'><strong>LIVE Environment:</strong></p>";
                    echo "<ul class='list-disc list-inside space-y-1 text-sm'>";
                    echo "<li>TrueLayer Console: <a href='https://console.truelayer.com' target='_blank' class='underline'>https://console.truelayer.com</a></li>";
                    echo "<li>Add redirect URI: <code>https://123cashcontrol.com/bank/callback.php</code></li>";
                    echo "<li>Use your LIVE client ID and secret</li>";
                    echo "<li>App must be approved for production</li>";
                    echo "</ul>";
                }
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg'>";
                echo "<h3 class='font-bold'>❌ Error</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
