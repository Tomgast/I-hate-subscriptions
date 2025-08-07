<?php
/**
 * PHASE 1.3: CONFIGURATION AUDIT
 * Comprehensive configuration analysis for CashControl recovery
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 1.3 - Configuration Audit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔧 Phase 1.3: Configuration Audit</h1>
            <p class="text-gray-600 mb-8">Comprehensive configuration system analysis for CashControl recovery</p>
            
            <div class="space-y-8">
                
                <!-- Secure Config Loading Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔐 Secure Configuration Loading</h2>
                    <?php
                    $configStatus = [];
                    
                    // Test 1: Check if secure_loader.php exists and loads
                    echo "<h3 class='font-bold mb-2'>1. Secure Loader Test</h3>";
                    try {
                        require_once __DIR__ . '/config/secure_loader.php';
                        echo "<div class='text-green-600'>✅ secure_loader.php loaded successfully</div>";
                        $configStatus['loader'] = true;
                        
                        // Test if getSecureConfig function exists
                        if (function_exists('getSecureConfig')) {
                            echo "<div class='text-green-600'>✅ getSecureConfig() function available</div>";
                            $configStatus['function'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ getSecureConfig() function not found</div>";
                            $configStatus['function'] = false;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ secure_loader.php failed to load: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $configStatus['loader'] = false;
                    }
                    ?>
                </div>

                <?php if (isset($configStatus['function']) && $configStatus['function']): ?>
                
                <!-- Credential Access Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔑 Credential Access Test</h2>
                    <?php
                    $credentials = [
                        'Database' => [
                            'DB_PASSWORD' => 'Database password',
                            'DB_HOST' => 'Database host',
                            'DB_NAME' => 'Database name',
                            'DB_USER' => 'Database user'
                        ],
                        'Google OAuth' => [
                            'GOOGLE_CLIENT_ID' => 'Google OAuth Client ID',
                            'GOOGLE_CLIENT_SECRET' => 'Google OAuth Client Secret'
                        ],
                        'Stripe' => [
                            'STRIPE_SECRET_KEY' => 'Stripe Secret Key',
                            'STRIPE_PUBLISHABLE_KEY' => 'Stripe Publishable Key',
                            'STRIPE_WEBHOOK_SECRET' => 'Stripe Webhook Secret'
                        ],
                        'Email SMTP' => [
                            'SMTP_HOST' => 'SMTP Host',
                            'SMTP_PORT' => 'SMTP Port',
                            'SMTP_USER' => 'SMTP Username',
                            'SMTP_PASS' => 'SMTP Password'
                        ],
                        'TrueLayer' => [
                            'TRUELAYER_CLIENT_ID' => 'TrueLayer Client ID',
                            'TRUELAYER_CLIENT_SECRET' => 'TrueLayer Client Secret',
                            'TRUELAYER_ENVIRONMENT' => 'TrueLayer Environment'
                        ]
                    ];
                    
                    foreach ($credentials as $service => $keys) {
                        echo "<h3 class='font-bold mb-2 mt-4'>$service Credentials</h3>";
                        echo "<div class='ml-4'>";
                        
                        $serviceWorking = true;
                        foreach ($keys as $key => $description) {
                            try {
                                $value = getSecureConfig($key);
                                if ($value && !empty(trim($value))) {
                                    // Mask the value for security
                                    $maskedValue = substr($value, 0, 4) . '***' . substr($value, -4);
                                    echo "<div class='text-green-600'>✅ $description: $maskedValue</div>";
                                } else {
                                    echo "<div class='text-red-600'>❌ $description: Not found or empty</div>";
                                    $serviceWorking = false;
                                }
                            } catch (Exception $e) {
                                echo "<div class='text-red-600'>❌ $description: Error loading</div>";
                                $serviceWorking = false;
                            }
                        }
                        
                        if ($serviceWorking) {
                            echo "<div class='text-green-600 font-bold mt-2'>✅ $service: All credentials loaded</div>";
                        } else {
                            echo "<div class='text-red-600 font-bold mt-2'>❌ $service: Missing credentials</div>";
                        }
                        
                        echo "</div>";
                    }
                    ?>
                </div>

                <!-- Configuration File Analysis -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📁 Configuration Files Analysis</h2>
                    <?php
                    $configFiles = [
                        'secure-config.php' => [
                            'paths' => [
                                __DIR__ . '/../secure-config.php',
                                __DIR__ . '/secure-config.php',
                                '/hoofdmap/secure-config.php'
                            ],
                            'purpose' => 'Main secure configuration file'
                        ],
                        'config/db_config.php' => [
                            'paths' => [__DIR__ . '/config/db_config.php'],
                            'purpose' => 'Database configuration'
                        ],
                        'config/database.php' => [
                            'paths' => [__DIR__ . '/config/database.php'],
                            'purpose' => 'Alternative database configuration'
                        ],
                        'config/auth.php' => [
                            'paths' => [__DIR__ . '/config/auth.php'],
                            'purpose' => 'Authentication configuration'
                        ],
                        'config/email.php' => [
                            'paths' => [__DIR__ . '/config/email.php'],
                            'purpose' => 'Email configuration'
                        ]
                    ];
                    
                    foreach ($configFiles as $filename => $info) {
                        echo "<h3 class='font-bold mb-2'>$filename</h3>";
                        echo "<div class='ml-4 mb-4'>";
                        echo "<p class='text-gray-600 mb-2'>{$info['purpose']}</p>";
                        
                        $found = false;
                        foreach ($info['paths'] as $path) {
                            if (file_exists($path)) {
                                echo "<div class='text-green-600'>✅ Found at: " . htmlspecialchars($path) . "</div>";
                                echo "<div class='text-blue-600'>📏 Size: " . number_format(filesize($path)) . " bytes</div>";
                                echo "<div class='text-blue-600'>📅 Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "</div>";
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            echo "<div class='text-red-600'>❌ Not found in expected locations:</div>";
                            foreach ($info['paths'] as $path) {
                                echo "<div class='text-gray-500 ml-4'>- " . htmlspecialchars($path) . "</div>";
                            }
                        }
                        
                        echo "</div>";
                    }
                    ?>
                </div>

                <!-- Environment Variables Check -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🌍 Environment Variables</h2>
                    <?php
                    echo "<h3 class='font-bold mb-2'>PHP Environment</h3>";
                    echo "<div class='ml-4'>";
                    echo "<div class='text-blue-600'>PHP Version: " . phpversion() . "</div>";
                    echo "<div class='text-blue-600'>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
                    echo "<div class='text-blue-600'>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
                    echo "<div class='text-blue-600'>Script Path: " . __DIR__ . "</div>";
                    echo "</div>";
                    
                    echo "<h3 class='font-bold mb-2 mt-4'>Plesk Environment Variables</h3>";
                    echo "<div class='ml-4'>";
                    
                    $pleskVars = ['PLESK_DOMAIN', 'PLESK_VHOST_DIR', 'DOCUMENT_ROOT'];
                    foreach ($pleskVars as $var) {
                        $value = $_SERVER[$var] ?? getenv($var);
                        if ($value) {
                            echo "<div class='text-green-600'>✅ $var: " . htmlspecialchars($value) . "</div>";
                        } else {
                            echo "<div class='text-yellow-600'>⚠️ $var: Not set</div>";
                        }
                    }
                    echo "</div>";
                    ?>
                </div>

                <!-- Service Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔗 Service Integration Test</h2>
                    <?php
                    $services = [
                        'Database' => [
                            'test' => function() {
                                try {
                                    $dbPassword = getSecureConfig('DB_PASSWORD');
                                    if (!$dbPassword) return ['status' => false, 'message' => 'DB password not found'];
                                    
                                    $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
                                    $pdo = new PDO($dsn, '123cashcontrol', $dbPassword, [
                                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                    ]);
                                    return ['status' => true, 'message' => 'Connection successful'];
                                } catch (Exception $e) {
                                    return ['status' => false, 'message' => $e->getMessage()];
                                }
                            }
                        ],
                        'Google OAuth' => [
                            'test' => function() {
                                $clientId = getSecureConfig('GOOGLE_CLIENT_ID');
                                $clientSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
                                
                                if (!$clientId || !$clientSecret) {
                                    return ['status' => false, 'message' => 'Missing OAuth credentials'];
                                }
                                
                                // Basic validation of format
                                if (strpos($clientId, '.apps.googleusercontent.com') === false) {
                                    return ['status' => false, 'message' => 'Invalid client ID format'];
                                }
                                
                                if (!preg_match('/^GOCSPX-/', $clientSecret)) {
                                    return ['status' => false, 'message' => 'Invalid client secret format'];
                                }
                                
                                return ['status' => true, 'message' => 'Credentials format valid'];
                            }
                        ],
                        'Stripe' => [
                            'test' => function() {
                                $secretKey = getSecureConfig('STRIPE_SECRET_KEY');
                                $publishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
                                
                                if (!$secretKey || !$publishableKey) {
                                    return ['status' => false, 'message' => 'Missing Stripe keys'];
                                }
                                
                                // Basic format validation
                                if (!preg_match('/^sk_(test_|live_)/', $secretKey)) {
                                    return ['status' => false, 'message' => 'Invalid secret key format'];
                                }
                                
                                if (!preg_match('/^pk_(test_|live_)/', $publishableKey)) {
                                    return ['status' => false, 'message' => 'Invalid publishable key format'];
                                }
                                
                                return ['status' => true, 'message' => 'Keys format valid'];
                            }
                        ],
                        'SMTP Email' => [
                            'test' => function() {
                                $host = getSecureConfig('SMTP_HOST');
                                $user = getSecureConfig('SMTP_USER');
                                $pass = getSecureConfig('SMTP_PASS');
                                $port = getSecureConfig('SMTP_PORT');
                                
                                if (!$host || !$user || !$pass || !$port) {
                                    return ['status' => false, 'message' => 'Missing SMTP credentials'];
                                }
                                
                                return ['status' => true, 'message' => 'All SMTP credentials present'];
                            }
                        ]
                    ];
                    
                    foreach ($services as $serviceName => $config) {
                        echo "<h3 class='font-bold mb-2'>$serviceName</h3>";
                        echo "<div class='ml-4 mb-4'>";
                        
                        try {
                            $result = $config['test']();
                            if ($result['status']) {
                                echo "<div class='text-green-600'>✅ " . htmlspecialchars($result['message']) . "</div>";
                            } else {
                                echo "<div class='text-red-600'>❌ " . htmlspecialchars($result['message']) . "</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='text-red-600'>❌ Test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                        
                        echo "</div>";
                    }
                    ?>
                </div>

                <?php endif; ?>

                <!-- Configuration Issues Summary -->
                <div class="border rounded-lg p-6 bg-blue-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Configuration Audit Summary</h2>
                    <?php
                    $issues = [];
                    $recommendations = [];
                    
                    if (!isset($configStatus['loader']) || !$configStatus['loader']) {
                        $issues[] = "Secure configuration loader not working";
                        $recommendations[] = "Fix config/secure_loader.php path and loading";
                    }
                    
                    if (!isset($configStatus['function']) || !$configStatus['function']) {
                        $issues[] = "getSecureConfig() function not available";
                        $recommendations[] = "Verify secure_loader.php defines getSecureConfig() function";
                    }
                    
                    if (!empty($issues)) {
                        echo "<div class='mb-4'>";
                        echo "<h3 class='font-bold text-red-600 mb-2'>🚨 Critical Issues:</h3>";
                        echo "<ul class='list-disc list-inside text-red-600'>";
                        foreach ($issues as $issue) {
                            echo "<li>$issue</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                        
                        echo "<div class='mb-4'>";
                        echo "<h3 class='font-bold text-blue-600 mb-2'>💡 Recommendations:</h3>";
                        echo "<ul class='list-disc list-inside text-blue-600'>";
                        foreach ($recommendations as $rec) {
                            echo "<li>$rec</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-green-100 text-green-800 p-3 rounded'>";
                        echo "✅ Configuration audit completed. Ready to proceed to Phase 2: Core Infrastructure Rebuild.";
                        echo "</div>";
                    }
                    ?>
                </div>

            </div>
            
            <div class="mt-8 text-center">
                <a href="RECOVERY_PLAN.md" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 inline-block mr-4">
                    📋 View Recovery Plan
                </a>
                <a href="PHASE1_AUDIT_REPORT.md" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 inline-block mr-4">
                    📊 View Audit Report
                </a>
                <a href="phase1-database-assessment.php" class="bg-purple-500 text-white px-6 py-3 rounded-lg hover:bg-purple-600 inline-block">
                    🗄️ Database Assessment
                </a>
            </div>
        </div>
    </div>
</body>
</html>
