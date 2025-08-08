<?php
/**
 * TRUELAYER SANDBOX DEBUG TOOL
 * Comprehensive debugging for TrueLayer integration issues
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/bank_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>TrueLayer Sandbox Debug Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

// Test 1: Configuration Check
echo "<h2>🔧 Configuration Check</h2>";

try {
    // Load secure configuration
    require_once 'config/secure_loader.php';
    
    $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
    $trueLayerClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
    $trueLayerEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT') ?: 'sandbox';
    $trueLayerRedirectUri = getSecureConfig('TRUELAYER_REDIRECT_URI');
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
    echo "<strong>TrueLayer Configuration:</strong><br>";
    echo "<span style='color: " . ($trueLayerClientId ? 'green' : 'red') . ";'>Client ID:</span> " . ($trueLayerClientId ? substr($trueLayerClientId, 0, 10) . '...' : 'NOT SET') . "<br>";
    echo "<span style='color: " . ($trueLayerClientSecret ? 'green' : 'red') . ";'>Client Secret:</span> " . ($trueLayerClientSecret ? substr($trueLayerClientSecret, 0, 10) . '...' : 'NOT SET') . "<br>";
    echo "<span style='color: " . ($trueLayerEnvironment ? 'green' : 'red') . ";'>Environment:</span> " . ($trueLayerEnvironment ?: 'NOT SET') . "<br>";
    echo "<span style='color: " . ($trueLayerRedirectUri ? 'green' : 'red') . ";'>Redirect URI:</span> " . ($trueLayerRedirectUri ?: 'NOT SET') . "<br>";
    echo "</div>";
    
    // Configuration issues
    $configIssues = [];
    if (!$trueLayerClientId) $configIssues[] = "Missing TRUELAYER_CLIENT_ID";
    if (!$trueLayerClientSecret) $configIssues[] = "Missing TRUELAYER_CLIENT_SECRET";
    if (!$trueLayerRedirectUri) $configIssues[] = "Missing TRUELAYER_REDIRECT_URI";
    if ($trueLayerEnvironment !== 'sandbox') $configIssues[] = "Environment should be 'sandbox' for testing";
    
    if (!empty($configIssues)) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin-top: 10px;'>";
        echo "<strong>❌ Configuration Issues:</strong><ul>";
        foreach ($configIssues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ul></div>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin-top: 10px;'>";
        echo "✅ Configuration looks good!";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Configuration Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 2: TrueLayer API Connectivity
echo "<h2>🌐 TrueLayer API Connectivity</h2>";

if (isset($trueLayerClientId) && isset($trueLayerClientSecret)) {
    // Test sandbox API endpoint
    $sandboxUrl = 'https://auth.truelayer-sandbox.com/connect/token';
    
    echo "<p>Testing connection to: <code>{$sandboxUrl}</code></p>";
    
    // Test basic connectivity
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sandboxUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $trueLayerClientId,
        'client_secret' => $trueLayerClientSecret,
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
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>API Test Results:</strong><br>";
    echo "<strong>HTTP Code:</strong> {$httpCode}<br>";
    
    if ($curlError) {
        echo "<span style='color: red;'><strong>cURL Error:</strong> {$curlError}</span><br>";
    }
    
    if ($response) {
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo "<strong>Response:</strong><br>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
            echo json_encode($responseData, JSON_PRETTY_PRINT);
            echo "</pre>";
            
            if (isset($responseData['access_token'])) {
                echo "<span style='color: green;'>✅ API connectivity working!</span>";
            } elseif (isset($responseData['error'])) {
                echo "<span style='color: red;'>❌ API Error: " . $responseData['error'] . "</span>";
                if (isset($responseData['error_description'])) {
                    echo "<br><span style='color: red;'>Description: " . $responseData['error_description'] . "</span>";
                }
            }
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

// Test 3: Authorization URL Generation
echo "<h2>🔗 Authorization URL Generation</h2>";

if (isset($trueLayerClientId) && isset($trueLayerRedirectUri)) {
    try {
        $bankService = new BankService();
        
        // Generate auth URL (if method exists)
        if (method_exists($bankService, 'generateAuthUrl')) {
            $authUrl = $bankService->generateAuthUrl($userId);
            
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
            echo "<strong>✅ Authorization URL Generated:</strong><br>";
            echo "<a href='{$authUrl}' target='_blank' style='word-break: break-all;'>{$authUrl}</a><br>";
            echo "<br><button onclick=\"window.open('{$authUrl}', '_blank')\" style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Authorization Flow</button>";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
            echo "⚠️ generateAuthUrl method not found in BankService";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Error generating auth URL:</strong> " . $e->getMessage();
        echo "</div>";
    }
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
    echo "⚠️ Cannot generate auth URL - missing Client ID or Redirect URI";
    echo "</div>";
}

// Test 4: Database Tables Check
echo "<h2>🗄️ Database Tables Check</h2>";

try {
    $pdo = getDBConnection();
    
    // Check if bank_scans table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bank_scans'");
    $bankScansExists = $stmt->rowCount() > 0;
    
    // Check if bank_connections table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bank_connections'");
    $bankConnectionsExists = $stmt->rowCount() > 0;
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Database Tables:</strong><br>";
    echo "<span style='color: " . ($bankScansExists ? 'green' : 'red') . ";'>bank_scans table:</span> " . ($bankScansExists ? 'EXISTS' : 'MISSING') . "<br>";
    echo "<span style='color: " . ($bankConnectionsExists ? 'green' : 'red') . ";'>bank_connections table:</span> " . ($bankConnectionsExists ? 'EXISTS' : 'MISSING') . "<br>";
    echo "</div>";
    
    if (!$bankScansExists || !$bankConnectionsExists) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin-top: 10px;'>";
        echo "<strong>⚠️ Missing Tables:</strong> Some database tables are missing. This may cause bank scan functionality to fail.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 5: VWO Issue Check
echo "<h2>🚫 VWO (Visual Website Optimizer) Issue</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<strong>About the CORS Error:</strong><br>";
echo "The error you saw (<code>Cross-Origin-aanvraag geblokkeerd</code>) is from <strong>VWO (Visual Website Optimizer)</strong>, not TrueLayer.<br><br>";
echo "<strong>VWO</strong> is an A/B testing and optimization service that appears to be running on your site.<br>";
echo "This CORS error is unrelated to your bank integration and can be safely ignored for TrueLayer testing.<br><br>";
echo "<strong>Solutions:</strong><br>";
echo "1. Disable VWO temporarily for testing<br>";
echo "2. Add proper CORS headers for VWO<br>";
echo "3. Ignore the error (it won't affect TrueLayer functionality)";
echo "</div>";

// Quick Fix Actions
echo "<h2>🔧 Quick Fix Actions</h2>";

// Action 1: Create missing tables
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Action 1: Create Missing Database Tables</h3>";
echo "<p>This will create the bank_scans and bank_connections tables if they don't exist.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='create_tables'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Tables</button>";
echo "</form>";
echo "</div>";

// Action 2: Test TrueLayer connection
echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Action 2: Test TrueLayer Connection</h3>";
echo "<p>This will attempt to connect to TrueLayer sandbox and get an access token.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='test_connection'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Connection</button>";
echo "</form>";
echo "</div>";

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        switch ($_POST['action']) {
            case 'create_tables':
                // Create bank_scans table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS bank_scans (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        scan_status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
                        scan_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        scan_completed_at TIMESTAMP NULL,
                        subscriptions_found INT DEFAULT 0,
                        total_amount DECIMAL(10,2) DEFAULT 0.00,
                        scan_data JSON NULL,
                        error_message TEXT NULL,
                        plan_type VARCHAR(20) NULL,
                        INDEX idx_user_id (user_id),
                        INDEX idx_scan_status (scan_status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Create bank_connections table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS bank_connections (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        provider VARCHAR(50) NOT NULL DEFAULT 'truelayer',
                        connection_id VARCHAR(255) NOT NULL,
                        access_token TEXT NULL,
                        refresh_token TEXT NULL,
                        token_expires_at TIMESTAMP NULL,
                        connection_status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_provider (user_id, provider),
                        INDEX idx_user_id (user_id),
                        INDEX idx_connection_status (connection_status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>✅ Database Tables Created!</strong><br>";
                echo "Both bank_scans and bank_connections tables have been created successfully.";
                echo "</div>";
                break;
                
            case 'test_connection':
                // Test TrueLayer connection
                $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
                $trueLayerClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
                
                if ($trueLayerClientId && $trueLayerClientSecret) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://auth.truelayer-sandbox.com/connect/token');
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'grant_type' => 'client_credentials',
                        'client_id' => $trueLayerClientId,
                        'client_secret' => $trueLayerClientSecret,
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
                    curl_close($ch);
                    
                    if ($httpCode === 200 && $response) {
                        $responseData = json_decode($response, true);
                        if (isset($responseData['access_token'])) {
                            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                            echo "<strong>✅ TrueLayer Connection Successful!</strong><br>";
                            echo "Successfully obtained access token from TrueLayer sandbox.";
                            echo "</div>";
                        } else {
                            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
                            echo "<strong>❌ TrueLayer Connection Failed!</strong><br>";
                            echo "Response: " . htmlspecialchars($response);
                            echo "</div>";
                        }
                    } else {
                        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
                        echo "<strong>❌ TrueLayer Connection Failed!</strong><br>";
                        echo "HTTP Code: {$httpCode}<br>";
                        echo "Response: " . htmlspecialchars($response);
                        echo "</div>";
                    }
                } else {
                    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 20px 0;'>";
                    echo "<strong>⚠️ Cannot Test Connection!</strong><br>";
                    echo "Missing TrueLayer credentials.";
                    echo "</div>";
                }
                break;
        }
        
        // Refresh page to show updated data
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🧪 Test Links</h2>";
echo "<p>";
echo "<a href='bank/scan.php' style='color: #007bff; margin-right: 15px;'>Bank Scan Page</a>";
echo "<a href='dashboard.php' style='color: #007bff; margin-right: 15px;'>Dashboard</a>";
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
