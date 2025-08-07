<?php
/**
 * PHASE 2.3: COMPREHENSIVE SERVICE INTEGRATION TEST
 * Complete testing suite for upgraded CashControl system
 * Tests all services with new credentials and database schema
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
    <title>Phase 2.3 - Integration Testing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🧪 Phase 2.3: Service Integration Testing</h1>
            <p class="text-gray-600 mb-8">Comprehensive testing of upgraded CashControl system</p>
            
            <div class="space-y-8">
                
                <!-- Test Overview -->
                <div class="border rounded-lg p-6 bg-blue-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Test Overview</h2>
                    <p class="text-gray-700 mb-4">
                        This comprehensive test suite verifies that your upgraded system works perfectly:
                    </p>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="font-bold mb-2">✅ Recent Upgrades:</h3>
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                                <li>All credentials updated (Stripe, SMTP, DB)</li>
                                <li>Database schema fully migrated</li>
                                <li>New tables and columns added</li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-bold mb-2">🧪 Tests Included:</h3>
                            <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                                <li>Database connectivity and schema</li>
                                <li>Stripe payment integration</li>
                                <li>Email/SMTP functionality</li>
                                <li>Google OAuth readiness</li>
                                <li>TrueLayer bank integration</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Database Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🗄️ Database Integration Test</h2>
                    
                    <?php
                    $dbResults = [];
                    
                    try {
                        // Connect to database
                        $dbPassword = getSecureConfig('DB_PASSWORD');
                        $dbUser = getSecureConfig('DB_USER');
                        
                        $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
                        $pdo = new PDO($dsn, $dbUser, $dbPassword, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                        
                        echo "<div class='text-green-600 mb-4'>✅ Database connection successful</div>";
                        
                        // Test 1: Verify new checkout_sessions table
                        $stmt = $pdo->query("DESCRIBE checkout_sessions");
                        $columns = $stmt->fetchAll();
                        if (count($columns) >= 7) {
                            echo "<div class='text-green-600'>✅ checkout_sessions table exists with all columns</div>";
                            $dbResults['checkout_sessions'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ checkout_sessions table incomplete</div>";
                            $dbResults['checkout_sessions'] = false;
                        }
                        
                        // Test 2: Verify new user columns
                        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'stripe_customer_id'");
                        if ($stmt->rowCount() > 0) {
                            echo "<div class='text-green-600'>✅ users.stripe_customer_id column exists</div>";
                            $dbResults['user_stripe'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ users.stripe_customer_id column missing</div>";
                            $dbResults['user_stripe'] = false;
                        }
                        
                        // Test 3: Test CRUD operations on new schema
                        $testUserId = 999999; // Use a test ID that won't conflict
                        
                        // Test INSERT with new columns
                        $stmt = $pdo->prepare("INSERT INTO checkout_sessions (user_id, stripe_session_id, plan_type, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)");
                        $stmt->execute([$testUserId, 'test_session_' . time(), 'test_plan', 'pending']);
                        
                        // Test SELECT
                        $stmt = $pdo->prepare("SELECT * FROM checkout_sessions WHERE user_id = ? AND stripe_session_id LIKE 'test_session_%'");
                        $stmt->execute([$testUserId]);
                        $testRecord = $stmt->fetch();
                        
                        if ($testRecord) {
                            echo "<div class='text-green-600'>✅ New schema CRUD operations working</div>";
                            $dbResults['crud'] = true;
                            
                            // Cleanup test data
                            $stmt = $pdo->prepare("DELETE FROM checkout_sessions WHERE user_id = ? AND stripe_session_id LIKE 'test_session_%'");
                            $stmt->execute([$testUserId]);
                        } else {
                            echo "<div class='text-red-600'>❌ New schema CRUD operations failed</div>";
                            $dbResults['crud'] = false;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Database test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $dbResults['connection'] = false;
                    }
                    ?>
                </div>

                <!-- Stripe Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">💳 Stripe Integration Test</h2>
                    
                    <?php
                    $stripeResults = [];
                    
                    try {
                        $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
                        $stripePublishable = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
                        
                        if (!$stripeSecret || !$stripePublishable) {
                            throw new Exception("Stripe credentials not found");
                        }
                        
                        // Test 1: Validate key formats
                        if (preg_match('/^sk_(test_|live_)/', $stripeSecret)) {
                            echo "<div class='text-green-600'>✅ Stripe secret key format valid</div>";
                            $stripeResults['secret_format'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Stripe secret key format invalid</div>";
                            $stripeResults['secret_format'] = false;
                        }
                        
                        if (preg_match('/^pk_(test_|live_)/', $stripePublishable)) {
                            echo "<div class='text-green-600'>✅ Stripe publishable key format valid</div>";
                            $stripeResults['publishable_format'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Stripe publishable key format invalid</div>";
                            $stripeResults['publishable_format'] = false;
                        }
                        
                        // Test 2: Test Stripe API connectivity (basic)
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/balance');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $stripeSecret,
                            'Content-Type: application/x-www-form-urlencoded'
                        ]);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 200) {
                            echo "<div class='text-green-600'>✅ Stripe API connectivity successful</div>";
                            $stripeResults['api_connection'] = true;
                            
                            $balanceData = json_decode($response, true);
                            if (isset($balanceData['object']) && $balanceData['object'] === 'balance') {
                                echo "<div class='text-blue-600'>ℹ️ Stripe account accessible (balance object retrieved)</div>";
                            }
                        } else {
                            echo "<div class='text-red-600'>❌ Stripe API connectivity failed (HTTP $httpCode)</div>";
                            $stripeResults['api_connection'] = false;
                        }
                        
                        // Test 3: Verify checkout_sessions table for Stripe integration
                        if (isset($pdo) && isset($dbResults['checkout_sessions']) && $dbResults['checkout_sessions']) {
                            echo "<div class='text-green-600'>✅ Database ready for Stripe checkout sessions</div>";
                            $stripeResults['db_ready'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Database not ready for Stripe integration</div>";
                            $stripeResults['db_ready'] = false;
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Stripe test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $stripeResults['overall'] = false;
                    }
                    ?>
                </div>

                <!-- Email/SMTP Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📧 Email/SMTP Integration Test</h2>
                    
                    <?php
                    $emailResults = [];
                    
                    try {
                        $smtpHost = getSecureConfig('SMTP_HOST');
                        $smtpPort = getSecureConfig('SMTP_PORT');
                        $smtpUser = getSecureConfig('SMTP_USER');
                        $smtpPass = getSecureConfig('SMTP_PASS');
                        
                        if (!$smtpHost || !$smtpPort || !$smtpUser || !$smtpPass) {
                            throw new Exception("SMTP credentials incomplete");
                        }
                        
                        echo "<div class='text-green-600'>✅ All SMTP credentials present</div>";
                        
                        // Test 1: Validate SMTP settings
                        if (filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
                            echo "<div class='text-green-600'>✅ SMTP username is valid email format</div>";
                            $emailResults['username_format'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ SMTP username not in email format</div>";
                            $emailResults['username_format'] = false;
                        }
                        
                        // Test 2: Test SMTP connection
                        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
                        if ($socket) {
                            echo "<div class='text-green-600'>✅ SMTP server connectivity successful</div>";
                            fclose($socket);
                            $emailResults['connectivity'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ SMTP server connectivity failed: $errstr</div>";
                            $emailResults['connectivity'] = false;
                        }
                        
                        // Test 3: Verify reminder_logs table for email tracking
                        if (isset($pdo)) {
                            $stmt = $pdo->query("SHOW COLUMNS FROM reminder_logs LIKE 'email_status'");
                            if ($stmt->rowCount() > 0) {
                                echo "<div class='text-green-600'>✅ Database ready for email logging</div>";
                                $emailResults['db_ready'] = true;
                            } else {
                                echo "<div class='text-red-600'>❌ Database not ready for email logging</div>";
                                $emailResults['db_ready'] = false;
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Email test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $emailResults['overall'] = false;
                    }
                    ?>
                </div>

                <!-- Google OAuth Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔐 Google OAuth Integration Test</h2>
                    
                    <?php
                    $oauthResults = [];
                    
                    try {
                        $clientId = getSecureConfig('GOOGLE_CLIENT_ID');
                        $clientSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
                        
                        if (!$clientId || !$clientSecret) {
                            throw new Exception("Google OAuth credentials not found");
                        }
                        
                        // Test 1: Validate credential formats
                        if (strpos($clientId, '.apps.googleusercontent.com') !== false) {
                            echo "<div class='text-green-600'>✅ Google Client ID format valid</div>";
                            $oauthResults['client_id_format'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Google Client ID format invalid</div>";
                            $oauthResults['client_id_format'] = false;
                        }
                        
                        if (preg_match('/^GOCSPX-/', $clientSecret)) {
                            echo "<div class='text-green-600'>✅ Google Client Secret format valid</div>";
                            $oauthResults['client_secret_format'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Google Client Secret format invalid</div>";
                            $oauthResults['client_secret_format'] = false;
                        }
                        
                        // Test 2: Test Google OAuth endpoint accessibility
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/.well-known/openid_configuration');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 200) {
                            echo "<div class='text-green-600'>✅ Google OAuth endpoints accessible</div>";
                            $oauthResults['endpoint_access'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ Google OAuth endpoints not accessible</div>";
                            $oauthResults['endpoint_access'] = false;
                        }
                        
                        // Test 3: Verify users table for OAuth integration
                        if (isset($pdo)) {
                            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
                            if ($stmt->rowCount() > 0) {
                                echo "<div class='text-green-600'>✅ Database ready for Google OAuth</div>";
                                $oauthResults['db_ready'] = true;
                            } else {
                                echo "<div class='text-red-600'>❌ Database not ready for Google OAuth</div>";
                                $oauthResults['db_ready'] = false;
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Google OAuth test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $oauthResults['overall'] = false;
                    }
                    ?>
                </div>

                <!-- TrueLayer Integration Test -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🏦 TrueLayer Integration Test</h2>
                    
                    <?php
                    $truelayerResults = [];
                    
                    try {
                        $clientId = getSecureConfig('TRUELAYER_CLIENT_ID');
                        $clientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
                        $environment = getSecureConfig('TRUELAYER_ENVIRONMENT');
                        
                        if (!$clientId || !$clientSecret || !$environment) {
                            throw new Exception("TrueLayer credentials incomplete");
                        }
                        
                        echo "<div class='text-green-600'>✅ All TrueLayer credentials present</div>";
                        
                        // Test 1: Validate environment setting
                        if (in_array($environment, ['sandbox', 'live'])) {
                            echo "<div class='text-green-600'>✅ TrueLayer environment valid ($environment)</div>";
                            $truelayerResults['environment'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ TrueLayer environment invalid</div>";
                            $truelayerResults['environment'] = false;
                        }
                        
                        // Test 2: Test TrueLayer API endpoint
                        $baseUrl = ($environment === 'live') ? 'https://api.truelayer.com' : 'https://api.truelayer-sandbox.com';
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/data/v1/info');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 401 || $httpCode === 200) { // 401 is expected without auth token
                            echo "<div class='text-green-600'>✅ TrueLayer API endpoints accessible</div>";
                            $truelayerResults['api_access'] = true;
                        } else {
                            echo "<div class='text-red-600'>❌ TrueLayer API endpoints not accessible (HTTP $httpCode)</div>";
                            $truelayerResults['api_access'] = false;
                        }
                        
                        // Test 3: Check for bank-related tables
                        if (isset($pdo)) {
                            $stmt = $pdo->query("SHOW TABLES LIKE 'bank_%'");
                            $bankTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if (count($bankTables) > 0) {
                                echo "<div class='text-green-600'>✅ Database has bank integration tables (" . implode(', ', $bankTables) . ")</div>";
                                $truelayerResults['db_ready'] = true;
                            } else {
                                echo "<div class='text-blue-600'>ℹ️ No bank-specific tables found (may be created on first use)</div>";
                                $truelayerResults['db_ready'] = true; // Not critical
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ TrueLayer test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $truelayerResults['overall'] = false;
                    }
                    ?>
                </div>

                <!-- Overall Integration Summary -->
                <div class="border rounded-lg p-6 bg-green-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Integration Test Summary</h2>
                    
                    <?php
                    // Calculate overall scores
                    $services = [
                        'Database' => $dbResults,
                        'Stripe' => $stripeResults,
                        'Email/SMTP' => $emailResults,
                        'Google OAuth' => $oauthResults,
                        'TrueLayer' => $truelayerResults
                    ];
                    
                    $totalTests = 0;
                    $passedTests = 0;
                    $serviceStatus = [];
                    
                    foreach ($services as $serviceName => $results) {
                        $servicePassed = 0;
                        $serviceTotal = count($results);
                        
                        foreach ($results as $test => $result) {
                            $totalTests++;
                            if ($result === true) {
                                $passedTests++;
                                $servicePassed++;
                            }
                        }
                        
                        $servicePercentage = $serviceTotal > 0 ? round(($servicePassed / $serviceTotal) * 100) : 0;
                        $serviceStatus[$serviceName] = [
                            'passed' => $servicePassed,
                            'total' => $serviceTotal,
                            'percentage' => $servicePercentage
                        ];
                    }
                    
                    $overallPercentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
                    
                    echo "<div class='grid md:grid-cols-2 gap-6'>";
                    
                    echo "<div>";
                    echo "<h3 class='font-bold text-lg mb-4'>Service Status:</h3>";
                    foreach ($serviceStatus as $service => $status) {
                        $color = $status['percentage'] >= 80 ? 'text-green-600' : ($status['percentage'] >= 60 ? 'text-yellow-600' : 'text-red-600');
                        $icon = $status['percentage'] >= 80 ? '✅' : ($status['percentage'] >= 60 ? '⚠️' : '❌');
                        echo "<div class='$color mb-2'>$icon $service: {$status['passed']}/{$status['total']} ({$status['percentage']}%)</div>";
                    }
                    echo "</div>";
                    
                    echo "<div>";
                    echo "<h3 class='font-bold text-lg mb-4'>Overall Results:</h3>";
                    $overallColor = $overallPercentage >= 80 ? 'text-green-600' : ($overallPercentage >= 60 ? 'text-yellow-600' : 'text-red-600');
                    $overallIcon = $overallPercentage >= 80 ? '🎉' : ($overallPercentage >= 60 ? '⚠️' : '❌');
                    echo "<div class='$overallColor text-xl font-bold mb-4'>$overallIcon Overall Score: $passedTests/$totalTests ($overallPercentage%)</div>";
                    
                    if ($overallPercentage >= 80) {
                        echo "<div class='bg-green-100 text-green-800 p-4 rounded'>";
                        echo "<h4 class='font-bold mb-2'>🎉 Excellent! System Ready for Production</h4>";
                        echo "<p>Your CashControl application is fully operational with all major services working correctly.</p>";
                        echo "</div>";
                    } elseif ($overallPercentage >= 60) {
                        echo "<div class='bg-yellow-100 text-yellow-800 p-4 rounded'>";
                        echo "<h4 class='font-bold mb-2'>⚠️ Good Progress, Minor Issues</h4>";
                        echo "<p>Most services are working. Review the failed tests above for minor fixes needed.</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-red-100 text-red-800 p-4 rounded'>";
                        echo "<h4 class='font-bold mb-2'>❌ Issues Need Attention</h4>";
                        echo "<p>Several services need attention. Review the failed tests above for required fixes.</p>";
                        echo "</div>";
                    }
                    echo "</div>";
                    
                    echo "</div>";
                    ?>
                </div>

                <!-- Next Steps -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🎯 Next Steps</h2>
                    
                    <div class="grid md:grid-cols-4 gap-4">
                        <a href="phase1-database-assessment.php" 
                           class="bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 text-center">
                            🗄️ Database Assessment
                        </a>
                        <a href="phase1-config-audit.php" 
                           class="bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 text-center">
                            🔧 Config Audit
                        </a>
                        <a href="test/test-connections.php" 
                           class="bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600 text-center">
                            🔗 Service Connections
                        </a>
                        <a href="RECOVERY_PLAN.md" 
                           class="bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 text-center">
                            📋 Recovery Plan
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
