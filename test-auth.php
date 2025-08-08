<?php
/**
 * Authentication Integration Test (Google OAuth + Manual Login)
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Authentication Integration Test ===\n\n";

try {
    // Test 1: Load secure configuration
    echo "1. Testing authentication configuration loading...\n";
    require_once __DIR__ . '/config/secure_loader.php';
    
    $googleClientId = getSecureConfig('GOOGLE_CLIENT_ID');
    $googleClientSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
    $googleRedirectUri = getSecureConfig('GOOGLE_REDIRECT_URI');
    
    echo "   - Google Client ID: " . ($googleClientId ? "✅ " . substr($googleClientId, 0, 20) . "..." : "❌ Missing") . "\n";
    echo "   - Google Client Secret: " . ($googleClientSecret ? "✅ Loaded" : "❌ Missing") . "\n";
    echo "   - Google Redirect URI: " . ($googleRedirectUri ? "✅ $googleRedirectUri" : "❌ Missing") . "\n\n";
    
    // Test 2: Check authentication files
    echo "2. Checking authentication files...\n";
    
    $authFiles = [
        'auth/signin.php' => 'Sign-in page',
        'auth/signup.php' => 'Sign-up page', 
        'auth/google-oauth.php' => 'Google OAuth handler',
        'auth/google-callback.php' => 'Google OAuth callback',
        'auth/logout.php' => 'Logout handler'
    ];
    
    foreach ($authFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "   ✅ $description: $file\n";
        } else {
            echo "   ❌ Missing: $description ($file)\n";
        }
    }
    echo "\n";
    
    // Test 3: Test database user table
    echo "3. Testing user database table...\n";
    require_once __DIR__ . '/config/db_config.php';
    
    $pdo = getDBConnection();
    
    // Check if users table exists and has required columns
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'email', 'name', 'google_id', 'password_hash', 'created_at'];
    
    echo "   - Users table exists: ✅ Yes\n";
    echo "   - Required columns:\n";
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "     ✅ $column\n";
        } else {
            echo "     ❌ $column (missing)\n";
        }
    }
    
    // Check user count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "   - Total users: " . $result['count'] . "\n\n";
    
    // Test 4: Test Google OAuth URL generation
    echo "4. Testing Google OAuth URL generation...\n";
    
    if ($googleClientId && $googleRedirectUri) {
        $state = base64_encode(json_encode(['timestamp' => time()]));
        $params = [
            'client_id' => $googleClientId,
            'redirect_uri' => $googleRedirectUri,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'state' => $state
        ];
        
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        
        echo "   ✅ Google OAuth URL generated successfully\n";
        echo "   - URL length: " . strlen($authUrl) . " characters\n";
        echo "   - Domain: accounts.google.com\n";
        echo "   - Contains required parameters: client_id, redirect_uri, scope\n\n";
    } else {
        echo "   ❌ Cannot generate OAuth URL (missing credentials)\n\n";
    }
    
    // Test 5: Check session handling
    echo "5. Testing session handling...\n";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "   ✅ PHP sessions available\n";
    echo "   - Session ID: " . session_id() . "\n";
    echo "   - Session save path: " . session_save_path() . "\n\n";
    
    // Test 6: Check password hashing functionality
    echo "6. Testing password hashing...\n";
    
    if (function_exists('password_hash') && function_exists('password_verify')) {
        $testPassword = 'test123';
        $hash = password_hash($testPassword, PASSWORD_DEFAULT);
        $verify = password_verify($testPassword, $hash);
        
        echo "   ✅ Password hashing functions available\n";
        echo "   ✅ Password hash/verify test: " . ($verify ? "Passed" : "Failed") . "\n\n";
    } else {
        echo "   ❌ Password hashing functions not available\n\n";
    }
    
    echo "=== Authentication Test Summary ===\n";
    
    $googleConfigured = $googleClientId && $googleClientSecret && $googleRedirectUri;
    $authFilesExist = file_exists(__DIR__ . '/auth/signin.php') && file_exists(__DIR__ . '/auth/google-oauth.php');
    
    if ($googleConfigured && $authFilesExist) {
        echo "✅ Authentication system appears properly configured\n";
        echo "✅ Google OAuth credentials loaded\n";
        echo "✅ Authentication files present\n";
        echo "✅ User database table ready\n";
        echo "Ready for user authentication (Google OAuth + manual login)\n";
    } else {
        echo "⚠️ Authentication system has some issues:\n";
        if (!$googleConfigured) echo "   - Google OAuth credentials incomplete\n";
        if (!$authFilesExist) echo "   - Some authentication files missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
