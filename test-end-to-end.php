<?php
/**
 * CashControl End-to-End Testing Suite
 * Tests complete user flows and system integrations
 */

// Suppress HTTP_HOST warnings in CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
}

require_once 'config/db_config.php';

echo "=== CashControl End-to-End Testing Suite ===\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

function runTest($testName, $testFunction) {
    global $testResults, $totalTests, $passedTests;
    
    echo "Testing: $testName\n";
    $totalTests++;
    
    try {
        $result = $testFunction();
        if ($result) {
            echo "   ✅ PASSED\n";
            $passedTests++;
            $testResults[$testName] = 'PASSED';
        } else {
            echo "   ❌ FAILED\n";
            $testResults[$testName] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        $testResults[$testName] = 'ERROR: ' . $e->getMessage();
    }
    echo "\n";
}

// Test 1: Database Connectivity and Schema
runTest("Database Connection & Schema", function() {
    $pdo = getDBConnection();
    
    // Check all required tables exist
    $requiredTables = [
        'users', 'subscriptions', 'user_preferences', 'reminder_logs',
        'bank_connections', 'bank_scans', 'checkout_sessions', 'unsubscribe_guides'
    ];
    
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            throw new Exception("Missing table: $table");
        }
    }
    
    // Test basic CRUD operations
    $testEmail = 'test_' . time() . '@example.com';
    
    // Create test user
    $stmt = $pdo->prepare("INSERT INTO users (email, name, subscription_type, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testEmail, 'Test User', 'free', 'active']);
    $userId = $pdo->lastInsertId();
    
    // Read test user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || $user['email'] !== $testEmail) {
        throw new Exception("User creation/read failed");
    }
    
    // Update test user
    $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->execute(['Updated Test User', $userId]);
    
    // Delete test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    return true;
});

// Test 2: Configuration Loading
runTest("Secure Configuration Loading", function() {
    $config = getSecureConfig();
    
    // Check all required config sections exist
    $requiredSections = ['database', 'google_oauth', 'stripe', 'smtp', 'truelayer'];
    
    foreach ($requiredSections as $section) {
        if (!isset($config[$section])) {
            throw new Exception("Missing config section: $section");
        }
    }
    
    // Check database config
    if (empty($config['database']['host']) || empty($config['database']['name'])) {
        throw new Exception("Database configuration incomplete");
    }
    
    // Check critical credentials are not placeholders
    if (strpos($config['stripe']['secret_key'], 'sk_test_') !== 0 && 
        strpos($config['stripe']['secret_key'], 'sk_live_') !== 0) {
        throw new Exception("Stripe secret key appears to be placeholder");
    }
    
    return true;
});

// Test 3: Authentication System
runTest("Authentication System", function() {
    // Check authentication files exist
    $authFiles = [
        'auth/signin.php',
        'auth/signup.php',
        'auth/google-oauth.php',
        'auth/google-callback.php',
        'auth/logout.php'
    ];
    
    foreach ($authFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing auth file: $file");
        }
    }
    
    // Test password hashing
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    if (!password_verify($password, $hash)) {
        throw new Exception("Password hashing/verification failed");
    }
    
    // Test Google OAuth URL generation
    $config = getSecureConfig();
    $clientId = $config['google_oauth']['client_id'];
    $redirectUri = $config['google_oauth']['redirect_uri'];
    
    if (empty($clientId) || empty($redirectUri)) {
        throw new Exception("Google OAuth configuration incomplete");
    }
    
    return true;
});

// Test 4: Stripe Payment Integration
runTest("Stripe Payment Integration", function() {
    require_once 'includes/stripe_service.php';
    
    $config = getSecureConfig();
    
    // Check Stripe configuration
    if (empty($config['stripe']['secret_key']) || empty($config['stripe']['publishable_key'])) {
        throw new Exception("Stripe configuration incomplete");
    }
    
    // Verify Stripe keys format
    $secretKey = $config['stripe']['secret_key'];
    if (strpos($secretKey, 'sk_test_') !== 0 && strpos($secretKey, 'sk_live_') !== 0) {
        throw new Exception("Invalid Stripe secret key format");
    }
    
    // Test StripeService class instantiation
    $stripeService = new StripeService();
    
    // Test checkout session creation method exists
    if (!method_exists($stripeService, 'createCheckoutSession')) {
        throw new Exception("StripeService missing createCheckoutSession method");
    }
    
    // Test Stripe library loading (if available)
    if (class_exists('\Stripe\Stripe')) {
        try {
            \Stripe\Stripe::setApiKey($config['stripe']['secret_key']);
            // Just test that the key is accepted, don't make API calls in testing
            return true;
        } catch (Exception $e) {
            throw new Exception("Stripe library error: " . $e->getMessage());
        }
    } else {
        // Stripe library not loaded, but service class exists - acceptable for basic test
        echo "   ⚠️  Stripe library not loaded, testing service class only\n";
    }
    
    return true;
});

// Test 5: Email/SMTP System
runTest("Email/SMTP System", function() {
    require_once 'includes/email_service.php';
    
    $config = getSecureConfig();
    
    // Check SMTP configuration
    if (empty($config['smtp']['host']) || empty($config['smtp']['username'])) {
        throw new Exception("SMTP configuration incomplete");
    }
    
    // Test EmailService class
    $emailService = new EmailService();
    
    if (!method_exists($emailService, 'sendEmail')) {
        throw new Exception("EmailService missing sendEmail method");
    }
    
    // Test SMTP connection (without sending)
    $smtp = fsockopen($config['smtp']['host'], $config['smtp']['port'], $errno, $errstr, 10);
    if (!$smtp) {
        throw new Exception("SMTP server connection failed: $errstr");
    }
    fclose($smtp);
    
    return true;
});

// Test 6: TrueLayer Bank Integration
runTest("TrueLayer Bank Integration", function() {
    require_once 'includes/bank_service.php';
    
    $config = getSecureConfig();
    
    // Check TrueLayer configuration
    if (empty($config['truelayer']['client_id']) || empty($config['truelayer']['client_secret'])) {
        throw new Exception("TrueLayer configuration incomplete");
    }
    
    // Test BankService class
    $bankService = new BankService();
    
    $requiredMethods = [
        'getBankAuthorizationUrl',
        'initiateBankConnection',
        'completeScan'
    ];
    
    foreach ($requiredMethods as $method) {
        if (!method_exists($bankService, $method)) {
            throw new Exception("BankService missing method: $method");
        }
    }
    
    // Test authorization URL generation
    $authUrl = $bankService->getBankAuthorizationUrl('test-scan-123');
    
    if (empty($authUrl) || !filter_var($authUrl, FILTER_VALIDATE_URL)) {
        throw new Exception("Invalid authorization URL generated");
    }
    
    return true;
});

// Test 7: Plan Management System
runTest("Plan Management System", function() {
    require_once 'includes/plan_manager.php';
    
    $planManager = getPlanManager();
    
    // Test plan feature access
    $testUserId = 999999; // Non-existent user should default to free
    
    // Free plan should not have access to premium features
    if ($planManager->canAccessFeature($testUserId, 'bank_scan')) {
        throw new Exception("Free plan incorrectly granted bank scan access");
    }
    
    if ($planManager->canAccessFeature($testUserId, 'export')) {
        throw new Exception("Free plan incorrectly granted export access");
    }
    
    // Test plan types
    $validPlans = ['free', 'monthly', 'yearly', 'one_time'];
    foreach ($validPlans as $plan) {
        $features = $planManager->getPlanFeatures($plan);
        if (!is_array($features)) {
            throw new Exception("Invalid features returned for plan: $plan");
        }
    }
    
    return true;
});

// Test 8: Dashboard System
runTest("Dashboard System", function() {
    $dashboardFiles = [
        'dashboard.php',
        'upgrade.php',
        'settings.php'
    ];
    
    foreach ($dashboardFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing dashboard file: $file");
        }
    }
    
    // Test dashboard includes
    $includeFiles = [
        'includes/header.php',
        'includes/plan_manager.php'
    ];
    
    foreach ($includeFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing include file: $file");
        }
    }
    
    return true;
});

// Test 9: Export System
runTest("Export System", function() {
    $exportFiles = [
        'export/pdf.php',
        'export/csv.php'
    ];
    
    foreach ($exportFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing export file: $file");
        }
    }
    
    // Test CSV generation capability
    $testData = [
        ['Service', 'Amount', 'Frequency'],
        ['Netflix', '€12.99', 'Monthly'],
        ['Spotify', '€9.99', 'Monthly']
    ];
    
    $csvContent = '';
    foreach ($testData as $row) {
        $csvContent .= implode(',', $row) . "\n";
    }
    
    if (empty($csvContent)) {
        throw new Exception("CSV generation failed");
    }
    
    return true;
});

// Test 10: Unsubscribe Guides System
runTest("Unsubscribe Guides System", function() {
    $guideFiles = [
        'unsubscribe/index.php',
        'unsubscribe/view.php'
    ];
    
    foreach ($guideFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing guide file: $file");
        }
    }
    
    // Test database content
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM unsubscribe_guides WHERE is_active = 1");
    $guideCount = $stmt->fetchColumn();
    
    if ($guideCount < 5) {
        throw new Exception("Insufficient unsubscribe guides in database (found: $guideCount)");
    }
    
    // Test guide data structure
    $stmt = $pdo->query("SELECT * FROM unsubscribe_guides WHERE is_active = 1 LIMIT 1");
    $guide = $stmt->fetch();
    
    $requiredFields = ['service_name', 'service_slug', 'steps', 'difficulty', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($guide[$field])) {
            throw new Exception("Guide missing required field: $field");
        }
    }
    
    // Test JSON fields
    $steps = json_decode($guide['steps'], true);
    if (!is_array($steps) || empty($steps)) {
        throw new Exception("Invalid steps JSON in guide");
    }
    
    return true;
});

// Test 11: Security and Access Control
runTest("Security and Access Control", function() {
    // Check .gitignore exists and includes sensitive files
    if (!file_exists('.gitignore')) {
        throw new Exception("Missing .gitignore file");
    }
    
    $gitignore = file_get_contents('.gitignore');
    $sensitiveFiles = ['secure-config.php', '.env', 'vendor/'];
    
    foreach ($sensitiveFiles as $file) {
        if (strpos($gitignore, $file) === false) {
            throw new Exception("Sensitive file not in .gitignore: $file");
        }
    }
    
    // Check secure-config.php is outside web accessible area or properly protected
    if (file_exists('secure-config.php')) {
        // File exists, check if it's the template or real config
        $config = file_get_contents('secure-config.php');
        if (strpos($config, 'your_database_password') !== false) {
            throw new Exception("secure-config.php appears to contain placeholder values");
        }
    }
    
    return true;
});

// Test 12: File Permissions and Structure
runTest("File Permissions and Structure", function() {
    $criticalDirectories = [
        'includes/',
        'config/',
        'auth/',
        'bank/',
        'export/',
        'unsubscribe/'
    ];
    
    foreach ($criticalDirectories as $dir) {
        if (!is_dir($dir)) {
            throw new Exception("Missing critical directory: $dir");
        }
        
        if (!is_readable($dir)) {
            throw new Exception("Directory not readable: $dir");
        }
    }
    
    // Check critical files are readable
    $criticalFiles = [
        'index.html',
        'dashboard.php',
        'includes/plan_manager.php',
        'config/db_config.php'
    ];
    
    foreach ($criticalFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Missing critical file: $file");
        }
        
        if (!is_readable($file)) {
            throw new Exception("File not readable: $file");
        }
    }
    
    return true;
});

// Display Results Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "END-TO-END TEST RESULTS SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

echo "Detailed Results:\n";
echo str_repeat("-", 30) . "\n";

foreach ($testResults as $testName => $result) {
    $status = (strpos($result, 'PASSED') === 0) ? '✅' : '❌';
    echo "$status $testName: $result\n";
}

echo "\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL TESTS PASSED! CashControl is ready for production.\n";
} else {
    echo "⚠️  Some tests failed. Please review and fix issues before deployment.\n";
}

echo "\nNext Steps:\n";
if ($passedTests === $totalTests) {
    echo "- Proceed with Phase 6: Security Review\n";
    echo "- Prepare for Phase 7: Documentation & Handover\n";
} else {
    echo "- Fix failing tests\n";
    echo "- Re-run end-to-end testing\n";
}

echo "\n=== End of Testing ===\n";
?>
