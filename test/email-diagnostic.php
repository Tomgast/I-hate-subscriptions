<?php
// Comprehensive Email Diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Email Diagnostic Tool</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Step 1: Check if form was submitted
if (isset($_POST['test_email'])) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ STEP 1: Form submission detected! Button was clicked.";
    echo "</div>";
    
    // Step 2: Test config file paths
    echo "<h3>🔧 STEP 2: Testing Config File Paths</h3>";
    
    $paths = [
        'Inside project' => dirname(__DIR__) . '/secure-config.php',
        'Outside project' => dirname(__DIR__) . '/../secure-config.php',
        'Two levels up' => dirname(__DIR__) . '/../../secure-config.php',
        'Root level' => $_SERVER['DOCUMENT_ROOT'] . '/../secure-config.php'
    ];
    
    $configFound = false;
    $config = [];
    
    foreach ($paths as $description => $path) {
        echo "<p><strong>$description:</strong> $path</p>";
        if (file_exists($path)) {
            echo "<p style='color: green;'>✅ File exists!</p>";
            try {
                $testConfig = include $path;
                if (is_array($testConfig)) {
                    echo "<p style='color: green;'>✅ Config loaded successfully</p>";
                    $config = $testConfig;
                    $configFound = true;
                    break;
                } else {
                    echo "<p style='color: orange;'>⚠️ File exists but doesn't return array</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error loading config: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ File not found</p>";
        }
        echo "<hr>";
    }
    
    if ($configFound) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ STEP 2: Config file found and loaded!";
        echo "</div>";
        
        // Step 3: Check SMTP credentials
        echo "<h3>📧 STEP 3: SMTP Credentials Check</h3>";
        
        $smtpHost = $config['SMTP_HOST'] ?? 'NOT SET';
        $smtpPort = $config['SMTP_PORT'] ?? 'NOT SET';
        $smtpUser = $config['SMTP_USERNAME'] ?? 'NOT SET';
        $smtpPass = $config['SMTP_PASSWORD'] ?? 'NOT SET';
        $fromEmail = $config['FROM_EMAIL'] ?? 'NOT SET';
        
        echo "<p><strong>SMTP Host:</strong> $smtpHost</p>";
        echo "<p><strong>SMTP Port:</strong> $smtpPort</p>";
        echo "<p><strong>SMTP Username:</strong> $smtpUser</p>";
        echo "<p><strong>SMTP Password:</strong> " . (empty($smtpPass) ? "❌ NOT SET" : "✅ SET (length: " . strlen($smtpPass) . ")") . "</p>";
        echo "<p><strong>From Email:</strong> $fromEmail</p>";
        
        if (!empty($smtpPass) && !empty($smtpUser) && !empty($smtpHost)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "✅ STEP 3: All SMTP credentials are set!";
            echo "</div>";
            
            // Step 4: Test email sending
            echo "<h3>📤 STEP 4: Testing Email Send</h3>";
            
            $to = 'info@123cashcontrol.com';
            $subject = 'Diagnostic Test - ' . date('Y-m-d H:i:s');
            $message = '<h2>Email Diagnostic Test</h2><p>This test email was sent at: ' . date('Y-m-d H:i:s') . '</p><p>From: ' . $fromEmail . '</p>';
            
            // Set up headers for SMTP
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $fromEmail\r\n";
            $headers .= "Reply-To: $fromEmail\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            echo "<p><strong>To:</strong> $to</p>";
            echo "<p><strong>Subject:</strong> $subject</p>";
            echo "<p><strong>From:</strong> $fromEmail</p>";
            
            $mailResult = mail($to, $subject, $message, $headers);
            
            if ($mailResult) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "✅ STEP 4: PHP mail() returned TRUE - Email should be sent!";
                echo "<br>Check your inbox at info@123cashcontrol.com";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "❌ STEP 4: PHP mail() returned FALSE - Email failed to send";
                echo "<br>This could be an SMTP authentication issue or server configuration problem.";
                echo "</div>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "❌ STEP 3: Missing SMTP credentials - cannot test email sending";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "❌ STEP 2: No config file found in any of the tested paths";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "⏳ Waiting for form submission...";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>🧪 Email Diagnostic Test</h2>
    <p>This will test form submission, config loading, credentials, and email sending step by step.</p>
    
    <form method="post">
        <button type="submit" name="test_email" value="1">🚀 Run Complete Email Diagnostic</button>
    </form>
    
    <hr>
    <p><a href="php-test.php">← Back to PHP Test</a> | <a href="test-connections.php">Connection Tests →</a></p>
</body>
</html>
