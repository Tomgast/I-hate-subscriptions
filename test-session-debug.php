<?php
/**
 * Simple Session Debugging Script
 * Helps identify why users are being redirected to signin.php during payment flow
 */

session_start();

echo "<h1>Session Debug - Payment Flow Issue</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .error { color: red; } .success { color: green; } .info { color: blue; } .warning { color: orange; }</style>";

echo "<h2>Current Session Status</h2>";

// Check session basics
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . " (1=disabled, 2=active)</p>";

// Check session data
echo "<h3>Session Data:</h3>";
if (!empty($_SESSION)) {
    echo "<div class='success'>";
    echo "<p>✅ Session contains data:</p>";
    foreach ($_SESSION as $key => $value) {
        if (is_string($value)) {
            echo "<p>- <strong>$key:</strong> " . htmlspecialchars($value) . "</p>";
        } else {
            echo "<p>- <strong>$key:</strong> " . gettype($value) . "</p>";
        }
    }
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ No session data found</p>";
    echo "</div>";
}

// Check authentication status (same logic as checkout.php)
echo "<h3>Authentication Check (checkout.php logic):</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>";
    echo "<p>✅ User is authenticated - checkout.php would proceed</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User Email: " . ($_SESSION['user_email'] ?? 'Not set') . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ User is NOT authenticated - checkout.php would redirect to signin.php</p>";
    echo "<p>This is exactly what's happening in your payment flow!</p>";
    echo "</div>";
}

// Test session persistence
echo "<h3>Session Persistence Test:</h3>";
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    echo "<p class='info'>First visit - set test counter to 1</p>";
} else {
    $_SESSION['test_counter']++;
    echo "<p class='success'>✅ Session persists - counter is now: " . $_SESSION['test_counter'] . "</p>";
}

// Check cookies
echo "<h3>Session Cookie Information:</h3>";
$cookieParams = session_get_cookie_params();
echo "<p>Cookie Lifetime: " . $cookieParams['lifetime'] . " seconds</p>";
echo "<p>Cookie Path: " . $cookieParams['path'] . "</p>";
echo "<p>Cookie Domain: " . $cookieParams['domain'] . "</p>";
echo "<p>Cookie Secure: " . ($cookieParams['secure'] ? 'Yes' : 'No') . "</p>";
echo "<p>Cookie HttpOnly: " . ($cookieParams['httponly'] ? 'Yes' : 'No') . "</p>";

// Check if session cookie exists in browser
$sessionName = session_name();
echo "<p>Session Name: " . $sessionName . "</p>";
if (isset($_COOKIE[$sessionName])) {
    echo "<p class='success'>✅ Session cookie exists in browser</p>";
} else {
    echo "<p class='error'>❌ Session cookie NOT found in browser</p>";
}

echo "<h2>Troubleshooting Steps</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>";
    echo "<h3>❌ Main Issue: User not logged in</h3>";
    echo "<p>The payment flow fails because the user session is not maintained.</p>";
    echo "<h4>Possible causes:</h4>";
    echo "<ul>";
    echo "<li>User never logged in properly</li>";
    echo "<li>Session expired or was destroyed</li>";
    echo "<li>Session cookies not being set/sent</li>";
    echo "<li>Session configuration issues</li>";
    echo "<li>Cross-domain/subdomain issues</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><a href='auth/signin.php'>Try logging in</a></li>";
    echo "<li>After login, <a href='test-session-debug.php'>return to this page</a></li>";
    echo "<li>Check if session data persists</li>";
    echo "<li>Then try the <a href='payment/checkout.php?plan=monthly'>payment flow</a></li>";
    echo "</ol>";
} else {
    echo "<div class='success'>";
    echo "<h3>✅ User is logged in</h3>";
    echo "<p>Session authentication is working. You can proceed with payment testing.</p>";
    echo "<p><a href='payment/checkout.php?plan=monthly'>Test Payment Flow</a></p>";
    echo "</div>";
}

echo "<h2>Additional Debug Information</h2>";
echo "<p><strong>PHP Session Configuration:</strong></p>";
echo "<ul>";
echo "<li>session.save_path: " . ini_get('session.save_path') . "</li>";
echo "<li>session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "</li>";
echo "<li>session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "</li>";
echo "<li>session.use_cookies: " . ini_get('session.use_cookies') . "</li>";
echo "<li>session.use_only_cookies: " . ini_get('session.use_only_cookies') . "</li>";
echo "</ul>";

echo "<h3>Manual Session Test</h3>";
echo "<p>Refresh this page multiple times to see if the test counter increases.</p>";
echo "<p>If it doesn't increase, sessions are not persisting properly.</p>";

echo "<hr>";
echo "<p><small>Debug script created to troubleshoot payment flow redirect issue</small></p>";
?>
