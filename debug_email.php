<?php
require_once 'config/db_config.php';

echo "<h2>🔍 Email Configuration Debug</h2>";

try {
    echo "<h3>1. Testing getSecureConfig function:</h3>";
    
    // Test if getSecureConfig function exists and works
    if (function_exists('getSecureConfig')) {
        echo "<p>✅ getSecureConfig function exists</p>";
        
        $smtpHost = getSecureConfig('SMTP_HOST');
        $smtpPort = getSecureConfig('SMTP_PORT');
        $smtpUsername = getSecureConfig('SMTP_USERNAME');
        $smtpPassword = getSecureConfig('SMTP_PASSWORD');
        
        echo "<ul>";
        echo "<li><strong>SMTP_HOST:</strong> " . ($smtpHost ?: 'NOT SET (using default: shared58.cloud86-host.nl)') . "</li>";
        echo "<li><strong>SMTP_PORT:</strong> " . ($smtpPort ?: 'NOT SET (using default: 587)') . "</li>";
        echo "<li><strong>SMTP_USERNAME:</strong> " . ($smtpUsername ?: 'NOT SET (using default: info@123cashcontrol.com)') . "</li>";
        echo "<li><strong>SMTP_PASSWORD:</strong> " . ($smtpPassword ? '***SET***' : 'NOT SET') . "</li>";
        echo "</ul>";
        
        if (!$smtpPassword) {
            echo "<p>❌ <strong>CRITICAL: SMTP_PASSWORD is not configured!</strong></p>";
            echo "<p>This is likely why emails are not being sent.</p>";
        }
        
    } else {
        echo "<p>❌ getSecureConfig function does not exist</p>";
    }
    
    echo "<h3>2. Testing PHP mail() function:</h3>";
    
    // Test basic PHP mail function
    $testEmail = 'tom@degruijterweb.nl'; // Send to your test email
    $subject = 'CashControl Email Test - Basic';
    $message = 'This is a basic PHP mail test from CashControl using noreply@123cashcontrol.com.';
    $headers = 'From: noreply@123cashcontrol.com' . "\r\n" .
               'Reply-To: noreply@123cashcontrol.com' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    $basicMailResult = mail($testEmail, $subject, $message, $headers);
    
    if ($basicMailResult) {
        echo "<p>✅ PHP mail() function returned TRUE</p>";
        echo "<p>📧 Basic test email sent to: $testEmail</p>";
    } else {
        echo "<p>❌ PHP mail() function returned FALSE</p>";
        echo "<p>This indicates a server-level email configuration issue.</p>";
    }
    
    echo "<h3>3. Server Email Configuration Check:</h3>";
    
    // Check PHP mail configuration
    $sendmailPath = ini_get('sendmail_path');
    $smtpServer = ini_get('SMTP');
    $smtpPort = ini_get('smtp_port');
    
    echo "<ul>";
    echo "<li><strong>sendmail_path:</strong> " . ($sendmailPath ?: 'Not set') . "</li>";
    echo "<li><strong>SMTP server:</strong> " . ($smtpServer ?: 'Not set') . "</li>";
    echo "<li><strong>SMTP port:</strong> " . ($smtpPort ?: 'Not set') . "</li>";
    echo "</ul>";
    
    echo "<h3>4. Plesk Email Configuration Requirements:</h3>";
    echo "<div style='background: #f0f9ff; padding: 15px; border-left: 4px solid #0ea5e9; margin: 15px 0;'>";
    echo "<p><strong>For Plesk hosting, you typically need:</strong></p>";
    echo "<ul>";
    echo "<li>SMTP Host: shared58.cloud86-host.nl</li>";
    echo "<li>SMTP Port: 587 (TLS) or 465 (SSL)</li>";
    echo "<li>SMTP Username: info@123cashcontrol.com</li>";
    echo "<li>SMTP Password: [Your email account password]</li>";
    echo "</ul>";
    echo "<p><strong>These credentials should be stored in your secure-config.php file outside the web directory.</strong></p>";
    echo "</div>";
    
    echo "<h3>5. Recommended Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Create/Update secure-config.php:</strong> Add SMTP credentials</li>";
    echo "<li><strong>Verify email account:</strong> Ensure info@123cashcontrol.com exists and password is correct</li>";
    echo "<li><strong>Test SMTP connection:</strong> Use a more robust SMTP library if needed</li>";
    echo "<li><strong>Check server logs:</strong> Look for email-related errors in server logs</li>";
    echo "</ol>";
    
    echo "<h3>6. Sample secure-config.php content:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;'>";
    echo htmlspecialchars("<?php
return [
    'db_host' => '45.82.188.227',
    'db_port' => '3306',
    'db_name' => 'vxmjmwlj_',
    'db_user' => '123cashcontrol',
    'db_password' => 'Super-mannetje45',
    
    // Email configuration
    'SMTP_HOST' => 'shared58.cloud86-host.nl',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => 'info@123cashcontrol.com',
    'SMTP_PASSWORD' => 'YOUR_EMAIL_PASSWORD_HERE',
    'FROM_EMAIL' => 'info@123cashcontrol.com',
    'FROM_NAME' => 'CashControl',
    
    // Other credentials...
    'google_client_id' => '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com',
    'google_client_secret' => 'YOUR_GOOGLE_SECRET',
    'stripe_secret_key' => 'YOUR_STRIPE_SECRET',
    // etc...
];
?>");
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error during email debug:</strong> " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; max-width: 1000px; }
h2 { color: #059669; }
h3 { color: #047857; margin-top: 30px; }
ul, ol { margin: 10px 0; padding-left: 30px; }
li { margin: 5px 0; }
p { margin: 10px 0; }
pre { overflow-x: auto; }
</style>
