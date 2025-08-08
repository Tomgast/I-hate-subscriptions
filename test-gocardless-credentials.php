<?php
/**
 * GOCARDLESS CREDENTIALS TEST
 * Simple test to verify GoCardless API credentials and connection
 */

session_start();
require_once 'config/db_config.php';

echo "<h1>🔑 GoCardless Credentials Test</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Test GoCardless API credentials and authentication to identify any configuration issues.";
echo "</div>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📋 Step 1: Load Secure Configuration</h2>";

try {
    // Load secure config the same way as the service
    $configPath = dirname(__DIR__) . '/../secure-config.php';
    if (file_exists($configPath)) {
        $secureConfig = include $configPath;
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<strong>✅ Secure Config Loaded</strong><br>";
        echo "Config file found at: " . $configPath;
        echo "</div>";
    } else {
        throw new Exception('Secure config file not found at: ' . $configPath);
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Config Loading Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 2: Check GoCardless Credentials</h2>";

$secretId = $secureConfig['GOCARDLESS_SECRET_ID'] ?? '';
$secretKey = $secureConfig['GOCARDLESS_SECRET_KEY'] ?? '';

if (empty($secretId) || empty($secretKey)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ GoCardless Credentials Missing</strong><br>";
    echo "Secret ID: " . (empty($secretId) ? 'NOT SET' : 'SET (length: ' . strlen($secretId) . ')') . "<br>";
    echo "Secret Key: " . (empty($secretKey) ? 'NOT SET' : 'SET (length: ' . strlen($secretKey) . ')') . "<br>";
    echo "<br><strong>Action Required:</strong> Add your GoCardless credentials to secure-config.php";
    echo "</div>";
    exit;
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ GoCardless Credentials Found</strong><br>";
    echo "Secret ID: SET (length: " . strlen($secretId) . ")<br>";
    echo "Secret Key: SET (length: " . strlen($secretKey) . ")<br>";
    echo "First 8 chars of Secret ID: " . substr($secretId, 0, 8) . "...";
    echo "</div>";
}

echo "<h2>📋 Step 3: Test GoCardless API Authentication</h2>";

try {
    $apiBaseUrl = 'https://bankaccountdata.gocardless.com/api/v2/';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiBaseUrl . 'token/new/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'secret_id' => $secretId,
            'secret_key' => $secretKey
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 10px 0;'>";
    echo "<strong>📊 API Request Details:</strong><br>";
    echo "URL: " . $apiBaseUrl . "token/new/<br>";
    echo "HTTP Code: " . $httpCode . "<br>";
    echo "Response Length: " . strlen($response) . " characters<br>";
    if ($curlError) {
        echo "cURL Error: " . $curlError . "<br>";
    }
    echo "</div>";
    
    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }
    
    if ($httpCode === 200 || $httpCode === 201) {
        $data = json_decode($response, true);
        if (isset($data['access'])) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<strong>✅ GoCardless Authentication Successful</strong><br>";
            echo "Access token received (length: " . strlen($data['access']) . ")<br>";
            echo "Token expires in: " . ($data['access_expires'] ?? 'Unknown') . " seconds<br>";
            echo "First 20 chars of token: " . substr($data['access'], 0, 20) . "...";
            echo "</div>";
            
            $accessToken = $data['access'];
        } else {
            throw new Exception('No access token in response: ' . json_encode($data));
        }
    } else {
        $errorData = json_decode($response, true);
        $errorMsg = 'HTTP ' . $httpCode;
        
        if (isset($errorData['detail'])) {
            $errorMsg .= ': ' . $errorData['detail'];
        } elseif (isset($errorData['error'])) {
            $errorMsg .= ': ' . $errorData['error'];
        } else {
            $errorMsg .= ': ' . $response;
        }
        
        throw new Exception($errorMsg);
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ GoCardless Authentication Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 15px 0;'>";
    echo "<h4>🔧 Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Check Credentials:</strong> Verify your GoCardless Secret ID and Secret Key are correct</li>";
    echo "<li><strong>Account Status:</strong> Ensure your GoCardless account is active and verified</li>";
    echo "<li><strong>API Access:</strong> Confirm you have API access enabled in your GoCardless dashboard</li>";
    echo "<li><strong>Environment:</strong> Make sure you're using production credentials for production environment</li>";
    echo "</ol>";
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 4: Test Institution Retrieval</h2>";

if (isset($accessToken)) {
    try {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiBaseUrl . 'institutions/?country=NL',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            throw new Exception('cURL Error: ' . $curlError);
        }
        
        if ($httpCode === 200) {
            $institutions = json_decode($response, true);
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<strong>✅ Institution Retrieval Successful</strong><br>";
            echo "Found " . count($institutions) . " Dutch banks:<br><br>";
            
            // Show first 5 banks
            $displayBanks = array_slice($institutions, 0, 5);
            foreach ($displayBanks as $bank) {
                echo "• " . ($bank['name'] ?? 'Unknown Bank') . " (ID: " . ($bank['id'] ?? 'N/A') . ")<br>";
            }
            
            if (count($institutions) > 5) {
                echo "... and " . (count($institutions) - 5) . " more banks";
            }
            echo "</div>";
            
        } else {
            $errorData = json_decode($response, true);
            throw new Exception('HTTP ' . $httpCode . ': ' . json_encode($errorData));
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<strong>❌ Institution Retrieval Failed:</strong><br>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>🎯 Summary</h2>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>GoCardless Integration Status</h3>";

if (isset($accessToken) && isset($institutions) && count($institutions) > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 10px 0;'>";
    echo "<strong>🎉 SUCCESS: GoCardless Integration Ready!</strong><br>";
    echo "✅ Credentials configured correctly<br>";
    echo "✅ API authentication working<br>";
    echo "✅ Institution data available<br>";
    echo "<br><strong>Next Step:</strong> Test the unified scan page with EU bank selection";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ ISSUES FOUND</strong><br>";
    echo "GoCardless integration has configuration or authentication issues.<br>";
    echo "Please review the error messages above and fix the identified problems.";
    echo "</div>";
}

echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; margin: 15px 0;'>";
echo "<strong>🔗 Next Steps:</strong><br>";
echo "<p><a href='test-unified-bank-integration.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🔄 Re-run Full Integration Test</a></p>";
echo "<p><a href='bank/unified-scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;'>🌍 Test Unified Scan Page</a></p>";
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
