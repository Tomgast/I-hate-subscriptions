<?php
/**
 * Bank Scan Debug Test
 * Test the bank scanning flow step by step
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate logged in user (use your test user ID)
$_SESSION['user_id'] = 1; // Adjust this to your test user ID
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'support@origens.nl';

require_once __DIR__ . '/includes/bank_service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Scan Debug Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔍 Bank Scan Debug Test</h1>
            <p class="text-gray-600 mb-8">Testing the bank scanning flow step by step</p>
            
            <?php
            try {
                echo "<div class='space-y-4'>";
                
                // Test 1: Check session
                echo "<div class='bg-blue-50 p-4 rounded-lg'>";
                echo "<h3 class='font-bold text-blue-900'>1. Session Check</h3>";
                if (isset($_SESSION['user_id'])) {
                    echo "<p class='text-green-600'>✅ User ID: " . $_SESSION['user_id'] . "</p>";
                } else {
                    echo "<p class='text-red-600'>❌ No user session</p>";
                }
                echo "</div>";
                
                // Test 2: Initialize BankService
                echo "<div class='bg-blue-50 p-4 rounded-lg'>";
                echo "<h3 class='font-bold text-blue-900'>2. BankService Initialization</h3>";
                try {
                    $bankService = new BankService();
                    echo "<p class='text-green-600'>✅ BankService created successfully</p>";
                } catch (Exception $e) {
                    echo "<p class='text-red-600'>❌ BankService error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "</div>";
                
                // Test 3: Check if initiateBankConnection method exists
                echo "<div class='bg-blue-50 p-4 rounded-lg'>";
                echo "<h3 class='font-bold text-blue-900'>3. Method Availability</h3>";
                if (method_exists($bankService, 'initiateBankConnection')) {
                    echo "<p class='text-green-600'>✅ initiateBankConnection method exists</p>";
                } else {
                    echo "<p class='text-red-600'>❌ initiateBankConnection method missing</p>";
                }
                echo "</div>";
                
                // Test 4: Test configuration
                echo "<div class='bg-blue-50 p-4 rounded-lg'>";
                echo "<h3 class='font-bold text-blue-900'>4. Configuration Test</h3>";
                try {
                    $config = $bankService->testConfiguration();
                    if ($config['configured']) {
                        echo "<p class='text-green-600'>✅ TrueLayer configured</p>";
                        echo "<p class='text-sm text-gray-600'>Environment: " . htmlspecialchars($config['environment']) . "</p>";
                        echo "<p class='text-sm text-gray-600'>Client ID: " . htmlspecialchars($config['client_id']) . "</p>";
                    } else {
                        echo "<p class='text-red-600'>❌ Configuration issues:</p>";
                        foreach ($config['errors'] as $error) {
                            echo "<p class='text-sm text-red-600'>• " . htmlspecialchars($error) . "</p>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p class='text-red-600'>❌ Config test error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "</div>";
                
                // Test 5: Try to initiate bank connection
                if (isset($_SESSION['user_id']) && method_exists($bankService, 'initiateBankConnection')) {
                    echo "<div class='bg-yellow-50 p-4 rounded-lg'>";
                    echo "<h3 class='font-bold text-yellow-900'>5. Bank Connection Test</h3>";
                    try {
                        $authUrl = $bankService->initiateBankConnection($_SESSION['user_id'], 'monthly');
                        if ($authUrl) {
                            echo "<p class='text-green-600'>✅ Authorization URL generated successfully</p>";
                            echo "<div class='mt-2 p-3 bg-white rounded border'>";
                            echo "<p class='text-sm font-semibold mb-2'>Generated URL:</p>";
                            echo "<p class='text-xs font-mono break-all'>" . htmlspecialchars($authUrl) . "</p>";
                            echo "</div>";
                            echo "<div class='mt-4'>";
                            echo "<a href='" . htmlspecialchars($authUrl) . "' target='_blank' class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>Test Authorization URL</a>";
                            echo "</div>";
                        } else {
                            echo "<p class='text-red-600'>❌ Failed to generate authorization URL</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='text-red-600'>❌ Bank connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    echo "</div>";
                }
                
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
                    <a href="bank/scan.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Go to Bank Scan Page
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
