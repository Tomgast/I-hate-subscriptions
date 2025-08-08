<?php
require_once 'includes/email_service.php';
require_once 'config/secure_loader.php';

echo "<h2>Email System Diagnostic Test</h2>";

// Test 1: Check SMTP Configuration
echo "<h3>1. SMTP Configuration Check</h3>";
echo "<pre>";
echo "SMTP_HOST: " . (getSecureConfig('SMTP_HOST') ?: 'shared58.cloud86-host.nl') . "\n";
echo "SMTP_PORT: " . (getSecureConfig('SMTP_PORT') ?: '587') . "\n";
echo "SMTP_USERNAME: " . (getSecureConfig('SMTP_USERNAME') ?: 'info@123cashcontrol.com') . "\n";
echo "SMTP_PASSWORD: " . (getSecureConfig('SMTP_PASSWORD') ? '[SET]' : '[NOT SET]') . "\n";
echo "FROM_EMAIL: " . (getSecureConfig('FROM_EMAIL') ?: 'info@123cashcontrol.com') . "\n";
echo "FROM_NAME: " . (getSecureConfig('FROM_NAME') ?: 'CashControl') . "\n";
echo "</pre>";

// Test 2: Check PHP mail() function availability
echo "<h3>2. PHP Mail Function Check</h3>";
echo "<pre>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available\n";
} else {
    echo "❌ PHP mail() function is NOT available\n";
}

// Check mail configuration
$mailConfig = ini_get_all('mail');
if ($mailConfig) {
    echo "Mail configuration found:\n";
    foreach (['SMTP', 'smtp_port', 'sendmail_from'] as $key) {
        if (isset($mailConfig[$key])) {
            echo "  $key: " . $mailConfig[$key]['local_value'] . "\n";
        }
    }
} else {
    echo "No mail configuration found in PHP\n";
}
echo "</pre>";

// Test 3: Create EmailService and test basic functionality
echo "<h3>3. EmailService Class Test</h3>";
echo "<pre>";
try {
    $emailService = new EmailService();
    echo "✅ EmailService created successfully\n";
    
    // Test methods exist
    $methods = ['sendEmail', 'sendWelcomeEmail', 'sendUpgradeConfirmation', 'sendTestEmail'];
    foreach ($methods as $method) {
        if (method_exists($emailService, $method)) {
            echo "✅ Method $method exists\n";
        } else {
            echo "❌ Method $method missing\n";
        }
    }
} catch (Exception $e) {
    echo "❌ EmailService creation failed: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 4: Send test email
echo "<h3>4. Send Test Email</h3>";
echo "<pre>";
try {
    $testEmail = 'tom@degruijterweb.nl'; // Your email
    $result = $emailService->sendTestEmail($testEmail, 'Tom');
    
    if ($result) {
        echo "✅ Test email sent successfully to $testEmail\n";
        echo "Check your inbox (and spam folder) for the test email\n";
    } else {
        echo "❌ Test email failed to send\n";
    }
} catch (Exception $e) {
    echo "❌ Test email error: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 5: Send welcome email
echo "<h3>5. Send Welcome Email Test</h3>";
echo "<pre>";
try {
    $result = $emailService->sendWelcomeEmail('tom@degruijterweb.nl', 'Tom');
    
    if ($result) {
        echo "✅ Welcome email sent successfully\n";
    } else {
        echo "❌ Welcome email failed to send\n";
    }
} catch (Exception $e) {
    echo "❌ Welcome email error: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 6: Send upgrade confirmation email
echo "<h3>6. Send Upgrade Confirmation Email Test</h3>";
echo "<pre>";
try {
    $result = $emailService->sendUpgradeConfirmation('tom@degruijterweb.nl', 'Tom');
    
    if ($result) {
        echo "✅ Upgrade confirmation email sent successfully\n";
    } else {
        echo "❌ Upgrade confirmation email failed to send\n";
    }
} catch (Exception $e) {
    echo "❌ Upgrade confirmation email error: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 7: Check server mail logs (if accessible)
echo "<h3>7. Server Information</h3>";
echo "<pre>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Operating System: " . PHP_OS . "\n";
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "\n";
echo "</pre>";

echo "<h3>8. Recommendations</h3>";
echo "<pre>";
echo "If emails are not being delivered:\n";
echo "1. Check your spam/junk folder\n";
echo "2. Verify SMTP credentials in secure-config.php\n";
echo "3. Check Plesk email settings and logs\n";
echo "4. Consider using PHPMailer with SMTP authentication\n";
echo "5. Test with a different email address\n";
echo "</pre>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981; }
</style>
