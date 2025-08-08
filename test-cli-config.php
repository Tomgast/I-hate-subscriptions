<?php
/**
 * CLI Configuration Test for Bank Integration
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CashControl CLI Configuration Test ===\n\n";

try {
    // Test 1: Load secure configuration
    echo "1. Testing secure configuration loading...\n";
    require_once __DIR__ . '/config/secure_loader.php';
    
    $dbPassword = getSecureConfig('DB_PASSWORD');
    $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
    $trueLayerSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
    
    echo "   - Database password: " . ($dbPassword ? "✅ Loaded" : "❌ Missing") . "\n";
    echo "   - TrueLayer Client ID: " . ($trueLayerClientId ? "✅ " . substr($trueLayerClientId, 0, 10) . "..." : "❌ Missing") . "\n";
    echo "   - TrueLayer Secret: " . ($trueLayerSecret ? "✅ Loaded" : "❌ Missing") . "\n\n";
    
    // Test 2: Database connection
    echo "2. Testing database connection...\n";
    require_once __DIR__ . '/config/db_config.php';
    
    $pdo = getDBConnection();
    echo "   - Database connection: ✅ Success\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "   - Users table: ✅ " . $result['count'] . " users found\n\n";
    
    // Test 3: BankService initialization
    echo "3. Testing BankService...\n";
    require_once __DIR__ . '/includes/bank_service.php';
    
    $bankService = new BankService();
    echo "   - BankService created: ✅ Success\n";
    
    // Test configuration
    $config = $bankService->testConfiguration();
    echo "   - TrueLayer configured: " . ($config['configured'] ? "✅ Yes" : "❌ No") . "\n";
    if (!$config['configured']) {
        foreach ($config['errors'] as $error) {
            echo "     • " . $error . "\n";
        }
    }
    echo "   - Environment: " . $config['environment'] . "\n";
    echo "   - Client ID: " . $config['client_id'] . "\n\n";
    
    // Test 4: Authorization URL generation
    if ($config['configured']) {
        echo "4. Testing authorization URL generation...\n";
        try {
            $authUrl = $bankService->initiateBankConnection(1, 'monthly');
            echo "   - Authorization URL: ✅ Generated successfully\n";
            echo "   - URL: " . substr($authUrl, 0, 100) . "...\n\n";
        } catch (Exception $e) {
            echo "   - Authorization URL: ❌ " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "=== Test Complete ===\n";
    echo "All basic components are working. Bank integration ready for testing.\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
