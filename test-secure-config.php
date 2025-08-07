<?php
/**
 * Simple test to verify secure-config.php is working
 */

// Load the secure configuration system
require_once __DIR__ . '/config/secure_loader.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CashControl - Secure Config Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-12 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">🔧 Secure Configuration Test</h1>
            
            <div class="space-y-6">
                
                <!-- Database Configuration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🗄️ Database Configuration</h2>
                    <?php
                    try {
                        $dbPassword = getSecureConfig('DB_PASSWORD');
                        if ($dbPassword) {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Database password loaded successfully</div>";
                            echo "<p class='text-sm text-gray-600'>Password: " . str_repeat('*', strlen($dbPassword)) . " (length: " . strlen($dbPassword) . ")</p>";
                            
                            // Test actual database connection
                            try {
                                require_once __DIR__ . '/config/db_config.php';
                                $pdo = getDBConnection();
                                echo "<div class='bg-green-100 text-green-800 p-3 rounded mt-2'>✅ Database connection successful!</div>";
                                
                                // Test a simple query
                                $stmt = $pdo->query("SELECT 1 as test");
                                $result = $stmt->fetch();
                                if ($result['test'] == 1) {
                                    echo "<div class='bg-green-100 text-green-800 p-3 rounded mt-2'>✅ Database query test successful!</div>";
                                }
                                
                            } catch (Exception $e) {
                                echo "<div class='bg-red-100 text-red-800 p-3 rounded mt-2'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                            
                        } else {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Database password not found in config</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Error loading database config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Google OAuth Configuration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔐 Google OAuth Configuration</h2>
                    <?php
                    try {
                        $googleClientId = getSecureConfig('GOOGLE_CLIENT_ID');
                        $googleClientSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
                        
                        if ($googleClientId && $googleClientSecret) {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Google OAuth credentials loaded successfully</div>";
                            echo "<p class='text-sm text-gray-600 mb-2'>Client ID: " . htmlspecialchars($googleClientId) . "</p>";
                            echo "<p class='text-sm text-gray-600'>Client Secret: " . str_repeat('*', strlen($googleClientSecret)) . " (length: " . strlen($googleClientSecret) . ")</p>";
                        } else {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Google OAuth credentials not found</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Error loading Google OAuth config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Email Configuration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📧 Email Configuration</h2>
                    <?php
                    try {
                        $smtpHost = getSecureConfig('SMTP_HOST');
                        $smtpUsername = getSecureConfig('SMTP_USERNAME');
                        $smtpPassword = getSecureConfig('SMTP_PASSWORD');
                        
                        if ($smtpHost && $smtpUsername && $smtpPassword) {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Email SMTP credentials loaded successfully</div>";
                            echo "<p class='text-sm text-gray-600 mb-2'>SMTP Host: " . htmlspecialchars($smtpHost) . "</p>";
                            echo "<p class='text-sm text-gray-600 mb-2'>SMTP Username: " . htmlspecialchars($smtpUsername) . "</p>";
                            echo "<p class='text-sm text-gray-600'>SMTP Password: " . str_repeat('*', strlen($smtpPassword)) . " (length: " . strlen($smtpPassword) . ")</p>";
                            
                            // Test SMTP connection
                            if ($_POST['test_smtp'] ?? false) {
                                try {
                                    $smtp = fsockopen($smtpHost, 587, $errno, $errstr, 10);
                                    if ($smtp) {
                                        echo "<div class='bg-green-100 text-green-800 p-3 rounded mt-2'>✅ SMTP server connection successful!</div>";
                                        fclose($smtp);
                                    } else {
                                        echo "<div class='bg-red-100 text-red-800 p-3 rounded mt-2'>❌ SMTP server connection failed: $errstr ($errno)</div>";
                                    }
                                } catch (Exception $e) {
                                    echo "<div class='bg-red-100 text-red-800 p-3 rounded mt-2'>❌ SMTP test error: " . htmlspecialchars($e->getMessage()) . "</div>";
                                }
                            }
                            
                            echo "<form method='post' class='mt-4'>";
                            echo "<button type='submit' name='test_smtp' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>Test SMTP Connection</button>";
                            echo "</form>";
                            
                        } else {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Email SMTP credentials not found</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Error loading email config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Stripe Configuration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">💳 Stripe Configuration</h2>
                    <?php
                    try {
                        $stripePublishable = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
                        $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
                        
                        if ($stripePublishable && $stripeSecret) {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ Stripe credentials loaded successfully</div>";
                            echo "<p class='text-sm text-gray-600 mb-2'>Publishable Key: " . htmlspecialchars(substr($stripePublishable, 0, 20)) . "...</p>";
                            echo "<p class='text-sm text-gray-600'>Secret Key: " . str_repeat('*', strlen($stripeSecret)) . " (length: " . strlen($stripeSecret) . ")</p>";
                        } else {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Stripe credentials not found</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Error loading Stripe config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- TrueLayer Configuration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🏦 TrueLayer Configuration</h2>
                    <?php
                    try {
                        $trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
                        $trueLayerSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
                        $trueLayerEnv = getSecureConfig('TRUELAYER_ENVIRONMENT');
                        
                        if ($trueLayerClientId && $trueLayerSecret) {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>✅ TrueLayer credentials loaded successfully</div>";
                            echo "<p class='text-sm text-gray-600 mb-2'>Client ID: " . htmlspecialchars($trueLayerClientId) . "</p>";
                            echo "<p class='text-sm text-gray-600 mb-2'>Environment: " . htmlspecialchars($trueLayerEnv) . "</p>";
                            echo "<p class='text-sm text-gray-600'>Client Secret: " . str_repeat('*', strlen($trueLayerSecret)) . " (length: " . strlen($trueLayerSecret) . ")</p>";
                        } else {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ TrueLayer credentials not found</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>❌ Error loading TrueLayer config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Configuration File Status -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📁 Configuration File Status</h2>
                    <?php
                    // Show debug information about config loading
                    if (function_exists('getSecureConfigDebug')) {
                        $debug = getSecureConfigDebug();
                        echo "<div class='bg-blue-100 text-blue-800 p-3 rounded mb-4'>";
                        echo "<h3 class='font-bold mb-2'>Config Loading Debug Info:</h3>";
                        echo "<pre class='text-xs'>" . htmlspecialchars(print_r($debug, true)) . "</pre>";
                        echo "</div>";
                    }
                    
                    // Check if secure-config.php exists in expected location
                    $expectedPath = dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php';
                    if (file_exists($expectedPath)) {
                        echo "<div class='bg-green-100 text-green-800 p-3 rounded'>✅ secure-config.php found at: " . htmlspecialchars($expectedPath) . "</div>";
                    } else {
                        echo "<div class='bg-yellow-100 text-yellow-800 p-3 rounded'>⚠️ secure-config.php not found at expected location: " . htmlspecialchars($expectedPath) . "</div>";
                    }
                    ?>
                </div>

            </div>
            
            <div class="mt-8 text-center">
                <a href="test/test-connections.php" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 inline-block">
                    🚀 Run Full Service Tests
                </a>
            </div>
        </div>
    </div>
</body>
</html>
