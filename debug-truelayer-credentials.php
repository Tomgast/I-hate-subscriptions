<?php
/**
 * TRUELAYER CREDENTIALS DEBUG TOOL
 * Comprehensive debugging for TrueLayer credential loading issues
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>TrueLayer Credentials Debug Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

// Test 1: Global getSecureConfig function
echo "<h2>🔧 Global getSecureConfig Function Test</h2>";

try {
    require_once 'config/secure_loader.php';
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
    echo "<strong>Global getSecureConfig Results:</strong><br>";
    
    $globalClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
    $globalClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
    $globalEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT');
    $globalRedirectUri = getSecureConfig('TRUELAYER_REDIRECT_URI');
    
    echo "<span style='color: " . ($globalClientId ? 'green' : 'red') . ";'>TRUELAYER_CLIENT_ID:</span> " . ($globalClientId ? $globalClientId : 'NOT FOUND') . "<br>";
    echo "<span style='color: " . ($globalClientSecret ? 'green' : 'red') . ";'>TRUELAYER_CLIENT_SECRET:</span> " . ($globalClientSecret ? substr($globalClientSecret, 0, 10) . '...' : 'NOT FOUND') . "<br>";
    echo "<span style='color: " . ($globalEnvironment ? 'green' : 'red') . ";'>TRUELAYER_ENVIRONMENT:</span> " . ($globalEnvironment ?: 'NOT FOUND (defaulting to sandbox)') . "<br>";
    echo "<span style='color: " . ($globalRedirectUri ? 'green' : 'red') . ";'>TRUELAYER_REDIRECT_URI:</span> " . ($globalRedirectUri ?: 'NOT FOUND') . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Global Config Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 2: BankService credential loading
echo "<h2>🏦 BankService Credential Loading Test</h2>";

try {
    require_once 'includes/bank_service.php';
    
    // Create BankService instance
    $bankService = new BankService();
    
    // Use reflection to access private properties
    $reflection = new ReflectionClass($bankService);
    
    $clientIdProperty = $reflection->getProperty('trueLayerClientId');
    $clientIdProperty->setAccessible(true);
    $bankServiceClientId = $clientIdProperty->getValue($bankService);
    
    $clientSecretProperty = $reflection->getProperty('trueLayerClientSecret');
    $clientSecretProperty->setAccessible(true);
    $bankServiceClientSecret = $clientSecretProperty->getValue($bankService);
    
    $environmentProperty = $reflection->getProperty('trueLayerEnvironment');
    $environmentProperty->setAccessible(true);
    $bankServiceEnvironment = $environmentProperty->getValue($bankService);
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; font-family: monospace;'>";
    echo "<strong>BankService Loaded Credentials:</strong><br>";
    echo "<span style='color: " . ($bankServiceClientId ? 'green' : 'red') . ";'>Client ID:</span> " . ($bankServiceClientId ?: 'NOT LOADED') . "<br>";
    echo "<span style='color: " . ($bankServiceClientSecret ? 'green' : 'red') . ";'>Client Secret:</span> " . ($bankServiceClientSecret ? substr($bankServiceClientSecret, 0, 10) . '...' : 'NOT LOADED') . "<br>";
    echo "<span style='color: " . ($bankServiceEnvironment ? 'green' : 'red') . ";'>Environment:</span> " . ($bankServiceEnvironment ?: 'NOT LOADED') . "<br>";
    echo "</div>";
    
    // Compare global vs BankService credentials
    echo "<h3>🔍 Credential Comparison</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    
    if ($globalClientId === $bankServiceClientId) {
        echo "<span style='color: green;'>✅ Client ID matches between global and BankService</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Client ID mismatch!</span><br>";
        echo "Global: " . ($globalClientId ?: 'NULL') . "<br>";
        echo "BankService: " . ($bankServiceClientId ?: 'NULL') . "<br>";
    }
    
    if ($globalClientSecret === $bankServiceClientSecret) {
        echo "<span style='color: green;'>✅ Client Secret matches between global and BankService</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Client Secret mismatch!</span><br>";
    }
    
    if ($globalEnvironment === $bankServiceEnvironment) {
        echo "<span style='color: green;'>✅ Environment matches between global and BankService</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Environment mismatch!</span><br>";
        echo "Global: " . ($globalEnvironment ?: 'NULL') . "<br>";
        echo "BankService: " . ($bankServiceEnvironment ?: 'NULL') . "<br>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>BankService Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 3: TrueLayer Client ID Format Validation
echo "<h2>🔍 TrueLayer Client ID Format Validation</h2>";

if (isset($globalClientId) && $globalClientId) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Client ID Analysis:</strong><br>";
    echo "<strong>Value:</strong> {$globalClientId}<br>";
    echo "<strong>Length:</strong> " . strlen($globalClientId) . " characters<br>";
    echo "<strong>Format:</strong> ";
    
    // Check TrueLayer client ID format
    if (preg_match('/^[a-zA-Z0-9\-_]+$/', $globalClientId)) {
        echo "<span style='color: green;'>✅ Valid format (alphanumeric with hyphens/underscores)</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Invalid format (contains invalid characters)</span><br>";
    }
    
    // Check if it looks like a TrueLayer sandbox client ID
    if (strpos($globalClientId, 'sandbox-') === 0) {
        echo "<strong>Type:</strong> <span style='color: green;'>✅ Sandbox Client ID</span><br>";
    } elseif (strlen($globalClientId) > 20 && !strpos($globalClientId, 'sandbox-')) {
        echo "<strong>Type:</strong> <span style='color: blue;'>ℹ️ Likely Live Client ID</span><br>";
    } else {
        echo "<strong>Type:</strong> <span style='color: orange;'>⚠️ Unknown format</span><br>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>❌ No Client ID found to validate</strong>";
    echo "</div>";
}

// Test 4: Direct TrueLayer API Test with Current Credentials
echo "<h2>🌐 Direct TrueLayer API Test</h2>";

if (isset($globalClientId) && isset($globalClientSecret) && $globalClientId && $globalClientSecret) {
    echo "<p>Testing direct API call with current credentials...</p>";
    
    $testUrl = 'https://auth.truelayer-sandbox.com/connect/token';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $globalClientId,
        'client_secret' => $globalClientSecret,
        'scope' => 'info accounts balance'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<strong>API Test Results:</strong><br>";
    echo "<strong>HTTP Code:</strong> {$httpCode}<br>";
    
    if ($curlError) {
        echo "<span style='color: red;'><strong>cURL Error:</strong> {$curlError}</span><br>";
    }
    
    if ($response) {
        $responseData = json_decode($response, true);
        if ($responseData) {
            if (isset($responseData['access_token'])) {
                echo "<span style='color: green;'>✅ API Test Successful - Valid Credentials!</span><br>";
            } elseif (isset($responseData['error'])) {
                echo "<span style='color: red;'>❌ API Error: " . $responseData['error'] . "</span><br>";
                if (isset($responseData['error_description'])) {
                    echo "<span style='color: red;'>Description: " . $responseData['error_description'] . "</span><br>";
                }
                
                // Specific error analysis
                if ($responseData['error'] === 'invalid_client') {
                    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
                    echo "<strong>⚠️ Invalid Client Error Analysis:</strong><br>";
                    echo "• Client ID format may be incorrect<br>";
                    echo "• Client ID may not be registered in TrueLayer Console<br>";
                    echo "• Environment mismatch (using sandbox credentials with live endpoint or vice versa)<br>";
                    echo "• Credentials may be expired or deactivated<br>";
                    echo "</div>";
                }
            }
            
            echo "<br><strong>Full Response:</strong><br>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px;'>";
            echo json_encode($responseData, JSON_PRETTY_PRINT);
            echo "</pre>";
        } else {
            echo "<span style='color: red;'>❌ Invalid JSON response</span><br>";
            echo "<strong>Raw Response:</strong> " . htmlspecialchars($response);
        }
    } else {
        echo "<span style='color: red;'>❌ No response from API</span>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
    echo "⚠️ Cannot test API - missing credentials";
    echo "</div>";
}

// Test 5: Authorization URL Generation Test
echo "<h2>🔗 Authorization URL Generation Test</h2>";

if (isset($bankService)) {
    try {
        $testAuthUrl = $bankService->generateAuthUrl($userId);
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ Authorization URL Generated Successfully:</strong><br>";
        echo "<div style='word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo $testAuthUrl;
        echo "</div>";
        
        // Parse URL to check parameters
        $parsedUrl = parse_url($testAuthUrl);
        parse_str($parsedUrl['query'] ?? '', $params);
        
        echo "<strong>URL Parameters:</strong><br>";
        echo "<span style='color: " . (isset($params['client_id']) ? 'green' : 'red') . ";'>client_id:</span> " . ($params['client_id'] ?? 'MISSING') . "<br>";
        echo "<span style='color: " . (isset($params['response_type']) ? 'green' : 'red') . ";'>response_type:</span> " . ($params['response_type'] ?? 'MISSING') . "<br>";
        echo "<span style='color: " . (isset($params['scope']) ? 'green' : 'red') . ";'>scope:</span> " . ($params['scope'] ?? 'MISSING') . "<br>";
        echo "<span style='color: " . (isset($params['redirect_uri']) ? 'green' : 'red') . ";'>redirect_uri:</span> " . ($params['redirect_uri'] ?? 'MISSING') . "<br>";
        echo "<span style='color: " . (isset($params['state']) ? 'green' : 'red') . ";'>state:</span> " . (isset($params['state']) ? 'PRESENT' : 'MISSING') . "<br>";
        
        echo "<br><button onclick=\"window.open('{$testAuthUrl}', '_blank')\" style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test This Authorization URL</button>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Authorization URL Generation Failed:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🔧 Recommended Actions</h2>";
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<strong>Based on the test results above:</strong><br><br>";
echo "1. <strong>If credentials are missing:</strong> Check your secure-config.php file<br>";
echo "2. <strong>If 'invalid_client' error:</strong> Verify Client ID in TrueLayer Console<br>";
echo "3. <strong>If environment mismatch:</strong> Ensure sandbox credentials for sandbox environment<br>";
echo "4. <strong>If authorization URL fails:</strong> Check redirect URI registration<br>";
echo "</div>";

echo "<hr>";
echo "<h2>🧪 Test Links</h2>";
echo "<p>";
echo "<a href='bank/scan.php' style='color: #007bff; margin-right: 15px;'>Bank Scan Page</a>";
echo "<a href='debug-truelayer.php' style='color: #007bff; margin-right: 15px;'>TrueLayer Debug</a>";
echo "<a href='align-plan-database.php' style='color: #007bff; margin-right: 15px;'>Plan Alignment</a>";
echo "</p>";
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
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 14px;
}
code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
