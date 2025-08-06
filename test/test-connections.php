<?php
// Test endpoint for all service connections
session_start();
require_once '../includes/email_service.php';
require_once '../includes/google_oauth.php';
require_once '../includes/bank_service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CashControl - Service Connection Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-12 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">🔧 Service Connection Test</h1>
            
            <?php
            echo "<div class='space-y-6'>";
            
            // Test Email Service
            echo "<div class='border rounded-lg p-6'>";
            echo "<h2 class='text-xl font-bold text-gray-900 mb-4'>📧 Email Service (Plesk SMTP)</h2>";
            
            try {
                $emailService = new EmailService();
                
                if ($_POST['test_email'] ?? false) {
                    $result = $emailService->testEmailConfiguration();
                    if ($result) {
                        echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Test email sent successfully!</div>";
                    } else {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Failed to send test email</div>";
                    }
                }
                
                echo "<p class='text-gray-600 mb-4'>Email service is configured and ready.</p>";
                echo "<form method='post'><button type='submit' name='test_email' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>Send Test Email</button></form>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Email service error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            echo "</div>";
            
            // Test Google OAuth
            echo "<div class='border rounded-lg p-6'>";
            echo "<h2 class='text-xl font-bold text-gray-900 mb-4'>🔐 Google OAuth Service</h2>";
            
            try {
                $googleOAuth = new GoogleOAuthService();
                $config = $googleOAuth->testConfiguration();
                
                if ($config['configured']) {
                    echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Google OAuth is properly configured</div>";
                    echo "<p class='text-sm text-gray-600 mb-2'>Client ID: " . htmlspecialchars($config['client_id']) . "</p>";
                    echo "<p class='text-sm text-gray-600 mb-4'>Redirect URI: " . htmlspecialchars($config['redirect_uri']) . "</p>";
                    
                    $authUrl = $googleOAuth->getAuthorizationUrl();
                    echo "<a href='" . htmlspecialchars($authUrl) . "' class='bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 inline-block'>Test Google Sign-In</a>";
                } else {
                    echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Google OAuth configuration issues:</div>";
                    echo "<ul class='list-disc list-inside text-red-700'>";
                    foreach ($config['errors'] as $error) {
                        echo "<li>" . htmlspecialchars($error) . "</li>";
                    }
                    echo "</ul>";
                }
                
            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Google OAuth error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            echo "</div>";
            
            // Test Bank Service
            echo "<div class='border rounded-lg p-6'>";
            echo "<h2 class='text-xl font-bold text-gray-900 mb-4'>🏦 Bank Integration Service (TrueLayer)</h2>";
            
            try {
                $bankService = new BankService();
                $config = $bankService->testConfiguration();
                
                if ($config['configured']) {
                    echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Bank integration is properly configured</div>";
                    echo "<p class='text-sm text-gray-600 mb-2'>Environment: " . htmlspecialchars($config['environment']) . "</p>";
                    echo "<p class='text-sm text-gray-600 mb-4'>Client ID: " . htmlspecialchars($config['client_id']) . "</p>";
                    
                    if (isset($_SESSION['user_id'])) {
                        $authUrl = $bankService->getBankAuthorizationUrl($_SESSION['user_id']);
                        echo "<a href='" . htmlspecialchars($authUrl) . "' class='bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 inline-block'>Test Bank Connection</a>";
                    } else {
                        echo "<p class='text-gray-600'>Please sign in to test bank connection</p>";
                    }
                } else {
                    echo "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>❌ Bank integration configuration issues:</div>";
                    echo "<ul class='list-disc list-inside text-red-700'>";
                    foreach ($config['errors'] as $error) {
                        echo "<li>" . htmlspecialchars($error) . "</li>";
                    }
                    echo "</ul>";
                }
                
            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Bank service error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            echo "</div>";
            
            // Test Database Connection
            echo "<div class='border rounded-lg p-6'>";
            echo "<h2 class='text-xl font-bold text-gray-900 mb-4'>🗄️ Database Connection (MariaDB)</h2>";
            
            try {
                $pdo = getDBConnection();
                echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Database connection successful</div>";
                
                // Test table existence
                $tables = ['users', 'subscriptions', 'user_sessions', 'categories', 'user_preferences'];
                echo "<p class='text-gray-600 mb-2'>Table status:</p>";
                echo "<ul class='list-disc list-inside text-sm text-gray-600'>";
                
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        echo "<li class='text-green-600'>✅ $table</li>";
                    } else {
                        echo "<li class='text-red-600'>❌ $table (missing)</li>";
                    }
                }
                echo "</ul>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            echo "</div>";
            
            echo "</div>";
            ?>
            
            <div class="mt-8 text-center">
                <a href="../dashboard.php" class="bg-indigo-500 text-white px-6 py-3 rounded-lg hover:bg-indigo-600 inline-block">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
