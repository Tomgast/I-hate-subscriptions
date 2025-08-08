<?php
/**
 * Comprehensive Payment Session and Email Diagnostic Script
 * Tests session handling, Stripe configuration, and email delivery
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/stripe_service.php';

echo "<h1>Payment Session & Email Diagnostic Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<h2>1. Session Debugging</h2>";

// Check session configuration
echo "<h3>Session Configuration:</h3>";
echo "<p class='info'>Session ID: " . session_id() . "</p>";
echo "<p class='info'>Session Status: " . session_status() . " (1=disabled, 2=active)</p>";
echo "<p class='info'>Session Save Path: " . session_save_path() . "</p>";
echo "<p class='info'>Session Cookie Params: " . json_encode(session_get_cookie_params()) . "</p>";

// Check current session data
echo "<h3>Current Session Data:</h3>";
if (!empty($_SESSION)) {
    echo "<p class='success'>✅ Session data exists:</p>";
    foreach ($_SESSION as $key => $value) {
        if (is_string($value)) {
            echo "<p>- $key: " . htmlspecialchars($value) . "</p>";
        } else {
            echo "<p>- $key: " . gettype($value) . "</p>";
        }
    }
} else {
    echo "<p class='error'>❌ No session data found</p>";
}

// Check if user is logged in (same logic as checkout.php)
echo "<h3>Authentication Check:</h3>";
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Unknown';
    $userEmail = $_SESSION['user_email'] ?? 'Unknown';
    
    echo "<p class='success'>✅ User is logged in:</p>";
    echo "<p>- User ID: $userId</p>";
    echo "<p>- Name: $userName</p>";
    echo "<p>- Email: $userEmail</p>";
    
    // Test database connection for this user
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, email, name, subscription_type, subscription_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p class='success'>✅ User found in database:</p>";
            echo "<p>- DB Email: " . $user['email'] . "</p>";
            echo "<p>- DB Name: " . $user['name'] . "</p>";
            echo "<p>- Subscription Type: " . $user['subscription_type'] . "</p>";
            echo "<p>- Subscription Status: " . $user['subscription_status'] . "</p>";
        } else {
            echo "<p class='error'>❌ User not found in database with ID: $userId</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p class='error'>❌ User is NOT logged in (no user_id in session)</p>";
    echo "<p class='warning'>This is why checkout.php redirects to signin.php</p>";
}

echo "<h2>2. Stripe Configuration Test</h2>";

try {
    $stripeService = new StripeService();
    $configTest = $stripeService->testConfiguration();
    
    if ($configTest['configured']) {
        echo "<p class='success'>✅ Stripe configuration is valid</p>";
        echo "<p class='info'>Publishable Key: " . substr($configTest['publishable_key'], 0, 12) . "...</p>";
        
        // Test payment session creation if user is logged in
        if (isset($_SESSION['user_id'])) {
            echo "<h3>Testing Payment Session Creation:</h3>";
            
            $session = $stripeService->createCheckoutSession(
                $_SESSION['user_id'],
                $_SESSION['user_email'] ?? 'test@example.com',
                'monthly',
                'https://123cashcontrol.com/payment/success.php',
                'https://123cashcontrol.com/upgrade.php?cancelled=1'
            );
            
            if ($session && isset($session['url'])) {
                echo "<p class='success'>✅ Payment session created successfully!</p>";
                echo "<p class='info'>Session ID: " . $session['id'] . "</p>";
                echo "<p class='info'>Checkout URL: <a href='" . $session['url'] . "' target='_blank'>Test Checkout</a></p>";
            } else {
                echo "<p class='error'>❌ Failed to create payment session</p>";
                echo "<p class='error'>Check PHP error logs for detailed Stripe API errors</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Cannot test payment session creation - user not logged in</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Stripe configuration errors:</p>";
        foreach ($configTest['errors'] as $error) {
            echo "<p class='error'>- $error</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Stripe service error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Email Configuration Test</h2>";

try {
    // Test email configuration
    require_once 'includes/email_service.php';
    
    echo "<h3>Email Service Test:</h3>";
    
    // Check if email service can be initialized
    $emailService = new EmailService();
    echo "<p class='success'>✅ EmailService initialized</p>";
    
    // Test email configuration
    $config = getSecureConfig();
    $emailKeys = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD'];
    
    echo "<h4>SMTP Configuration:</h4>";
    foreach ($emailKeys as $key) {
        $value = $config[$key] ?? 'Not set';
        if ($key === 'SMTP_PASSWORD') {
            $value = !empty($value) ? 'Set (hidden)' : 'Not set';
        }
        echo "<p>- $key: $value</p>";
    }
    
    // Test sending a simple email if user is logged in
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
        echo "<h4>Test Email Sending:</h4>";
        echo "<p class='info'>Attempting to send test email to: " . $_SESSION['user_email'] . "</p>";
        
        $testResult = $emailService->sendTestEmail($_SESSION['user_email'], $_SESSION['user_name'] ?? 'User');
        
        if ($testResult) {
            echo "<p class='success'>✅ Test email sent successfully!</p>";
            echo "<p class='info'>Check your inbox (and spam folder) for the test email</p>";
        } else {
            echo "<p class='error'>❌ Failed to send test email</p>";
            echo "<p class='error'>Check PHP error logs for detailed SMTP errors</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Cannot test email sending - user not logged in</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Email service error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Session Troubleshooting</h2>";

echo "<h3>Common Session Issues:</h3>";
echo "<ul>";
echo "<li><strong>Session cookies not being set:</strong> Check browser developer tools → Application → Cookies</li>";
echo "<li><strong>Session path issues:</strong> Verify session.save_path is writable</li>";
echo "<li><strong>Domain/subdomain issues:</strong> Session cookies might not work across subdomains</li>";
echo "<li><strong>HTTPS/HTTP mismatch:</strong> Session cookies might be restricted to HTTPS</li>";
echo "<li><strong>Session timeout:</strong> Sessions might be expiring too quickly</li>";
echo "</ul>";

echo "<h3>Manual Session Test:</h3>";
echo "<p>If you're not logged in, try logging in first, then return to this page.</p>";
echo "<p><a href='auth/signin.php'>Go to Sign In</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";

echo "<h2>5. Next Steps</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'><strong>Priority Issue:</strong> Session authentication is not working</p>";
    echo "<ol>";
    echo "<li>Log in through the normal flow</li>";
    echo "<li>Check if session persists across page loads</li>";
    echo "<li>Verify session cookies are being set in browser</li>";
    echo "<li>Check PHP session configuration on server</li>";
    echo "</ol>";
} else {
    echo "<p class='success'><strong>Session OK:</strong> Focus on Stripe and email issues</p>";
    echo "<ol>";
    echo "<li>Check PHP error logs for Stripe API errors</li>";
    echo "<li>Verify Stripe keys are for correct environment (test vs live)</li>";
    echo "<li>Test email delivery and check SMTP logs</li>";
    echo "</ol>";
}

echo "<p><strong>Error Log Locations to Check:</strong></p>";
echo "<ul>";
echo "<li>PHP Error Log: Usually in /var/log/php_errors.log or similar</li>";
echo "<li>Apache Error Log: Usually in /var/log/apache2/error.log</li>";
echo "<li>Plesk Error Logs: Check Plesk control panel → Logs</li>";
echo "</ul>";
?>
