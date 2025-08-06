<?php
require_once 'includes/email_service.php';

echo "<h2>CashControl Email System Test</h2>";

try {
    $emailService = new EmailService();
    
    echo "<h3>📧 Current Email Configuration:</h3>";
    echo "<ul>";
    echo "<li><strong>SMTP Host:</strong> shared58.cloud86-host.nl</li>";
    echo "<li><strong>Port:</strong> 587</li>";
    echo "<li><strong>From Email:</strong> noreply@123cashcontrol.com</li>";
    echo "<li><strong>From Name:</strong> CashControl</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Testing Email Functionality:</h3>";
    
    // Test welcome email to support address
    echo "<p>Testing welcome email to support@123cashcontrol.com...</p>";
    $welcomeTest = $emailService->sendWelcomeEmail('support@123cashcontrol.com', 'Support Test');
    
    if ($welcomeTest) {
        echo "<p>✅ <strong>Welcome email test PASSED</strong></p>";
        echo "<p>📬 Welcome email sent to: support@123cashcontrol.com</p>";
    } else {
        echo "<p>❌ <strong>Welcome email test FAILED</strong></p>";
        echo "<p>Check server logs for detailed error information.</p>";
    }
    
    // Test upgrade confirmation email
    echo "<p>Testing upgrade confirmation email to support@123cashcontrol.com...</p>";
    $upgradeTest = $emailService->sendUpgradeConfirmation('support@123cashcontrol.com', 'Support Test');
    
    if ($upgradeTest) {
        echo "<p>✅ <strong>Upgrade confirmation email test PASSED</strong></p>";
        echo "<p>📬 Upgrade email sent to: support@123cashcontrol.com</p>";
    } else {
        echo "<p>❌ <strong>Upgrade confirmation email test FAILED</strong></p>";
        echo "<p>Check server logs for detailed error information.</p>";
    }
    
    echo "<h3>📋 Current Email Types Available:</h3>";
    echo "<ul>";
    echo "<li>🎉 <strong>Welcome Email</strong> - Sent after Google OAuth signup</li>";
    echo "<li>🚀 <strong>Upgrade Confirmation</strong> - Sent after successful Stripe payment</li>";
    echo "<li>📅 <strong>Renewal Reminders</strong> - Available but not automatically scheduled</li>";
    echo "</ul>";
    
    echo "<h3>🔍 Email Triggers Currently Active:</h3>";
    echo "<ul>";
    echo "<li>✅ Google OAuth signup → Welcome email</li>";
    echo "<li>✅ Stripe payment success → Upgrade confirmation email</li>";
    echo "<li>❌ Regular signup → No email (not implemented)</li>";
    echo "<li>❌ Automatic reminders → No scheduler (not implemented)</li>";
    echo "</ul>";
    
    echo "<h3>💡 Next Steps to Test:</h3>";
    echo "<ol>";
    echo "<li>Try signing up with a new Google account to test welcome email</li>";
    echo "<li>Try upgrading to Pro to test upgrade confirmation email</li>";
    echo "<li>Check your email inbox and spam folder</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error testing email system:</strong> " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
h2 { color: #059669; }
h3 { color: #047857; margin-top: 30px; }
ul, ol { margin: 10px 0; padding-left: 30px; }
li { margin: 5px 0; }
p { margin: 10px 0; }
</style>
