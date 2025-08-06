<?php
// Simple email test with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$result = null;
$error = null;
$debug_info = [];

if ($_POST['send_test'] ?? false) {
    try {
        // Test 1: Check if secure config file exists
        $configPath = dirname(__DIR__) . '/secure-config.php';
        $debug_info[] = "Config path: " . $configPath;
        $debug_info[] = "Config exists: " . (file_exists($configPath) ? "YES" : "NO");
        
        if (file_exists($configPath)) {
            $config = include $configPath;
            $debug_info[] = "Config loaded: " . (is_array($config) ? "YES" : "NO");
            
            // Test 2: Check SMTP settings
            $smtpHost = $config['SMTP_HOST'] ?? 'NOT SET';
            $smtpPort = $config['SMTP_PORT'] ?? 'NOT SET';
            $smtpUser = $config['SMTP_USERNAME'] ?? 'NOT SET';
            $smtpPass = $config['SMTP_PASSWORD'] ?? 'NOT SET';
            $fromEmail = $config['FROM_EMAIL'] ?? 'NOT SET';
            
            $debug_info[] = "SMTP Host: " . $smtpHost;
            $debug_info[] = "SMTP Port: " . $smtpPort;
            $debug_info[] = "SMTP User: " . $smtpUser;
            $debug_info[] = "SMTP Pass: " . (empty($smtpPass) ? "NOT SET" : "SET (length: " . strlen($smtpPass) . ")");
            $debug_info[] = "From Email: " . $fromEmail;
            
            // Test 3: Try simple PHP mail() first
            $to = 'info@123cashcontrol.com';
            $subject = 'Simple Email Test - ' . date('Y-m-d H:i:s');
            $message = 'This is a simple test email sent at ' . date('Y-m-d H:i:s');
            $headers = "From: $fromEmail\r\nContent-Type: text/html; charset=UTF-8\r\n";
            
            $debug_info[] = "Attempting to send email...";
            $mailResult = mail($to, $subject, $message, $headers);
            
            if ($mailResult) {
                $result = "✅ PHP mail() function returned TRUE - email should be sent";
                $debug_info[] = "Mail function result: TRUE";
            } else {
                $error = "❌ PHP mail() function returned FALSE - email failed";
                $debug_info[] = "Mail function result: FALSE";
            }
            
        } else {
            $error = "❌ Secure config file not found at: " . $configPath;
        }
        
    } catch (Exception $e) {
        $error = "❌ PHP Exception: " . $e->getMessage();
        $debug_info[] = "Exception: " . $e->getMessage();
    } catch (Error $e) {
        $error = "❌ PHP Error: " . $e->getMessage();
        $debug_info[] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Email Test - CashControl</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🧪 Simple Email Test</h1>
    
    <p>This is a simplified email test to diagnose the issue with the main email debug page.</p>
    
    <?php if ($result): ?>
        <div class="success"><?= htmlspecialchars($result) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <button type="submit" name="send_test">Send Simple Test Email</button>
    </form>
    
    <?php if (!empty($debug_info)): ?>
        <div class="debug">
            <h3>Debug Information:</h3>
            <?php foreach ($debug_info as $info): ?>
                <div><?= htmlspecialchars($info) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <hr>
    <p><a href="test-connections.php">← Back to Connection Tests</a></p>
</body>
</html>
