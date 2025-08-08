<?php
/**
 * Email/SMTP Integration Test
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Email/SMTP Integration Test ===\n\n";

try {
    // Test 1: Load secure configuration
    echo "1. Testing SMTP configuration loading...\n";
    require_once __DIR__ . '/config/secure_loader.php';
    
    $smtpHost = getSecureConfig('SMTP_HOST');
    $smtpPort = getSecureConfig('SMTP_PORT');
    $smtpUser = getSecureConfig('SMTP_USER');
    $smtpPass = getSecureConfig('SMTP_PASS');
    $smtpFrom = getSecureConfig('SMTP_FROM_EMAIL');
    
    echo "   - SMTP Host: " . ($smtpHost ? "✅ $smtpHost" : "❌ Missing") . "\n";
    echo "   - SMTP Port: " . ($smtpPort ? "✅ $smtpPort" : "❌ Missing") . "\n";
    echo "   - SMTP User: " . ($smtpUser ? "✅ $smtpUser" : "❌ Missing") . "\n";
    echo "   - SMTP Password: " . ($smtpPass ? "✅ Loaded" : "❌ Missing") . "\n";
    echo "   - From Email: " . ($smtpFrom ? "✅ $smtpFrom" : "❌ Missing") . "\n\n";
    
    // Test 2: Load EmailService
    echo "2. Loading EmailService...\n";
    
    // Check if EmailService exists
    if (file_exists(__DIR__ . '/includes/email_service.php')) {
        require_once __DIR__ . '/includes/email_service.php';
        
        if (class_exists('EmailService')) {
            $emailService = new EmailService();
            echo "   ✅ EmailService loaded successfully\n\n";
        } else {
            echo "   ❌ EmailService class not found in file\n\n";
        }
    } else {
        echo "   ❌ EmailService file not found\n";
        echo "   Looking for alternative email implementations...\n";
        
        // Check for other email files
        $emailFiles = [
            'includes/email.php',
            'config/email.php',
            'includes/mailer.php'
        ];
        
        foreach ($emailFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "   ✅ Found: $file\n";
            }
        }
        echo "\n";
    }
    
    // Test 3: Test SMTP connection
    echo "3. Testing SMTP connection...\n";
    
    if ($smtpHost && $smtpPort && $smtpUser && $smtpPass) {
        // Test SMTP connection using fsockopen
        $connection = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
        
        if ($connection) {
            echo "   ✅ SMTP server connection successful\n";
            echo "   - Connected to $smtpHost:$smtpPort\n";
            fclose($connection);
        } else {
            echo "   ❌ SMTP server connection failed\n";
            echo "   - Error: $errstr ($errno)\n";
        }
        echo "\n";
    } else {
        echo "   ⚠️ Skipping SMTP connection test (missing credentials)\n\n";
    }
    
    // Test 4: Check for email templates
    echo "4. Checking email templates...\n";
    
    $templateDirs = [
        'templates/email/',
        'includes/templates/',
        'email/templates/',
        'templates/'
    ];
    
    $templatesFound = false;
    foreach ($templateDirs as $dir) {
        if (is_dir(__DIR__ . '/' . $dir)) {
            $templates = glob(__DIR__ . '/' . $dir . '*.{php,html}', GLOB_BRACE);
            if (!empty($templates)) {
                echo "   ✅ Found templates in: $dir\n";
                foreach ($templates as $template) {
                    echo "     - " . basename($template) . "\n";
                }
                $templatesFound = true;
            }
        }
    }
    
    if (!$templatesFound) {
        echo "   ⚠️ No email templates found in common locations\n";
    }
    echo "\n";
    
    // Test 5: Test email functionality (if available)
    echo "5. Testing email functionality...\n";
    
    if (isset($emailService) && $smtpHost) {
        echo "   ✅ Email service and SMTP configured\n";
        echo "   - Ready to send emails\n";
        echo "   - Test email can be sent to verify functionality\n";
    } else {
        echo "   ⚠️ Email functionality limited\n";
        echo "   - Missing EmailService class or SMTP configuration\n";
    }
    echo "\n";
    
    echo "=== Email Test Summary ===\n";
    if ($smtpHost && $smtpUser && $smtpPass) {
        echo "✅ SMTP configuration appears complete\n";
        if (isset($emailService)) {
            echo "✅ EmailService is available\n";
            echo "Ready for email notifications\n";
        } else {
            echo "⚠️ EmailService needs to be implemented or fixed\n";
        }
    } else {
        echo "❌ SMTP configuration incomplete\n";
        echo "Missing credentials need to be added to secure-config.php\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
