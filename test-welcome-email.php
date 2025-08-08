<?php
// Test script to verify welcome email functionality
require_once 'includes/email_service.php';

echo "<h2>Welcome Email Test</h2>";

try {
    $emailService = new EmailService();
    
    echo "<h3>Testing Welcome Email Send</h3>";
    echo "<p>Sending welcome email to: tom@degruijterweb.nl</p>";
    
    $result = $emailService->sendWelcomeEmail('tom@degruijterweb.nl', 'Tom');
    
    if ($result) {
        echo "<p style='color: green;'>✅ Welcome email sent successfully!</p>";
        echo "<p>Check your inbox (and spam folder) for the welcome email.</p>";
    } else {
        echo "<p style='color: red;'>❌ Welcome email failed to send.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>What I Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Standard Signup (auth/signup.php)</strong>: Now calls sendWelcomeEmail() after user creation</li>";
echo "<li>✅ <strong>Google OAuth Signup (auth/google-callback.php)</strong>: Now calls sendWelcomeEmail() for new Google users</li>";
echo "<li>✅ <strong>Stripe Payment Success</strong>: Fixed method name from sendUpgradeConfirmationEmail() to sendUpgradeConfirmation()</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>Create a new test account to verify welcome email is sent</li>";
echo "<li>Complete a test payment to verify upgrade confirmation email works</li>";
echo "<li>Both signup flows should now send welcome emails automatically</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
ul { margin: 20px 0; }
li { margin: 5px 0; }
</style>
