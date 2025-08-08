<?php
/**
 * Simple CLI Bank Integration Test
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'support@origens.nl';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TrueLayer Bank Integration Test ===\n\n";

try {
    // Test 1: Load BankService
    echo "1. Loading BankService...\n";
    require_once __DIR__ . '/includes/bank_service.php';
    $bankService = new BankService();
    echo "   ✅ BankService loaded successfully\n\n";
    
    // Test 2: Check configuration
    echo "2. Testing TrueLayer configuration...\n";
    $config = $bankService->testConfiguration();
    
    if ($config['configured']) {
        echo "   ✅ TrueLayer is properly configured\n";
        echo "   - Environment: " . $config['environment'] . "\n";
        echo "   - Client ID: " . $config['client_id'] . "\n\n";
    } else {
        echo "   ❌ TrueLayer configuration issues:\n";
        foreach ($config['errors'] as $error) {
            echo "     • " . $error . "\n";
        }
        echo "\n";
    }
    
    // Test 3: Check if initiateBankConnection method exists
    echo "3. Checking method availability...\n";
    if (method_exists($bankService, 'initiateBankConnection')) {
        echo "   ✅ initiateBankConnection method exists\n\n";
    } else {
        echo "   ❌ initiateBankConnection method missing\n\n";
        exit(1);
    }
    
    // Test 4: Try to generate authorization URL
    if ($config['configured']) {
        echo "4. Testing authorization URL generation...\n";
        try {
            $authUrl = $bankService->initiateBankConnection($_SESSION['user_id'], 'monthly');
            
            if ($authUrl) {
                echo "   ✅ Authorization URL generated successfully!\n";
                echo "   - URL Length: " . strlen($authUrl) . " characters\n";
                echo "   - Domain: " . parse_url($authUrl, PHP_URL_HOST) . "\n";
                echo "   - Full URL: " . $authUrl . "\n\n";
                
                echo "=== SUCCESS ===\n";
                echo "TrueLayer bank integration is working!\n";
                echo "You can test the authorization flow by visiting the URL above.\n";
            } else {
                echo "   ❌ Failed to generate authorization URL\n\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error generating authorization URL: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "4. Skipping authorization URL test (configuration issues)\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
