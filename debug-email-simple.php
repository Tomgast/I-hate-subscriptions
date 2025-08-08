<?php
echo "<h1>🔧 Email System Debug</h1>";

// Step 1: Check if EmailService file exists
echo "<h2>Step 1: File Check</h2>";
if (file_exists('includes/email_service.php')) {
    echo "✅ EmailService file exists<br>";
} else {
    echo "❌ EmailService file NOT found<br>";
    exit;
}

// Step 2: Try to include the file
echo "<h2>Step 2: Include Test</h2>";
try {
    require_once 'includes/email_service.php';
    echo "✅ EmailService included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error including EmailService: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Try to create EmailService instance
echo "<h2>Step 3: Class Creation</h2>";
try {
    $emailService = new EmailService();
    echo "✅ EmailService instance created<br>";
} catch (Exception $e) {
    echo "❌ Error creating EmailService: " . $e->getMessage() . "<br>";
    echo "<p><strong>This usually means:</strong></p>";
    echo "<ul>";
    echo "<li>Missing secure-config.php file</li>";
    echo "<li>Missing SMTP credentials</li>";
    echo "<li>PHP syntax error in EmailService</li>";
    echo "</ul>";
    exit;
}

// Step 4: Check if methods exist
echo "<h2>Step 4: Method Check</h2>";
if (method_exists($emailService, 'sendWelcomeEmail')) {
    echo "✅ sendWelcomeEmail method exists<br>";
} else {
    echo "❌ sendWelcomeEmail method missing<br>";
}

if (method_exists($emailService, 'sendUpgradeConfirmation')) {
    echo "✅ sendUpgradeConfirmation method exists<br>";
} else {
    echo "❌ sendUpgradeConfirmation method missing<br>";
}

// Step 5: Test simple email send (without actually sending)
echo "<h2>Step 5: Email Test (Dry Run)</h2>";
echo "<p>Testing email template generation...</p>";

try {
    // Test if we can call the private method via reflection (just to test template generation)
    $reflection = new ReflectionClass($emailService);
    $method = $reflection->getMethod('getWelcomeEmailTemplate');
    $method->setAccessible(true);
    $template = $method->invoke($emailService, 'Test User');
    
    if (strlen($template) > 100) {
        echo "✅ Welcome email template generated (" . strlen($template) . " characters)<br>";
    } else {
        echo "❌ Welcome email template too short or empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Error generating template: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 Next Steps</h2>";
echo "<p>If all checks pass, the issue might be:</p>";
echo "<ul>";
echo "<li><strong>SMTP Configuration:</strong> Check secure-config.php has correct email settings</li>";
echo "<li><strong>Server Email:</strong> Your server might block outgoing emails</li>";
echo "<li><strong>Firewall:</strong> SMTP ports might be blocked</li>";
echo "</ul>";

echo "<p><strong>Try this:</strong> Visit <a href='test-email-system.php'>test-email-system.php</a> for more detailed email diagnostics.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1, h2 { color: #333; }
ul { line-height: 1.6; }
</style>
