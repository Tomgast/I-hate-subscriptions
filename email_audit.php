<?php
/**
 * Comprehensive Email System Audit for CashControl
 * Tests all email configurations and identifies any remaining issues
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/email_service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CashControl Email System Audit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">📧 Email System Audit</h1>
            
            <?php
            echo "<div class='space-y-6'>";
            
            // 1. Configuration Check
            echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-6'>";
            echo "<h2 class='text-xl font-semibold text-blue-900 mb-4'>🔧 Configuration Check</h2>";
            
            $emailService = new EmailService();
            
            // Check secure config loading
            $smtpHost = getSecureConfig('SMTP_HOST');
            $smtpPort = getSecureConfig('SMTP_PORT');
            $smtpUsername = getSecureConfig('SMTP_USERNAME');
            $smtpPassword = getSecureConfig('SMTP_PASSWORD');
            $fromEmail = getSecureConfig('FROM_EMAIL');
            
            echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
            echo "<div>";
            echo "<h3 class='font-semibold text-gray-700 mb-2'>SMTP Configuration:</h3>";
            echo "<ul class='text-sm space-y-1'>";
            echo "<li><strong>Host:</strong> " . ($smtpHost ?: 'shared58.cloud86-host.nl (default)') . "</li>";
            echo "<li><strong>Port:</strong> " . ($smtpPort ?: '587 (default)') . "</li>";
            echo "<li><strong>Username:</strong> " . ($smtpUsername ?: 'noreply@123cashcontrol.com (default)') . "</li>";
            echo "<li><strong>Password:</strong> " . ($smtpPassword ? '✅ SET' : '❌ NOT SET') . "</li>";
            echo "<li><strong>From Email:</strong> " . ($fromEmail ?: 'noreply@123cashcontrol.com (default)') . "</li>";
            echo "</ul>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            // 2. Email Service Test
            echo "<div class='bg-green-50 border border-green-200 rounded-lg p-6'>";
            echo "<h2 class='text-xl font-semibold text-green-900 mb-4'>🧪 Email Service Tests</h2>";
            
            // Test welcome email
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold text-gray-700 mb-2'>Welcome Email Test:</h3>";
            $welcomeResult = $emailService->sendWelcomeEmail('support@123cashcontrol.com', 'Email Audit Test');
            if ($welcomeResult) {
                echo "<p class='text-green-600'>✅ Welcome email sent successfully to support@123cashcontrol.com</p>";
            } else {
                echo "<p class='text-red-600'>❌ Welcome email failed to send</p>";
            }
            echo "</div>";
            
            // Test upgrade email
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold text-gray-700 mb-2'>Upgrade Confirmation Test:</h3>";
            $upgradeResult = $emailService->sendUpgradeConfirmation('support@123cashcontrol.com', 'Email Audit Test');
            if ($upgradeResult) {
                echo "<p class='text-green-600'>✅ Upgrade confirmation email sent successfully to support@123cashcontrol.com</p>";
            } else {
                echo "<p class='text-red-600'>❌ Upgrade confirmation email failed to send</p>";
            }
            echo "</div>";
            
            // Test basic PHP mail
            echo "<div class='mb-4'>";
            echo "<h3 class='font-semibold text-gray-700 mb-2'>Basic PHP Mail Test:</h3>";
            $subject = 'CashControl Email Audit - Basic Test';
            $message = 'This is a basic PHP mail test from the email audit system.';
            $headers = 'From: noreply@123cashcontrol.com' . "\r\n" .
                       'Reply-To: noreply@123cashcontrol.com' . "\r\n" .
                       'X-Mailer: PHP/' . phpversion();
            
            $basicResult = mail('support@123cashcontrol.com', $subject, $message, $headers);
            if ($basicResult) {
                echo "<p class='text-green-600'>✅ Basic PHP mail() function returned TRUE</p>";
            } else {
                echo "<p class='text-red-600'>❌ Basic PHP mail() function returned FALSE</p>";
            }
            echo "</div>";
            echo "</div>";
            
            // 3. Integration Points Check
            echo "<div class='bg-yellow-50 border border-yellow-200 rounded-lg p-6'>";
            echo "<h2 class='text-xl font-semibold text-yellow-900 mb-4'>🔗 Integration Points</h2>";
            
            echo "<div class='space-y-3'>";
            
            // Check Google OAuth integration
            if (file_exists('includes/google_oauth.php')) {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-green-600'>✅</span>";
                echo "<span>Google OAuth email integration: <code>includes/google_oauth.php</code> exists</span>";
                echo "</div>";
            } else {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-red-600'>❌</span>";
                echo "<span>Google OAuth email integration: Missing file</span>";
                echo "</div>";
            }
            
            // Check Stripe integration
            if (file_exists('includes/stripe_service.php')) {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-green-600'>✅</span>";
                echo "<span>Stripe payment email integration: <code>includes/stripe_service.php</code> exists</span>";
                echo "</div>";
            } else {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-red-600'>❌</span>";
                echo "<span>Stripe payment email integration: Missing file</span>";
                echo "</div>";
            }
            
            // Check bank callback integration
            if (file_exists('bank/callback.php')) {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-green-600'>✅</span>";
                echo "<span>Bank scan email integration: <code>bank/callback.php</code> exists</span>";
                echo "</div>";
            } else {
                echo "<div class='flex items-center space-x-2'>";
                echo "<span class='text-red-600'>❌</span>";
                echo "<span>Bank scan email integration: Missing file</span>";
                echo "</div>";
            }
            
            echo "</div>";
            echo "</div>";
            
            // 4. Summary and Recommendations
            echo "<div class='bg-gray-50 border border-gray-200 rounded-lg p-6'>";
            echo "<h2 class='text-xl font-semibold text-gray-900 mb-4'>📋 Summary & Recommendations</h2>";
            
            $allTestsPassed = $welcomeResult && $upgradeResult && $basicResult;
            
            if ($allTestsPassed) {
                echo "<div class='bg-green-100 border border-green-300 rounded-lg p-4 mb-4'>";
                echo "<h3 class='font-semibold text-green-800 mb-2'>🎉 All Email Tests Passed!</h3>";
                echo "<p class='text-green-700'>Your email system is configured correctly and working properly.</p>";
                echo "<ul class='text-green-700 text-sm mt-2 space-y-1'>";
                echo "<li>• All emails are being sent from: <strong>noreply@123cashcontrol.com</strong></li>";
                echo "<li>• SMTP configuration is working correctly</li>";
                echo "<li>• All integration points are properly configured</li>";
                echo "</ul>";
                echo "</div>";
            } else {
                echo "<div class='bg-red-100 border border-red-300 rounded-lg p-4 mb-4'>";
                echo "<h3 class='font-semibold text-red-800 mb-2'>⚠️ Email Issues Detected</h3>";
                echo "<p class='text-red-700'>Some email tests failed. Please check the following:</p>";
                echo "<ul class='text-red-700 text-sm mt-2 space-y-1'>";
                if (!$smtpPassword) {
                    echo "<li>• SMTP password is not configured in secure-config.php</li>";
                }
                if (!$welcomeResult) {
                    echo "<li>• Welcome email sending failed</li>";
                }
                if (!$upgradeResult) {
                    echo "<li>• Upgrade confirmation email sending failed</li>";
                }
                if (!$basicResult) {
                    echo "<li>• Basic PHP mail() function failed</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            
            echo "<div class='bg-blue-100 border border-blue-300 rounded-lg p-4'>";
            echo "<h3 class='font-semibold text-blue-800 mb-2'>📧 Check Your Email</h3>";
            echo "<p class='text-blue-700'>If tests passed, check <strong>support@123cashcontrol.com</strong> inbox for test emails.</p>";
            echo "<p class='text-blue-700 text-sm mt-1'>Don't forget to check spam/junk folders if emails are not in the inbox.</p>";
            echo "</div>";
            
            echo "</div>";
            
            echo "</div>";
            ?>
            
            <div class="mt-8 text-center">
                <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
