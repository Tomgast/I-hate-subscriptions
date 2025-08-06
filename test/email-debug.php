<?php
session_start();
require_once __DIR__ . '/../includes/email_service.php';

$testResult = null;
$error = null;

if ($_POST['send_test'] ?? false) {
    try {
        $emailService = new EmailService();
        
        // Try to send a simple test email
        $to = 'info@123cashcontrol.com';
        $subject = 'CashControl Email Test - ' . date('Y-m-d H:i:s');
        $body = '<h2>Email Test</h2><p>This is a test email sent at ' . date('Y-m-d H:i:s') . '</p><p>If you receive this, your email configuration is working!</p>';
        
        $testResult = $emailService->sendEmail($to, $subject, $body);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Debug - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">📧 Email Debug Tool</h1>
            
            <div class="space-y-6">
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Email Configuration Status</h2>
                    
                    <?php
                    try {
                        $emailService = new EmailService();
                        
                        // Get current configuration via reflection
                        $reflection = new ReflectionClass($emailService);
                        $smtpHost = $reflection->getProperty('smtpHost');
                        $smtpHost->setAccessible(true);
                        $smtpPort = $reflection->getProperty('smtpPort');
                        $smtpPort->setAccessible(true);
                        $smtpUsername = $reflection->getProperty('smtpUsername');
                        $smtpUsername->setAccessible(true);
                        $smtpPassword = $reflection->getProperty('smtpPassword');
                        $smtpPassword->setAccessible(true);
                        $fromEmail = $reflection->getProperty('fromEmail');
                        $fromEmail->setAccessible(true);
                        
                        echo "<div class='space-y-2 text-sm'>";
                        echo "<p><strong>SMTP Host:</strong> " . htmlspecialchars($smtpHost->getValue($emailService)) . "</p>";
                        echo "<p><strong>SMTP Port:</strong> " . htmlspecialchars($smtpPort->getValue($emailService)) . "</p>";
                        echo "<p><strong>SMTP Username:</strong> " . htmlspecialchars($smtpUsername->getValue($emailService)) . "</p>";
                        echo "<p><strong>SMTP Password:</strong> " . (empty($smtpPassword->getValue($emailService)) ? "❌ NOT SET" : "✅ SET") . "</p>";
                        echo "<p><strong>From Email:</strong> " . htmlspecialchars($fromEmail->getValue($emailService)) . "</p>";
                        echo "</div>";
                        
                        if (empty($smtpPassword->getValue($emailService))) {
                            echo "<div class='bg-red-100 text-red-800 p-3 rounded mt-4'>";
                            echo "❌ <strong>SMTP Password is missing!</strong><br>";
                            echo "Add your Plesk email password to secure-config.php:<br>";
                            echo "<code>'SMTP_PASSWORD' => 'your-actual-email-password'</code>";
                            echo "</div>";
                        } else {
                            echo "<div class='bg-green-100 text-green-800 p-3 rounded mt-4'>";
                            echo "✅ Email configuration appears complete";
                            echo "</div>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='bg-red-100 text-red-800 p-3 rounded'>";
                        echo "❌ Error loading email service: " . htmlspecialchars($e->getMessage());
                        echo "</div>";
                    }
                    ?>
                </div>
                
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Send Test Email</h2>
                    
                    <?php if ($testResult !== null): ?>
                        <?php if ($testResult): ?>
                            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                                ✅ Test email sent successfully to info@123cashcontrol.com!<br>
                                Check your email inbox (and spam folder).
                            </div>
                        <?php else: ?>
                            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                                ❌ Failed to send test email.<br>
                                This usually means SMTP credentials are incorrect or missing.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                            ❌ Error: <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <button type="submit" name="send_test" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Send Test Email to info@123cashcontrol.com
                        </button>
                    </form>
                    
                    <p class="text-sm text-gray-600 mt-4">
                        This will send a test email to info@123cashcontrol.com using your configured SMTP settings.
                    </p>
                </div>
                
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Troubleshooting</h2>
                    
                    <div class="space-y-3 text-sm">
                        <div class="bg-yellow-50 p-3 rounded">
                            <strong>If emails aren't being received:</strong>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Check your spam/junk folder</li>
                                <li>Verify SMTP password is correct in secure-config.php</li>
                                <li>Ensure info@123cashcontrol.com email account exists in Plesk</li>
                                <li>Check Plesk email logs for delivery issues</li>
                            </ul>
                        </div>
                        
                        <div class="bg-blue-50 p-3 rounded">
                            <strong>Common Plesk SMTP Issues:</strong>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Wrong SMTP port (should be 587 for TLS)</li>
                                <li>Incorrect email password</li>
                                <li>Email account not properly configured in Plesk</li>
                                <li>Server firewall blocking SMTP</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <a href="test-connections.php" class="bg-indigo-500 text-white px-6 py-3 rounded-lg hover:bg-indigo-600 inline-block">
                    Back to Connection Tests
                </a>
            </div>
        </div>
    </div>
</body>
</html>
